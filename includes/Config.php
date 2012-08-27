<?php
/*
 * Config Class
 * 
 * Used to write config information into a static var to be
 * used anywhere
 */
class Config {
	/*
	 * @var $configArray
	 *
	 * Used to store all the configs
	 */
	static $confArray;
	/*
	 * Config Read function
	 * 
	 * Reads the config value from the $confArray
	 * 
	 * @param string $name the key in the array
	 */
	public static function read($name) {
		return self::$confArray[$name];
	}
	/*
	 * Config Write function
	 * 
	 * Writes data to the $confArray
	 * 
	 * @param string $name the key in the array
	 * @param string $value the value of the key in the array
	 */
	public static function write($name, $value) {
		self::$confArray[$name] = $value;
	}
}
?>
