# OAuth consumer component for CakePHP

## Purpose

An OAuth consumer component for CakePHP 1.x supporting OAuth 1.0 as defined in http://tools.ietf.org/html/rfc5849.

## Installation

* Copy the component and the `oauth_consumers` folder to the `controllers/components` folder of your application

## Usage

For each API you want to use, you have to write a consumer class. This class is responsible to handle the consumer key and consumer secret you get from the API provider (for using the Twitter API, as in this example, you have to register your application at https://twitter.com/oauth).

The requirements for such a class are:

* its class name must be camel-cased and end with `Consumer`, e.g. `TwitterConsumer`
* it must extend `AbstractConsumer`
* the file name must use underscores, e.g. `twitter_consumer.php`
* it must be placed in the `oauth_consumers` folder

As you can see in the example below, a consumer class is pretty simple:

```php
<?php
// controllers/components/oauth_consumers/twitter_consumer.php
class TwitterConsumer extends AbstractConsumer {
  public function __construct() {
    parent::__construct('YOUR_CONSUMER_KEY', 'YOUR_CONSUMER_SECRET');
  }
}
```
As usual in CakePHP, you have to add the component to the `$components` array of the controller(s) in which you want to use the component.

In the `index` method a request token is obtained and the user is redirected to Twitter where he has to authorize the request token. Notice the first parameter passed to the `getRequestToken` method, it is the name (without `Consumer`) of the previously created consumer class and tells the component which credentials it should use for the request.

In the `callback` method the request token is exchanged for an access token. Using this access token, a new status is posted to Twitter. Please note that in a real application, you would save the access token data in a database to avoid that the user has to get an access token over and over again.

```php
<?php
// controllers/twitter_controller.php
class TwitterController extends AppController {
  public $uses = array();
  public $components = array('OauthConsumer');

  public function index() {
    $requestToken = $this->OauthConsumer->getRequestToken('Twitter', 'https://api.twitter.com/oauth/request_token', 'http://' . $_SERVER['HTTP_HOST'] . '/twitter/callback');

    if ($requestToken) {
      $this->Session->write('twitter_request_token', $requestToken);
      $this->redirect('https://api.twitter.com/oauth/authorize?oauth_token=' . $requestToken->key);
    } else {
      // an error occured when obtaining a request token
    }
  }

  public function callback() {
    $requestToken = $this->Session->read('twitter_request_token');
    $accessToken = $this->OauthConsumer->getAccessToken('Twitter', 'https://api.twitter.com/oauth/access_token', $requestToken);

    if ($accessToken) {
      $this->OauthConsumer->post('Twitter', $accessToken->key, $accessToken->secret, 'https://api.twitter.com/1/statuses/update.json', array('status' => 'hello world!'));
    }
    exit;
  }
}
```

## Contact

If you have questions or feedback, feel free to contact me via Twitter ([@dhofstet](https://twitter.com/dhofstet)) or by email (daniel.hofstetter@42dh.com).

## License

The OAuth consumer component is licensed under the MIT license.
