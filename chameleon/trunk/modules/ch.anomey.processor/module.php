<?php

class Request extends Bean {

	private $method;

	public function getMethod() {
		return $this->method;
	}
	
	private $url;
	
	public function getURL() {
		return $this->url;
	}

	private $trail;

	public function getTrail() {
		return $this->trail;
	}

	public function setTrail($trail) {
		$this->trail = $trail;
	}

	private $session;

	public function getSession() {
		return $this->session;
	}

	private $cookie;

	public function getCookie() {
		return $this->cookie;
	}

	private $parameters;

	public function getParameters() {
		return $this->parameters;
	}

	public function getParameter($name, $default = '') {
		return Value :: get($this->parameters[$name], $default);
	}

	public function addParameters($parameters) {
		$this->parameters->merge($parameters);
	}

	public function addParameter($key, $value) {
		$this->parameters->set($key, $value);
	}

	private $messages;

	public function getMessages() {
		$this->flushMessages();
		return $this->messages;
	}

	private function flushMessages() {
		foreach ($this->messages as $key => $value) {
			if ($value->isDisplayed()) {
				unset ($this->messages[$key]);
			}
		}
	}

	public function addMessage($message) {
		$this->messages[] = $message;
	}

	public function addMessages($messages) {
		$this->messages->merge($messages);
	}

	public function __construct($method, URL $url, $trail, Session $session, Cookie $cookie) {
		$this->method = $method;
		$this->url = $url;
		$this->trail = $trail;
		$this->session = $session;
		$this->cookie = $cookie;
		$this->parameters = new Vector();
		$this->messages = new Vector();
	}
}

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
	
	public function __construct(ExtensionPointElement $element) {
		$this->class = $element->getValue();
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
