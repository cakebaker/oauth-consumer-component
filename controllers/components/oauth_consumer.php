<?php
/**
 * A simple OAuth consumer component for CakePHP.
 * 
 * Requires the OAuth library from http://oauth.googlecode.com/svn/code/php/
 * 
 * Copyright (c) by Daniel Hofstetter (http://cakebaker.42dh.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 */

App::import('Core', 'http_socket');

class OauthConsumerComponent extends Object {
	private $url = null;
	private $fullResponse = null;
	
	public function __construct() {
		parent::__construct();

		$pathToVendorsFolder = $this->getPathToVendorsFolderWithOAuthLibrary();
		
		if ($pathToVendorsFolder == '') {
			exit('Unable to find the PHP library for OAuth');
		}
		
		$importPrefix = '';

		if ($this->isPathWithinPlugin($pathToVendorsFolder)) {
			$importPrefix = $this->getPluginName() . '.';
		}
		
		App::import('Vendor', $importPrefix.'oauth', array('file' => 'OAuth'.DS.'OAuth.php'));
	}
	
	/**
	 * Call API with a GET request
	 */
	public function get($consumerName, $accessTokenKey, $accessTokenSecret, $url, $getData = array()) {
		$accessToken = new OAuthToken($accessTokenKey, $accessTokenSecret);
		$request = $this->createRequest($consumerName, 'GET', $url, $accessToken, $getData);
		
		return $this->doGet($request->to_url());
	}
	
	public function getAccessToken($consumerName, $accessTokenURL, $requestToken, $httpMethod = 'POST', $parameters = array()) {
		$this->url = $accessTokenURL;
		$queryStringParams = OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);
		$parameters['oauth_verifier'] = $queryStringParams['oauth_verifier'];
		$request = $this->createRequest($consumerName, $httpMethod, $accessTokenURL, $requestToken, $parameters);
		
		return $this->doRequest($request);
	}
	
	/**
	 * Useful for debugging purposes to see what is returned when requesting a request/access token.
	 */
	public function getFullResponse() {
		return $this->fullResponse;
	}
	
	/**
	 * @param $consumerName
	 * @param $requestTokenURL
	 * @param $callback An absolute URL to which the Service Provider will redirect the User back when the Obtaining User 
	 * 					Authorization step is completed. If the Consumer is unable to receive callbacks or a callback URL 
	 * 					has been established via other means, the parameter value MUST be set to oob (case sensitive), to 
	 * 					indicate an out-of-band configuration. Section 6.1.1 from http://oauth.net/core/1.0a
	 * @param $httpMethod 'POST' or 'GET'
	 * @param $parameters
	 */
	public function getRequestToken($consumerName, $requestTokenURL, $callback = 'oob', $httpMethod = 'POST', $parameters = array()) {
		$this->url = $requestTokenURL;
		$parameters['oauth_callback'] = $callback;
		$request = $this->createRequest($consumerName, $httpMethod, $requestTokenURL, null, $parameters);
		
		return $this->doRequest($request);
	}
	
	/**
	 * Call API with a POST request
	 */
	public function post($consumerName, $accessTokenKey, $accessTokenSecret, $url, $postData = array()) {
		$accessToken = new OAuthToken($accessTokenKey, $accessTokenSecret);
		$request = $this->createRequest($consumerName, 'POST', $url, $accessToken, $postData);
		
		return $this->doPost($url, $request->to_postdata());
	}
	
	protected function createOAuthToken($response) {
		if (isset($response['oauth_token']) && isset($response['oauth_token_secret'])) {
			return new OAuthToken($response['oauth_token'], $response['oauth_token_secret']);
		}
		
		return null;
	}
	
	private function createConsumer($consumerName) {
		$CONSUMERS_PATH = dirname(__FILE__).DS.'oauth_consumers'.DS;
		App::import('File', 'abstractConsumer', array('file' => $CONSUMERS_PATH.'abstract_consumer.php'));
		
		$fileName = Inflector::underscore($consumerName) . '_consumer.php';
		$className = $consumerName . 'Consumer';
		
		if (App::import('File', $fileName, array('file' => $CONSUMERS_PATH.$fileName))) {
			$consumerClass = new $className();
			return $consumerClass->getConsumer();
		} else {
			throw new InvalidArgumentException('Consumer ' . $fileName . ' not found!');
		}
	}
	
	private function createRequest($consumerName, $httpMethod, $url, $token, array $parameters) {
		$consumer = $this->createConsumer($consumerName);
		$request = OAuthRequest::from_consumer_and_token($consumer, $token, $httpMethod, $url, $parameters);
		$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, $token);
		
		return $request;
	}
	
	private function doGet($url) {
		$socket = new HttpSocket();
		return $socket->get($url);
	}
	
	private function doPost($url, $data) {
		$socket = new HttpSocket();
		return $socket->post($url, $data);
	}
	
	private function doRequest($request) {
		if ($request->get_normalized_http_method() == 'POST') {
			$data = $this->doPost($this->url, $request->to_postdata());
		} else {
			$data = $this->doGet($request->to_url());
		}

		$this->fullResponse = $data;
		$response = array();
		parse_str($data, $response);

		return $this->createOAuthToken($response);
	}
	
	private function getPathToVendorsFolderWithOAuthLibrary() {
		$pathToVendorsFolder = '';
		
		if ($this->isPathWithinPlugin(__FILE__)) {
			$pluginName = $this->getPluginName();
			
			if (file_exists(APP.'plugins'.DS.$pluginName.DS.'vendors'.DS.'OAuth')) {
				$pathToVendorsFolder = APP.'plugins'.DS.$pluginName.DS.'vendors'.DS;
			}
		}

		if ($pathToVendorsFolder == '') {
			if (file_exists(APP.'vendors'.DS.'OAuth')) {
				$pathToVendorsFolder = APP.'vendors'.DS;
			} elseif (file_exists(VENDORS.'OAuth')) {
				$pathToVendorsFolder = VENDORS;
			}
		}
		
		return $pathToVendorsFolder;
	}
	
	private function getPluginName() {
		$result = array();
		if (preg_match('#'.DS.'plugins'.DS.'(.*)'.DS.'controllers#', __FILE__, $result)) { 
			return $result[1];
		}
		
		return false;
	}
	
	private function isPathWithinPlugin($path) {
		return strpos($path, DS.'plugins'.DS) ? true : false;
	}
}