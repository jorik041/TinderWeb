<?php

/*
 * Test for tinder api
 * by rodrigo nas
 * 
 * 9dc3b5f88fd9
 * 
 */

set_time_limit(0);
error_reporting(E_ALL);

Class TinderApi {
	var $fb_token;
	var $fb_id;
	var $x_auth_token;
	var $tinder_host;
	var $url;
	var $post_payload;
	var $http_response;
	var $http_info;
	var $http_header;
	var $post_ssl;
	var $general_account_info;
	var $auth_status;

	function __construct($fb_token, $fb_id) {
		$this->fb_token     = $fb_token;
		$this->fb_id        = $fb_id;
		$this->http_header  = array();
		$this->post_ssl     = false;
		$this->x_auth_token = false;
		$this->auth_status  = false;
	}

	function getFbToken() {
		return $this->fb_token;
	}

	function getFbId() {
		return $this->fb_id;
	}

	function setPostPayload($post_payload) {
		$this->post_payload = json_encode($post_payload);
		return $this;
	}

	function getPostPayload(){
		return $this->post_payload;
	}

	function addHttpHeader($payload) {
		$this->http_header[] = $payload;
		return $this;
	}
	
	function setHttpHeader(array $payload) {
		$this->http_header = $payload;
		return $this;
	}

	function getHttpHeder() {
		return $this->http_header;
	}

	function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	function getUrl() {
		return $this->url;
	}

	function postSsl($opt = true) {
		$this->post_ssl = $opt;
		return $this;
	}

	function getRequest() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->http_header);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$this->http_response = curl_exec($ch);
		$this->http_info     = curl_getinfo($ch);

		return $this;
	}

	function postRequest() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->http_header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_payload);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$this->http_response = curl_exec($ch);
		$this->http_info     = curl_getinfo($ch);

		return $this;
	}

	function getResponse() {
		return $this->http_response;
	}

	function getResponseInfo($key = false) {
		$info = ($key !== false) ? $this->http_info[$key] : $this->http_info;
		return $info;
	}

	function getGeneralAccountInfo($key = false) {
		$info = ($key !== false) ? $this->$this->general_account_info[$key] : $this->general_account_info;
		return $info;
	}

	function authOk() {
		return $this->auth_status;
	}

	function authStatus($status = false) {
		$this->auth_status = $status;
	}

	/**
	 * This method prepare tinder to interact with common activities after auth:
	 * - Update your profile
	 * - Report a user
	 * - Message Send
	 * - Update location
	 * - Get updates
	 * - Like or pass a user
	 * - Recommendations (list of users to like or pass)
	 *
         * API Details
         * Host 	    api.gotinder.com
         * Protocol 	SSL only
         * 
         * Request headers
         * Header name 	    Description / example 	                                                  Required?
         * X-Auth-Token     A UUID4 format authentication token obtained via the /auth api endpoint       Yes
         * Content-type     application/json 	                                                          Yes
         * app_version 	    3                                                                      	  no
         * platform 	    ios 	                                                                  no
         * User-agent 	    Tinder/3.0.4 (iPhone; iOS 7.1; Scale/2.00) 	                                  Yes
         * os_version 	    700001 	                                                                  No
         * 
	 */
	function prepareTinder() {

		if ($this->getResponseInfo("http_code") == 200) {

			$this->general_account_info = json_decode($this->http_response, true); // info about profile
			$this->x_auth_token         = $this->general_account_info['token']; // X-Auth-Token
			
			$this->addHttpHeader(sprintf("X-Auth-Token: %s", $this->x_auth_token));
			$this->authStatus(true); // authOk() now returns TRUE
		}

		return $this;

	}

	function startWithXAuth($x_auth_token) {
		
		$http_header = array();
		$http_header[] = 'app-version: 123';
		$http_header[] = 'platform: ios';
		$http_header[] = 'content-type: application/json';
		$http_header[] = 'User-agent: Tinder/4.0.9 (iPhone; iOS 8.0.2; Scale/2.00)';
		$http_header[] = sprintf("X-Auth-Token: %s", $x_auth_token);
	   
		$this->setHttpHeader($http_header);

		return $this;
	}

	function getRecs() {
		$this->setUrl('https://api.gotinder.com/user/recs')
		     ->getRequest();

		$recs = json_decode($this->http_response, true);
	    if ($recs['status'] == 200) {
			var_dump($recs['results'][1]);
		} else {
			exit("Error in recommendations listing");
		}
	    
	    exit;
	}
}

/*
 * First to make any activity in api you need to have the X-Auth-Token acquired via
 * post in https://api.gotinder.com/auth:
 * 
 *  You need to post facebook_token and facebook_id:
 *  $this->post_payload = json_encode(array('facebook_token' => $this->fb_token,
 * 			                                'facebook_id'    => $this->fb_id));
 * 
 *  A good way to research the data is via:
 *  http://opauth.org/ in facebook Try me to cat all information about the facebook account
 */
$tinder = new TinderApi('fb_token', 'fb_id');

// Start with x-auth-token
$tinder->startWithXAuth('x-auth-token')
       ->getRecs();
       
       exit;

// Start without x-auth-token
$tinder->setPostPayload(array('facebook_token' => $tinder->getFbToken(), 'facebook_id' => $tinder->getFbId()))
       ->setUrl('https://api.gotinder.com/auth')
       ->addHttpHeader('app-version: 123')
       ->addHttpHeader('platform: ios')
       ->addHttpHeader('content-type: application/json')
       ->addHttpHeader('User-agent: Tinder/4.0.9 (iPhone; iOS 8.0.2; Scale/2.00)')
       ->postSsl(false) // Do not check ssl.
       ->postRequest() // Post request
       ->prepareTinder() // Prepare header with X-Auth-Token
       ->getRecs(); // Recommendantion list


