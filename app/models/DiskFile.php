<?php
use Symfony\Component\HttpFoundation\File\UploadedFile;


class DiskFile extends PlatypusBaseModel {
	

	// some settings
    static $directoryLevels = 2;
    static $hashAlgorithm = 'sha256';
    static $directoryPermissions = 0770;
    static $filePermissions = 0440;
    static $directoryDelimiter = '/';
    static $baseDir;// default value set below.    
    
    protected $table = 'disk_files';
	

	// fields we want converted to Carbon timestamps on the fly.
	public function getDates() {
		return array ('created_at', 'updated_at');
	}
    
	public static $automaticGarbageCollectionRelations = array('textBlockAttachments');
	
   	// define the relationships to other models
   	// Note: there are a lot of possible relationships depending on the role. They should all be added here for convenience.
   	public static $relationsData = array (
 			'textBlockAttachments'  => array(self::HAS_MANY, 'TextBlockAttachment', 'foreignKey' => 'file_id'),
   	);
   	
   	public static $rules = array(
   			'type'     => 'required|max:100',
   			'hash'     => 'required|min:4|max:1000',
   			'disk_file_name' => 'required|min:2|max:1000',
   	);
   	
   	
   	// get the full disk file name.
   	public function fullDiskFileName() {
   		if (substr($this->disk_file_name,0,1) == static::$directoryDelimiter) {
   			return $this->disk_file_name;
   		} else {
   			return static::$baseDir . static::$directoryDelimiter . $this->disk_file_name;
   		}
   	}
   	
   	public function getDowloadResponse($name, $download = false, $lastModified = null) {
   		if (empty($name)) {
   			$name = ''. $this->id;
   		}
   		
   		$header = array();
   		if (!empty($this->type)) {
   			$header['content-type'] = $this->type;
   		}
   		
   		if ($download) {
   			$disposition = 'attachment';
   		} else {
   			$disposition = 'inline';
   		}
   		
   		$filename = $this->fullDiskFileName();
   		if (!file_exists($filename)) {
   			App::abort(404, "The file could not be found within the file storage.");
   		}
   		
   		$response = Response::download($filename, $name, $header, $disposition);
   		$request = Request::instance();

   		if(isset($lastModified)) {
   			$response->setLastModified($lastModified);
   		} else {
   			$response->setLastModified(Carbon::now());
   		}
   		
   		$response->setPrivate();   		
   		$response->setEtag($this->hash);
   		$response->setMaxAge(1);
   		
   		if($response->isNotModified($request)) {
   			// Return empty response if not modified
   			return $response;
   		} else {
   			// Return file if first request / modified
   			$response->prepare($request);
	   		return $response;    		
   		}
   	}
   	
   	// move the given file into its place.
   	// when addind a file, this should be the final action of the transaction, after all the database writes have succeeded already.
   	// returns false on failure.
   	public function moveFile($filename) {
   		$fullDiskFileName = $this->fullDiskFileName();
   		if (file_exists($fullDiskFileName)) {
   			
   			// get the real file name in case there is a symlink.
   			$realDiskFileName = (new SplFileInfo($fullDiskFileName))->getRealPath();
   			if(!(new SplFileInfo($realDiskFileName))->isFile()) {
   				return false;
   			}
   			
   			// make sure the existing file has the correct hash.
   			if ($this->hashFile($realDiskFileName) == $this->hash) {
   				return true;
   			}
   			
   			// the existing file is corrupted. Move the given file in its place.
   			// we ignore symlinks now and overwrite them.
   			if (!move_uploaded_file($filename, $fullDiskFileName)) {
   				return false;
   			}
   			
   			@chmod($fullDiskFileName , static::$filePermissions);
   			
   			return true;
   			
   		} else {
   			
   			// make sure the directory exists
   			$path = (new SplFileInfo($fullDiskFileName))->getPath();
			if (! (new SplFileInfo($path))->isDir()) {
   				$umask = umask(0000);
   				$success = @mkdir($path, static::$directoryPermissions, true);
   				umask($umask);
   				if (!$success) {
   					return false;
   				}
   			}
   			
   			// Move the given file in its place.
   			if (!move_uploaded_file($filename, $fullDiskFileName)) {
				$e = error_get_last();
				if ($e['message'] != '') {
					Log::warning($e['message']);
				}
   				return false;
   			}
   			
   			chmod($fullDiskFileName , static::$filePermissions);
   			
   			return true;
   		}
   	}
   	
   	// returns the has of the given file.
   	static public function hashFile($filename) {
   		return hash_file(static::$hashAlgorithm ,$filename);
   	}
   	
