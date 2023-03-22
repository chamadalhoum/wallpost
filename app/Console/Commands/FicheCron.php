<?php

namespace App\Console\Commands;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Etiquetgroupe;
use App\Models\Iconfiche;
use App\Models\Postfiche;
use App\Models\Paramater;
use App\Models\profilincomplete;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helper\Helper;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\FicheuserController;
use Carbon\Carbon;
use App\Models\Categorie;
use App\Models\Fiche;
use App\Models\Fichehour;
use App\Models\Ficheuser;
use App\Models\Metadata;
use App\Models\Morehours;
use App\Models\Pay;
use App\Models\State;
use App\Models\User;
use App\Models\Service;
use App\Models\Accountagence;
use App\Models\Attribute;
use App\Models\Attributeshistorique;
use App\Models\Categorieshistorique;
use App\Models\FicheHourhistorique;
use App\Models\Ficheshistorique;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Servicearea;
use App\Models\Serviceareashistorique;
use App\Models\Serviceshistorique;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use GoogleMyBusinessService;
use Google;
use GooglePlaces;

class FicheCron extends Command
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fiche:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    
    public function handle()
    {
   // ini_set("display_errors",1);
      $user_id=39;
 
      $notification=array();
      $client = Helper::googleClient();

      
 
      
      $serviceLocation = new Google\Service\MyBusinessBusinessInformation($client);
      $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);
      $verificationLocation = new Google\Service\MyBusinessVerifications($client); 

      $list_accounts = $serviceAccount->accounts->listAccounts();
      
        try {

          $read_masknotif="name,languageCode,storeCode,title,phoneNumbers,categories,storefrontAddress,websiteUri,regularHours,specialHours,serviceArea,labels,adWordsLocationExtensions,latlng,openInfo,profile,relationshipData,moreHours,";
 


          $pr =Paramater::where('name','profilincomplet')->first();
              
          
          foreach ($list_accounts->accounts as $keyAccount => $account) {
      
              $accountsList[] = $account;
          }
          

              $read_mask="name,languageCode,storeCode,title,phoneNumbers,categories,storefrontAddress,websiteUri,regularHours,specialHours,serviceArea,labels,adWordsLocationExtensions,latlng,openInfo,metadata,profile,relationshipData,moreHours,serviceItems";
     

                foreach ($accountsList as $keyLocation => $accounts) {
                  
                  $nextPageToken = null;
              do{
                $user_id=39;
                $franchise_id=1;
      



                  $list_locations =($nextPageToken != null) ? $serviceLocation->accounts_locations->listAccountsLocations($accounts->name,
                   ["readMask" => $read_mask,'pageToken'=>$list_locations->nextPageToken,'pageSize'=>100]): $serviceLocation->accounts_locations->listAccountsLocations($accounts->name, array('pageSize' => 100,
                   "readMask" => $read_mask));
                
                  $nextPageToken = $list_locations->nextPageToken ? $list_locations->nextPageToken : null;
                  $housspec= 0;
                  $logo = 0;
                  $url = 0;
                  $tel = 0;
                  $attribute = 0;
                  $service=0;
                  $hours=0;

                  foreach ($list_locations->locations as $list) {
     $tab=$client->getAccessToken();
   
  
        if(!defined('CLIENT_SECRET_PATH')){
          define('CLIENT_SECRET_PATH', storage_path('app/client_secret.json'));
  
      }
          if(!defined('CREDENTIALS_PATH')){
          define('CREDENTIALS_PATH', storage_path('app/authorization_token.json'));
  
      }
      $credentialsPath = CREDENTIALS_PATH;
      $client->refreshToken($client->getRefreshToken());
      $jsontoken = $client->getAccessToken();
      file_put_contents($credentialsPath, json_encode($jsontoken));

                    
                    try{
                    $total = 0;
                    $pr =Paramater::where('name','profilincomplet')->first();
                    $prs= (int)$pr->value;
                    $prc=0;
               
                  
               
                  try{
                    $diffesk = $serviceLocation->locations->getGoogleUpdated($list['name'], ["readMask" => $read_masknotif]);
                    $tab=array();
                
                       $fiche=Fiche::where('name', $accounts->name.'/'.$list['name'])->first();
                       $dataprofil['notification']=false;
                       if (isset($diffesk['diffMask'])) {
     
                       $notification=explode(',',$diffesk['diffMask']);
     
                       }
                      }catch (\Google_Service_Exception $ex) {
  
                        print_r($ex->getMessage());
                        continue;
                        
                        
                      
                      }
                   
                 
                    if(in_array('locationName',$notification)){
                    if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){ 
                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='locationName';
                    $datafiche['newobject']=$list['title'];
                    $datafiche['oldobject']=$fiche->locationName;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                          }
                    }
                  //////// Verifications ///////////////
                  $data=array();
                  $dataattr=array();
                  $data['franchises_id'] = $franchise_id;
     
                  try{
                    $verifications = $verificationLocation->locations_verifications->listLocationsVerifications($list['name']);
                    if(isset( $verifications['verifications'])){
                      if(isset($verifications['verifications'][0]['method']) )
                      { 
                          $data['methodverif'] = $verifications['verifications'][0]['method'];
                      }
                      if(isset($verifications['verifications'][0]['state'])){
                        $data['state'] = $verifications['verifications'][0]['state'];
                        if($verifications['verifications'][0]['state']==='COMPLETED'){
                        
                          $dataprofil['states']=true;
                        }else{
                          $dataprofil['states']=false;
                        }
                        if($verifications['verifications'][0]['state']=="PENDING"){
                          $datstate['hasPendingVerification']=true;
                        }
                    
                    if(isset($verifications['verifications'][0]['createTime'])){
                      $data['closedatestrCode'] = Carbon::parse($verifications['verifications'][0]['createTime'])->translatedFormat('Y-m-d');
                     }
                     }
                    }

                  }catch (\Google_Service_Exception $ex) {
                    
                    print_r($ex->getMessage());
                    continue;
                       
                   
                  }
                  
                 
                
       
                  /////// end Verifications ///////////
                  //// storeCode /////
                  if (isset($list["storeCode"])) {
                  $data['storeCode'] = $list["storeCode"];
                  $dataprofil['storeCode']=true;

                  if(in_array('storeCode',$notification)){
                    if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='storeCode';
                    $datafiche['newobject']=$list['storeCode'];
                    $datafiche['oldobject']=$fiche->storeCode;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                    }
                  }
                
              }else{
                $prs--;
               $dataprofil['storeCode']=false;
              }
                  ///// profile /////
               //   if (array_key_exists('profile', $list)) {
                if(isset($list['profile'])){
                      $data['description'] = $list['profile']["description"];
                     
                      $dataprofil['description']=true;
                 
                      if(in_array("profile.description",$notification)){
                        if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                        $datafiche["state"] = "Inactif";
                        $datafiche['diffMask']='profile.description';
                        $datafiche['newobject']=$list['profile']["description"];
                        $datafiche['oldobject']=$fiche->description;
                        $datafiche['fiche_id']=$fiche->id;
                        Notification::updateOrCreate($datafiche);
                        }
                      }
                  } else{
                    $prs--;
                    $dataprofil['description']=false;
                  }
                  ////// labels /////////
                 // if (array_key_exists('labels', $list)) {
                  if(isset($list['labels'])){
                    $dataprofil['labels']=true;
                    if($list['labels']){
                        $data['labels'] ='["'. collect($list['labels'])->implode('","').'"]';
                        if(in_array('labels',$notification)){
                          if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                          $datafiche["state"] = "Inactif";
                          $datafiche['diffMask']='labels';
                          $datafiche['newobject']='["'. collect($list['labels'])->implode('","').'"]';
                          $datafiche['oldobject']=$fiche->labels;
                          $datafiche['fiche_id']=$fiche->id;
                          Notification::updateOrCreate($datafiche);
                          }
                        }
                      }
                  }  else{
                    $prs--;
                    $dataprofil['labels']=false;
                  }
                  $day = null;
                  //////// openInfo ///////
                 // if (array_key_exists('openInfo', $list)) {
                  if(isset($list['openInfo'])){
                      $data['OpenInfo_status'] = $list['openInfo']['status'];
                      $data['OpenInfo_canreopen'] = $list['openInfo']['canReopen'];
                      if(in_array('openInfo.status',$notification)){
                        if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                        $datafiche["state"] = "Inactif";
                        $datafiche['diffMask']='openInfo.status';
                        $datafiche['newobject']=$list['openInfo']['status'];
                        $datafiche['oldobject']=$fiche->OpenInfo_status;
                        $datafiche['fiche_id']=$fiche->id;
                        Notification::updateOrCreate($datafiche);
                        }
                      } 
                    }
                       ////// openingDate  /////////
                       if(isset($list['openInfo'])){
                        if(isset($list['openInfo']['openingDate']['day'])){
                    //  if (array_key_exists('openingDate', $list['openInfo'])) {
                        //  if (array_key_exists('day', $list['openInfo']['openingDate'])) {
                              $day =  $list['openInfo']['openingDate']["day"];
                          }
                          if($day){
                              $dt='-'.$day;
                          }else{
                              $dt=$day;
                          }
                          if(isset($list['openInfo']['openingDate']["year"])&& isset($list['openInfo']['openingDate']["month"])){
                            $date = $list['openInfo']['openingDate']["year"] . '-' . $list['openInfo']['openingDate']["month"] . $dt;
                        
                            $data['OpenInfo_opening_date'] =  Carbon::parse($date)->translatedFormat('Y-m-d');
                            if(in_array('openInfo.openingDate',$notification)){
                              if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                              $datafiche["state"] = "Inactif";
                              $datafiche['diffMask']='openInfo.openingDate';
                              $datafiche['newobject']=Carbon::parse($date)->translatedFormat('Y-m-d');
                              $datafiche['oldobject']=$fiche->OpenInfo_opening_date;
                              $datafiche['fiche_id']=$fiche->id;
                              Notification::updateOrCreate($datafiche);
                              }
                            }
                          }
                          
                       
                      }
                      /////// start  phoneNumbers ///
                      /////// primaryPhone  //////
                      if(isset($list['phoneNumbers']['primaryPhone'])){
                        $dataprofil['primaryPhone']=true;
                  //    if (array_key_exists('primaryPhone', $list['phoneNumbers'])) {
                        $data['primaryPhone'] = $list['phoneNumbers']['primaryPhone'];
                        if(in_array('phoneNumbers.primaryPhone',$notification)){
                          if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                            $datafiche["state"] = "Inactif";
                          $datafiche['diffMask']='primaryPhone';
                          $datafiche['newobject']=$list['phoneNumbers']['primaryPhone'];
                          $datafiche['oldobject']=$fiche->primaryPhone;
                          $datafiche['fiche_id']=$fiche->id;
                          Notification::updateOrCreate($datafiche);
                          }
                         
                        }
                    }else{
                      $prs--;
                      $dataprofil['primaryPhone']=false;
                    }
                    if(isset($list['phoneNumbers']['additionalPhones'])){
                 //   if (array_key_exists('additionalPhones', $list['phoneNumbers'])) {
                          $data['additionalPhones'] = json_encode($list['phoneNumbers']['additionalPhones']);
                          if(in_array('phoneNumbers.additionalPhones',$notification)){
                            if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                            $datafiche["state"] = "Inactif";
                            $datafiche['diffMask']='additionalPhones';
                            $datafiche['newobject']=collect($list['phoneNumbers']['additionalPhones'])->toJson(JSON_UNESCAPED_UNICODE);
                            $datafiche['oldobject']=$fiche->additionalPhones;
                            $datafiche['fiche_id']=$fiche->id;
                            Notification::updateOrCreate($datafiche);
                            }
                          }  
                                    }
                                    ////////end phoneNumbers //////// 

                                    /////// websiteUri /////
                                    if(isset($list['websiteUri'])){
                                  //  if (array_key_exists("websiteUri", $list)) {
                                      $data['websiteUrl'] = $list["websiteUri"];
                                      if(in_array('websiteUrl',$notification)){
                                        if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                                        $datafiche["state"] = "Inactif";
                                        $datafiche['diffMask']='websiteUrl';
                                      $datafiche['newobject']=$list["websiteUri"];
                                        $datafiche['oldobject']=$fiche->websiteUrl;
                                        $datafiche['fiche_id']=$fiche->id;
                                        Notification::updateOrCreate($datafiche);
                                        }
                                      }
                                      $dataprofil['websiteUrl']=true;
                                  }else{
                                    $prs--;
                                    $dataprofil['websiteUrl']=false;
                                  }
                                   ////// adWordsLocationExtensions  ///////
                                   if(isset($list['adWordsLocationExtensions'])){
                                    $dataprofil['adwPhone']=true;
                              //    if (array_key_exists('adWordsLocationExtensions', $list)) {
                                        $data['adwPhone'] = $list['adWordsLocationExtensions']["adPhone"];
                                    if(in_array('adWordsLocationExtensions.adPhone',$notification)){
                                      if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                                      $datafiche["state"] = "Inactif";
                                      $datafiche['diffMask']='adWordsLocationExtensions.adPhone';
                                      $datafiche['newobject']=$list['adWordsLocationExtensions']["adPhone"];
                                      $datafiche['oldobject']=$fiche->adwPhone;
                                      $datafiche['fiche_id']=$fiche->id;
                                      Notification::updateOrCreate($datafiche);
                                      }
                                    }
                                } else{
                                  $prs--;
                                  $dataprofil['adwPhone']=false;
                                }
                     
                                $adresses='';
                              
                                ////// storefrontAddress /////
                              if(isset($list['storefrontAddress'])){
          //  if (array_key_exists('storefrontAddress', $list)) {
           
                $data['city'] = $list['storefrontAddress']['locality'];
                $pays = Pay::where('alpha2',$list['storefrontAddress']['regionCode'])->select('pays')->first();
                $data['country'] = $pays->pays;
             
                $data['postalCode'] = $list['storefrontAddress']['postalCode'];
              
                if(isset($list['storefrontAddress']['addressLines'])){
                 
                  $data['address'] = $list['storefrontAddress']['addressLines'][0];
                  $response = \GoogleMaps::load('geocoding')
            ->setParam(['address' => $list['storefrontAddress']['addressLines'][0]])
            ->get('results.geometry.location');
          

    
          //  if (array_key_exists('geometry', $response)) {
       
              if(count($response['results'])>0){
                foreach ($response as $res) {
                     $data['latitude'] =  $res[0]['geometry']['location']['lat'];
                     $data['longitude'] =  $res[0]['geometry']['location']['lng'];
                 }
              }
   
 // }
                $adresses.=','.$list['storefrontAddress']['addressLines'][0].', '.$list['storefrontAddress']['postalCode'].' '.$list['storefrontAddress']['locality'].','. $pays->pays;
               $dataprofil['address']=true;
          }
                  if(in_array('storefrontAddress',$notification)){
                 
                  if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                    if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='address.addressLines';
                    $datafiche['newobject']=$list['storefrontAddress']['addressLines'][0];
                    $datafiche['oldobject']=$fiche->address;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                    }
                  }
                 
                }
              }else{
                $prs--;
                $dataprofil['address']=false;
               }
          
              ////// "latlng ///////
              if(isset($list['latlng'])){
            //if (array_key_exists('latlng', $list)) {
             $data['latitude'] = $list['latlng']['latitude'];
             $data['longitude'] = $list['latlng']['longitude'];
            } 
   
            //// Place ID ////
            if(isset($list['metadata'])){
            //if (array_key_exists('placeId', $list["metadata"])){
  
              $data['placeId'] = $list["metadata"]['placeId'];
              $metadata['placeId'] = $list["metadata"]['placeId'];
                  }
            /////// start list admins ///////

            $data['locationName'] = $list['title'];
          
            $dataprofil['locationName']=true;
            $createfiche = Fiche::updateOrCreate(['name' => $accounts->name.'/'.$list['name']], $data);
            $fiches = $createfiche->id;
           // $listadmin=  FicheuserController::getUser($accounts->name.'/'.$list['name']);
            $tabfichee[]=$fiches;
            $userst=User::where('franchises_id',$franchise_id)->whereIN('role_id',[1])->get();
