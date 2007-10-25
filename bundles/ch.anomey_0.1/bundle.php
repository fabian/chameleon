<?php

require_once 'profile.php';

class Anomey {

	const PROFILE_XML = 'profile.xml';

	const DEFAULT_PROFILE = 'default';

	const MEDIA_WEB = 'web';

	const MEDIA_CLI = 'cli';

	private $profilesPath;

	/**
	 * @var Vector
	 */
	private $profiles;

	/**
	 * @var Profile
	 */
	private $profile;

	/**
	 * @var string web or cli
	 */
	private $media;

	public function __construct($profilesPath) {
		$this->profilesPath = $profilesPath;

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
	}

	public function getProfilesPath() {
		return $this->profilesPath;
	}

	public function setProfilesPath($profilesPath) {
		$this->profilesPath = $profilesPath;
	}

	/**
	 * @return Profile
	 */
	public function getProfiles() {
		return $this->profiles;
	}

	public function getProfile() {
		return $this->profile;
	}

	/**
	 * @param Profile $profile
	 */
	public function setProfile($profile) {
		$this->profile = $profile;
	}

	public function getMedia() {
		return $this->media;
	}

	public function setMedia($media) {
		$this->media = $media;
	}

	public function isMediaWeb() {
		return $this->media == self::MEDIA_WEB;
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
}

class AnomeyBundle extends Bundle {

	/**
	 * @var Anomey
	 */
	private $anomey;

	public function invoke() {
		$xml = simplexml_load_file('configuration/ch.anomey/settings.xml');
		$profilesPath = (string) $xml->profilesPath;

		$this->anomey = new Anomey($profilesPath);

		// web or cli?
		if(isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER)) {
			$this->anomey->setMedia(Anomey::MEDIA_WEB);

			// choose profile
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

			foreach($this->anomey->getProfiles() as $profile) {
				if($profile->getHosts()->contains($host)) {
					$this->anomey->setProfile($profile);
					break;
				}
			}

			if($this->anomey->getProfile() == null) {
				$this->anomey->setProfile($this->anomey->getDefaultProfile());
			}

			// -----------------------------
			// Instantiate the base URL
			// -----------------------------

			// Find out the current schemata.
			$serverHttpsEnabled = isset ($_SERVER["HTTPS"]) ? $_SERVER["HTTPS"] : 'off';
			if (strtolower($serverHttpsEnabled) == 'on') {
				$scheme = 'https';
			} else {
				$scheme = 'http';
			}

			// Read the server host.
			$host = $_SERVER['HTTP_HOST'];

			// Read the path of the script
			$path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

			// Add a slash to the end of the path if
			// anomey doesn't run in the root folder
			$path .= $path != '/' ? '/' : '';

			$url = new URL($scheme, $host, $path);

			// -----------------------------
			// Instantiate the request.
			// -----------------------------

			// Read the request method.
			$method = $_SERVER['REQUEST_METHOD'];

			// Find out the trail.
			$trail = Value :: get($_GET['trail'], '/');

			// Merge the parameters passed over POST and GET.
			$parameters = new Vector(array_merge($_POST, $_GET));

			// Wipe out nasty php quotes ...
			if (get_magic_quotes_gpc()) {
				$parameters = String :: stripslashes($parameters);
			}

			// Initialize session.
			$session = new Session();

			// Load and clear messages.
			$messages = $session->load('systemMessages', array ());
			$session->clear('systemMessages');

			// Initialize cookie.
			$cookie = new Cookie($url);

			$request = new Request($method, $url, $trail, $session, $cookie);
			$request->addParameters($parameters);
			$request->addMessages($messages);

			foreach($this->getExtensions('http://anomey.ch/core/processor/web') as $extension) {
				$class = $extension->getClass();
				$processor = new $class($request, $this->anomey);
				$processor->process();
			}
		} else {
			// cli
			$this->anomey->setMedia(Anomey::MEDIA_CLI);

			$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
			$parameters = new Vector();


			for($i = 0, $l = count($argv); $i < $l; $i++) {
				if($argv[$i] == '-p' or $argv[$i] == '--profile') {
					$parameters->set('profile', isset($argv[$i+1]) ? $argv[$i+1] : '');
				}
			}

			$this->anomey->setProfile($this->anomey->getProfiles()->get($parameters->get('profile')));

			if($this->anomey->getProfile() == null) {
				$this->anomey->setProfile($this->anomey->getDefaultProfile());
			}

			$auto = false;
			$answer = '';

			if(!$auto) {
				echo 'The following bundles will be upgraded:' . "\n";
				echo '  ch.anomey.framework ch.anomey.security' . "\n";
				echo 'Do you want to continue [Y/n]? ';

				fscanf(STDIN, "%c\n", $answer);
			}


			if(empty($answer) || $auto) {
				$answer = 'Y';
			}

			if(strtoupper($answer) == 'Y') {
				echo 'Updating.';
				for($i = 0; $i < 10; $i++) {
					usleep(250000);
					echo '.';
				}
				echo ' Finished!' . "\n";
			} else {
				echo 'Aborted!' . "\n";
			}
		}
	}
}

class AnomeyWebProcessor {

	/**
	 * @var Request
	 */
	private $request;

	public function __construct(Request $request, Anomey $anomey) {
		$this->request = $request;
	}

	protected function getRequest() {
		return $this->request;
	}

	public function process() {

	}

	public function end() {

	}
}

?>