<?php
/*
 * Mailer
 *
 * Handels mailing for Tutis
 */
class mailer {
	/*
	 * @var $email_master
	 *
	 * The Master E-Mail
	 */
	public $email_master;
	/*
	 * @var $template
	 *
	 * Stores template choice
	 */
	public $template;

	/*
	 * __construct
	 *
	 * Sets template
	 *
	 * @param string $template Sets template
	 */
	public function __construct($email_master, $template = 'default') {
		$this->email_master = $email_master;
		$this->template = $template;
	}

	/*
	 * genTemplate
	 *
	 * Parsers the template
	 *
	 * @param string $subject Subject of the email
	 * @param string $content Content of the email
	 */
	public function genTemplate($subject, $content) {
		$search = array(
			'subject' => '{{subject}}',
			'content' => '{{content}}'
		);
		$template = array(
			'subject' => $subject,
			'content' => $content
		);
		/* Set Template Path */
		$template_path = 'includes/login/email_templates/' . $this->template . '.html';
		/* E-Mail body */
		if (file_exists($template_path)) {
			$body = file_get_contents($template_path);
			$body = str_replace($search, $template, $body);
		} else {
			$body = $template['subject'] . '<hr />' . $template['content'];
		}
		return $body;
	}

	/*
	 * mail
	 *
	 * Mails inputed data
	 *
	 * @param string $email The email reciver
	 * @param string $subject Subject of the email
	 * @param string $content Content of the email
	 */
	public function mail($email, $subject, $content) {
		if (function_exists('mail')) {
			/* Headers */
			$headers = "From: " . strip_tags($this->email_master) . "\r\n";
			$headers .= "Reply-To: ". strip_tags($this->email_master) . "\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
			/* Send it */
			if (mail($email, $subject, $this->genTemplate($subject, $content), $headers)) {
				return true;
			} else {
				return false;
			}
		} else {
			return 'PHP Mail() function is not enabled!';
		}
	}
}
?>
