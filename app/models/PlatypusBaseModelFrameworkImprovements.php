<?php
use LaravelBook\Ardent\Ardent;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;

class PlatypusBuilder extends LaravelBook\Ardent\Builder {

	public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
	{
		if (strpos($relation, '.') !== false)
		{
			return $this->hasNested($relation, $operator, $count, $boolean, $callback);
		}
	
		$relation = $this->getHasRelationQuery($relation);
		
		if ( (($operator == '>=') && ($count == 1)) || (($operator == '>') && ($count == 0)) ) {
			if (method_exists($relation, 'getWhereHasOneConstraints')) {
				$whereHasOneConstraints = $relation->getWhereHasOneConstraints($callback, $this);
				if (is_array($whereHasOneConstraints) && !empty($whereHasOneConstraints)) {
					return $this->whereRaw($whereHasOneConstraints['sql'], $whereHasOneConstraints['bindings'], $boolean);
				}
			}
		}
	
		$query = $relation->getRelationCountQuery($relation->getRelated()->newQuery(), $this);
	
		if ($callback) call_user_func($callback, $query);
	
		return $this->addHasWhere($query, $relation, $operator, $count, $boolean);
	}
	
}

class PlatypusBelongsTo extends Illuminate\Database\Eloquent\Relations\BelongsTo {
	
	public function getWhereHasOneConstraints($callback, $parent) {
		$parentKey = $this->wrap($this->getQualifiedForeignKey());
		$selectKey = $this->wrap($this->query->getModel()->getTable().'.'.$this->otherKey);
		
		if ($callback) call_user_func($callback, $this->query);
		$this->query->select(new Expression($selectKey));
		
		return array(
			'sql' => new Expression($parentKey .' in (' . $this->query->toSql() . ')'),
			'bindings' => $this->query->getBindings(),
		);		
	}
	
}

trait PlatypusHasOneOrManyTrait {

	public function getWhereHasOneConstraints($callback, $parent) {
	
	
		$parentKey = $this->wrap($this->getQualifiedParentKeyName());
		$selectKey = $this->wrap($this->getHasCompareKey());
	
		if ($callback) call_user_func($callback, $this->query);
		$this->query->select(new Expression($selectKey));
	
		return array(
			'sql' => new Expression($parentKey .' in (' . $this->query->toSql() . ')'),
			'bindings' => $this->query->getBindings(),
		);	
	}	
}

class PlatypusHasMany extends Illuminate\Database\Eloquent\Relations\HasMany {
	use PlatypusHasOneOrManyTrait;
}

class PlatypusHasOne extends Illuminate\Database\Eloquent\Relations\HasOne {
	use PlatypusHasOneOrManyTrait;
}

class PlatypusBelongsToMany extends Illuminate\Database\Eloquent\Relations\BelongsToMany {

	public function getWhereHasOneConstraints(Closure $callback, $parent) {
	
		if ($parent->getQuery()->from == $this->getRelated()->newQuery()->getQuery()->from) {
			// Table aliasing isn't implemented here. Return null to tell the caller to fall back
			// to the count query method.
			return null;
		}
		
		$parentKey = $this->wrap($this->getQualifiedParentKeyName());
		$selectKey = $this->wrap($this->getHasCompareKey());
		
		if ($callback) call_user_func($callback, $this->query);
		$this->query->select(new Expression($selectKey));
	
		return array(
				'sql' => new Expression($parentKey .' in (' . $this->query->toSql() . ')'),
				'bindings' => $this->query->getBindings(),
		);
	}	

}


class PlatypusBaseModelFrameworkImprovements extends Ardent {

	/**
	 * Set the specific relationship in the model.
	 *
	 * @param  string  $relation
	 * @param  mixed   $value
	 * @return $this
	 */
	public function setRelation($relation, $value) {
		// fix a bug in upstream where relations don't get stored properly if it is camelCase.
		return parent::setRelation(snake_case($relation), $value);
	}
	
