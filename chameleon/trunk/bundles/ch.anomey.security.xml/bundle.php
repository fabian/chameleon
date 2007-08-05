<?php

class XMLUser implements User {

	/**
	 * The id of the user. A unique string.
	 *
	 * @var mixed
	 */
	private $id;

	/**
	 * The user's nick name. 
	 *
	 * @var string
	 */
	private $nick;
	
	/**
	 * @var Vector
	 */
	private $groups;
	
	public function __construct($id, $nick) {
		$this->id = $id;
		$this->nick = $nick;
		$this->groups = new Vector();
	}

	public function getId() {
		return 'xml/' . $this->id;
	}

	public function getNick() {
		return $this->nick;
	}

	public function setNick($nick) {
		$this->nick = $nick;
	}
	
	public function getGroups() {
		return $this->groups;
	}
	
	public function addGroup(XMLGroup $group) {
		if(!$this->groups->contains($group)) {
			$this->groups->append($group);
			$group->addUser($this);
		}
	}
}

class XMLGroup implements Group {

	/**
	 * The id of the group. A unique string.
	 *
	 * @var mixed
	 */
	private $id;

	/**
	 * The name of the group. 
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * @var Vector
	 */
	private $users;
	
	public function __construct($id, $name) {
		$this->id = $id;
		$this->name = $name;
		$this->users = new Vector();
	}

	public function getId() {
		return 'xml/' . $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}
	
	public function getUsers() {
		return $this->users;
	}
	
	public function addGroup(XMLUser $user) {
		if(!$this->users->contains($user)) {
			$this->users->append($user);
			$user->addGroup($this);
		}
	}
}

/**
 * Implementation of the security provider interface with XML files.
 * Users and groups get stored in XML files.
 */
class XMLSecurityProvider implements SecurityProvider  {

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
			$usersXml = XML::load('data/ch.anomey.security.xml/users.xml');
			foreach($usersXml->user as $userXml) {
				$user = new XMLUser((string) $userXml['id'], (string) $userXml['nick']);
				$this->users->set($user->getId(), $user);
			}
		} catch(FileNotFoundException $e) {
		}

		$this->groups = new Vector();
		try {
			$groupsXml = XML::load('data/ch.anomey.security.xml/groups.xml');
			foreach($groupsXml->group as $groupXml) {
				$group = new XMLGroup((string) $groupXml['id'], (string) $groupXml['name']);

				// load users into group
				foreach($groupXml->user as $userXml) {
					if($this->users->exists((string) $userXml['id'])) {
						$user = $this->users->get((string) $userXml['id']);
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

class SecurityXMLBundle extends Bundle {
	public function invoke() {

	}
}

?>