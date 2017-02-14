<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Google;
use AppBundle\Entity\Dropbox;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Config\Definition\Exception\Exception;

define("DBX_OAUTH2_CREDENTIALS", __DIR__ . '/../dropbox-cred.json');
define("CLIENT_IDENTIFIER", "FileTransfer/2.0");
//define("DBX_REDIRECT_URI", 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']."/dbxauth");
define("DBX_REDIRECT_URI", 'https://localhost:81/cloud/web/dbxauth');
define("GOOGLE_OAUTH2_CREDENTIALS",__DIR__ . '/../google-cred.json');
//define("GOOGLE_REDIRECT_URI",'https://localhost:81/cloud/web/app_dev.php/gauth');
define("GOOGLE_REDIRECT_URI",'https://localhost:81/cloud/web/gauth');
define("GOOGLE_API","https://www.googleapis.com/auth/drive");
define("PAGE_REDIRECT", 'https://localhost:81/cloud/web');

define("INACTIVE", FALSE);
define("ACTIVE", TRUE);

class DefaultController extends Controller
{

    private $dropbox;

    private $session;

    private $googleauth;




    public function loadToken($userId, $provider)
    {
        try{
            $this->checkProviders($provider);
        }catch(Exception $e){
            echo "pokazilo sa to";
        }
        $em = $this->getDoctrine()->getManager();
        $token = $em->getRepository('AppBundle:'.ucfirst($provider))->findOneByuserid($userId);
        if(!$token)
            return false;
        else
            return $token;


    }

    public function checkProviders($provider)
    {
        $providers = ["dropbox", "google", "onedrive"];
        if(in_array($provider, $providers))
           return $provider;
        else
            throw new Exception("Unsupported provider");
    }

    public function insertToken($provider, $userId, $token_value)
    {
        
        try{
            $this->checkProviders($provider);
        }catch(Exception $e){
            echo "pokazilo sa to";
        }
        $entity = "AppBundle\Entity\\".ucfirst($provider);
        //var_dump($provider);
        $token = new $entity;
        $token->setUserid($userId);
        $token->setToken($token_value);
        if($provider === "google")
            $token->setStatus(ACTIVE);
        $em = $this->getDoctrine()->getManager();
        $em->persist($token);
        $em->flush();
        
    }

    public function removeToken($provider, $userId)
    {
         $this->session = $this->get('session');
        try{
            $this->checkProviders($provider);
        }catch(Exception $e){
            echo "pokazilo sa to";
        }
        $em = $this->getDoctrine()->getManager();
        
        $query = $em->createQuery(
            'SELECT p
            FROM AppBundle:'.ucfirst($provider).' p
            WHERE p.userid = :userId'
        )->setParameter('userId', '1');
        $token = $query->getResult();
        if(!empty($token)){
            foreach ($token as $index => $object) {
                $em->remove($object);}
              }
        $em->flush();
        
    }

    public function updateAccountStatus($userId, $provider, $status)
    {
        $em = $this->getDoctrine()->getManager();
        $record = $em->getRepository('AppBundle:'.ucfirst($provider))->findOneByuserid($userId);
        var_dump($record);
        if($record !== null)
            $record->setStatus($status);
        $em->flush();
    }


    public function dropbox()
    {
            //získam ID usera
        if((($this->session->get('access_token/dropbox')) == null)){
                $token = $this->loadToken("1", 'dropbox');
                if($token)
                {
                    $this->session->set('access_token/dropbox', $token);
                        //$_SESSION['access_token']['dropbox'] = $token;
                }else
                {
                    $this->session->set('auth/dropbox', new DropboxAuthorizeController(DBX_OAUTH2_CREDENTIALS, CLIENT_IDENTIFIER, DBX_REDIRECT_URI));
                        //$_SESSION['auth']['dropbox'] = new DropboxAuthorizeController(DBX_OAUTH2_CREDENTIALS, CLIENT_IDENTIFIER, DBX_REDIRECT_URI);
                    return $this->session->get('auth/dropbox')->getAuthUrl();
                        //return $_SESSION['auth']['dropbox']->getAuthUrl();
                }
            }
        return;

    }

