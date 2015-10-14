<?php



use Illuminate\Support\MessageBag;
use League\Csv\Reader;


class SubjectController extends BaseController {

	public function create() {
		$subject = new Subject();
		$subject->visibility = true;
		return View::make('subject.subject_new_layout', array (
				'subject' => $subject 
		));
	}

	public function createPost() {
		return Platypus::transaction(function() {
			
			$onlyValues = array (
					'code',
					'title',
					'start_date',
					'end_date',
					'visibility' 
			);
			$input = Input::only($onlyValues);
			
			$subject = new Subject($input);
			
			if (! $subject->validate()) {
				Input::flashOnly($onlyValues);
				return Redirect::route('newSubject')->withErrors($subject->validationErrors);
			} else {
				
				$subject->save();
				$subject->addMemberSave(Auth::user(), SubjectMemberRoles::lecturer);
				
				return Redirect::route('showSubject', $subject->id)->with('success', 'The class ' . htmlentities($subject->code) . ' has been created.');
			}
			
		});
	}

	public function listAll() {
		return Platypus::transaction(function () {
			if (!Auth::user()->isAdmin()) {
				App::abort(403);
			}

			return View::make('subject.subject_listAll');
		});
	}	
	
	public function show($id) {
		return Platypus::transaction(function() use($id) {
			
			$subject = Subject::findOrFail($id);
			if (! $subject->mayView(Auth::user()))
				App::abort(403);
			
			$assignments = array ();
			foreach ( $subject->assignments()->orderBy('answers_due')->get() as $assignment ) {
				if ($assignment->mayViewAssignment(Auth::user())) {
					$assignments [] = $assignment;
				}
			}
			
			return View::make('subject.subject_show_layout', array (
					'subject' => $subject,
					'assignments' => $assignments 
			));
		});		
	}

	public function edit($id) {
		return Platypus::transaction(function() use($id) {
				
			$subject = Subject::findOrFail($id);
			if (! $subject->mayEdit(Auth::user())) App::abort(403);
				
				
			return View::make('subject.subject_edit')->withSubject($subject);
		});
	}	
	
	public function editPost($id) {
		return Platypus::transaction(function() use($id) {
				
			$subject = Subject::findOrFail($id);
			if (! $subject->mayEdit(Auth::user())) App::abort(403);
			
			$onlyValues = array (
					'code',
					'title',
					'start_date',
					'end_date',
					'visibility' 
			);
			$input = Input::only($onlyValues);
			
			$subject->fill($input);
			
			if (! $subject->validate()) {
				Input::flashOnly($onlyValues);
				return Redirect::route('editSubject', $subject->id)->withErrors($subject->validationErrors);
			} else {
				
				$subject->save();
				
				return Redirect::route('showSubject', $subject->id)->with('success', 'Your changes have been saved.');
			}
		});
	}	
	
	public function delete($id) {
		return Platypus::transaction(function() use($id) {
	
			$subject = Subject::findOrFail($id);
			if (! $subject->mayEdit(Auth::user())) App::abort(403);
	
			return View::make('subject.subject_delete')->withSubject($subject);
		});
	}
	
	public function deletePost($id) {
		return Platypus::transaction(function() use($id) {
	
			$subject = Subject::findOrFail($id);
			if (! $subject->mayEdit(Auth::user())) App::abort(403);

			if (Input::get('doit') == 'now') {
				$subject->delete();
				return Redirect::route('home')->withSuccess('The class has been deleted.');
			} else {
				$errors = new MessageBag();
				$errors->add('doit', 'You need to tick this box to delete.');
				return Redirect::route('deleteSubject', $subject->id)->withErrors($errors);
			} 
		});
	}	
	
	
	public function manageMembers($id, $existingOnly = false) {
		return Platypus::transaction(function () use($id, $existingOnly) {
			$subject = Subject::findOrFail($id);
			if (! $subject->mayViewMembers(Auth::user()))
				App::abort(403);
			
			if ($existingOnly) {
				$users = $subject->users();
			} else {
				$users = User::query();
			}
			
			$users = UserController::autoPaginateUsers($users);
			
			return View::make('subject.subject_addStudent', array (
					'users' => $users,
					'subject' => $subject,
					'showEdit' => $subject->mayManageMembers(Auth::user()),
			));
		});
	}

