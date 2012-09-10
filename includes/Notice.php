<?php
/*
 * Originally part of Tutis Login <http://www.firedartstudios.com/labs/tutis-login>
 * Author: FireDart
 * License: CC-BY-SA 3.0 <http://creativecommons.org/licenses/by-sa/3.0/>
 *
 * Modified by Serrano Pereira for MaSIS
 */

/**
 * The Notice class handles error reporting.
 */
class Notice {
	private $_notices = array();

	/**
	 * Adds a notice to the notice array
	 *
	 * @param $type Type of notice (info, error, success)
	 * @param $message The notice message
	 */
	public function add($type, $message) {
		$this->_notice[$type][] = $message;
	}

	/**
	 * Reports all notices (info, error, success)
	 */
	public function report() {
		$data = '';
		/* Report any Info */
		if (isset($this->_notice['info'])) {
			foreach($this->_notice['info'] as $message) {
				$data .= '<div class="notice info">' . $message . '</div>';
			}
		}
		/* Report any Errors */
		if (isset($this->_notice['error'])) {
			foreach($this->_notice['error'] as $message) {
				$data .= '<div class="notice error">' . $message . '</div>';
			}
		}
		/* Report any Success */
		if (isset($this->_notice['success'])) {
			foreach($this->_notice['success'] as $message) {
				$data .= '<div class="notice success">' . $message . '</div>';
			}
		}
		/* Return data */
		if (isset($data)) {
			return $data;
		}

	}

	/**
	 * Check if any errors exist.
     *
     * @return boolean TRUE if errors exist, otherwise FALSE.
	 */
	public function errorsExist() {
		return !empty($this->_notice['error']);
	}
}