foreach($userst as $us){
$datafichesuser['user_id']=$us->id;
$datafichesuser['fiche_id']=$fiches;
$datafichesuser['franchise_id']=$franchise_id;
$datafichesuser['role_id']=$us->role_id;
$datafichesuser['pendingInvitation']=0;
Ficheuser::updateOrCreate($datafichesuser);
}

$listadmin=array();
/*  $accessToken = Helper::GMBServiceToken();
  $token_acces = json_decode($accessToken, true);*/

    try {
   
      $tab = $serviceAccount->accounts_admins->listAccountsAdmins($list['name'],array());
        
      foreach($tab["admins"] as $lists){
          $listadmin[]=$lists;
     }
      
 
         foreach($listadmin as $admin){
         $datauser=array();
         $verifuser=User::join('ficheusers','users.id','=','ficheusers.user_id')
         ->where('users.username',$admin['admin'])->exists();
         $datauser['password'] = bcrypt('123456789');
         $role= Role::where('nameenglais',$admin['role'])->first();
         $datauser['role_id'] = $role->id;
         $datauser['state']=1;
         $datauser['franchises_id']=$franchise_id;
         $datauser['lastname']=$admin['admin'];
        
         
if (filter_var($admin['admin'], FILTER_VALIDATE_EMAIL)) {
$datauser['email']=$admin['admin'];
} 
$users = User::updateOrCreate(['username'=>$admin['admin']],$datauser);
$pendingInvitation=0;

if (array_key_exists('pendingInvitation', $admin)) {
  $admin['pendingInvitation']==true?$pendingInvitation=1:$pendingInvitation=0;
}
              $datafiches['user_id']=$users->id;
              $datafiches['fiche_id']=$fiches;
              $datafiches['franchise_id']=$franchise_id;
              $datafiches['role_id']=$role->id;
              $datafiches['namefiche']=$admin['name'];
              $datafiches['pendingInvitation']=$pendingInvitation;
              Ficheuser::updateOrCreate($datafiches);
             }
          
          } catch (\Google_Service_Exception $ex) {

            print_r($ex->getMessage());
            continue;
                 

           
        }
              /////// end list admins ///////

              /////// metadata ///
              if(isset($list['metadata'])){
  
             // if (array_key_exists('metadata', $list)){
          //  if (array_key_exists('mapsUri', $list["metadata"]) && array_key_exists('newReviewUri', $list["metadata"])) {
              $metadata['locationName'] = $list['title'];
              $metadata['mapsUrl'] = $list['metadata']["mapsUri"];
              $metadata['newReviewUrl']='https://www.google.com/search?hl=fr-TN&gl=tn&q='.$list['title'].$adresses;
         //   }  
           
              $metadata['fiche_id'] = $fiches;
               $meta=  Metadata::updateOrCreate($metadata);
            }
            
            
            
            /// regularHours ////
            if(isset($list['regularHours'])){
             
                $dataprofil['regularHours']=true;
              
         //   if (array_key_exists('regularHours', $list)) {
            
              $i = 0;
                foreach ($list['regularHours']['periods'] as $hour) {
                  $minutes=null;
                  $minutesclose=null;
                  $hours=null;
                  $hoursclose=null;
                  $datahour['type'] = true;
                  $datahour['open_date'] = $this->dateToFrench($hour['openDay']);
                  $datahour['close_date'] = $this->dateToFrench($hour['closeDay']);
                  if(isset($hour['openTime']["hours"])){
                    // if (array_key_exists('hours', $hour['openTime'])) {
                       $hours= $hour['openTime']["hours"];
                     }
                     if(isset($hour['closeTime']["hours"])){
                   //  if (array_key_exists('hours', $hour['closeTime'])) {
                       $hoursclose=$hour['closeTime']["hours"];
                     }
                  if(isset($hour['openTime']['minutes'])){
                  //if (array_key_exists('minutes', $hour['openTime'])) {
                    $minutes= ':'.$hour['openTime']["minutes"];
                  }
                  if(isset($hour['closeTime']['minutes'])){
                 // if (array_key_exists('minutes', $hour['closeTime'])) {
                    $minutesclose= ':'.$hour['closeTime']["minutes"];
                  }
                  
                  $datahour['open_time'] =$hours.$minutes;
                  $datahour['close_time'] = $hoursclose.$minutesclose;
                  $datahour["fiche_id"] = $fiches;
                  $testhours=Fichehour::where('open_date',$this->dateToFrench($hour['openDay']))
                 ->where('open_time',$hours.$minutes)
                 ->where('close_time',$hoursclose.$minutesclose)
                 ->where('fiche_id',$fiches);
                 if($testhours->doesntExist() && in_array('regularHours',$notification)){
                  $statehour = "Inactif";
                 }else{
                  $statehour = "Actif";
                 }
                 if($testhours->doesntExist() && in_array('regularHours',$notification)){
                  if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                  $datafiche["state"] = "Inactif";
                  $datafiche['diffMask']='regularHours';
                  $datafiche['newobject']=collect($datahour)->toJson(JSON_UNESCAPED_UNICODE);
                  $datafiche['oldobject']=collect($testhours->get())->toJson(JSON_UNESCAPED_UNICODE);
                  $datafiche['fiche_id']=$fiches;
                  Notification::updateOrCreate($datafiche,['state'=>$statehour]);
                  }
                }
                  $histhours= Fichehour::updateOrCreate($datahour,["user_id", $user_id,'state'=>$statehour]);
                  $tabhours[]=$histhours->id;
                  $i++;
              }
              if(isset($histhours)){
                Fichehour::whereNotIn('id',$tabhours)->whereNULL('specialhours_start_date')->where('fiche_id',$fiches)->delete();
              }

          } else{
            $prs--;
            $dataprofil['regularHours']=false;
          }

          ///// Servicearea ////////////
          if(isset($list['serviceArea'])){

      $dataprofil['serviceArea']=true;
         // if (array_key_exists('serviceArea', $list)) {
            $i = 0;
            if (isset($list['serviceArea']["places"])) {
         //   if (array_key_exists('places', $list['serviceArea'])) {

foreach ($list['serviceArea']["places"]["placeInfos"]as $zon) {

$serviceArea['placeId']=$zon['placeId'];
$serviceArea['name']=$zon['placeName'];
$serviceArea['zone']=$zon['placeName'];
$serviceArea['fiche_id']=$fiches;
$serviceArea['businessType']=$list['serviceArea']["businessType"];
$testserviceAreas=Servicearea::where('placeId',$zon['placeId'])
               ->where('name',$zon['placeName'])
               ->where('zone',$zon['placeName'])
               ->where('businessType',$list['serviceArea']["businessType"])
               ->where('fiche_id',$fiches);
               if($testserviceAreas->doesntExist() && in_array('serviceArea',$notification)){
                $stateAreas = "Inactif";
               }else{
                $stateAreas = "Actif";
               }
               if(in_array('serviceArea',$notification)){
                if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                $dataserviceAreas["state"] = "Inactif";
                $dataserviceAreas['diffMask']='serviceArea';
                $dataserviceAreas['newobject']=collect($serviceArea)->toJson(JSON_UNESCAPED_UNICODE);
                $dataserviceAreas['oldobject']=collect($testserviceAreas->get())->toJson(JSON_UNESCAPED_UNICODE);
                $dataserviceAreas['fiche_id']=$fiches;
                Notification::updateOrCreate($dataserviceAreas);
                }
              }
             
$serid=Servicearea::updateOrCreate($serviceArea,["state"=>$stateAreas]);  
$tabserid[]=$serid->id;
                $i++;
            }
            if(isset($tabserid)){
              Servicearea::whereNotIn('id',$tabserid)->where('fiche_id',$fiches)->delete();
            }
          }
      
    }
    else{
      $prs--;
      $dataprofil['serviceArea']=false;
    }
    ////// specialHours ////
    if(isset($list['specialHours'])){
      $dataprofil['specialHours']=true;
 //   if (array_key_exists('specialHours', $list)) {
      $i = 0;
     
      foreach ($list['specialHours']['specialHourPeriods'] as $hourspe) {
        $specialhours=null;
        $specialhoursclose=null;
        $minutesopen=null;
        $minutesclose=null;
        $startDatespecial=null;
        $endDatespecial=null;
          $startDatespecial = strftime("%F", strtotime($hourspe['startDate']['year'] . "-" . $hourspe['startDate']['month'] . "-" . $hourspe['startDate']['day']));
          $endDatespecial = strftime("%F", strtotime($hourspe['endDate']['year'] . "-" . $hourspe['endDate']['month'] . "-" . $hourspe['endDate']['day']));
          if (isset($hourspe["closed"])) {
       //   if (array_key_exists('closed', $hourspe)) {
              $datahourspe['type'] = $hourspe["closed"];
          }
          $datahourspe['specialhours_start_date'] = $startDatespecial;
          $datahourspe['specialhours_end_date'] = $endDatespecial;
          if (isset($hourspe["openTime"])) {
      
          if (isset($hourspe['openTime']["minutes"])) {
         
         
              $minutesopen= ':'.$hourspe['openTime']["minutes"] ;
            }
         
            if (isset($hourspe['openTime']["hours"])) {
          
              $specialhours=$hourspe['openTime']["hours"];
            }
           
              
          }
          if (isset($hourspe['closeTime'])) {
       
            if (isset($hourspe['closeTime']["minutes"])) {
             
              $minutesclose= ':'.$hourspe['closeTime']["minutes"] ;
            }
            if (isset($hourspe['closeTime']["hours"])) {
             
                  $specialhoursclose=$hourspe['closeTime']["hours"] ;
                }
              
          }
          if($minutesopen && $specialhours==null)
          {
            $specialhours='00';
          } 
          if($specialhours && $minutesopen==null)
{
  $minutesopen=':00';
} 
          if($minutesclose && $specialhoursclose==null)
          {
            $specialhoursclose='00';
          } 
          if($specialhoursclose && $minutesclose==null)
{
  $minutesclose=':00';
}          $datahourspe['specialhours_open_time'] = $specialhours.$minutesopen;
          $datahourspe['specialhours_close_time'] =$specialhoursclose.$minutesclose;
          $datahourspe["fiche_id"] = $fiches;
          $testdatahourspe=Fichehour::Where('specialhours_close_time',$specialhoursclose.$minutesclose)
          ->Where('specialhours_open_time',$specialhours.$minutesopen)
          ->where('specialhours_start_date',$startDatespecial)
          ->Where('fiche_id',$fiches);
          if($testdatahourspe->doesntExist() && in_array('specialHours',$notification)){
            $statespecialHours = "Inactif";
          }else{
            $statespecialHours = "Actif";
          }
          if(in_array('specialHours',$notification)){
            if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
            $datafiches["state"] = "Inactif";
            $datafiches['diffMask']='specialHours';
            $datafiches['newobject']=collect($datahourspe)->toJson(JSON_UNESCAPED_UNICODE);
            $datafiches['oldobject']=collect($testdatahourspe->get())->toJson(JSON_UNESCAPED_UNICODE);
            $datafiches['fiche_id']=$fiches;
            Notification::updateOrCreate($datafiches);
            }
          }
          $spehisthours= Fichehour::updateOrCreate($datahourspe,["user_id"=> $user_id,"state"=>$statespecialHours]);
     $tabelspecial[]=$spehisthours->id;
          $i++;
      }
      if(isset($tabelspecial)){
        Fichehour::whereNotIn('id',$tabelspecial)->whereNull('open_time')->where('fiche_id',$fiches)->delete();
      }
    
  }
  else{
    $prs--;
    $dataprofil['specialHours']=false;
  }
  ////// categories ///// 
 
  if ($list["categories"]) {
    if (isset($list["categories"]["primaryCategory"])) {
   // if (array_key_exists('primaryCategory',$list["categories"])) {
    $categorie["type"] = "primaryCategory";
    $categorie["displayName"] = $list["categories"]["primaryCategory"]["displayName"];
    $categorie["categorieId"] = $list["categories"]["primaryCategory"]["name"];
    $opt = array(
      "languageCode" => "fr",
      'categoryId' => $list["categories"]["primaryCategory"]["name"],
       'pageSize' => 100,
  );


$datcat=array();
            try {
               // $client = Helper::googleClient();
          
                $params=["regionCode"=>"FR","languageCode"=>"fr","showAll"=>false,'categoryName'=>$list["categories"]["primaryCategory"]["name"]];
                $attributeCat= $serviceLocation->attributes->listAttributes($params);
              
            
                 // return $list;
               
            } catch (\Google_Service_Exception $e) {
             
              print_r($e->getMessage());
              continue;
                  $client = Helper::googleClient();
                        $serviceLocation = new Google\Service\MyBusinessBusinessInformation($client);
                        $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);
                        $verificationLocation = new Google\Service\MyBusinessVerifications($client);
            
            }



   // $attributeCat = AttributeController::getAttribute($list["categories"]["primaryCategory"]["name"]);

    $categorie["fiche_id"] = $fiches;
    $testCategorie=Categorie::where('displayName',$list["categories"]["primaryCategory"]["displayName"])
             ->where('categorieId',$list["categories"]["primaryCategory"]["name"])
             ->where('type',"primaryCategory")
             ->where('fiche_id',$fiches);
             if($testCategorie->doesntExist() && in_array('categories',$notification)){
              $statecat = "Inactif";
             }else{
              $statecat = "Actif";
             }
             if(in_array('categories',$notification)){
              if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
              $datacategorie["state"] = "Inactif";
              $datacategorie['diffMask']='primaryCategory';
              $datacategorie['newobject']=collect($categorie)->toJson(JSON_UNESCAPED_UNICODE);
              $datacategorie['oldobject']=collect($testCategorie->get())->toJson(JSON_UNESCAPED_UNICODE);
              $datacategorie['fiche_id']=$fiches;
              Notification::updateOrCreate($datacategorie);
              }
            }
            $categorie["user_id"] =$user_id;
           $cats =  Categorie::updateOrCreate($categorie,['user_id'=>$user_id,"state"=>$statecat]);
                        $catp = $cats->id;
                        if(isset($tabcat)){
                          Categorie::where('id','!=',$catp)->where('fiche_id',$fiches)->where('type',"primaryCategory")->delete();
                        }
                          
                        if (isset($list["categories"]["primaryCategory"]["serviceTypes"])) {
                       // if (array_key_exists('serviceTypes',$list["categories"]["primaryCategory"])) {
                          $listservice=$list["categories"]["primaryCategory"]["serviceTypes"];
                        }
                    }
                    
                      if (isset($list["categories"]["additionalCategories"])) {
                    //if (array_key_exists('additionalCategories', $list["categories"])) {
                      foreach ($list["categories"]["additionalCategories"] as $val){
                    
                          $categorie["type"] = "additionalCategories";
                      $categorie["displayName"] = $val["displayName"];
                      $categorie["categorieId"] = $val["name"];
                     
                      $categorie["fiche_id"] = $fiches;
                      $testCategories=Categorie::where('displayName',$val["displayName"])
                      ->where('categorieId','=',$val["name"])
                      ->where('type',"additionalCategories")
                      ->where('fiche_id',$fiches);
                      if($testCategories->doesntExist() && in_array('additionalCategories',$notification) ){
                        $statecat = "Inactif";
                      }else{
                        $statecat = "Actif";
                      }
                      if($testCategories->doesntExist() && in_array('additionalCategories',$notification)){
                        if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                        $datadditionalCategories["state"] = "Inactif";
                        $datadditionalCategories['diffMask']='additionalCategories';
                        $datadditionalCategories['newobject']=collect($categorie)->toJson(JSON_UNESCAPED_UNICODE);
                        $datadditionalCategories['oldobject']=collect($testCategorie->get())->toJson(JSON_UNESCAPED_UNICODE);
                        $datadditionalCategories['fiche_id']=$fiches;
                        Notification::updateOrCreate($datadditionalCategories);
                        }
                      }
                      $cats =  Categorie::updateOrCreate($categorie,["user_id"=>$user_id,"state"=>$statecat]);
                     $cat = $cats->id;
                     $tabcat[]=$cat;
                // if (array_key_exists('serviceTypes', $locationsListUpdate["additionalCategories"])) {
                   /*if( $val["metadata"]){
               
                         // foreach ($locationsListUpdate["additionalCategories"]["serviceTypes"] as $servicetype) {
                           foreach ($val["metadata"]["serviceTypes"] as $servicetype) {
                           
                              $serviceadditional['categorie_id'] = $cat;
                            
                              $serviceadditional['serviceId'] = $servicetype["serviceTypeId"];
                              $serviceadditional['displayName'] = $servicetype["displayName"];
                              $teststypes=Service::where('serviceId',$servicetype["serviceTypeId"])
                              ->where('displayName',$servicetype["displayName"])
                              ->where('categorie_id',$cat);
                              if($teststypes->doesntExist() &&  in_array('additionalCategories',$notification)){
                                $state = "Inactif";
                              }else{
                                $state= "Actif";
                              }
                              if($teststypes->doesntExist() && in_array('additionalCategories',$notification)){
                                $datservice["state"] = "Inactif";
                                $datservice['diffMask']='service';
                                $datservice['newobject']=collect($serviceadditional)->toJson(JSON_UNESCAPED_UNICODE);
                                $datservice['oldobject']='';
                                $datservice['fiche_id']=$fiches;
                                Notification::updateOrCreate($datservice);
                              }
                               $servicecat= Service::updateOrCreate($serviceadditional,["user_id"=>$user_id,'state'=>$state]);
                            
                          }
                      }*/
                    }
               
        if(isset($tabcat)){
          Categorie::whereNotIn('id',$tabcat)->where('fiche_id',$fiches)->where('type',"additionalCategories")->delete();
        }
                  }}
              ////// Attribute /////

  try{
   $name= $list['name'];
   $tab=$client->getAccessToken();
  //  $listattribute = AttributeController::getAttributesfiche($list['name']);
    $url = "https://mybusinessbusinessinformation.googleapis.com/v1/$name/attributes";
    $headers = array("Accept: application/json","Authorization: Bearer ".$tab['access_token']);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
    }
    curl_close($ch);
    $listattribute=json_decode($response,1);
    if (isset($listattribute["attributes"])) {
      $dataprofil['attributes']=false;
        foreach( $listattribute["attributes"] as $attribtus){
          $search=$attribtus['name'];
         
          $itemCollection = collect($attributeCat); 
          $filtered = $itemCollection->filter(function ($item) use ($search) {
        if(isset($item['name'])){
          return stripos($item['name'], $search) !== false;
        }
             // return stripos($item['name'], $search) !== false;
          });
     if($attribtus['name']=="attributes/url_appointment"){
              $dataattr['displayName'] = "Liens pour prendre rendez-vous";
              $dataattr['groupDisplayName']="URL des pages Google Adresses";
              $dataprofil['attributesUrl']=true;
     }
          foreach($filtered->all() as $fil){
              $dataattr['displayName'] = $fil->displayName;
              $dataattr['groupDisplayName']=$fil->groupDisplayName;
          }
            $dataattr['attributeId'] = $attribtus['name'];
            
            $dataattr['valueType'] = $attribtus['valueType'];
            if (isset($attribtus["values"])) {
        //    if (array_key_exists('values', $attribtus)) {
          if(is_array($attribtus["values"])){
            
              if($attribtus["values"][0]===1 || $attribtus["values"][0]===true){
                $dataattr['values'] ='true';
              }
              elseif($attribtus["values"][0]===0 || $attribtus["values"][0]==='Closed'){
                $dataattr['values'] ='Closed';
              }
          }
        }
        if (isset($attribtus["repeatedEnumValue"])) {
     
       // if (array_key_exists('repeatedEnumValue', $attribtus)) {
          if(is_array($attribtus["repeatedEnumValue"])){
          if(isset($attribtus["repeatedEnumValue"]['setValues'])){
   $dataattr['repeatedEnumValue'] = $attribtus["repeatedEnumValue"]['setValues'][0];
          }
       
          }
        }
        if (isset($attribtus["uriValues"])) {
          //  if(array_key_exists('uriValues',$attribtus)){
                 $dataattr['valueType'] = $attribtus["uriValues"][0]['uri'];
                }
            $dataattr["fiche_id"] = $fiches;
            if(isset($attribtus['attributeId'])){
              $testAttributes=Attribute::Where('attributeId',$attribtus['attributeId'])
              ->Where('fiche_id',$fiches)
              ->Where('valueType',$attribtus['valueType']);
              if($testAttributes->doesntExist() && in_array('attributes',$notification)){
                $dataattr["state"] = "Inactif";
              }else{
                $dataattr["state"] = "Actif";
              }
            }
           
            if(in_array('attributes',$notification)){
              if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
              $dataserviceAreas["state"] = "Inactif";
              $dataserviceAreas['diffMask']='attributes';
              $dataserviceAreas['newobject']=collect($dataattr)->toJson(JSON_UNESCAPED_UNICODE);
              $dataserviceAreas['oldobject']=collect($testAttributes->get())->toJson(JSON_UNESCAPED_UNICODE);
              $dataserviceAreas['fiche_id']=$fiches;
              Notification::updateOrCreate($dataserviceAreas);
              }
            }
            $dataattr["user_id"] = $user_id;
            $atts = Attribute::updateOrCreate(["attributeId"=> $attribtus['name']],$dataattr);
         
            $tabatt[]=$atts->id;
        }
    
    if(isset($tabatt)){
      Attribute::whereNotIn('id',$tabatt)->where('fiche_id',$fiches)->delete();
    }
        
   
}
else{
$prs--;
$dataprofil['attributes']=false;
$dataprofil['attributesUrl']=false;
}
  } catch (\Google_Service_Exception $ex) {
   
    print_r($ex->getMessage());
    continue;
 
   
  }
          ///// Service /////
          if(isset($list['serviceItems'])){
            $dataprofil['Service']=true;
                //  if (array_key_exists('serviceItems',$list)) {
                    $testservicetypes=array();
                    foreach ($list["serviceItems"] as $servicetype) {
                      if (isset($servicetype['structuredServiceItem'])) {
                 //   if (array_key_exists('structuredServiceItem',$servicetype)) {
                      $search=$servicetype['structuredServiceItem']['serviceTypeId'];
                      $itemCollection = collect($listservice);
                   
                      $filtereService = $itemCollection->filter(function ($item) use ($search) {
                          return stripos($item['displayName'], $search) !== false;
                      });
                       
                        $servicetypes['serviceId'] = $servicetype['structuredServiceItem']['serviceTypeId'];
                        foreach($filtereService->all() as $fil){
                          $servicetypes['categorie_id'] =$catp;
                          $servicetypes['displayName'] = $fil["displayName"];
                          $testservicetypes=Service::where('serviceId',$servicetype['structuredServiceItem']['serviceTypeId'])
                          ->where('categorie_id',$catp);
                      }
                    }
                    if (isset($servicetype['freeFormServiceItem'])) {
                 //   if (array_key_exists('freeFormServiceItem',$servicetype)) {
                      $servicetypes['categorie_id'] = $catp;
                      $servicetypes['displayName'] = $servicetype['freeFormServiceItem']['label']['displayName'];
                      $testservicetypes=Service::where('displayName',$servicetype['freeFormServiceItem']['label']['displayName'])
                      ->where('categorie_id',$catp);
                      if($testservicetypes->doesntExist() && in_array('serviceItems',$notification)){
                        $stateservicetypes = "Inactif";
                      }else{
                        $stateservicetypes = "Actif";
                      }
                      if($testservicetypes->doesntExist() && in_array('primaryCategory',$notification)){
                        if(Fiche::where('name', $accounts->name.'/'.$list['name'])->exists()){
                        $dataprimaryCategory["state"] = "Inactif";
                        $dataprimaryCategory['diffMask']='service';
                        $dataprimaryCategory['newobject']=collect($servicetypes)->toJson(JSON_UNESCAPED_UNICODE);
                        $dataprimaryCategory['oldobject']=collect($testservicetypes)->toJson(JSON_UNESCAPED_UNICODE);
                        $dataprimaryCategory['fiche_ids']=$fiches;
                        Notification::updateOrCreate($dataprimaryCategory);
                        }
                      }
                      $IDSERVE=Service::updateOrCreate($servicetypes,["user_id" => $user_id,"state"=>$stateservicetypes]);
                      $tabserv[]=$IDSERVE->id;
                  }
              
              
                        
                    }
                    if(isset($tabserv)){
                      Service::whereNotIn('id',$tabserv)->where('categorie_id',$catp)->delete();
                    }
                }else{
                 
                    $prs--;
                    $dataprofil['Service']=false;
            
                }
            
  