    /**
    *@Route("/dbxauth", name="dbxauth")
   */
    public function dropboxAuthorizationCodeHandle()
    {
        $this->session = $this->get('session');
        if((($this->session->get('access_token/dropbox')) == null) && (isset($_GET['code'])))
        {
            //$_SESSION['auth']['dropbox']->obtainAccessToken();
            //$_SESSION['access_token']['dropbox'] = $_SESSION['auth']['dropbox']->getAccessToken();
            //var_dump($_SESSION['access_token']['dropbox']);
            $this->session->get('auth/dropbox')->obtainAccessToken();
            $this->session->set('access_token/dropbox', $this->session->get('auth/dropbox')->getAccessToken());
            $this->insertToken("dropbox", "1", $this->session->get('access_token/dropbox'));
            
        }
         return $this->redirect(PAGE_REDIRECT);
    }

    public function google()
    {
        // $this->session->remove("refresh_token/google");
        $token = $this->loadToken("1", "google");
        if((($this->session->get('refresh_token/google')) == null) || ($token))
        {

            if($token && $token->getStatus())
            {
                $this->session->set('refresh_token/google', $token->getToken());
            }
            else
            {
                $this->googleauth = new GoogleAuthorize(GOOGLE_OAUTH2_CREDENTIALS, GOOGLE_REDIRECT_URI, GOOGLE_API);
                return $this->googleauth->getAuthUrl();
            }
        }elseif($this->session->get('refresh_token/google') && (!$token))
            $this->insertToken("google", "1", $this->session->get('refresh_token/google'));
        return;
    }

    /**
     * @Route("/gauth", name="gauth")
     */
    public function googleAuthorizationCodeHandle()
    {
        $this->session = $this->get('session');

        if(isset($_GET['code']))
        {
            if(!isset($this->googleauth))
            {
                $this->googleauth = new GoogleAuthorize(GOOGLE_OAUTH2_CREDENTIALS, GOOGLE_REDIRECT_URI, GOOGLE_API);
            }

            $token = $this->googleauth->obtainToken();
            if(isset($token['refresh_token']))
            {
                $this->session->set('refresh_token/google', $token['refresh_token']);
                $this->insertToken("google", "1", $this->session->get('refresh_token/google'));
            }else
            {
                $this->updateAccountStatus("1","google", ACTIVE);
            }
        }
        return $this->redirect(PAGE_REDIRECT);
    }   



    /**
     * @Route("/logout/{provider}", name="logout")
     */
    public function logout($provider)
    {
        try{
            $this->checkProviders($provider);
        }catch(Exception $e){
            echo "pokazilo sa to";
        }
        
        if($provider === "dropbox")
            $this->logoutDropbox($provider);
        elseif($provider === "google")
            $this->logoutGoogle($provider);

        return $this->redirect(PAGE_REDIRECT);
    }

    //google odhlasenie, odstran token zo sessny, nastav parameter v databazy na odhlásený
    //dropbox odhlasenie, odstran access token z DB, odstran token zo sessny
    //opravenie url po redirectu 


    public function logoutDropbox($provider)
    {
        $this->removeToken("dropbox", "1");
        $this->session->remove("access_token/".$provider);
    }

    public function logoutGoogle($provider)
    {
        $this->session = $this->get('session');
        $this->session->remove("refresh_token/".$provider);
        $this->updateAccountStatus("1", "google", INACTIVE);
    }




    /**
     * @Route("upload/dropbox", name="uploaddbx")
     */
    public function uploadDropbox()
    {
        $this->session = $this->get('session');
        $upload = new DbxUpload($this->session->get('access_token/dropbox'), CLIENT_IDENTIFIER);
        $upload->getFiles();
        $upload->UploadFiles();
        return $this->redirect(PAGE_REDIRECT);
    }

    /**
     * @Route("upload/google", name="uploadgoogle")
     */
    public function uploadGoogle()
    {
        $this->session = $this->get('session');

        if(!isset($this->googleauth)){
            $this->googleauth = new GoogleAuthorize(GOOGLE_OAUTH2_CREDENTIALS, GOOGLE_REDIRECT_URI, GOOGLE_API);
        }
        $this->googleauth->getTokenWithRefreshToken($this->session->get('refresh_token/google'));
        //$this->googleauth->setToken($this->session->get('access_token/google'));
        $upload = new GoogleUpload($this->googleauth->getGoogleClient());
        $upload->getFiles();
        $upload->uploadFiles();
       return $this->redirect(PAGE_REDIRECT);

    }


    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {

        $this->session = $this->get('session');

        $google = $this->google();
        $dropbox = $this->dropbox();
        return $this->render('default/index.html.twig', array(
            'authurl' => $dropbox,
            'gauthurl'=> $google
            
            ));

    }


}