	// fix an issue with Ardent: Ardent re-queries a relationship again and again if it is null.  
	public function getAttribute($key) {
		$attr = Illuminate\Database\Eloquent\Model::getAttribute($key);
	
		if ($attr === null) {
			// check if the relation might exist but is null.
			if (array_key_exists($key, $this->relations)) {
				return $this->relations[$key];
			}
			
			// ok, we need to load it if possible.
			$camelKey = camel_case($key);
			if (array_key_exists($camelKey, static::$relationsData)) {
				$this->relations[$key] = $this->$camelKey()->getResults();
				return $this->relations[$key];
			}
		}
	
		return $attr;
	}	
	
	
	public function newQuery($excludeDeleted = true) {
		$builder = new PlatypusBuilder($this->newBaseQueryBuilder());
		$builder->throwOnFind = static::$throwOnFind;
	
		// Once we have the query builders, we will set the model instances so the
		// builder can easily access any information it may need from the model
		// while it is constructing and executing various queries against it.
		$builder->setModel($this)->with($this->with);
	
		if ($excludeDeleted and $this->softDelete)
		{
			$builder->whereNull($this->getQualifiedDeletedAtColumn());
		}
	
		return $builder;
	}

	/**
	 * Define an inverse one-to-one or many relationship.
	 * Overriden from {@link Eloquent\Model} to allow the usage of the intermediary methods to handle the {@link
	 * $relationsData} array.
	 *
	 * @param  string  $related
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function belongsTo($related, $foreignKey = NULL, $otherKey = NULL, $relation = NULL) {

		if (is_null($relation)) {
			$backtrace = debug_backtrace(false);
			$caller = ($backtrace[1]['function'] == 'handleRelationalArray')? $backtrace[3] : $backtrace[1];
	
			// If no foreign key was supplied, we can use a backtrace to guess the proper
			// foreign key name by using the name of the relationship function, which
			// when combined with an "_id" should conventionally match the columns.
			$relation = $caller['function'];
		}
	
		if (is_null($foreignKey)) {
			$foreignKey = snake_case($relation).'_id';
		}
	
		// Once we have the foreign key names, we'll just create a new Eloquent query
		// for the related models and returns the relationship instance which will
		// actually be responsible for retrieving and hydrating every relations.
		$instance = new $related;
	
		$otherKey = $otherKey ?: $instance->getKeyName();
	
		$query = $instance->newQuery();
	
		return new PlatypusBelongsTo($query, $this, $foreignKey, $otherKey, $relation);
	}	
	
	/**
	 * Define a one-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $foreignKey
	 * @param  string  $localKey
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function hasMany($related, $foreignKey = null, $localKey = null)
	{
		$foreignKey = $foreignKey ?: $this->getForeignKey();
	
		$instance = new $related;
	
		$localKey = $localKey ?: $this->getKeyName();
	
		return new PlatypusHasMany($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
	}	
	
	/**
	 * Define a one-to-one relationship.
	 *
	 * @param  string  $related
	 * @param  string  $foreignKey
	 * @param  string  $localKey
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function hasOne($related, $foreignKey = null, $localKey = null)
	{
		$foreignKey = $foreignKey ?: $this->getForeignKey();
	
		$instance = new $related;
	
		$localKey = $localKey ?: $this->getKeyName();
	
		return new PlatypusHasMany($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
	}	
	
	/**
	 * Define a many-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $table
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @param  string  $relation
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function belongsToMany($related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
	{
		// If no relationship name was passed, we will pull backtraces to get the
		// name of the calling function. We will use that function name as the
		// title of this relation since that is a great convention to apply.
		if (is_null($relation))
		{
			$relation = $this->getBelongsToManyCaller();
		}

		// First, we'll need to determine the foreign key and "other key" for the
		// relationship. Once we have determined the keys we'll make the query
		// instances as well as the relationship instances we need for this.
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		$instance = new $related;

		$otherKey = $otherKey ?: $instance->getForeignKey();

		// If no table name was provided, we can guess it by concatenating the two
		// models using underscores in alphabetical order. The two model names
		// are transformed to snake case from their default CamelCase also.
		if (is_null($table))
		{
			$table = $this->joiningTable($related);
		}

		// Now we're ready to create a new query builder for the related model and
		// the relationship instances for the relation. The relations will set
		// appropriate query constraint and entirely manages the hydrations.
		$query = $instance->newQuery();

		return new PlatypusBelongsToMany($query, $this, $table, $foreignKey, $otherKey, $relation);
	}
	
	
}


