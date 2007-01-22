<?php
abstract class Bean implements ArrayAccess {

	function offsetGet($key) {
		$key = preg_replace('/\W/', '', $key);
		$method = 'get' . ucfirst($key);

		if ($this->offsetExists($method)) {
			return $this-> $method ();
		}
	}

	function offsetExists($key) {
		return method_exists($this, $key);
	}

	function offsetSet($key, $value) {
		$key = preg_replace('/\W/', '', $key);
		$method = 'set' . ucfirst($key);

		if ($this->offsetExists($method)) {
			return $this-> $method ($value);
		}
	}
	
	function offsetUnset($key) {
	}
}

class XML extends SimpleXMLElement {
	public static function create($tag) {
		return new XML('<' . $tag . '/>');
	}

	public function save($file) {
		$domNode = dom_import_simplexml($this);
		$dom = new DOMDocument('1.0');
		$domNode = $dom->importNode($domNode, true);
		$dom->appendChild($domNode);
		$dom->formatOutput = TRUE;

		$dom->save($file);
	}

	public static function load($fileName) {
		if (file_exists($fileName)) {
			return simplexml_load_file($fileName, 'XML');
		} else {
			throw new FileNotFoundException('The XML file "' . $fileName . '" does not exist.');
		}
	}

	public static function import($string) {
		return simplexml_load_string($string, 'XML');
	}
}

class Session {

	private $id;

	public function getId() {
		return $this->id;
	}

	public function __construct() {
		session_name('sid');
		session_start();

		$this->id = session_id();
	}

	public function store($key, $obj) {
		$_SESSION['anomey'][$key] = $obj;
	}

	public function load($key, $default = '') {
		return Value::get($_SESSION['anomey'][$key], $default);
	}

	public function clear($key) {
		unset ($_SESSION['anomey'][$key]);
	}

	public function commit() {
		session_commit();
	}
	
	public function regenerate() {
		session_regenerate_id();
		$this->id = session_id();
	}
}

class Cookie {
	public $url;

	public function getURL() {
		return $this->url;
	}

	public function __construct(URL $url) {
		$this->url = $url;
	}

	public function get($key, $default = '') {
		return Value :: get($_COOKIE[$key], $default);
	}

	public function store($key, $obj, $time = null) {
		if($time === null) {
			// default expire is one hour
			$time = time() + 3600;
		}
		setcookie($key, $obj, $time, $this->getURL()->getPath());
	}

	public function clear($key) {
		$this->store($key, '', time() - 3600);
	}
}

class URL extends Bean {

	private $scheme;

	private $host;

	private $path;

	public function getPath() {
		return $this->path;
	}

	function __construct($scheme, $host, $path) {
		$this->scheme = $scheme;
		$this->host = $host;
		$this->path = $path;
	}

	public function getServer() {
		$this->scheme . '://' . $this->host;
	}

	public function toString() {
		return $this->getServer() . $this->path;
	}
}

abstract class HTML {
	/**
	* This function adapts htmlentities()
	* to the $variable. If the variable is an array,
	* the function htmlentities will be adapt to the
	* containing $variables recursively. 
	*
	* @author Fabian Vogler <fabian@ap04a.ch>
	* @param mixed $variable
	* @return mixed
	*/
	public static function entities($variable) {
		if (is_array($variable)) {
			foreach ($variable as $key => $value) {
				$variable[$key] = self :: entities($value);
			}
		}
		elseif (!is_object($variable)) {
			$variable = htmlentities($variable, ENT_COMPAT, 'UTF-8');
		}

		return $variable;
	}
}

abstract class String {
	public static function truncate($string, $maxlength) {
		$stringArray = explode('.', $string);

		$newString = '';

		if (count($stringArray) > 1) {
			$letters = strlen($stringArray[0]) + 1;
			for ($i = 0; $letters <= $maxlength && $i < count($stringArray); $i++) {
				$newString .= $stringArray[$i] . '.';
				if (isset ($stringArray[$i +1])) {
					$letters += strlen($stringArray[$i +1]) + 1;
				}
			}
		}
		return $newString;
	}

	public static function stripslashes($value) {
		if (is_array($value)) {
			$value = array_map(array (
				'String',
				'stripslashes'
			), $value);
		}
		elseif (!is_object($value)) {
			$value = stripslashes($value);
		}
		return $value;
	}
}

class Message extends Bean {

	private $value;
	private $type;
	private $displayed = false;

	function __construct($value, $type = 'info') {
		$this->value = $value;
		$this->type = $type;
	}

	public function getValue() {
		$this->displayed = true;
		return $this->value;
	}

	public function getType() {
		return $this->type;
	}

	public function isDisplayed() {
		return $this->displayed;
	}
}

class ErrorMessage extends Message {
	const TYPE = 'error';

	public function __construct($value) {
		parent::__construct($value, self::TYPE);
	}
}

class WarningMessage extends Message {
	const TYPE = 'warning';

	public function __construct($value) {
		parent::__construct($value, self::TYPE);
	}
}

class ApplicationError extends Exception {}

class FileNotFoundException extends Exception {}

?>
