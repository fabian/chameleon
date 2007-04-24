<?php

class AnomeyProcessorModule extends Module {

	private $url;

	public function invoke() {
		$this->parse();
	}

	private function parse() {
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

		$this->url = new URL($scheme, $host, $path);
		$this->url->setBase('index.php/');

		// -----------------------------
		// Instantiate the request.
		// -----------------------------

		// Read the request method.
		$method = $_SERVER['REQUEST_METHOD'];

		// Trick out a CGI bug
		if (isset ($_SERVER['PATH_INFO'])) {
			if ($_SERVER['PATH_INFO'] == "" AND isset ($_SERVER['ORIG_PATH_INFO'])) {
				$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
			}
		}

		// Find out the trail.
		$trail = Value :: get($_SERVER['PATH_INFO'], '/');

		// Merge the parameters passed over POST and GET.
		$parameters = new Vector(array_merge($_POST, $_GET));

		// Wipe out nasty php quotes ...
		if (get_magic_quotes_gpc()) {
			$parameters = String :: stripslashes($parameters);
		}

		// Initialize session.
		$session = new Session();

		// Load and clear messages.
		$messages = $session->load('systemMessages', new Vector());
		$session->clear('systemMessages');

		// Initialize cookie.
		$cookie = new Cookie($this->url);

		$request = new Request($method, $this->url, $trail, $session, $cookie);
		$request->addParameters($parameters);
		$request->addMessages($messages);

		foreach($this->getExtensions('http://anomey.ch/core/processor') as $extension) {
			$class = $extension->getClass();
			$processor = new $class($request);
			$processor->process();
		}
	}

	public function getURL() {
		return $this->url;
	}
}

class AnomeyProcessorExtension extends Extension {

	private $class;

	public function getClass() {
		return $this->class;
	}

	public function load(ExtensionPointElement $element) {
		$this->class = trim($element->getValue());
	}
}

interface AnomeyProcessor {
	public function __construct(Request $request);
	public function process();
}

class AnomeyModuleProcessor implements AnomeyProcessor {

	private $trail;

	private $runpath;

	public function __construct(Request $request) {
		$this->trail = $request->getTrail();
		$this->runpath = $request->getURL()->getRunpath();
	}

	public function process() {
		echo '<a href="' . $this->runpath . '/foo/bar">Goto foobar</a><br/>';
		echo $this->trail;
	}
}

?>
