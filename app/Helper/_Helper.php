<?php
namespace App\Helper;
use Google; // See: https://github.com/pulkitjalan/google-apiclient
use GoogleMyBusiness;
use Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_GenerateAccountNumberRequest;
use Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_ListAccountAdminsResponse;
use Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_LocalPost;
use Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_LocalPostProduct;
use Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_Location;
use Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_ReportGoogleLocationRequest;
use Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_SearchGoogleLocationsRequest;
use Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_CallToAction;
class Helper
{

public static function GMB(){
        define('CLIENT_SECRET_PATH', storage_path('app/client_secret.json'));
        define('CREDENTIALS_PATH', storage_path('app/authorization_token.json'));
        $credentialsPath = CREDENTIALS_PATH;
        $client = Google::getClient();
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();

            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);
            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {


            $client->refreshToken($client->getRefreshToken());
            $jsontoken = $client->getAccessToken();
            file_put_contents($credentialsPath, json_encode($jsontoken));
        }

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\GoogleMyBusiness($client);
        return($mybusinessService);
}
    public static function GMBServiceToken(){

        $credentialsPath = CREDENTIALS_PATH;
        $client = Google::getClient();
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();

            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);
            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {


            $client->refreshToken($client->getRefreshToken());
            $jsontoken = $client->getAccessToken();
            file_put_contents($credentialsPath, json_encode($jsontoken));
        }

       // $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\GoogleMyBusiness($client);
        return($accessToken);
    }
public static function GMBgetClass($name){


    $credentialsPath = CREDENTIALS_PATH;
    $client = Google::getClient();
    $client->setAuthConfigFile(CLIENT_SECRET_PATH);
    if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
        $accessToken = file_get_contents($credentialsPath);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();

        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->authenticate($authCode);
        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, $accessToken);
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired()) {


        $client->refreshToken($client->getRefreshToken());
        $jsontoken = $client->getAccessToken();
        file_put_contents($credentialsPath, json_encode($jsontoken));
    }
    $var = \Scottybo\LaravelGoogleMyBusiness;
    $mybusinessService = new $var::$name($client);
}
public static function GMBMATCH(){


$mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_VerifyLocationRequest();
return($mybusinessService);
}
public static function  GmbCompleteVerification(){


$mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_CompleteVerificationRequest();
return($mybusinessService);
}
public static function GMBPLACEID(){
  $credentialsPath = CREDENTIALS_PATH;
    $client = Google::getClient();

    $client->setAuthConfigFile(CLIENT_SECRET_PATH);
    if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
        $accessToken = file_get_contents($credentialsPath);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();

        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->authenticate($authCode);
        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, $accessToken);
        printf("Credentials saved to %s\n", $credentialsPath);
    }

    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired()) {


        $client->refreshToken($client->getRefreshToken());
        $jsontoken = $client->getAccessToken();
        file_put_contents($credentialsPath, json_encode($jsontoken));
    }

    $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_AssociateLocationRequest($client);

return($mybusinessService);
}
    public static function GMBPTransfer(){
        $credentialsPath = CREDENTIALS_PATH;
        $client = Google::getClient();

        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();

            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);
            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }

        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {


            $client->refreshToken($client->getRefreshToken());
            $jsontoken = $client->getAccessToken();
            file_put_contents($credentialsPath, json_encode($jsontoken));
        }

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_GenerateAccountNumberRequest($client);

        return($mybusinessService);
    }
    public static function GMBbatchGet($name){

        $credentialsPath = CREDENTIALS_PATH;
        $client = Google::getClient();
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.categories
            $authUrl = $client->createAuthUrl();

            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);
            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {


            $client->refreshToken($client->getRefreshToken());
            $jsontoken = $client->getAccessToken();
            file_put_contents($credentialsPath, json_encode($jsontoken));
        }

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_BatchGetLocationsRequest($client);

         $mybusinessService->setLocationNames($name);


        return ($mybusinessService);
    }
