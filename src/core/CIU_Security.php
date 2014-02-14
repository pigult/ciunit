<?php

/**
 * 
 * @author Arūnas Kučinskas
 * @since 2013-04-09 18.56.14
 */
class CIU_Security extends CI_Security
{

	private $_csrf_valid = true;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Verify Cross Site Request Forgery Protection
	 * No need to show 404 just return false();
	 * @return	object
	 */
	public function csrf_verify()
	{
		$valid = true;
		$this->_csrf_valid = true;

		// jei neijungtas confige reiskia nereikia ir tikrinti
		if (!config_item('csrf_protection') === TRUE) {
			return $valid;
		}

		// If no POST data exists we will set the CSRF cookie
		if (count($_POST) == 0) {
			return $this->csrf_set_cookie();
		}

		// Do the tokens exist in both the _POST and _COOKIE arrays?
		if (!isset($_POST[$this->_csrf_token_name]) OR !isset($_COOKIE[$this->_csrf_cookie_name])) {
			$valid = false;
			$this->_csrf_valid = false;
		}

		// Do the tokens match?
		if (empty($_POST[$this->_csrf_token_name]) || empty($_COOKIE[$this->_csrf_cookie_name]) || $_POST[$this->_csrf_token_name]
			!= $_COOKIE[$this->_csrf_cookie_name]
		) {
			$valid = false;
			$this->_csrf_valid = false;
		}

		// We kill this since we're done and we don't want to 
		// polute the _POST array
		unset($_POST[$this->_csrf_token_name]);

		// Nothing should last forever
		unset($_COOKIE[$this->_csrf_cookie_name]);
		$this->_csrf_set_hash();
		$this->csrf_set_cookie();

		log_message('debug', "CSRF token verified ");

		return $valid;
	}

	public function csrf_set_cookie()
	{
		$expire = time() + $this->_csrf_expire;
		$secure_cookie = (config_item('cookie_secure') === TRUE) ? 1 : 0;

		if ($secure_cookie) {
			$req = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : FALSE;
			$req = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 'on' : $req;

			if (!$req OR $req == 'off') {
				return FALSE;
			}
		}

		//setcookie($this->_csrf_cookie_name, $this->_csrf_hash, $expire, config_item('cookie_path'), config_item('cookie_domain'), $secure_cookie);
		//log_message('debug', "CRSF cookie Set");

		return $this;
	}

	public function isCsrfValid()
	{
		return $this->_csrf_valid;
	}

	// --------------------------------------------------------------------

	/**
	 * Get CSRF Cookie Name
	 *
	 * @return 	string 	self::csrf_cookie_name
	 */
	public function get_csrf_cookie_name()
	{
		return $this->_csrf_cookie_name;
	}

}
