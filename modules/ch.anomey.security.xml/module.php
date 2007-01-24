<?php

/**
 * Implementation of the security provider interface with XML files.
 * Users and groups get stored in XML files.
 */
class AnomeyXMLSecurityProvider implements AnomeySecurityProvider  {

	/**
	 * @var Vector
	 */
	private $users;

	/**
	 * @var Vector
	 */
	private $groups;

	public function __construct() {
		$this->users = new Vector();
		try {
			$usersXml = XML::load('xml/ch.anomey.security.xml/users.xml');
			foreach($usersXml->user as $userXml) {
				$user = new User();
				$user->setId((string) $userXml['id']);
				$user->setName((string) $userXml);
				$this->users->set($user->getId(), $user);
			}
		} catch(FileNotFoundException $e) {
		}

		$this->groups = new Vector();
		try {
			$groupsXml = XML::load('xml/ch.anomey.security.xml/groups.xml');
			foreach($groupsXml->group as $groupXml) {
				$group = new Group();
				$group->setId((string) $groupXml['id']);
				$group->setName((string) $groupXml['name']);

				// load users into group
				foreach($groupXml->user as $userXml) {
					$user = $this->users->get((string) $userXml['id']);
					if($user != null) {
						$group->addUser($user);
					}
				}

				$this->groups->set($group->getId(), $group);
			}
		} catch(FileNotFoundException $e) {
		}
	}

	public function getUsers() {
		return $this->users->getValues();
	}

	public function getGroups() {
		return $this->groups->getValues();
	}

	/**
	 * Tries to authenticate the user with the parameters
	 * $parameters[username] and $parameters[password].
	 *
	 * @param array $parameters array with login parameters
	 * @return mixed <code>false</code> on failure, otherwise the User object
	 */
	public function authenticate($parameters) {
		return false;
	}
}

class AnomeySecurityXMLModule extends Module {
	public function invoke() {

	}
}

?>