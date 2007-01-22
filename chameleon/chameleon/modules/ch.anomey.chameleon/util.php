<?php

class Vector extends ArrayObject {

	public function set($key, $value) {
		$this[$key] = $value;
	}

	public function get($key) {
		return $this[$key];
	}

	public function exists($key) {
		return isset($this[$key]);
	}

	public function contains($value) {
		return in_array($value, (array) $this);
	}

	public function merge(Vector $array) {
		$this->exchangeArray(array_merge((array) $this, (array) $array));
	}

	public function map($function) {
		array_map($function, $this);
	}

	public function getKeys() {
		return new Vector(array_keys($this));
	}

	public function getValues() {
		return new Vector(array_values((array) $this));
	}

	public function toReadableString() {
		return self::readableString($this->array);
	}

	public function drop($key) {
		unset($this[key]);
	}

	public function remove($value) {
		$this->drop(array_search($value, (array) $this));
	}

	public static function readableString($array) {
		$string = '';
		reset($array);
		$size = count($array);

		if ($size > 0) {
			$string .= current($array);

			if ($size > 1) {
				next($array);

				$i = 1;
				while ($i < $size -1) {
					$string .= ', ' . current($array);
					next($array);
					$i++;
				}

				$string .= ' and ' . current($array);
			}
		}

		return $string;
	}
}

abstract class Value {
	public static function get(& $variable, $default = '') {
		if (isset ($variable)) {
			return $variable;
		} else {
			return $default;
		}
	}
}

?>