///////  moreHours ////serviceItems
if(isset($list['moreHours'])){
  $dataprofil['moreHours']=true;
     // if (array_key_exists('moreHours', $list)) {
        foreach ($list['moreHours'] as $key=>$value) {
            $i = 0;
            $minutes=null;

            foreach ($value['periods'] as $hour) {
                $moreHours['morehoursId'] = $value["hoursTypeId"];
                $moreHours['displayName'] = $value["hoursTypeId"];
                $moreHours['type'] = true;
                $moreHours['categorie_id'] =$catp;
                $moreHours['openDay'] = $this->dateToFrench($hour['openDay']);
                $moreHours['closeDay'] = $this->dateToFrench($hour['closeDay']);
                if (isset($hour['openTime']["minutes"])) {
               // if (array_key_exists('minutes', $hour['openTime'])) {
                  $minutes= ':'.$hour['openTime']["minutes"];
                }

                if (isset($hour['closeTime']["minutes"])) {
               // if (array_key_exists('minutes', $hour['closeTime'])) {
                  $minutesclose= ':'.$hour['closeTime']["minutes"];
                }
                $moreHours['openTime'] = $hour['openTime']['hours'].$minutes;
                $moreHours['closeTime'] = $hour['closeTime']['hours'].$minutesclose;
                $moreHours["fiche_id"] = $fiches;
               $IDmours= Morehours::updateOrCreate(
                    $moreHours,["user_id"=> $user_id]);
                    $tabmours[]=$IDmours->id;
                $i++;
            }
         
        }
     
        if(isset($tabmours)){
          Morehours::whereNotIn('id',$tabmours)->where('fiche_id',$fiches)->delete();
        }
       }else{
        $prs--;
        $dataprofil['moreHours']=false;
      }

       ////// locationstate ////// 




       $datstate=array();
   //    if(array_key_exists('metadata',$list)){
    if(isset($list['metadata'])){

      if (isset($list["metadata"]['hasGoogleUpdated'])) {
        //if(array_key_exists('hasGoogleUpdated',$list['metadata'])){
        $datstate['isGoogleUpdated']=$list["metadata"]['hasGoogleUpdated'];
        }
        if (isset($list["metadata"]['hasPendingEdits'])) {
       // if(array_key_exists('hasPendingEdits',$list['metadata'])){
          $datstate['hasPendingEdits']=$list["metadata"]['hasPendingEdits'];
        }

      
        $datstate['canDelete']=$list["metadata"]['canDelete'];
        if (isset($list["metadata"]['canOperateLocalPost'])) {
       // if(array_key_exists('canOperateLocalPost',$list['metadata'])){
          $datstate['isLocalPostApiDisabled']=$list["metadata"]['canOperateLocalPost'];
        }
        if (isset($list["metadata"]['canModifyServiceList'])) {
        //if(array_key_exists('canModifyServiceList',$list['metadata'])){
         
        $datstate['canModifyServiceList']=$list["metadata"]['canModifyServiceList'];
        }
        if (isset($list["metadata"]['canHaveFoodMenus'])) {
      //  if(array_key_exists('canHaveFoodMenus',$list['metadata'])){
        $datstate['canHaveFoodMenus']=$list["metadata"]['canHaveFoodMenus'];
        }
        if (isset($list["metadata"]['canOperateHealthData'])) {
       // if(array_key_exists('canOperateHealthData',$list['metadata'])){
        $datstate['canOperateHealthData']=$list["metadata"]['canOperateHealthData'];
        }
        if (isset($list["metadata"]['canOperateLodgingData'])) {
       // if(array_key_exists('canOperateLodgingData',$list['metadata'])){
        $datstate['canOperateLodgingData']=$list["metadata"]['canOperateLodgingData'];
        }
        if (isset($list["metadata"]['duplicateLocation'])) {
          if($list["metadata"]['duplicateLocation']==true){
            $datstate['isDuplicate']=true;
            $iconfiche= Iconfiche::where('code','isDuplicate')->first();
            $dataprofil['statestorelocatore']= ["state"=>"Demande accés","couleur"=>"#FFFF00","icon"=>"https://demo-apiwall.bforbiz-dev.com/public/$iconfiche->path"];
           
          }
      //  if(array_key_exists('duplicateLocation',$list['metadata'])){
       
        }
      
        
        if (isset($list["metadata"]['canUpdate'])) {
       // if(array_key_exists('canUpdate',$list['metadata'])){
          $datstate['canUpdate']=$list["metadata"]['canUpdate'];
        }
        if (isset($list["metadata"]['needsReverification'])) {
      //  if(array_key_exists('needsReverification',$list['metadata'])){
          $datstate['needsReverification']=$list["metadata"]['needsReverification'];
        }
        if (isset($list["metadata"]['isPendingReview'])) {
      //  if(array_key_exists('isPendingReview',$list['metadata'])){
          $datstate['isPendingReview']=$list["metadata"]['isPendingReview'];
          $iconfiche= Iconfiche::where('code','isPendingReview')->first();
          $dataprofil['statestorelocatore']=["state"=>"Personnaliser","couleur"=>"#008000","icon"=>"https://demo-apiwall.bforbiz-dev.com/public/$iconfiche->path"];
        }
        if (isset($list["metadata"]['isDisabled'])) {
       // if(array_key_exists('isDisabled',$list['metadata'])){
          $datstate['isDisabled']=$list["metadata"]['isDisabled'];
        }
        if (isset($list["metadata"]['isDisconnected'])) {
        //if(array_key_exists('isDisconnected',$list['metadata'])){
          $datstate['isDisconnected']=$list["metadata"]['isDisconnected'];
        }


      //  $list_complete = $serviceLocation->locations_verifications->complete($list['name'],$pinverification);



      try{
        $locationstate=$verificationLocation->locations->getVoiceOfMerchantState($list['name']);
 
    
       // if (isset($list["metadata"]['isDisconnected'])) {
        if(isset($locationstate['verify'])){
          if(isset($locationstate["verify"]['hasPendingVerification'])){
        $datstate['hasPendingVerification']=$locationstate["verify"]['hasPendingVerification'];
        $iconfiche= Iconfiche::where('code','hasPendingVerification')->first();
    
        $dataprofil['statestorelocatore']=["state"=>"Code Google","couleur"=>"#ff00ff","icon"=>"https://demo-apiwall.bforbiz-dev.com/public/$iconfiche->path"];
    
        
          }
        }
        if (isset($locationstate['hasBusinessAuthority'])) {
          //  if(array_key_exists('hasVoiceOfMerchant',$list['metadata'])){
                $datstate['isVerified']=true;
              
           //   $datstate['isVerified']=$list["metadata"]['isVerified'];
             // $datstate['isPublished']=$list["metadata"]['isPublished'];
            } 

            if (isset($locationstate['hasVoiceOfMerchant'])) {
              //  if(array_key_exists('hasVoiceOfMerchant',$list['metadata'])){
                   $datstate['isPublished']=true;
                   $iconfiche= Iconfiche::where('code','isPublished')->first();
                   $dataprofil['statestorelocatore']=["state"=>"Valider","couleur"=>"#0080ff","icon"=>"https://demo-apiwall.bforbiz-dev.com/public/$iconfiche->path"];
               
                  
               //   $datstate['isVerified']=$list["metadata"]['isVerified'];
                 // $datstate['isPublished']=$list["metadata"]['isPublished'];
                }
                if (isset($locationstate['complyWithGuidelines']['recommendationReason'])=="BUSINESS_LOCATION_SUSPENDED") {
                  // if(array_key_exists('isSuspended',$list['metadata'])){
                     $datstate['isSuspended']=true;
                   }

      }catch (\Google_Service_Exception $ex) {
        
        print_r($ex->getMessage()); 
        continue;
    
      }

          
     $STATEtB=State::updateOrCreate(['fiche_id'=>$fiches],$datstate);

 
      State::where('id','!=',  $STATEtB->id)->where('fiche_id',$fiches)->delete();
 
    }  
 
  if(Photo::where('fiche_id', $fiches)->where('category','PROFILE')->doesntExist()){
    $prs--;
    $dataprofil['Photo']=false;
  }else{
    $dataprofil['Photo']=true;
  }

  if(Post::join('postfiches', 'postfiches.post_id', '=', 'posts.id')->where('postfiches.fiche_id', $fiches)->where('posts.type','=','Produits')->doesntExist()){
      $prs--;
      $dataprofil['Post']=false;
    }else{
      $dataprofil['Post']=true;
    }


    $prc = $prs * 100 /(int)$pr->value;
  
    $dataprofil['title']=$list['title'];
    $dataprofil['total']=(int) $prc;
    $dataprofil['totalfiche']=number_format((float)($prs /(int)$pr->value),2);
    $dataprofil['etat']=$this->statefiche($fiches);
  
    profilincomplete::updateOrCreate(['fiche_id'=>$fiches],$dataprofil);

    if(isset($list_locations->nextPageToken)){
     
      $nextPageToken=$list_locations->nextPageToken;

     }

          
          } catch (Exception $exc) {
            echo 'testcron---'.$exc->getMessage();
               
          continue;
     
        
        }
       
         
        


            }
           
        } while ($nextPageToken != null);
             }
   
          
            Fiche::whereNotIN('id',$tabfichee)->delete();
   
} catch (\Throwable $th) {
  echo 'testcron---'.$th->getMessage();
  $tken =$client->getAccessToken();

  }
}
            public  function statefiche($fiche_id){
    
  
              if((Etiquetgroupe::where('groupe_id', '=', 1)->where('fiche_id',$fiche_id)->where('state','=',1)->doesntExist() && Etiquetgroupe::Where('groupe_id', '=', 2)
              ->where('fiche_id',$fiche_id)->where('state','=',1)->exists())|| (Etiquetgroupe::where('groupe_id', '=', 2)->where('fiche_id',$fiche_id)->where('state','=',1)->doesntExist() && Etiquetgroupe::Where('groupe_id', '=', 1)
              ->where('fiche_id',$fiche_id)->where('state','=',1)->exists())
             
          ){
          //Where('groupe_id',1)->orwhere('groupe_id',2)){
          $iconfiche= Iconfiche::where('code','manque')->first();
          return 'https://demo-apiwall.bforbiz-dev.com/public/'.$iconfiche->path;
          }
          else if(Etiquetgroupe::where('groupe_id',1)->where('state','=',1)->where('fiche_id',$fiche_id)->exists()&& Etiquetgroupe::where('groupe_id',2)->where('state','=',1)->where('fiche_id',$fiche_id)->exists()){
              $iconfiche= Iconfiche::where('code','complet')->first();
              return 'https://demo-apiwall.bforbiz-dev.com/public/'.$iconfiche->path;
           }
           else if(Etiquetgroupe::where('groupe_id','!=',1)->where('state','=',1)->where('fiche_id',$fiche_id)->doesntExist()&& Etiquetgroupe::where('groupe_id','!=',2)->where('state','=',1)->where('fiche_id',$fiche_id)->doesntExist()){
             $iconfiche= Iconfiche::where('code','aucune')->first();
             return 'https://demo-apiwall.bforbiz-dev.com/public/'.$iconfiche->path;
          }
          
          }
           
  function mapped_implode($glue, $array, $symbol = '=') {
    return implode($glue, array_map(
            function($k, $v) use($symbol) {
                return $k . $symbol . $v;
            },
            array_keys($array),
            array_values($array)
            )
        );
}
  public static function dateToFrench($date) {
      $english_days = array('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY');
      $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
      return str_replace($english_days, $french_days, $date);
  }

  public static function dateToAnglash($date) {
      $english_days = array('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY');
      $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
      return str_replace($french_days, $english_days, $date);
  }
