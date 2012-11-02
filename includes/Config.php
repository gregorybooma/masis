<?php
/*
 * Originally part of Tutis Login <http://www.firedartstudios.com/labs/tutis-login>
 * Author: FireDart
 * License: CC-BY-SA 3.0 <http://creativecommons.org/licenses/by-sa/3.0/>
 *
 * Modified by Serrano Pereira for MaSIS
 */

/**
 * Read and write configurations.
 *
 * Configurations are written to a static variable that can be accessed from
 * anywhere within the program.
 */
class Config {
	/**
	 * Array that contains all configurations.
	 */
	static $confArray;

	/**
	 * Get the value for a configuration.
	 *
	 * @param string $name The key in the array
     * @return mixed The value for the configuration with key $name
	 */
	public static function read($name) {
		return self::$confArray[$name];
	}

	/**
	 * Set a configuration.
	 *
	 * @param string $name The key in the array
	 * @param string $value The value of the key in the array
	 */
	public static function write($name, $value) {
		self::$confArray[$name] = $value;
	}
}
