<?php

namespace AppBundle\Controller;

use \Dropbox as dbx;
require_once __DIR__.'/../../../vendor/autoload.php';



/**
 * Authorizes user and returns access token to dropbox account
 */
class DropboxAuthorizeController
{
	

	/**
	 * $webAuth stores WebAuth object
	 * @var \Dropbox\WebAuth
	 */
	private $webAuth;

	function getWebAuth(){ return $this->webAuth; }

	
	/**
	 * $csrfTokenStore stores CSRF token
	 * @var \Dropbox\ArrayEntryStore
	 */
	public $csrfTokenStore;

	function getCsrfTokenStore(){ return $this->csrfTokenStore; }


	/**
	 * $accessToken stores access token generated for client,
	 * which can also be used to construct a {@link Client}
	 * @var string
	 */
	public $accessToken;

	function getAccessToken(){ return $this->accessToken; } 

	/**
	 * $userId is the user ID of the user's Dropbox account
	 * @var int
	 */
	public $userId;

	function getUserId(){ return $this->userId; }

	/**
	 * $urlState is the value you originally passed in to {@link start()}
	 * @var string
	 */
	private $urlState;

	function getUrlState(){ return $this->urlState; }

	/**
	 * Check if access token is set in session
	 * @return boolean 
	 */
	function isLoggedIn()
	{
		return isset($_SESSION['access_token']['dropbox']);
	}
	

	function getAuthUrl()
	{
		if(!empty($this->webAuth))
			return $this->webAuth->start();
	}
	/**
	 * @return  \Dropbox\ArrayEntryStore 
	 * A class that gives get/put/clear access to a single entry in an array.
	 */
	function obtainCsrfToken(){ return new dbx\ArrayEntryStore($_SESSION, 'dropbox-auth-csrf-token'); }


	/**
	 * @param  string $o2AuthCredentials
	 *		path to a JSON file
	 * @param string $clientIdentifier
     * 		See {@link getClientIdentifier()}
	 * @param null|string $redirectUri
     * 		See {@link getRedirectUri()}
	 * @param null|ValueStore $csrfTokenStore
     *      See {@link getCsrfTokenStore()}
	 * @param  null|string $userLocale
	 * 		See {@link getUserLocale()}
	 * @return \Dropbox\WebAuth
	 */
	function obtainWebAuth($o2AuthCredentials, $clientIdentifier, $redirectUri, $userLocale = null)
	{
		$appinfo = dbx\AppInfo::loadFromJsonFile($o2AuthCredentials);
		$this->webAuth = new dbx\WebAuth($appinfo, $clientIdentifier, $redirectUri, $this->csrfTokenStore, $userLocale);
	}



	function __construct($o2AuthCredentials, $clientIdentifier, $redirectUri, $userLocale = null)
	{
		$this->csrfTokenStore = $this->obtainCsrfToken();
		$this->obtainWebAuth($o2AuthCredentials, $clientIdentifier, $redirectUri, $userLocale);	
	}

	function obtainAccessToken(){
		if(isset($_GET['code']))
		{
			try{
				list($this->accessToken, $this->userId, $this->urlState) = $this->webAuth->finish(array(
					'code' => $_GET['code'],
					'state' => $this->csrfTokenStore->get()));
			}
			catch(dbx\WebAuthException_BadRequest $ex){
				error_log("/dropbox-auth-finish: bad request: " . $ex->getMessage());
			}
			catch(dbx\WebAuthException_BadState $ex){
				
			}
			catch(dbx\WebAuthException_Csrf $ex){
				error_log("/dropbox-auth-finish: CSRF mismatch: " . $ex->getMessage());
			}
			catch(dbx\WebAuthException_NotApproved $ex){
				error_log("/dropbox-auth-finish: not approved: " . $ex->getMessage());
			}
			catch(dbx\WebAuthException_Provider $ex){
				error_log("/dropbox-auth-finish: error redirect from Dropbox: " . $ex->getMessage());
			}
			catch (dbx\Exception $ex) {
    			error_log("/dropbox-auth-finish: error communicating with Dropbox API: " . $ex->getMessage());
  			}
			return;
		}		
	}
}


