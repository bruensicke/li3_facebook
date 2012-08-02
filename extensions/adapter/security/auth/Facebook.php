<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_facebook\extensions\adapter\security\auth;

use li3_facebook\extensions\Facebook as FacebookProxy;
use lithium\security\Auth;
use lithium\storage\Session;
use lithium\core\Libraries;
use lithium\core\Environment;
use Exception;

/**
 * Extends Lithium's Auth adapter to look for a Facebook session
 * and use that to set auth if available.
 *
 * @see lithium\security\Auth
 * @see li3_facebook\extensions\FacebookProxy
*/

class Facebook extends \lithium\core\Object {

	/**
	 * Called by the `Auth` class to run an authentication check against the Facebook API
	 * and returns an array of user information on success, or `false` on failure.
	 *
	 * @todo move the FacebookConfig::checkConfiguration part into the __init?
	 *
	 * @throws lithium\core\ConfigException if the facebook App credentials arent set
	 *
	 * @param object $credentials A data container which wraps the authentication credentials used
	 *               to query the model (usually a `Request` object). See the documentation for this
	 *               class for further details.
	 * @param array $options Options which include the options for session key names and also FB API method options.
	 * @return array Returns an array containing user information on success, or `false` on failure.
	 */
	public function check($credentials, array $options = array()) {

		//get Url
		$base  = $credentials->env('HTTPS') ? 'https://' : 'http://';
		$base .= $credentials->env('HTTP_HOST');
		$base .= $credentials->env('base');
		$env = Environment::get();

		$facebook_config = Libraries::get('li3_facebook');
		if (!empty($facebook_config[$env])) {
			$facebook_config = $facebook_config[$env];
		}

		// get the options from the li3_facebook library configuration if set there
		$options += $facebook_config;

		// otherwise, set some defaults
		$defaults = array(
			'logout_url_options' => array(
				'next' => $base
			),
			'login_url_options' => array(
			),
			'logout_url_session_key' => 'fb_logout_url',
			'login_url_session_key' => 'fb_login_url',
			'local_fb_session_name' => 'fb_session'
		);

		/**
		 * If the adapter config() has those keys set, then use those as the default values.
		 * This allows various adapters to be created all which can change the options for logging in and out
		 * for Facebook, so when Auth::check() is called, each check can be used for different reasons.
		 * If the options are set with the Facebook library ($facebook_config) then there can only be one
		 * "configuration" for these login and logout parameters.
		 *
		 * So for example, Auth::check('popup', $this->request); or Auth::check('page', $this->request);
		 * The difference maybe between the two Auth configurations is the "login_url_options" array values
		 * of "display" being "page" or "popup" which tells the FB API how to display the login.
		 *
		 * We could also pass these options in the configuration under Libraries::add('li3_facebook'), but then
		 * it wouldn't be quite as easy to switch behaviors while using Auth::check();
		*/
		$defaults['logout_url_options'] = (isset($this->_config['logout_url_options'])) ? $this->_config['logout_url_options']:$defaults['logout_url_options'];
		$defaults['login_url_options'] = (isset($this->_config['login_url_options'])) ? $this->_config['login_url_options']:$defaults['login_url_options'];
		$defaults['logout_url_session_key'] = (isset($this->_config['logout_url_session_key'])) ? $this->_config['logout_url_session_key']:$defaults['logout_url_session_key'];
		$defaults['login_url_session_key'] = (isset($this->_config['login_url_session_key'])) ? $this->_config['login_url_session_key']:$defaults['login_url_session_key'];
		$defaults['local_fb_session_name'] = (isset($this->_config['local_fb_session_name'])) ? $this->_config['local_fb_session_name']:$defaults['local_fb_session_name'];

		// combine the defults with the options passed, giving those passed options the priority
		$options += $defaults;

		$user_data = false;

		$session = FacebookProxy::getSession();
		$uid = null;
		// Session based API call.
		if ($session) {
			// Set the session locally
			Session::write($options['local_fb_session_name'], $session);
			try {
				$uid = FacebookProxy::getUser();
			} catch (Exception $e) {
				//error_log($e);
			}
		}

		// If $uid is set, then write the fb_logout_url session key
		if (!empty($uid)) {
			if($options['logout_url_session_key']) {
				Session::write($options['logout_url_session_key'], FacebookProxy::getLogoutUrl($options['logout_url_options']));
			}

			// Get the user data to return
			$user_data = array();
			try {
				$user_data = FacebookProxy::api('/me');
			} catch(Exception $e) {
				//error_log($e);
			}

		} else {
			// Else, the user hasn't logged in yet, write the fb_login_url session key
			if($options['login_url_session_key']) {
				Session::write($options['login_url_session_key'], FacebookProxy::getLoginUrl($options['login_url_options']));
			}
		}

		return $user_data;
	}

	/**
	 * A pass-through method called by `Auth`. Returns the value of `$data`, which is written to
	 * a user's session. When implementing a custom adapter, this method may be used to modify or
	 * reject data before it is written to the session.
	 *
	 * @param array $data User data to be written to the session.
	 * @param array $options Adapter-specific options. Not implemented in the `Facebook` adapter.
	 * @return array Returns the value of `$data`.
	 */
	public function set($data, array $options = array()) {
		return $data;
	}

	/**
	 * Called by `Auth` when a user session is terminated. Not implemented in the `Facebook` adapter.
	 *
	 * @param array $options Adapter-specific options. Not implemented in the `Facebook` adapter.
	 * @return void
	 */
	public function clear(array $options = array()) {
	}

}
?>