public static function GMBSearch(){

    $credentialsPath = CREDENTIALS_PATH;
    $client = Google::getClient();
    $client->setAuthConfigFile(CLIENT_SECRET_PATH);
    if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
        $accessToken = file_get_contents($credentialsPath);
    } else {
        // Request authorization from the user.categories
        $authUrl = $client->createAuthUrl();

        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->authenticate($authCode);
        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, $accessToken);
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired()) {


        $client->refreshToken($client->getRefreshToken());
        $jsontoken = $client->getAccessToken();
        file_put_contents($credentialsPath, json_encode($jsontoken));
    }

$mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_SearchGoogleLocationsRequest();

return ($mybusinessService);
    }
    public static function Locationreponse($name){

        $credentialsPath = CREDENTIALS_PATH;
        $client = Google::getClient();
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.categories
            $authUrl = $client->createAuthUrl();

            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);
            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {


            $client->refreshToken($client->getRefreshToken());
            $jsontoken = $client->getAccessToken();
            file_put_contents($credentialsPath, json_encode($jsontoken));
        }


        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_SearchGoogleLocationsResponse($client);

        return ($mybusinessService);
    }

    public static function GMBfindMatches($name){

        $credentialsPath = CREDENTIALS_PATH;
        $client = Google::getClient();
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.categories
            $authUrl = $client->createAuthUrl();

            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);
            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {


            $client->refreshToken($client->getRefreshToken());
            $jsontoken = $client->getAccessToken();
            file_put_contents($credentialsPath, json_encode($jsontoken));
        }

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_FindMatchingLocationsRequest($client);

        return ($mybusinessService);
    }
    public static function GMBcreate(){
        $credentialsPath = CREDENTIALS_PATH;
        $client = Google::getClient();

        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        if (file_exists($credentialsPath) && file_get_contents($credentialsPath) != "") {
            $accessToken = file_get_contents($credentialsPath);

        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();

            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);
            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }

        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {


            $client->refreshToken($client->getRefreshToken());
            $jsontoken = $client->getAccessToken();
            file_put_contents($credentialsPath, json_encode($jsontoken));
        }

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_Location($client);

        return($mybusinessService);
    }
    public static function GMBLOCATIONPOST(){
       

        $mybusinessService = new Google_Service_MyBusiness_LocalPost();

        return($mybusinessService);
    }
     public static function AdminAction(){
      

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_Admin();

        return($mybusinessService);
    }
       public static function CallToAction(){
      

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_CallToAction();
             return($mybusinessService);

       }
        public static function EventAction(){
      

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_LocalPostEvent();
             return($mybusinessService);

       }
        public static function TimeIntervalAction(){
      

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_TimeInterval();
             return($mybusinessService);

       }
        public static function TimeOfDayAction(){
      

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_TimeOfDay();
             return($mybusinessService);

       }
       
       public static function DateAction(){
                   $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_Date();
             return($mybusinessService);
           
       }

       public static function OfferAction(){
      

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_LocalPostOffer();
          return($mybusinessService);

       }
     public static function GMBLOCATIdONPOST(){
       

        $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_LocalPost();

        return($mybusinessService);
    }
    public static function GMBPOSTREPORTAction(){
          $mybusinessService = new  \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_ReportLocalPostInsightsRequest();
    
          return($mybusinessService);
    }
      public static function BasicMetricsRequestAction(){
          $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_BasicMetricsRequest();
    
          return($mybusinessService);
    }
        public static function MetricRequestAction(){
          $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_MetricRequest();
    
          return($mybusinessService);
    }
     public static function TimeRangeAction(){
          $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_TimeRange();
    
          return($mybusinessService);
    }
    public static function ReportLocationInsightsRequestAction(){
          $mybusinessService = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_ReportLocationInsightsRequest();
    
          return($mybusinessService);
    }
   
}