	public function manageExistingMembers($id) {
		return $this->manageMembers($id, true);
	}
	
	
	public function createUserAndAddToSubject($id) {
		return Platypus::transaction(function () use($id) {
			$subject = Subject::findOrFail($id);
				
			if (! $subject->mayManageMembers(Auth::user()))
				App::abort(403);
			
			
			return View::make('subject.subject_addStudentNew')->withSubject($subject);
		});
	}
		
	
	public function createUserAndAddToSubjectPost($id) {
		return Platypus::transaction(function () use($id) {
			$subject = Subject::findOrFail($id);
			
			if (! $subject->mayManageMembers(Auth::user()))
				App::abort(403);
			
			$onlyValues = array (
					'first_name',
					'last_name',
					'email',
					'student_id',
					'role',
			);
			$input = array_map('trim', Input::only($onlyValues));

			if (! SubjectMemberRoles::isValid($input['role'])) App::abort(404);
			
			if (!empty($input ['student_id'])) {
				$user = User::where("student_id",$input ['student_id'])->first();
				if (!is_null($user)) {
					return Redirect::route('addExistingUserToSubjectConfirm', array('id' => $id, 'userid' => $user->id, 'role' => $input['role']));
				}
			}
			
			if (!empty($input ['email'])) {
				$user = User::where("email",$input ['email'])->first();
				if (!is_null($user)) {
					return Redirect::route('addExistingUserToSubjectConfirm', array('id' => $id, 'userid' => $user->id, 'role' => $input['role']));
				}
			}
			
				
			$rules = array ();
			$rules ["first_name"] = User::$rules ["first_name"];
			$rules ["last_name"] = User::$rules ["last_name"];
			$rules ["student_id"] = User::$rules ["student_id"];
			$rules ["email"] = User::$rules ["email"];
			$rules ["role"] = 'required|enumrange:SubjectMemberRoles';
			
			$validator = Validator::make($input, $rules);
			
			if ($validator->passes()) {
				$authenticators = array ();
				
				foreach ( AuthenticationDomainDefinitions::getDomainAuthenticators() as $authenticatorData ) {
					$authenticator = new $authenticatorData [1]();
					
					if ($authenticator->userIsValidToBeCreated($input ['first_name'], $input ['last_name'], $input ['student_id'], $input ['email'])) {
						$user = $authenticator->createUser($input ['first_name'], $input ['last_name'], $input ['student_id'], $input ['email']);
						$subject->addMemberSave($user, $input['role']);
						
						return Redirect::route('createUserAndAddToSubject',$id)->with('success', 'The student has been added to the class.');
						
					}
				}
				
				Input::flashOnly($onlyValues);
				return Redirect::route('createUserAndAddToSubject',$id)->with('danger', 'Student account could not be created. Maybe the student exists already?');
				
			}

			Input::flashOnly($onlyValues);
			return Redirect::route('createUserAndAddToSubject',$id)->withErrors($validator);
		});
		
	}
	
	public function addExistingUserToSubjectConfirm($id, $userid, $role) {
		return Platypus::transaction(function () use($id, $userid, $role) {
			$subject = Subject::findOrFail($id);
			$user  = User::findOrFail($userid);
			
			if (!SubjectMemberRoles::isValid($role)) App::abort(404);
	
			if (! $subject->mayManageMembers(Auth::user())) App::abort(403);
			
			if ($subject->isMember($user)) {
				return Redirect::route('createUserAndAddToSubject',$id)->withWarning("The user is already part of this class.");
			}
				
			return View::make('subject.subject_addExistingUserToSubjectConfirm')->withSubject($subject)->withUser($user)->withRole($role);
		});
	}
		
	public function addExistingUserToSubjectConfirmPost($id, $userid, $role) {
		return Platypus::transaction(function () use($id, $userid, $role) {
			$subject = Subject::findOrFail($id);
			$user  = User::findOrFail($userid);
			
			if (!SubjectMemberRoles::isValid($role)) App::abort(404);
	
			if (! $subject->mayManageMembers(Auth::user())) App::abort(403);
				
			if ($subject->isMember($user)) {
				return Redirect::route('createUserAndAddToSubject',$id)->withDanger("The user is already part of this class.");
			}
			
			$subject->addMemberSave($user, $role);
	
			return Redirect::route('createUserAndAddToSubject',$id)->withSuccess("The user has been added to this class.");
		});
	}

