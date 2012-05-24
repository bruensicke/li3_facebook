<?php

namespace li3_facebook\extensions;

use lithium\core\Libraries;
//use lithium\core\Environment;

use Exception;
use lithium\core\ClassNotFoundException;
use lithium\core\ConfigException;

/**
* The `FacebookProxy` class handles all Facebook related functionalities.
* The class is mainly a lithium wrapper for the existing Facebook API. It is oriented by
* the proxy-pattern which is using the original FB-API as a singleton.
* It has to be configured by an api key and the secret.
*/
class Facebook extends \lithium\core\StaticObject {

	/**
	 * Holds the configuration Options
	 * @var array
	*/
	protected static $_config = array();

	/**
	 * These are the class `defaults`
	 * @var array
	 */
	protected static $_defaults = array(
		'appId' => '',
		'secret' => '',
	);

	/**
	 * Holds the FacebookAPI as singleton
	 * @var Facebbok
	 */
	public static $_facebookApiInstance = null;

	public function __construct($config = array()) {
		if ($config){
			static::config($config);
		}
	}

	/**
	 *
	 * @return void
	 */
	public static function __init() {
		$libraryConfig = Libraries::get('li3_facebook');
		static::config($libraryConfig + static::$_defaults);

		/* wont work */
		/*
		 static::applyFilter('invokeMethod', function($self, $params, $chain) {
			// Custom pre-dispatch logic goes here
			die("awesome");
			$response = $chain->next($self, $params, $chain);
			return $response;
			});
		 *
		 */
	}

	/**
	 * Sets configurations for the FacebookApi.
	 * This Method is basically a copy and edit of the config in adaptable.
	 *
	 * @see lithium\core\adaptable
	 *
	 * @param array $config Configuratin.
	 * @return array|void `Collection` of configurations or true if setting configurations.
	 */
	public static function config($config = null) {
		//set if `config`is given
		if ($config && is_array($config)) {
			//filter only accepts configuration options
			foreach ($config as $key => $value){
				if (\array_key_exists($key, static::$_defaults)){
					static::$_config[$key] = $value;
				}
			};
			return true;
		}
		//if we r using more than one config...=> named config
		/*
		if ($config) {
			return static::_config($config);
		}
		 */

		//set and filter
		//$result = array();

		//due false and unset Values we disable the filtering:
		//static::$_config = array_filter(static::$_config);
		//we dont use named configs (now)
		/*
		foreach (array_keys(static::$_config) as $key) {
			$result[$key] = static::_config($key);
		}
		 */
		//so we return the current config
		$result = static::$_config;
		return $result;
	}

	/**
	 * Clears all configurations.
	 *
	 * @return void
	 */
	public static function reset() {
		static::$_facebookApiInstance = null;
		static::$_config = array();
	}

	/**
	 * Does proxying the method calls
	 * @param string $method
	 * @param mixed $arguments
	 */
	public static function __callStatic($method, $arguments) {
		return static::run($method,$arguments);
	}

	/**
	 * Calls should be rerouted to the facebookApiInstance of the proxy
	 * @todo insert a callable existance check
	 *
	 * @see lithium/core/StaticObject
	 *
	 * @throws FacebookApiException
	 *
	 * @param string $method
	 * @param mixed $params
	 * @return mixed Return value of the called api method
	 * @filter this Method is filterable
	 */
	public static function run($method, $params = array()) {
		$params = compact('method', 'params');
		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			extract($params);

			if (!$self::$_facebookApiInstance){
				$self::invokeMethod('instanciateFacebookApi');
			}

			if (!is_callable(array($self::$_facebookApiInstance,$method))){
				throw new Exception(__CLASS__ . " Method `$method` is not callable");
			}

			switch (count($params)) {
				case 0:
					return $self::$_facebookApiInstance->$method();
				case 1:
					return $self::$_facebookApiInstance->$method($params[0]);
				case 2:
					return $self::$_facebookApiInstance->$method($params[0], $params[1]);
				case 3:
					return $self::$_facebookApiInstance->$method(
						$params[0], $params[1], $params[2]
					);
				case 4:
					return $self::$_facebookApiInstance->$method(
						$params[0], $params[1], $params[2], $params[3]
					);
				case 5:
					return $self::$_facebookApiInstance->$method(
						$params[0], $params[1], $params[2], $params[3], $params[4]
					);
				default:
					//i am not sure if this is a good idea
					return call_user_func_array(array(get_called_class(), $method), $params);
			}
		});
	}

	/**
	 * Does savely instanciating the Facebook Api.
	 * @throws Exception for various Errors.
	 *
	 * @param array $config
	 * @return Facebook $apiInstance
	 * @filter This method may be filtered.
	 */
	public static function instanciateFacebookApi($config = array()){
		$params = compact('config');
		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			extract($params);

			if ($config){
				$self::config($config);
			}
			require_once LI3_FACEBOOK_PATH . '/libraries/facebook-sdk/src/facebook.php';
			$apiInstance = new \Facebook($self::config());
			if (!$apiInstance){
				throw new Exception('Facebook Api cant instanciated!');
			}
			$self::$_facebookApiInstance = $apiInstance;
			return $apiInstance;
		});
	}

	/**
	 * Returns the instaciated Facebook Class for own usage.
	 *
	 * @return Facebook $facebookInstance
	 */
	public static function getApiInstance(){
		return static::$_facebookApiInstance;
	}
}

?>