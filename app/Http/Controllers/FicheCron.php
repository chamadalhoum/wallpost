<?php

namespace App\Console\Commands;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helper\Helper;
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
    //ini_set("display_errors",1);
    $user_id=1;
    
      $mybusinessService = Helper::GMB();
      $accounts = $mybusinessService->accounts;
      $accountsList = $accounts->listAccounts()->getAccounts();
      $lists = $accounts->listAccounts()->getAccounts();
      $locations = $mybusinessService->accounts_locations;
      $verr = $mybusinessService->accounts_locations_verifications;
      
       
      $this->mybusinessService = Helper::GMB();
      $this->admins = $this->mybusinessService->accounts_locations_admins;
      $nextPageToken=null;
      do{
          try {
              $user_id=1;
              $franchise_id=1;
             // $data['franchises_id'] =1;
         $locationsList= $locations->listAccountsLocations('accounts/108337422416691105497',
           array('pageSize' => 100));
          /* $locationsList= $locations->listAccountsLocations('accounts/108337422416691105497',
           array('pageSize' => 100, 'pageToken' => $nextPageToken));*/
            
            if(!empty($locationsList)){
               
             $locationsLists=$locationsList->getLocations();
             
             if(!empty($locationsLists)){

           foreach ($locationsLists as $values) {
            $adresses=null;
              try{
           
     $locationsListUpdate = $locations->getGoogleUpdated($values['name'], array())->getLocation();
             
  
     
$diffesk = $locations->getGoogleUpdated($values['name'], array())->diffMask;
  
 $verif = $verr->listAccountsLocationsVerifications($values['name'], array());
         //$verif = $verr->listAccountsLocationsVerifications('accounts/108337422416691105497/locations/8809891710236480284', array());
       
         if($diffesk && $diffesk!="latlng" && $diffesk!="priceLists")
              {
                $data['etat'] = 'Inactif';
               // $data['notification'] =json_decode($diffesk,true)//  '["'. collect($diffesk)->implode('","').'"]';
                 $data['notification'] ='["'.str_replace ( ',', '"," ', $diffesk ).'"]';
               // '["'. collect($diffesk)->implode('","').'"]';
                ;
              }
         $data=array();
              $dataattr=array();
              $data['franchises_id'] = $franchise_id;
           

         
              if(array_key_exists('verifications', $verif)){
              
       if(array_key_exists('method', $verif['verifications'][0]) )
       { 
           $data['methodverif'] = $verif['verifications'][0]['method'];
       }
          if(array_key_exists('state', $verif['verifications'][0])){
              $data['state'] = $verif['verifications'][0]['state'];
          }
          if(array_key_exists('createTime', $verif['verifications'][0])){

               $data['closedatestrCode'] = Carbon::parse($verif['verifications'][0]['createTime'])->translatedFormat('Y-m-d');
          }
      }
    $fiche=Fiche::where('name', $locationsListUpdate['name'])->first();
     
              $data['locationName'] = $locationsListUpdate['locationName'];
              $notification=explode(',',$diffesk);
              if(in_array('locationName',$notification)){
                $datafiche["state"] = "Inactif";
                $datafiche['diffMask']='locationName';
                $datafiche['newobject']=$locationsListUpdate['locationName'];
                $datafiche['oldobject']=$fiche->locationName;
                $datafiche['fiche_id']=$fiche->id;
                Notification::updateOrCreate($datafiche);
              }

              $data['storeCode'] = $locationsListUpdate["storeCode"];
              if(in_array('storeCode',$notification)){
                $datafiche["state"] = "Inactif";
                $datafiche['diffMask']='storeCode';
                $datafiche['newobject']=$locationsListUpdate['storeCode'];
                $datafiche['oldobject']=$fiche->storeCode;
                $datafiche['fiche_id']=$fiche->id;
                Notification::updateOrCreate($datafiche);
              }
              if (array_key_exists('profile', $locationsListUpdate)) {
             
                  $data['description'] = $locationsListUpdate['profile']["description"];
                  if(in_array('profile.description',$notification)){
                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='profile.description';
                    $datafiche['newobject']=$locationsListUpdate['profile']["description"];
                    $datafiche['oldobject']=$fiche->description;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                  }
              }
              if (array_key_exists('primaryPhone', $locationsListUpdate)) {
              
                  $data['primaryPhone'] = $locationsListUpdate['primaryPhone'];
                  if(in_array('primaryPhone',$notification)){
                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='primaryPhone';
                    $datafiche['newobject']=$locationsListUpdate['primaryPhone'];
                    $datafiche['oldobject']=$fiche->primaryPhone;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                  }
              }
              if (array_key_exists('placeId', $locationsListUpdate['locationKey'])) {
               
                  $data['placeId'] = $locationsListUpdate['locationKey']['placeId'];
                  $metadata['placeId'] = $locationsListUpdate['locationKey']['placeId'];
              } 
              if (array_key_exists('websiteUrl', $locationsListUpdate)) {
              
                  $data['websiteUrl'] = $locationsListUpdate['websiteUrl'];
                  if(in_array('websiteUrl',$notification)){

                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='websiteUrl';
                  $datafiche['newobject']=$locationsListUpdate['websiteUrl'];
                    $datafiche['oldobject']=$fiche->websiteUrl;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                  }
              }
              if (array_key_exists('additionalPhones', $locationsListUpdate)) {
if($locationsListUpdate['additionalPhones']){
    $data['additionalPhones'] = json_encode($locationsListUpdate['additionalPhones']);
    if(in_array('additionalPhones',$notification)){
      $datafiche["state"] = "Inactif";
      $datafiche['diffMask']='additionalPhones';
      $datafiche['newobject']=collect($locationsListUpdate)->toJson(JSON_UNESCAPED_UNICODE);
 
      $datafiche['oldobject']=$fiche->additionalPhones;
      $datafiche['fiche_id']=$fiche->id;
  
      Notification::updateOrCreate($datafiche);
    }
}
               
              }
            
              if (array_key_exists('address', $locationsListUpdate)) {
                 $data['address'] = $locationsListUpdate['address']['addressLines'][0];
                  $data['city'] = $locationsListUpdate['address']['locality'];
                  $pays = Pay::where('alpha2',$locationsListUpdate['address']['regionCode'])->select('pays')->first();
                  $data['country'] = $pays->pays;
                  $data['postalCode'] = $locationsListUpdate['address']['postalCode'];
  
                  $adresses=','.$locationsListUpdate['address']['addressLines'][0].', '.$locationsListUpdate['address']['postalCode'].' '.$locationsListUpdate['address']['locality'].','. $pays->pays;
                  if(in_array('postalAddress',$notification)){
                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='address.addressLines';
                    $datafiche['newobject']=$locationsListUpdate['address']['addressLines'][0];
                    $datafiche['oldobject']=$fiche->address;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                  }
                }
              if (array_key_exists('latlng', $locationsListUpdate)) {
               $data['latitude'] = $locationsListUpdate['latlng']['latitude'];
               $data['longitude'] = $locationsListUpdate['latlng']['longitude'];
              }
              $day = null;
            
              if (array_key_exists('openInfo', $locationsListUpdate)) {
               
                  $data['OpenInfo_status'] = $locationsListUpdate['openInfo']['status'];
                  $data['OpenInfo_canreopen'] = $locationsListUpdate['openInfo']['canReopen'];
                  if(in_array('openInfo.status',$notification)){
                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='openInfo.status';
                    $datafiche['newobject']=$locationsListUpdate['openInfo']['status'];
                    $datafiche['oldobject']=$fiche->OpenInfo_status;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                  }
                  
                  if (array_key_exists('openingDate', $locationsListUpdate['openInfo'])) {
                      if (array_key_exists('day', $locationsListUpdate['openInfo']['openingDate'])) {
                          $day =  $locationsListUpdate['openInfo']['openingDate']["day"];
                      }
                      if($day){
                          $dt='-'.$day;
                      }else{
                          $dt=$day;
                      }


                      $date = $locationsListUpdate['openInfo']['openingDate']["year"] . '-' . $locationsListUpdate['openInfo']['openingDate']["month"] . $dt;
                      $data['OpenInfo_opening_date'] =  Carbon::parse($date)->translatedFormat('Y-m-d');
                      if(in_array('openInfo.openingDate',$notification)){
                        $datafiche["state"] = "Inactif";
                        $datafiche['diffMask']='openInfo.openingDate';
                        $datafiche['newobject']=Carbon::parse($date)->translatedFormat('Y-m-d');
                        $datafiche['oldobject']=$fiche->OpenInfo_opening_date;
                        $datafiche['fiche_id']=$fiche->id;
                        Notification::updateOrCreate($datafiche);
                      }
                  }
              }
          
              if (array_key_exists('labels', $locationsListUpdate)) {
                if($locationsListUpdate['labels']){
                  //  $data['labels'] =json_encode($locationsListUpdate['labels'],"UTF-8");
                    $data['labels'] ='["'. collect($locationsListUpdate['labels'])->implode('","').'"]';
                    if(in_array('labels',$notification)){
                      $datafiche["state"] = "Inactif";
                      $datafiche['diffMask']='labels';
                      $datafiche['newobject']='["'. collect($locationsListUpdate['labels'])->implode('","').'"]';
                      $datafiche['oldobject']=$fiche->labels;
                      $datafiche['fiche_id']=$fiche->id;
                      Notification::updateOrCreate($datafiche);
                    }
                  }
                  if(in_array('openInfo.openingDate',$notification)){
                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='openInfo.openingDate';
                    $datafiche['newobject']=Carbon::parse($date)->translatedFormat('Y-m-d');
                    $datafiche['oldobject']=$fiche->OpenInfo_opening_date;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                  }
              }   
              if (array_key_exists('adWordsLocationExtensions', $locationsListUpdate)) {
                  if (array_key_exists('adPhone', $locationsListUpdate['adWordsLocationExtensions'])) {
                      $data['adwPhone'] = $locationsListUpdate['adWordsLocationExtensions']["adPhone"];
                  }
                  if(in_array('adWordsLocationExtensions.adPhone',$notification)){
                    $datafiche["state"] = "Inactif";
                    $datafiche['diffMask']='adWordsLocationExtensions.adPhone';
                    $datafiche['newobject']=$locationsListUpdate['adWordsLocationExtensions']["adPhone"];
                    $datafiche['oldobject']=$fiche->adwPhone;
                    $datafiche['fiche_id']=$fiche->id;
                    Notification::updateOrCreate($datafiche);
                  }

                  
              } 
           
             
               
              $createfiche = Fiche::updateOrCreate(['name' => $locationsListUpdate['name']], $data);
            
              $locationsListUpdateetat = $locations->getGoogleUpdated($locationsListUpdate['name'], array())->getDiffMask();

$locationsListUpdateetat== NULL ||$locationsListUpdateetat=="priceLists"?$etat="Actif":$etat="Actif";
             $data['etat']=$etat;
           
    
                 $fiches = $createfiche->id;
               
                 $listadmin=$this->admins->listAccountsLocationsAdmins($values['name'])->getAdmins();
                $userst=User::where('franchises_id',$franchise_id)->whereIN('role_id',[1,2,3])->get();
foreach($userst as $us){
    
    $datafichesuser['user_id']=$us->id;
    $datafichesuser['fiche_id']=$fiches;
    $datafichesuser['franchise_id']=$franchise_id;
    $datafichesuser['role_id']=$us->role_id;
   // $datafiche['namefiche']=;
   $datafichesuser['pendingInvitation']=0;
    Ficheuser::updateOrCreate($datafichesuser);
}

                 foreach($listadmin as $admin){
                    $datauser=array();
             $verifuser=User::join('ficheusers','users.id','=','ficheusers.user_id')
            // ->where('ficheusers.namefiche')
             ->where('users.username',$admin->adminName)->exists();
           
             $datauser['password'] = bcrypt('123456789');
             $role= Role::where('nameenglais',$admin->role)->first();
             $datauser['role_id'] = $role->id;
            
             $datauser['state']=1;
             $datauser['franchises_id']=$franchise_id;
             $datauser['lastname']=$admin->adminName;
            
             
if (filter_var($admin->adminName, FILTER_VALIDATE_EMAIL)) {
    $datauser['email']=$admin->adminName;
  } 
  $users = User::updateOrCreate(['username'=>$admin->adminName],$datauser);
  $admin->pendingInvitation==true?$pendingInvitation=1:$pendingInvitation=0;
                  $datafiches['user_id']=$users->id;
                  $datafiches['fiche_id']=$fiches;
                  $datafiches['franchise_id']=$franchise_id;
                  $datafiches['role_id']=$role->id;
                  $datafiches['namefiche']=$admin->name;
                  $datafiches['pendingInvitation']=$pendingInvitation;
                  Ficheuser::updateOrCreate($datafiches);
              
            
           //  Ficheuser::updateOrCreate(['fiche_id' =>$fiches,'franchise_id'=> 1],['user_id'=>$user_id]);
                 }
       
            
         
  
              if (array_key_exists('regularHours', $locationsListUpdate)) {
               
                  $i = 0;
                    foreach ($locationsListUpdate['regularHours']['periods'] as $hour) {
                      $datahour['type'] = true;
                      $datahour['open_date'] = $this->dateToFrench($hour['openDay']);
                      $datahour['close_date'] = $this->dateToFrench($hour['closeDay']);
                      $datahour['open_time'] = $hour['openTime'];
                      $datahour['close_time'] = $hour['closeTime'];
                      $datahour["fiche_id"] = $fiches;
                     $testhours=Fichehour::where('open_date',$this->dateToFrench($hour['openDay']))
                     ->where('open_time',$hour['openTime'])
                     ->where('close_time',$hour['closeTime'])
                     ->where('fiche_id',$fiches);
                  
                     if($testhours->doesntExist() && in_array('regularHours',$notification)){
                        
                      $statehour = "Inactif";
                     }else{
                        $statehour = "Actif";
                     }
                  /*   $testhourst=Fichehour::where('open_date',$this->dateToFrench($hour['openDay']))
                     ->where('open_time',$hour['openTime'])
                     ->where('close_time',$hour['closeTime'])
                     ->where('fiche_id',$fiches)->first();*/
                     if($testhours->doesntExist() && in_array('regularHours',$notification)){
                      $datafiche["state"] = "Inactif";
                      $datafiche['diffMask']='regularHours';
                      $datafiche['newobject']=collect($datahour)->toJson(JSON_UNESCAPED_UNICODE);
                      $datafiche['oldobject']=collect($testhours->get())->toJson(JSON_UNESCAPED_UNICODE);
                      $datafiche['fiche_id']=$fiches;
                      Notification::updateOrCreate($datafiche,['state'=>$statehour]);
                    }
                      $histhours= Fichehour::updateOrCreate($datahour,["user_id", $user_id,'state'=>$statehour]);
                      
                     // $FicheHourhistoriqueexit=  FicheHourhistorique::where('fichehours_id',$histhours->id);
    /*if($FicheHourhistoriqueexit->doesntExist()){
        $datahour["user_id"] = $user_id;
        FicheHourhistorique::updateOrCreate(['fichehours_id'=>$histhours->id],$datahour);
    }*/
                      $i++;
                  }
              }    
              if (array_key_exists('serviceArea', $locationsListUpdate)) {
                  $i = 0;
                  if (array_key_exists('radius', $locationsListUpdate['serviceArea'])) {
                    
$serviceAreas['latitude']=$locationsListUpdate['serviceArea']["radius"]['latlng']['latitude'];
$serviceAreas['longitude']=$locationsListUpdate['serviceArea']["radius"]['latlng']['longitude'];
$serviceAreas['radiusKm']=$locationsListUpdate['serviceArea']["radius"]["radiusKm"];

$serviceAreas['fiche_id']=$fiches;
$serviceAreas['businessType']=$locationsListUpdate['serviceArea']["businessType"];
$testserviceAreas=Servicearea::where('latitude',$locationsListUpdate['serviceArea']["radius"]['latlng']['latitude'])
                     ->where('businessType',$locationsListUpdate['serviceArea']["businessType"])
                     ->where('longitude',$locationsListUpdate['serviceArea']["radius"]['latlng']['longitude'])
                     ->where('radiusKm',$locationsListUpdate['serviceArea']["radius"]["radiusKm"])
                     ->where('fiche_id',$fiches);
                     if($testserviceAreas->doesntExist() && in_array('serviceArea',$notification)){
                      $stateAreas = "Inactif";
                     }else{
                        $stateAreas = "Actif";
                     }
                     if(in_array('serviceArea',$notification)){
                      $dataserviceAreas["state"] = "Inactif";
                      $dataserviceAreas['diffMask']='serviceArea';
                      $dataserviceAreas['newobject']=collect($serviceAreas)->toJson(JSON_UNESCAPED_UNICODE);
                      $dataserviceAreas['oldobject']=collect($testserviceAreas->get())->toJson(JSON_UNESCAPED_UNICODE);
                      $dataserviceAreas['fiche_id']=$fiches;
                      Notification::updateOrCreate($dataserviceAreas);
                    }
                     
                   
   Servicearea::updateOrCreate($serviceAreas,['state'=>$stateAreas]);  

    
                  }
                  if (array_key_exists('places', $locationsListUpdate['serviceArea'])) {

 foreach ($locationsListUpdate['serviceArea']["places"]["placeInfos"]as $zon) {
 
/*$response =  GooglePlaces::placeDetails($zon['placeId'])->get('result');

$serviceArea['latitude']=$response['geometry']['location']['lat'];
$serviceArea['longitude']=$response['geometry']['location']['lng'];*/
$serviceArea['placeId']=$zon['placeId'];
$serviceArea['name']=$zon['name'];
// $serviceArea['pays']=$zon['name'];
$serviceArea['zone']=$zon['name'];
$serviceArea['fiche_id']=$fiches;
$serviceArea['businessType']=$locationsListUpdate['serviceArea']["businessType"];
$testserviceAreas=Servicearea::where('placeId',$zon['placeId'])
                     ->where('name',$zon['name'])
                     ->where('zone',$zon['name'])
                     ->where('businessType',$locationsListUpdate['serviceArea']["businessType"])
                     ->where('fiche_id',$fiches);
                     if($testserviceAreas->doesntExist() && in_array('serviceArea',$notification)){
                      $stateAreas = "Inactif";
                     }else{
                      $stateAreas = "Actif";
                     }
                     if(in_array('serviceArea',$notification)){
                      $dataserviceAreas["state"] = "Inactif";
                      $dataserviceAreas['diffMask']='serviceArea';
                      $dataserviceAreas['newobject']=collect($serviceAreas)->toJson(JSON_UNESCAPED_UNICODE);
                      $dataserviceAreas['oldobject']=collect($testserviceAreas->get())->toJson(JSON_UNESCAPED_UNICODE);
                      $dataserviceAreas['fiche_id']=$fiches;
                      Notification::updateOrCreate($dataserviceAreas);
                    }
                   
 Servicearea::updateOrCreate($serviceArea,["state"=>$stateAreas]);  

  
                      $i++;
                  }
                }
            
          }
          if ($locationsListUpdate["primaryCategory"]) {
            $categorie["type"] = "primaryCategory";
            
            $categorie["displayName"] = $locationsListUpdate["primaryCategory"]["displayName"];
            $categorie["categorieId"] = $locationsListUpdate["primaryCategory"]["categoryId"];
            $opt = array(
              "languageCode" => "fr",
              'categoryId' => $locationsListUpdate["primaryCategory"]["categoryId"],
               'pageSize' => 100,
          );
  
            $list = $this->mybusinessService->attributes->listAttributes($opt)->getAttributes();
           
           
            $categorie["fiche_id"] = $fiches;
            $testCategorie=Categorie::where('displayName',$locationsListUpdate["primaryCategory"]["displayName"])
                     ->where('categorieId',$locationsListUpdate["primaryCategory"]["categoryId"])
                     ->where('type',"primaryCategory")
                     ->where('fiche_id',$fiches);
                     if($testCategorie->doesntExist() && in_array('primaryCategory',$notification)){
                      $statecat = "Inactif";
                     }else{
                      $statecat = "Actif";
                     }
                     if(in_array('primaryCategory',$notification)){
                      $datacategorie["state"] = "Inactif";
                      $datacategorie['diffMask']='primaryCategory';
                      $datacategorie['newobject']=collect($categorie)->toJson(JSON_UNESCAPED_UNICODE);
                      $datacategorie['oldobject']=collect($testCategorie->get())->toJson(JSON_UNESCAPED_UNICODE);
                      $datacategorie['fiche_id']=$fiches;
                      Notification::updateOrCreate($datacategorie);
                    }
                    $categorie["user_id"] =1;
            $cats =  Categorie::updateOrCreate($categorie,['user_id'=>$user_id,"state"=>$statecat]);
       
                                $catp = $cats->id;
                                if (array_key_exists('serviceTypes',$locationsListUpdate["primaryCategory"]["modelData"])) {
                                  
                                  foreach ($locationsListUpdate["primaryCategory"]["serviceTypes"] as $servicetype) {
                                      $servicetypes['categorie_id'] = $catp;
                                     
                                      $servicetypes['serviceId'] = $servicetype["serviceTypeId"];
                                      $servicetypes['displayName'] = $servicetype["displayName"];
                                      $testservicetypes=Service::where('serviceId',$servicetype["serviceTypeId"])
                                      ->where('displayName',$servicetype["displayName"])
                                      ->where('categorie_id',$catp);
                                      if($testservicetypes->doesntExist() && in_array('primaryCategory.serviceTypes',$notification)){
                                        $stateservicetypes = "Inactif";
                                      }else{
                                        $stateservicetypes = "Actif";
                                      }
                                      if($testservicetypes->doesntExist() && in_array('primaryCategory',$notification)){
                                        $dataprimaryCategory["state"] = "Inactif";
                                        $dataprimaryCategory['diffMask']='service';
                                        $dataprimaryCategory['newobject']=collect($servicetypes)->toJson(JSON_UNESCAPED_UNICODE);
                                        $dataprimaryCategory['oldobject']=collect($testservicetypes)->toJson(JSON_UNESCAPED_UNICODE);
                                        $dataprimaryCategory['fiche_id']=$fiches;
                                        Notification::updateOrCreate($dataprimaryCategory);
                                      }
                                   Service::updateOrCreate($servicetypes,["user_id" => $user_id,"state"=>$stateservicetypes]);
                
                                      
                                  }
                              }
              if (array_key_exists('attributes', $locationsListUpdate)) {
               
                  foreach( $locationsListUpdate["attributes"] as $attribtus){
                    $search=$attribtus['attributeId'];
                    $itemCollection = collect($list);
                    $filtered = $itemCollection->filter(function ($item) use ($search) {
                        return stripos($item['attributeId'], $search) !== false;
                    });
               if($attribtus['attributeId']=="url_appointment"){
                $dataattr['displayName'] = NULL;
                        $dataattr['groupDisplayName']=NULL;
               }
                    foreach($filtered->all() as $fil){
                        
                        $dataattr['displayName'] = $fil->displayName;
                        $dataattr['groupDisplayName']=$fil->groupDisplayName;
                    }
                      $dataattr['attributeId'] = $attribtus['attributeId'];
                      
                      $dataattr['valueType'] = $attribtus['valueType'];
                    if(is_array($attribtus["values"])){
                      
                        if($attribtus["values"][0]===1 || $attribtus["values"][0]===true){
                          $dataattr['values'] ='true';
                        }
                        elseif($attribtus["values"][0]===0 || $attribtus["values"][0]==='Closed'){
                          $dataattr['values'] ='Closed';
                        }
                    }
                    if(is_array($attribtus["repeatedEnumValue"])){
                      $dataattr['repeatedEnumValue'] = $attribtus["repeatedEnumValue"][0];
                    }
                    
                      if(array_key_exists('urlValues',$attribtus)){
                           $dataattr['valueType'] = $attribtus["urlValues"][0]['url'];
                          }
                      $dataattr["fiche_id"] = $fiches;
                     
                    
                      $testAttributes=Attribute::Where('attributeId',$attribtus['attributeId'])
                      ->Where('fiche_id',$fiches)
                      ->Where('valueType',$attribtus['valueType']);
                      if($testAttributes->doesntExist() && in_array('attributes',$notification)){
                        $dataattr["state"] = "Inactif";
                      }else{
                        $dataattr["state"] = "Actif";
                      }
                      if(in_array('attributes',$notification)){
                        $dataserviceAreas["state"] = "Inactif";
                        $dataserviceAreas['diffMask']='attributes';
                       
                        $dataserviceAreas['newobject']=collect($dataattr)->toJson(JSON_UNESCAPED_UNICODE);
                        $dataserviceAreas['oldobject']=collect($testAttributes->get())->toJson(JSON_UNESCAPED_UNICODE);
                        //
                       // json_encode($testAttributes->get());
                        $dataserviceAreas['fiche_id']=$fiches;
                        Notification::updateOrCreate($dataserviceAreas);
                      }
                      $dataattr["user_id"] = $user_id;
                      $atts = Attribute::updateOrCreate(["attributeId"=> $attribtus['attributeId']],$dataattr);
                      
                   
                      
                  }
             
      }
           
             

              }
              if (array_key_exists('specialHours', $locationsListUpdate)) {
                $i = 0;
                foreach ($locationsListUpdate['specialHours']['specialHourPeriods'] as $hourspe) {
                    $startDate = strftime("%F", strtotime($hourspe['startDate']['year'] . "-" . $hourspe['startDate']['month'] . "-" . $hourspe['startDate']['day']));
                    $endDate = strftime("%F", strtotime($hourspe['endDate']['year'] . "-" . $hourspe['endDate']['month'] . "-" . $hourspe['endDate']['day']));

                    if (array_key_exists('isClosed', $hourspe)) {
                        $datahourspe['type'] = $hourspe["isClosed"];
                    }
                    $datahourspe['specialhours_start_date'] = $startDate;
                    $datahourspe['specialhours_end_date'] = $endDate;

                    if (array_key_exists('openTime', $hourspe)) {
                        $datahourspe['specialhours_open_time'] = $hourspe['openTime'];
                    }
                    if (array_key_exists('closeTime', $hourspe)) {
                        $datahourspe['specialhours_close_time'] = $hourspe['closeTime'];
                    }
                   
                    $datahourspe["fiche_id"] = $fiches;
                    $testdatahourspe=Fichehour::Where('specialhours_close_time',$hourspe['closeTime'])
                    ->Where('specialhours_open_time',$hourspe['openTime'])
                    ->where('specialhours_start_date',$startDate)
                    ->Where('fiche_id',$fiches);
                    if($testdatahourspe->doesntExist() && in_array('specialHours',$notification)){
                      $statespecialHours = "Inactif";
                    }else{
                      $statespecialHours = "Actif";
                    }
                    if(in_array('specialHours',$notification)){
                      $datafiches["state"] = "Inactif";
                      $datafiches['diffMask']='specialHours';
                      $datafiches['newobject']=collect($datahourspe)->toJson(JSON_UNESCAPED_UNICODE);
                      $datafiches['oldobject']=collect($testdatahourspe->get())->toJson(JSON_UNESCAPED_UNICODE);
                      $datafiches['fiche_id']=$fiches;
                      Notification::updateOrCreate($datafiches);
                    }
                    $spehisthours= Fichehour::updateOrCreate($datahourspe,["user_id"=> $user_id,"state"=>$statespecialHours]);
                  /*  $FicheHourhistoriqueexity= FicheHourhistorique::where('fichehours_id',$spehisthours->id);
                if($FicheHourhistoriqueexity->doesntExist()){
                    $datahourspe["user_id"] = $user_id;
                  FicheHourhistorique::updateOrCreate(['fichehours_id'=>$spehisthours->id],$datahourspe);
              }
                   */
                    $i++;
                }
            }
   
              if(isset($locationsListUpdate['moreHours'])){
                 
                  foreach ($locationsListUpdate['moreHours'] as $key=>$value) {
                      $i = 0;
                      foreach ($value['periods'] as $hour) {
                          $moreHours['morehoursId'] = $value["hoursTypeId"];
                          $moreHours['displayName'] = $value["hoursTypeId"];
                          $moreHours['type'] = true;
                          $moreHours['categorie_id'] =$catp;
                          $moreHours['openDay'] = $this->dateToFrench($hour['openDay']);
                          $moreHours['closeDay'] = $this->dateToFrench($hour['closeDay']);
                          $moreHours['openTime'] = $hour['openTime'];
                          $moreHours['closeTime'] = $hour['closeTime'];
                          
                          $moreHours["fiche_id"] = $fiches;
                             
                      Morehours::updateOrCreate(
                              $moreHours,["user_id"=> $user_id]);
                             
                          $i++;
                      }
                   
                  }
                 }
                 
              if (array_key_exists('additionalCategories', $locationsListUpdate)) {
                  foreach ($locationsListUpdate["additionalCategories"] as $val){
                
                      $categorie["type"] = "additionalCategories";
                  $categorie["displayName"] = $val["displayName"];
                  $categorie["categorieId"] = $val["categoryId"];
                 
                  $categorie["fiche_id"] = $fiches;
                  $testCategories=Categorie::where('displayName',$val["displayName"])
                  ->where('categorieId','=',$val["categoryId"])
                  ->where('type',"additionalCategories")
                  ->where('fiche_id',$fiches);
                  if($testCategories->doesntExist() && in_array('additionalCategories',$notification) ){
                    $statecat = "Inactif";
                  }else{
                    $statecat = "Actif";
                  }
                  if($testCategories->doesntExist() && in_array('additionalCategories',$notification)){
                    $datadditionalCategories["state"] = "Inactif";
                    $datadditionalCategories['diffMask']='additionalCategories';
                    $datadditionalCategories['newobject']=collect($categorie)->toJson(JSON_UNESCAPED_UNICODE);
                    $datadditionalCategories['oldobject']=collect($testCategorie->get())->toJson(JSON_UNESCAPED_UNICODE);
                    $datadditionalCategories['fiche_id']=$fiches;
                    Notification::updateOrCreate($datadditionalCategories);
                  }
                  $cats =  Categorie::updateOrCreate($categorie,["user_id"=>$user_id,"state"=>$statecat]);
               
                 
                 $cat = $cats->id;
         
              
            // if (array_key_exists('serviceTypes', $locationsListUpdate["additionalCategories"])) {
               if( $val["modelData"]){
           
                     // foreach ($locationsListUpdate["additionalCategories"]["serviceTypes"] as $servicetype) {
                       foreach ($val["modelData"]["serviceTypes"] as $servicetype) {
                       
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
                  }
                }
              }
              
              if (array_key_exists('mapsUrl', $locationsListUpdate["metadata"]) && array_key_exists('newReviewUrl', $locationsListUpdate["metadata"])) {
                $metadata['locationName'] = $locationsListUpdate['locationName'];
                $metadata['mapsUrl'] = $locationsListUpdate['metadata']["mapsUrl"];
                $metadata['newReviewUrl']='https://www.google.com/search?hl=fr-TN&gl=tn&q='.$locationsListUpdate['locationName'].$adresses;
               // $metadata['newReviewUrl'] = $locationsListUpdate['metadata']["newReviewUrl"];

                 $metadata['fiche_id'] = $fiches;
                
                $meta=  Metadata::updateOrCreate($metadata);
              }          
            if(array_key_exists('locationState',$locationsListUpdate)){

                $datstate['isGoogleUpdated']=$locationsListUpdate["locationState"]['isGoogleUpdated'];
                $datstate['isDuplicate']=$locationsListUpdate["locationState"]['isDuplicate'];
                $datstate['isSuspended']=$locationsListUpdate["locationState"]['isSuspended'];
                $datstate['canUpdate']=$locationsListUpdate["locationState"]['canUpdate'];
                $datstate['canDelete']=$locationsListUpdate["locationState"]['canDelete'];
                $datstate['isVerified']=$locationsListUpdate["locationState"]['isVerified'];
                $datstate['needsReverification']=$locationsListUpdate["locationState"]['needsReverification'];
                $datstate['isPendingReview']=$locationsListUpdate["locationState"]['isPendingReview'];
                $datstate['isDisabled']=$locationsListUpdate["locationState"]['isDisabled'];
                $datstate['isPublished']=$locationsListUpdate["locationState"]['isPublished'];
                $datstate['isDisconnected']=$locationsListUpdate["locationState"]['isDisconnected'];
                $datstate['isLocalPostApiDisabled']=$locationsListUpdate["locationState"]['isLocalPostApiDisabled'];
                $datstate['canModifyServiceList']=$locationsListUpdate["locationState"]['canModifyServiceList'];
                $datstate['canHaveFoodMenus']=$locationsListUpdate["locationState"]['canHaveFoodMenus'];
                $datstate['hasPendingEdits']=$locationsListUpdate["locationState"]['hasPendingEdits'];
                $datstate['hasPendingVerification']=$locationsListUpdate["locationState"]['hasPendingVerification'];
                $datstate['canOperateHealthData']=$locationsListUpdate["locationState"]['canOperateHealthData'];
                $datstate['canOperateLodgingData']=$locationsListUpdate["locationState"]['canOperateLodgingData'];
                
             State::updateOrCreate(['fiche_id'=>$fiches],$datstate);
          
            }
     
      
 
 
          }
           catch (\Throwable $th) {
                         //
                         print_r($th->getMessage());continue;
          }
              

             
      }
    }
  }
      
          } catch (\Throwable $th) {
           //print_r($th->getMessage());
            continue;
          }
       
        if(isset($locationsList->nextPageToken)){
             
           $nextPageToken=$locationsList->nextPageToken;

          }
   } while ($nextPageToken != null);
   
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




}