	public function changeMembershipAjax($id, $userid, $role, $suspended) {
		
		if ($suspended == 0) {
			$suspended = false;
		} else if ($suspended == 1) {
			$suspended = true;
		} else {
			App::abort(404);
		}
		
		// within this controller we use the convention that $role==-1 represents not being a member.
		return Platypus::transaction(function () use($id, $userid, $role, $suspended) {
			$subject = Subject::findOrFail($id);
			$user = User::findOrFail($userid);

			// check if the role is valid.
			if (($role != -1) && !SubjectMemberRoles::isValid($role)) {
				App::abort(404);
			}				
				
			// only students and tutors can be suspended.
			if ( $suspended && ( ($role!=SubjectMemberRoles::student) && ($role!=SubjectMemberRoles::tutor) ) ) {
				App::abort(404);
			}

			// check permissions.
			if (! $subject->mayManageMembers(Auth::user())) {
				App::abort(403);
			}
			
			
			$oldRole = $subject->getMembershipRole($user);
			if ($oldRole === null) {
				$oldRole = - 1;
			}
			
			$oldSuspended = $subject->isSuspended($user);
			
			$json = array ();
			$json ['success'] = true;
			
			$userName = $user->presenter()->name;
			
			if ($oldRole != $role) {
				if (($oldRole != - 1) && ($role != - 1)) {
					$json ['alert'] = "Could not process your change. The status of $userName might have been changed elsewhere. Please try again.";
				} else {
					switch ($oldRole) {
						case - 1:
							$subject->addMemberSave($user, $role);
							if ($suspended) {
								$subject->suspendMemberSave($user);
							}
							$json ['growl'] = "$userName was added to this class as " . $subject->getMembership($user)->presenter()->role_complete . ".";
							break;
						case SubjectMemberRoles::student :
							if ($subject->studentHasNonEmptyData($user)) {
								if ($role == - 1) {
									$subject->suspendMemberSave($user);
									$json ['alert'] = "Could not remove $userName from class because the student still has data associated with this class. The student got suspended instead.";
								} else {
									$json ['alert'] = "Could not remove $userName from class because the student still has data associated with this class.";
								}
							} else {
								if ($subject->studentHasData($user)) {
									$subject->deleteEmptyStudentData($user);
								}
								if ($subject->studentHasData($user)) {
									$json ['success'] = false;
									$json ['alert'] = "Could not remove $userName from class. Some data could not be cleaned. This is a bug and should not happen.";
								} else {
									$subject->removeMemberSave($user);
									$json ['growl'] = "$userName was removed from this class.";
								}
							}
							break;
						case SubjectMemberRoles::lecturer :
							if ($subject->numberOfLecturers() <= 1) {
								$json ['alert'] = "There has to be at least one lecturer for a class.";
							} else {
								$subject->removeMemberSave($user);
								$json ['growl'] = "$userName was removed from this class.";
								if (($user->id == Auth::user()->id) && (!Auth::user()->isAdmin()) )  {
									$json ['script'] = "window.location.href = '" . route('home') ."';"; 
								}
							}
							break;
						case SubjectMemberRoles::tutor :
							if ($subject->tutorHasNonEmptyData($user)) {
								if ($role == - 1) {
									$subject->suspendMemberSave($user);
									$json ['alert'] = "Could not remove $userName from class because the tutor still has data associated with this class. The tutor got suspended instead.";
								} else {
									$json ['alert'] = "Could not remove $userName from class because the tutor still has data associated with this class.";
								}
							} else {
								if ($subject->tutorHasData($user)) {
									$subject->deleteEmptyTutorData($user);
								}
								if ($subject->tutorHasData($user)) {
									$json ['success'] = false;
									$json ['alert'] = "Could not remove $userName from class. Some data could not be cleaned. This is a bug and should not happen.";
								} else {
									$subject->removeMemberSave($user);
									$json ['growl'] = "$userName was removed from this class.";
								}
							}
							break;
						case SubjectMemberRoles::questionobserver :
						case SubjectMemberRoles::fullobserver :
							$subject->removeMemberSave($user);
							$removeok = true;
							$json ['growl'] = "$userName was removed as observer of this class.";
							break;
						default :
							App::abort(500);
					}
				}
			} else {
				// no role change, just suspention change.
				// we already checked for validity of suspensions above.
				if ($suspended != $oldSuspended) {
					$subject->suspendMemberSave($user, $suspended);
					if ($suspended) {
						$json ['growl'] = "$userName was suspended.";
					} else {
						$json ['growl'] = "$userName was resumed as " . $subject->getMembership($user)->presenter()->role_complete . " of this class.";
					}
				}
			}
			

			
			$json ['html'] = View::make('subject.subject_addStudentButton_insert')->withSubject($subject)->withUser($user)->render();
				
			return Response::json($json);
		});
		
	}
	
