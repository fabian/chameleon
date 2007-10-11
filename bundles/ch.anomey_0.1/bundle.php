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
		if(isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER)) {
			
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
				$processor = new $class($request, $this);
				$processor->process();
			}
		} else {
			// cli
			var_dump('cli');
		}
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

	private $request;

	public function __construct(Request $request, AnomeyBundle $anomey) {
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