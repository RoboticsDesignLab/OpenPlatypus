<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GarbageCollector extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'platypus:collect_garbage';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Run the garbage collection on the database.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		// garbage collection usually happens transaction free. So no need to start a transaction.
		
		
		StudentGroup::collectGarbage();
		StudentGroupSuggestion::collectGarbage();
		ReviewGroup::collectGarbage();

		TextBlock::collectGarbage();
		AutosaveText::collectGarbage();
		TextBlockRestriction::collectGarbage();
		DiskFile::collectGarbage();
		
		ValueStore::setTime(StoredValue::lastGarbageCollection, Carbon::now());
		
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array();
		return array(
			array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array();
		return array(
			array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}

}
