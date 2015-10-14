<?php

class PlatypusZipStream extends ZipStream\ZipStream {
	public function addFile($name, $data, $opt = array()) {
		
		if(function_exists('gzdeflate')) {
			return parent::addFile($name, $data, $opt);
		}
		
		// we don't have zlib. So we just store everything uncompessed.
		
		$algo       = 'crc32b';
		
		// calculate header attributes
		$zlen = $len = strlen($data);
		
		// store method
		$meth = 0x00;
		$crc = hexdec(hash($algo, $data));

					
		// send file header
		$this->addFileHeader($name, $opt, $meth, $crc, $zlen, $len);
		
		// send data
		$this->send($data);
		
		
	}
	
	protected function addLargeFile($name, $path, $opt = array()) {
		if(!function_exists('gzdeflate')) {
			$this->opt[self::OPTION_LARGE_FILE_METHOD] = self::METHOD_DEFLATE;
		}
		
		return parent::addLargeFile($name, $path, $opt);
	}
}


class ZipArchiveController extends BaseController {
	
	private function addDirectoryTree($zip, $baseName, $dirName) {
		$ite=new RecursiveDirectoryIterator($dirName);
		
		foreach (new RecursiveIteratorIterator($ite) as $file) {
			
			if($file->isFile()) {
				$filename = $file->getPathname();
				$zipFileName = $baseName.substr($filename, strlen($dirName));
				$zip->addFileFromPath($zipFileName, $filename);
			}
		}
	}
	
	
	public function downloadAssignmentArchive($assignment_id) {
		
		// to prevent blocking the database we make it a bit unusual.
		//
		// we split the generation into smaller transactions and
		// create a list of the file attachments.
		//
		// then we add the files from disk without any transaction.
		
		$zip = null; // the zip archive
		$extraFiles = array(); // files we want to add to the archive
		$addedFiles = array(); // files we added to the archive (to prevent duplicates).
		
		// helper to schedule files to be added
		$addArchiveFile = function($name, $file) use (&$extraFiles) {
			$extraFiles[] = array('name' => $name, 'file' => $file);
		};
		
		// helper to add all scheduled files.
		$addArchiveFilesToArchive = function() use(&$zip, &$extraFiles, &$addedFiles) {
			foreach($extraFiles as $extra) {
					
				if(isset($addedFiles[$extra['name']])) continue; // remove duplicates.
					
				try {
					$zip->addFileFromPath($extra['name'], $extra['file']);
					$addedFiles[$extra['name']] = true;
				} catch (Exception $e) {
					$zip->addFile('MISSING_'.$extra['name'], '');
				}
			}
				
			$extraFiles = array();
				
		};		
		
		// stores which users we should process in subsequent transactions.
		$userIds = array();
		
		// we do the actual adding of the data outside the transaction to prevent buffler locks.
		$dataToAdd = array();
		
		// do the main transaction. It is also where the permission checking happens.
		$result = Platypus::transaction(function () use(&$assignment_id, &$zip, &$extraFiles, $addArchiveFile, &$userIds, &$dataToAdd) {
				
			$assignment = Assignment::findOrFail($assignment_id);
			$assignment_id = $assignment->id;
				
			if (! $assignment->mayBrowseStudents(Auth::user())) {
				App::abort(403);
			}
			
			//if(!function_exists('gzdeflate')) {
			//	return 'The server doesn\'t seem to have the Zlib extension installed. This is a requirement for zip-file generation.';  
			//}
			
			$zip = new PlatypusZipStream('assignment-'.Carbon::now()->format('Y-m-d-His') .'.zip', array('send_http_headers'=>true));
			
			$viewVariables = array(
					'renderForArchive' => true,
					'addArchiveFile' => $addArchiveFile,
					'assignment' => $assignment,
				);					
			

			// add the assignment questions, solutions and marking scheme.
			$viewVariables['attachmentPathPrefix'] = "assignment/";
			$data = View::make('archive.archive_assignment')->with($viewVariables)->render();
			
			$dataToAdd[] = array(
					'name' => 'assignment.html',
					'data' => $data,
				);
				
			

			// now we want to create the index file
			
			$users = $assignment->all_students;
			
						
			// add the user data
			foreach($users as $user) {
				$userIds[] = $user->id;
			}
		
			// create the index file
			$viewVariables['attachmentPathPrefix'] = "index/";
			$data = View::make('archive.archive_index')->with($viewVariables)->withUsers($users)->render();

			$dataToAdd[] = array(
					'name' => 'index.html',
					'data' => $data,
			);
				
			// add it with another filename so it shows up at top when sorting.
			$dataToAdd[] = array(
					'name' => 'AAAAAAAAA_index.html',
					'data' => $data,
			);
				
			
			// add the results file
			$data = AssignmentControlPanelController::getCsvFileContent($assignment);
			$dataToAdd[] = array(
					'name' => 'results.csv',
					'data' => $data,
			);
				
			
		});
		
		
		
		// if we have a response, return it. It should contain error messages or something like that.
		if (isset($result)) {
			return $result;
		}
		
		// sanity check
		if(!isset($zip)) {
			App::abort(500);
		}
		
		set_time_limit(3600);
		
		// add the data to the archive
		foreach($dataToAdd as $toAdd) {
			$zip->addFile($toAdd['name'], $toAdd['data']);
		}
		$dataToAdd = array();

		// flush the scheduled attachments
		$addArchiveFilesToArchive();
		
		// process the user pages in individual transactions.
		foreach($userIds as $userId) {
			
			set_time_limit(3600);
			
			$toAdd = Platypus::transaction(function () use($assignment_id, &$zip, $addArchiveFile, $userId) {
				$assignment = Assignment::find($assignment_id);
				if(!isset($assignment)) return;
				
				$user = User::find($userId);
				if(!isset($user)) return;
				
				$baseName = $user->presenter()->archive_file_name;
				
				$viewVariables = array(
						'renderForArchive' => true,
						'addArchiveFile' => $addArchiveFile,
						'assignment' => $assignment,
						'attachmentPathPrefix' =>  "$baseName/",
				);
				
				$data = View::make('archive.archive_browseStudentShow')->withUser($user)->with($viewVariables)->render();
				
				return array(
						'name' => "$baseName.html",
						'data' => $data,
					);
			});
			
			// add everything to the archive.
			if(isset($toAdd)) {
				$zip->addFile($toAdd['name'], $toAdd['data']);
			}
			
			$addArchiveFilesToArchive();
			
		}
		
		
		// now we add copies of our static assets.
		
		set_time_limit(7200);
		
 		$packages = array(
	 		'bootstrap',
 			'mathjax',
 			'jquery',
 			'stickytableheaders',
 		);
 		
 		foreach($packages as $package) {
			$this->addDirectoryTree($zip,"assets/packages/$package", public_path()."/packages/$package");
 		}
 		
 		
 		
 		$ckeditorVersion = null;
 		View::make('templates.ckeditor_version')->with(array('ckeditorVersionSetter' => function($v) use(&$ckeditorVersion){$ckeditorVersion=$v;}))->render();

 		$this->addDirectoryTree($zip,"assets/packages/highlight", public_path()."/packages/$ckeditorVersion/plugins/codesnippet/lib/highlight");
 		
 		//$this->addDirectoryTree($zip,"assets/js", public_path()."/js");
 		$this->addDirectoryTree($zip,"assets/themes", public_path()."/themes");

		$zip->finish();
		
		// we did all the output ourselves. Let's not get laravel in the way.
		ob_flush();
		flush();
		die();
		
	} 
	
}

	