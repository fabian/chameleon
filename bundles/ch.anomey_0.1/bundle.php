<?php

require_once 'profiles.php';

class AnomeyBundle extends Bundle {

	const PROFILE_XML = 'profile.xml';

	const DEFAULT_PROFILE = 'default';
	
	private $profilesPath;

	/**
	 * @var Vector
	 */
	private $profiles;

	/**
	 * @var Profile
	 */
	private $profile;

	public function invoke() {
		$xml = simplexml_load_file('configuration/ch.anomey/settings.xml');
		$this->profilesPath = (string) $xml->profilesPath;
		
		$this->profiles = new Vector();
		$this->profile = null;

		// load profiles
		foreach (scandir($this->profilesPath) as $profileName) {
			$path = $this->profilesPath . '/' . $profileName;
			if ($this->isProfile($path)) {
				// parse profile xml file
				$xml = simplexml_load_file($path . '/' . self::PROFILE_XML);

				$newProfile = new Profile($profileName, $path);

				foreach ($xml->host as $host) {
					$newProfile->getHosts()->append((string) $host);
				}

				$this->profiles->set($profileName, $newProfile);
			}
		}

		// web or cli?
		if(isset($_SERVER)) {
			// web
			
		} else {
			// cli
			
		}
		
		// choose profile
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
			
		foreach($this->profiles as $profile) {
			if($profile->getHosts()->contains($host)) {
				$this->profile = $profile;
			}
		}
		
		if($this->profile == null) {
			$this->profile = $this->getDefaultProfile();
		}
		
		var_dump($this->profile->getPath());
	}
	
	public function getDefaultProfile() {
		$profile = $this->profiles->get(self :: DEFAULT_PROFILE, null);
		
		if($profile == null) {
			if(@mkdir($this->profilesPath . '/' . self :: DEFAULT_PROFILE, 0700, true)) {
				// default profile folder created
			} else {
				// TODO better error handling required
				exit('Could not create default profile folder ');
			}
		}
		
		return $profile;
	}

	/**
	 * Check if the passsed path is a profile folder. Returns
	 * <code>true</code> if it is a profile folder or otherwise
	 * <code>false</code>.
	 *
	 * @param string $path name of the folder
	 * @return boolean
	 */
	private function isProfile($path) {
		return file_exists($path . '/' . self::PROFILE_XML);
	}
}

class AnomeyWebProcessor {

	public function __construct(Request $request) {
		
	}
	
	public function process() {
		
	}
	
	public function end() {
		
	}
}

?>