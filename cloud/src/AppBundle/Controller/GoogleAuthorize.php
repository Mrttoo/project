<?php

namespace AppBundle\Controller;
use \Google\Client;

require_once __DIR__.'/../../../vendor/autoload.php';


/**
 * Class that helps with authorization into google API
 */
class GoogleAuthorize
{
	/**
	 * @var Google_Client $client
	 */
	private $client;

	/**
	 * @var String $token
	 */
	private $token;

	function __construct($o2AuthCredentials, $redirect_uri, $scope)
	{
		$this->client = new \Google_Client();
		$this->client->setAuthConfig($o2AuthCredentials);
		$this->client->setRedirectUri($redirect_uri);
		$this->client->setAccessType('offline');
		$this->client->addScope($scope);
	}

	function getGoogleClient()
	{
		return $this->client;
	}


	/**
	 * Creates authorized url which redirects user to page for authentication 
	 * @return string
	 */
	function getAuthUrl()
	{
		return $this->client->createAuthUrl();
	}

	/**
	 * If authorization code is set, authenticate client and call setToken method
	 */
	function obtainToken()
	{
		if(isset($_GET['code']))
		{
			$this->client->authenticate($_GET['code']);
			return $this->token = $this->client->getAccessToken();
			//$this->setToken($this->client->getAccessToken());
		} 

	}

	function getToken()
	{
		return $this->token;
	}

	function getAccessToken()
	{
		return $this->token['access_token'];
	}

	
	/**
	 * Assign token to SESSION and also set it as part of client object
	 * @param string $token JSON string
	 */
	function setToken($token)
	{
		$this->token = $token;
		try{
			$this->client->setAccessToken($this->token);
		}
		catch(InvalidArgumentException $e)
		{
			print ($e->getMessage());	
		}
	}

	
	/**
	 * Returns refresh token which user gets first time he allows access to application
	 * @return string
	 */
	function getRefreshToken()
	{
		return $this->token['refresh_token'];
	}

	/**
	 * Uses refresh token to get new access token and sets it as part of client object
	 * @param  string $refreshToken 
	 */
	function getTokenWithRefreshToken($refreshToken = null)
	{
		
		$this->client->refreshToken($refreshToken);
		$this->setToken($this->client->getAccessToken());
	}

	/**
	 * Check if access token is expired and new token is needed to be fetch
	 * @return bool Returns true if token is expired
	 
	function checkExpiration()
	{
		if(isset($_SESSION['access_token']['google_drive']))
			return $this->client->isAccessTokenExpired($_SESSION['access_token']['google_drive']);
	}
*/
}