public function getGoogleUpdated($locations,$read_mask){

  $accessToken = Helper::GMBServiceToken();
  $token_acces = json_decode($accessToken, true);
           
                  $url = "https://mybusinessbusinessinformation.googleapis.com/v1/".$locations.":getGoogleUpdated?read_mask=".$read_mask;
  
                  $ch = curl_init($url);
                  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'Get');
                 
               
      
                  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $token_acces['access_token']));
                      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                  $response = curl_exec($ch);
                 
                  if (curl_errno($ch)) {
                      $error_msg = curl_error($ch);
                  }
                  curl_close($ch);
                  $locationsList=json_decode($response,1);
                  return $locationsList;
}
public function VoiceOfMerchantState($locations){

  $accessToken = Helper::GMBServiceToken();
  $token_acces = json_decode($accessToken, true);
           
                  $url = "https://mybusinessbusinessinformation.googleapis.com/v1/".$locations."/VoiceOfMerchantState";
  
                  $ch = curl_init($url);
                  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'Get');
                 
               
      
                  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $token_acces['access_token']));
                      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                  $response = curl_exec($ch);
                 
                  if (curl_errno($ch)) {
                      $error_msg = curl_error($ch);
                  }
                  curl_close($ch);
                  $locationsList=json_decode($response,1);
                  return $locationsList;
}
public function verifications($locations){

  $accessToken = Helper::GMBServiceToken();
  $token_acces = json_decode($accessToken, true);
           
                  $url = "https://mybusinessbusinessinformation.googleapis.com/v1/".$locations."/verifications";
  
                  $ch = curl_init($url);
                  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'Get');
                 
               
      
                  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $token_acces['access_token']));
                      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                  $response = curl_exec($ch);
                 
                  if (curl_errno($ch)) {
                      $error_msg = curl_error($ch);
                  }
                  curl_close($ch);
                  $locationsList=json_decode($response,1);
                  return $locationsList;
}



}
$pr =Paramater::where('name','profilincomplet')->first();