	public function massUploadStudents($id) {
		return Platypus::transaction(function () use($id) {
			$subject = Subject::findOrFail($id);
	
			if (! $subject->mayManageMembers(Auth::user()))
				App::abort(403);
				
			return View::make('subject.subject_massUploadStudents')->withSubject($subject);
		});
	}

	public function massUploadStudentsPost($id) {
		// This function feels a bit long.
		//
		// However, it is structured in blocks.
		//
		// It processes the input in stages and whenever a stage isn't successful, the next stage isn't started.
		// The user is then given the option to tweak input parameters and try again.
		// In the end it is attempted to add all users, but inless a confirmation is in the input, the transaction is rolled back. 
		
		return Platypus::transaction(function () use($id) {
			$subject = Subject::findOrFail($id);
			if (! $subject->mayManageMembers(Auth::user()))
				App::abort(403);

			$maxSize = 102400;
			
			$csvdata = array();
			$errors = new MessageBag();
			
			
			if (Input::hasFile('csvfile')) {
				// We have a csv file as file upload
				$csvfile = Input::file('csvfile');
				
				if ($csvfile->getSize() > $maxSize) {
					$errors = new MessageBag();
					$errors->add('csvfile', "The file must be smaller than $maxSize bytes.");
					return Redirect::route('massUploadStudentsToSubject', $subject->id)->withErrors($errors);
				}
				
				$csvdata['raw'] = file_get_contents($csvfile->getPathname());				
								
			} else if (Input::has('raw')) {
				// we have a csv file as normal post data
				$csvdata['raw'] = Input::get('raw');
				if (strlen($csvdata['raw']) > $maxSize) {
					$errors->add('csvfile', "The file must be smaller than $maxSize bytes.");
					return Redirect::route('massUploadStudentsToSubject', $subject->id)->withErrors($errors);						
				}
			} else {
				// no csv file, we're starting over.
				return Redirect::route('massUploadStudentsToSubject', $subject->id);
			}

			// sanitise windows line breaks and load the data into the reader.
			$csvdata['raw'] = str_replace("\r\n", "\n", $csvdata['raw']);	
			$csvdata['raw'] = str_replace("\r", "\n", $csvdata['raw']);	
			$csv = Reader::createFromString($csvdata['raw']);		

			// see if we have a delimiter
			if (Input::has('delimiter')) {
				$csvdata['delimiter'] = Input::get('delimiter');
				if (!validateSimple($csvdata['delimiter'], 'required|size:1')) {
					$errors->add('delimiter', "The delimiter must be a single character");
					unset($csvdata['delimiter']);
				}
			}
			if (!isset($csvdata['delimiter'])) {
				$delimiters = $csv->detectDelimiterList(5);
				if(!empty($delimiters)) {
					$csvdata['delimiter'] = array_values($delimiters)[0];
				}
			}
			if (isset($csvdata['delimiter'])) {
				$csv->setDelimiter($csvdata['delimiter']);
			} else {
				$errors->add('delimiter', "You must provide a delimiter.");
				return Redirect::route('massUploadStudentsToSubject', $subject->id)->with('csvdata', $csvdata)->withErrors($errors);
			}
			
			// see if we have a special quotation mark
			if (Input::has('quotation')) {
				$csvdata['quotation'] = Input::get('quotation');
				if (!validateSimple($csvdata['quotation'], 'required|size:1')) {
					$errors->add('quotation', "The quotation mark must be a single character");
					unset($csvdata['quotation']);
				}
			}
			if (!isset($csvdata['quotation'])) {
				$csvdata['quotation'] = '"';
			}
			$csv->setEnclosure($csvdata['quotation']);
						
			// see if we have to deal with a row offset and try to guess it
			if (Input::has('offset')) {
				$csvdata['offset'] = Input::get('offset');
				$validationerrors = null;
				if (!validateSimple($csvdata['offset'], 'required|numeric|min:0|max:100',$validationerrors)) {
					$errors->merge(array('offset' => $validationerrors));
					unset($csvdata['offset']);
				}
			}
			if (!isset($csvdata['offset'])) {
				$csvdata['offset'] = 0;
				for($i=0; $i<10; $i++) {
					$row = $csv->fetchOne($i);
					if (!is_array($row)) break;
					foreach($row as $cell) {
						if (stripos($cell, 'name') !== false) {
							$csvdata['offset'] = $i;
							break 2;
						}
					}
				}
			}
			
			// get the header information
			$csvdata['header'] = $csv->fetchOne($csvdata['offset']);
			if (!is_array($csvdata['header']) || count($csvdata['header']) < 2) {
				$errors->add('offset', "No valid header line could be found at the specified offset.");
				unset($csvdata['header']);
				return Redirect::route('massUploadStudentsToSubject', $subject->id)->with('csvdata', $csvdata)->withErrors($errors);
			}
			
			// check if the file is rectangular
			foreach($csv->setOffset($csvdata['offset'])->fetchAll() as $row) {
				if (count($row) != count($csvdata['header'])) {
					$errors->add('delimiter', "The table does not seem rectangular (i.e. there are different numbers of fields per line).");
					unset($csvdata['header']);
					return Redirect::route('massUploadStudentsToSubject', $subject->id)->with('csvdata', $csvdata)->withErrors($errors);
				}
			}				
				
			
			// try to retrieve or guess the columns
			$columns = array('first_name', 'last_name', 'email', 'student_id');
			$columnPatterns = array(
					'first_name' => '/(first|given).*name/i',
					'last_name' => '/(last|family).*name/i',
					'email' => '/email/i',
					'student_id' => '/student.*id/i',
			);
			foreach ( $columnPatterns as $key => $pattern ) {
				$field = "column_$key";
				if (Input::has($field)) {
					$csvdata [$field] = Input::get($field);
					$validationerrors = null;
					if (! validateSimple($csvdata [$field], 'required|numeric|min:0|max:100', $validationerrors)) {
						$errors->merge(array (
								$field => $validationerrors 
						));
						unset($csvdata [$field]);
					} else if (!isset($csvdata['header'][$csvdata [$field]])) {
						$errors->add($field, "Invalid selection");
						unset($csvdata [$field]);
					}
				}
				if (! isset($csvdata[$field])) {
					$count = -1;
					foreach($csvdata['header'] as $header) {
						$count++;
						if (preg_match($pattern, $header) == 1) {
							$csvdata[$field] = $count;
							break;
						}
					}
				}
			}

			
			$ok = true;
			foreach ( $columns as $key ) {
				$field = "column_$key";
				if(!isset($csvdata[$field])) {
					$ok = false;
					$errors->add($field, "Please select a column.");
				} else {
					foreach($columns as $otherKey) {
						if ($key == $otherKey) continue;
						$otherField = "column_$otherKey";
						if (isset($csvdata[$otherField]) && ($csvdata[$field] == $csvdata[$otherField])) {
							$ok = false;
							$errors->add($field, "You cannot select the same column twice.");
							break;
						}
					}
				}
			}
			
			if (!$ok) {
				return Redirect::route('massUploadStudentsToSubject', $subject->id)->with('csvdata', $csvdata)->withErrors($errors);
			}
			
			// extract the student information
			$csvdata['students'] = array();
			$count = $csvdata['offset'];
			foreach($csv->setOffset($csvdata['offset']+1)->fetchAll() as $row) {
				$count++;
				$userData = array();
				foreach( $columns as $key) {
					$field = "column_$key";
					if (!isset($row[$csvdata[$field]])) {
						$errors->add($field, "This column doesn't exist in row $count.");
						return Redirect::route('massUploadStudentsToSubject', $subject->id)->with('csvdata', $csvdata)->withErrors($errors);
					}
					$userData[$key] = $row[$csvdata[$field]];
				}
				$csvdata['students'][] = $userData;
			}
			
			// we are still good. Let's test if we can add the students to the class.
			// we actually attempt to add all students, but then we roll back the transaction.
			
			// first create the authenticators for performance
			$authenticators = array();
			foreach ( AuthenticationDomainDefinitions::getDomainAuthenticators() as $authenticatorData ) {
				$authenticators[] = new $authenticatorData[1]();
			}
						
			// determine if it is a test run only
			if ($ok && Input::has('doit') && (Input::get('doit') == 'now') ) {
				$doit = true;
			} else {
				$doit = false;
			}
			
			// now process the users.
			foreach($csvdata['students'] as &$student) {
				
				$userExists = false;

				if (!$userExists && !empty($student['student_id'])) {
					$query = User::where("student_id",$student['student_id']);
					if ($query->count() > 1) {
						$query = $query->where("email",$student['email']);
					}
					if ($query->count() == 1) {
						$user = $query->first();
						$userExists = true;
					} else if ($query->count() > 1){
						$ok = false;
						$student['error'] = 'The user seems to exist but is ambiguous.';
						continue;
					}
				}

				if (!$userExists && !empty($student['email'])) {
					$query = User::where("email",$student['email']);
					if ($query->count() > 1) {
						$query = $query->where("student_id",$student['student_id']);
					}
					if ($query->count() == 1) {
						$user = $query->first();
						$userExists = true;
					} else if ($query->count() > 1){
						$ok = false;
						$student['error'] = 'The user seems to exist but is ambiguous.';
						continue;
					}
				}
				
				
				if ($userExists) {
					foreach($columns as $key){
						if ($student[$key] != $user->$key) {
							$student["original_$key"] = $student[$key];
						}
						$student[$key] = $user->$key;
					}
					
					if ($subject->isStudent($user)) {
						if ($subject->isSuspended($user)) {
							$subject->suspendMemberSave($user, false);
							$student['status'] = 'Resume student';
						} else {
							$student['status'] = 'Already student';
						}
					} else if ($subject->isMember($user)) {
						$ok = false;
						$student['error'] = 'This person is already a member of this class, but not a student.';
					} else {
						$subject->addMemberSave($user, SubjectMemberRoles::student);
						$student['status'] = 'Add existing student to class';						
					}
				} else {
					$rules = array ();
					$rules ["first_name"] = User::$rules ["first_name"];
					$rules ["last_name"] = User::$rules ["last_name"];
					$rules ["student_id"] = User::$rules ["student_id"];
					$rules ["email"] = User::$rules ["email"];
					
					$validator = Validator::make($student, $rules);
					
					if ($validator->passes()) {

						$someAuthenticatorAccepted = false;
						foreach ( $authenticators as $authenticator ) {
							if ($authenticator->userIsValidToBeCreated($student ['first_name'], $student ['last_name'], $student ['student_id'], $student ['email'])) {
								$someAuthenticatorAccepted = true;
								$user = $authenticator->createUser($student ['first_name'], $student ['last_name'], $student ['student_id'], $student ['email'], (!$ok || !$doit ));
								$subject->addMemberSave($user, SubjectMemberRoles::student);
								
								foreach($columns as $key){
									if ($student[$key] != $user->$key) {
										$student["original_$key"] = $student[$key];
									}
									$student[$key] = $user->$key;
								}
								
								$student['status'] = 'Create new account';
								break;
							} 
						}
						
						if (!$someAuthenticatorAccepted) {
							$ok = false;
							$student ['error'] = 'This student account cannot be found or created.';
						}
					} else {
						$ok = false;
						$student ['error'] = "";
						foreach($validator->getMessageBag()->getMessages() as $messageList) {
							foreach($messageList as $message) {
								$student['error'] .= "$message ";
							}
						}
					}
				}	
			} unset($student);
			
			// roll back if anything is not ok or if we were just testing.
			if (!$ok || !$doit ) {
				DB::rollback();			
			} else {
				// commit!!!
				$csvdata['success'] = true;
				return Redirect::route('massUploadStudentsToSubject', $subject->id)->with('csvdata', $csvdata)->withSuccess('The students have been added to the class.');
			}
			
			if ($ok) {
				$csvdata['ok'] = true;
			}
			
			return Redirect::route('massUploadStudentsToSubject', $subject->id)->with('csvdata', $csvdata)->withErrors($errors);
			
		});
	}
}


