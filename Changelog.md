# Changelog

### v1.0.1 (2012-01-27)

* Inlining and updating the OAuth library

### v1.0.0 (2011-08-24)

* Switching to Semantic Versioning
* Re-purpose the `getFullResponse()` method so it will always return an array with the complete response of the last request

### v2009-09-05

* Including the PHP library for OAuth in the package for convenience purposes, so you no longer have to download this library separately
* Adding a `getFullResponse()` method for debugging purposes
* Adapting the class for OAuth 1.0a. The `getRequestToken()` method got a new parameter named `$callback`: `getRequestToken($consumerName, $requestTokenURL, $callback = 'oob', $httpMethod = 'POST', $parameters = array())`
* Fixing a bug that causes a "class not found" error when both the component and the "vendors" files are in a plugin

### v2009-02-06

* Fixing problem with loading consumer files when the component is used in a plugin. Thanks to [Rui Cruz](http://www.ruicruz.com/) for the patch!
* Adding a protected `createOAuthToken()` method

### v2008-11-10

* Fixing problem with Request/Access token urls using a querystring

### v2008-09-15

* Fixing some bugs. Please be aware that the signatures of `get()` and `post()` changed!

### v2008-09-01

* Initial release
