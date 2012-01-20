# OAuth consumer component for CakePHP

## Purpose

An OAuth consumer component for CakePHP 2.0.x supporting OAuth 1.0 as defined in http://tools.ietf.org/html/rfc5849.

## Installation

* Copy the component and the `OAuthConsumers` folder to the `Controller/Component` folder of your application

## Usage

For each API you want to use, you have to write a consumer class. This class is responsible to handle the consumer key and consumer secret you get from the API provider (for using the Twitter API, as in this example, you have to register your application at https://twitter.com/oauth).

The requirements for such a class are:

* its name must end with `Consumer`
* it must extend `AbstractConsumer`
* it must be placed in the `OAuthConsumers` folder

As you can see in the example below, a consumer class is pretty simple:

```php
// Controller/Component/OAuthConsumers/TwitterConsumer.php
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
// Controller/TwitterController.php
class TwitterController extends AppController {
  public $components = array('OAuthConsumer');

  public function index() {
    $requestToken = $this->OAuthConsumer->getRequestToken('Twitter', 'https://api.twitter.com/oauth/request_token', 'http://' . $_SERVER['HTTP_HOST'] . '/twitter/callback');
    $this->Session->write('twitter_request_token', $requestToken);
    $this->redirect('https://api.twitter.com/oauth/authorize?oauth_token=' . $requestToken->key);
  }

  public function callback() {
    $requestToken = $this->Session->read('twitter_request_token');
    $accessToken = $this->OAuthConsumer->getAccessToken('Twitter', 'https://api.twitter.com/oauth/access_token', $requestToken);

    $this->OAuthConsumer->post('Twitter', $accessToken->key, $accessToken->secret, 'https://api.twitter.com/1/statuses/update.json', array('status' => 'hello world!'));
    exit;
  }
}
```

## Migration from CakePHP 1.x to CakePHP 2.0.x

If you are migrating your application to CakePHP 2.0.x, you have to make a few changes beside updating the component. First, you have to rename `OauthConsumer` to `OAuthConsumer` in the `$components` array and everywhere you are using the component. And second, you have to move all consumer classes to the new `OAuthConsumers` folder and camel-case the file names, i.e. `twitter_consumer.php` becomes `TwitterConsumer.php`.

## Contact

If you have questions or feedback, feel free to contact me via Twitter ([@dhofstet](https://twitter.com/dhofstet)) or by email (daniel.hofstetter@42dh.com).

## License

The OAuth consumer component is licensed under the MIT license.