   	// creates a database record for a file.
   	// This function does not move the file into its place. 
   	// The idea is that you can call this function early within your transaction and then use DiskFile::moveFile() as final command before committing.
   	static public function createOrGetRecordForFile_noMove_save($filename) {
   		
   		if (!file_exists($filename)) {
   			App::abort(500);
   		}
 		
   		if(!(new SplFileInfo($filename))->isFile()) {
   			App::abort(500);
   		}
   		
   		// calculate the hash value of the file
   		$hash = static::hashFile($filename);
   		
   		// check if we have this file already.
   		$result =  static::where('hash',$hash)->first();
   		if (!is_null($result)) {
   			// we know this one already, just return the record.
   			return $result;
   		}
   		
		// we need to create a new record.
   		$result = new static();
   		
   		$result->hash = $hash;
   		
   		// store the size of the file.
   		$result->size = filesize($filename);
   		
   		// detect the mime type
   		$finfo = finfo_open(FILEINFO_MIME_TYPE);
   		$result->type = finfo_file($finfo, $filename);
   		finfo_close($finfo);   		
   		
   		// construct the file name with its directory levels.
   		$storageName = '';
   		for($i = 0; $i< static::$directoryLevels; $i++) {
   			$subfolder = substr($result->hash , $i*2 , 2);
   			if ($subfolder != "") {
   				$storageName .= $subfolder . static::$directoryDelimiter;
   			}
   		}
   		$storageName .= $result->hash;
   		$result->disk_file_name = $storageName;
   		
   		// save the record.
   		$result->save();   

   		return $result;
   	}
    
   	// we need to re-implement this because we also need to handle the files on disk.
   	public static function collectGarbage() {
   	
   		$deletedCount = 0;
   	
   		$relations = static::$automaticGarbageCollectionRelations;
   		if (is_array($relations) && !empty($relations)) {
   				
   			$chunkSize = static::$automaticGarbageCollectionChunkSize;
   			if ($chunkSize < 1) $chunkSize = 1;
   			
   			$lastId = 0;
   			while (true) {
   				$ids = static::where('id','>',$lastId)->orderBy('id')->limit($chunkSize)->lists('id');
   	
   				if (empty($ids)) break;
   	
   				$lastId = max($ids);
   	
   				$query = static::whereIn('id', $ids);
   	
   				foreach($relations as $relation) {
   					$query->has($relation, '<', 1);
   				}
   	
   				$toDelete = $query->lists('id');
   				
   				foreach ($toDelete as $id) {
   					$deletedCount += Platypus::transaction(function() use ($id, $relations) {
   						
   						$query = static::where('id', $id);
   						
   						// we do the whole query again because we were not in a transaction before.
   						foreach($relations as $relation) {
   							$query->has($relation, '<', 1);
   						}
   						$diskFile = $query->first();
   						
   						// check if the we can still delete the file. 
   						if (is_null($diskFile)) return;
   						
   						$filename = $diskFile->fullDiskFileName();
   						
   						// delete the record from the database.
   						$diskFile->delete();
   						
   						// delete the file from disk
   						if (file_exists($filename)) {
   							$success = unlink($filename);
   						} else {
   							// the file doesn't exist, but since we want to delete it anyway, that's ok.
   							$success = true;
   						}
   						
   						// roll back the transaction if anything went wrong.
   						if ($success) {
   							return 1;
   						} else {
   							DB::rollback();
   							echo "Warning: could not delete $filename.\n";
   							return 0;
   						}
   					});
   				}
   					
   			}
   				
   		}
   	
   		if ($deletedCount > 0) {
   			echo "$deletedCount ". get_called_class(). " objects deleted.\n";
   		}
   	
   	
   	}
   	
   	public static function totalFileSize() {
   		return static::sum('size');
   	}

   	public static function totalFileCount() {
   		return static::count();
   	}
   	
   	
	static public function createTable() {
		Schema::create('disk_files', function ($table) {
			$table->engine = 'InnoDB';
			$table->timestamps();
			
			$table->increments('id');
			$table->text('type', 1000);
			$table->string('hash', 255)->unique();
			$table->bigInteger('size')->unsigned()->index(); // indexed so we can quickly calculate the total used disk space.
			$table->text('disk_file_name');
		});
		DB::statement('CREATE INDEX disk_file_name_idx ON disk_files (disk_file_name(200));');
	}
    	
}

// set the base path for storing files.
if (strlen(storage_path()) < 3) {
	App::abort(500, 'No storage path has been configured.');
}
DiskFile::$baseDir = storage_path() . DiskFile::$directoryDelimiter . 'disk_files';

