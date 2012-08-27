<?php
/*
 * Notice
 *
 * Handels Error reporting for tutis
 */
class notice {
	/*
	 * @var $_notices
	 *
	 * Used to store all the notices
	 */
	private $_notices = array();

	/*
	 * Add Notice
	 *
	 * Adds a notice to the notice array
	 *
	 * @param $type Type of notice (info, error, success)
	 * @param $message The notice message
	 */
	public function add($type, $message) {
		$this->_notice[$type][] = $message;
	}

	/*
	 * Report
	 *
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

	/*
	 * errorsExist
	 *
	 * Do errors exist?
	 */
	public function errorsExist() {
		if (empty($this->_notice['error'])) {
			return false;
		} else {
			return true;
		}
	}
}
?>
