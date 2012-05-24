# li3_facebook

Lithium library to interact with facebook. At first, it is a facebook PHP SDK Decorator, but it is more than that.

## Installation

Add a submodule to your li3 libraries:

	git submodule add git@github.com:bruensicke/li3_facebook.git libraries/li3_facebook

and activate it in you app (config/bootstrap/libraries.php), of course:

	Libraries::add('li3_facebook', array(
		'appId' => 'key'
		'secret' => 'secret'
	));

## Usage

	// static calling
	li3_facebook\extension\Facebook::getAppId($params);

	// or
	li3_facebook\extension\Facebook::run('getAppId', $params);

## Other examples

	// get user
	$user = $facebook->getUser();

	if ($user) {
		try {
			$user_profile = $facebook->api('/me');
		} catch (FacebookApiException $e) {
			error_log($e);
			$user = null;
		}
	}

## Credits

* [li3](http://www.lithify.me)
* [tmaiaroto](https://github.com/tmaiaroto)
* [Marc Schwering, weluse GmbH](http://www.weluse.de)

