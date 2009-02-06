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
 * @version			$Revision: 57 $
 * @modifiedby		$LastChangedBy: dho $
 * @lastmodified	$Date: 2008-09-01 07:25:15 +0200 (Mon, 01 Sep 2008) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::import('Vendor', 'oauth', array('file' => 'OAuth'.DS.'OAuth.php'));
App::import('Core', 'http_socket');

class OauthConsumerComponent extends Object {
	private $url = null;
	
	/**
	 * Call API with a GET request
	 */
	public function get($consumerName, $accessTokenKey, $accessTokenSecret, $url, $getData = array()) {
		$accessToken = new OAuthToken($accessTokenKey, $accessTokenSecret);
		$request = $this->prepareRequest($consumerName, $accessToken, 'GET', $url, $getData);
		
		return $this->doGet($request->to_url());
	}
	
	public function getAccessToken($consumerName, $accessTokenURL, $requestToken, $httpMethod = 'POST', $parameters = array()) {
		$this->url = $accessTokenURL;
		$request = $this->prepareRequest($consumerName, $requestToken, $httpMethod, $accessTokenURL, $parameters);
		
		return $this->doRequest($request);
	}
	
	public function getRequestToken($consumerName, $requestTokenURL, $httpMethod = 'POST', $parameters = array()) {
		$this->url = $requestTokenURL;
		$request = $this->prepareRequest($consumerName, null, $httpMethod, $requestTokenURL, $parameters);
		
		return $this->doRequest($request);
	}
	
	/**
	 * Call API with a POST request
	 */
	public function post($consumerName, $accessTokenKey, $accessTokenSecret, $url, $postData = array()) {
		$accessToken = new OAuthToken($accessTokenKey, $accessTokenSecret);
		$request = $this->prepareRequest($consumerName, $accessToken, 'POST', $url, $postData);
		
		return $this->doPost($url, $request->to_postdata());
	}
	
	protected function createOauthToken($response) {
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

		$response = array();
		parse_str($data, $response);

		return $this->createOauthToken($response);
	}
	
	private function prepareRequest($consumerName, $token, $httpMethod, $url, $parameters) {
		$consumer = $this->createConsumer($consumerName);
		$request = OAuthRequest::from_consumer_and_token($consumer, $token, $httpMethod, $url, $parameters);
		$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, $token);
		
		return $request;
	}
}