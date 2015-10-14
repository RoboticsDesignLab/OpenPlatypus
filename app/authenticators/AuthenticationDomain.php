<?php 

// to add a new authenticator, two things are necessary
// 1) add a value to AuthenticationDomain
// 2) add the authenticator class to domainAuthenticatorsDefinition()




class AuthenticationDomain extends PlatypusEnum {

	// each authentication domain needs to be identified by a small integer value.
	// this value is used in the database to store which use belongs to which domain.
	// Thus, NEVER change the value as long as there might be users with that domain
	// in the database.
	
	const local = 0;
	const uqsso = 1;
	
	
}



function domainAuthenticatorsDefinition() {
	
	// A domain definition is a pair consisting of the database value from  
	// AuthenticationDomain as well as a class name of the authenticator.
	// The Authenticator must implement the AuthenticationInterface
	//
	// The order of the definitions matter and earlier authenticators take precedence.  
	
	return Config::get('authenticators.authenticators');
}





class AuthenticationDomainDefinitions {

	static public function getDomainAuthenticators() {
		return domainAuthenticatorsDefinition();
	}

	static public function getAuthenticatorName($authenticationDomain) {
		foreach ( static::getDomainAuthenticators() as $authenticator ) {
			if ($authenticator [0] == $authenticationDomain) {
				return $authenticator [1];
			}
		}
		
		return false;
	}

	static public function createAuthenticator($authenticationDomain) {
		$authenticatorName = static::getAuthenticatorName($authenticationDomain);
		if ($authenticatorName) {
			return new $authenticatorName();
		}
		
		return false;
	}
}