<?php

/**
 * Implementation of the security provider interface with XML files.
 * Users and groups get stored in XML files.
 */
class AnomeyXMLSecurityProvider implements AnomeySecurityProvider  {

	/**
	 * @var Vector
	 */
	private $roles;

	/**
	 * @var Vector
	 */
	private $resources;
	
	private function parseRole(SimpleXMLElement $xml, Role $parent = null) {
		foreach($xml->role as $rolexml) {
			$role = new Role((string) $rolexml['id'], (string) $rolexml['name'], $parent);
			$this->roles->set($role->getId(), $role);
			$this->parseRole($rolexml, $role);
		}
	}

	public function __construct() {
		$this->roles = new Vector();
		try {
			$rolesxml = XML::load('xml/ch.anomey.security.xml/roles.xml');
			$this->parseRole($rolesxml);
		} catch(FileNotFoundException $e) {
		}

		$this->resources = new Vector();
		try {
			$resourcesXml = XML::load('xml/ch.anomey.security.xml/resources.xml');
			foreach($resourcesXml->resource as $resourceXml) {
				$resource = new Resource();
				$resource->setName((string) $resourceXml['name']);
				$this->resources->append($resource);
			}			
		} catch(FileNotFoundException $e) {
		}
	}

	public function getRoles() {
		return $this->roles->getValues();
	}
	
	public function getResources() {
		return $this->resources->getValues();
	}

	/**
	 * Tries to authenticate the role with the parameters
	 * username and password.
	 *
	 * @param Request $request the request to authenticate
	 * @return mixed <code>false</code> on failure, otherwise the role object
	 */
	public function authenticate(Request $request) {
		return false;
	}
}

class AnomeySecurityXMLModule extends Module {
	public function invoke() {

	}
}

?>