<?php

namespace App\Jobs;

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
use App\Models\Service;
use App\Models\Accountagence;
use App\Models\Attribute;
use App\Models\Attributeshistorique;
use App\Models\Categorieshistorique;
use App\Models\FicheHourhistorique;
use App\Models\Ficheshistorique;
use App\Models\Servicearea;
use App\Models\Serviceareashistorique;
use App\Models\Serviceshistorique;
use Illuminate\Support\Facades\Log;
use GooglePlaces;

class CronFicheMybusines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      ini_set("display_errors",1);
      $user_id=1;
      
        $mybusinessService = Helper::GMB();
        $accounts = $mybusinessService->accounts;
        $accountsList = $accounts->listAccounts()->getAccounts();
        $lists = $accounts->listAccounts()->getAccounts();
        $locations = $mybusinessService->accounts_locations;
        $verr = $mybusinessService->accounts_locations_verifications;

        $this->mybusinessService = Helper::GMB();
        $nextPageToken=null;
        do{
            try {
                $user_id=1;
                $data['franchises_id'] =1;
           $locationsList= $locations->listAccountsLocations('accounts/108337422416691105497',
             array('pageSize' => 100, 'pageToken' => $nextPageToken));
     
             if(isset($locationsList) && !empty($locationsList)){
                $locationsLists=$locationsList->getLocations();
             foreach ($locationsLists as $values) {
              
                $data['franchises_id'] = 1;
                try{
                $locationsListUpdate = $locations->getGoogleUpdated($values['name'], array())->getLocation();
            
                $verif = $verr->listAccountsLocationsVerifications($locationsListUpdate['name'], array());
            
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
       
                //$data['name'] = $locationsListUpdate['name'];

                $data['locationName'] = $locationsListUpdate['locationName'];
         
                if (array_key_exists('profile', $locationsListUpdate)) {
                    $data['description'] = $locationsListUpdate['profile']["description"];
                }
                if (array_key_exists('primaryPhone', $locationsListUpdate)) {


                    $data['primaryPhone'] = $locationsListUpdate['primaryPhone'];
                }
               

                if (array_key_exists('placeId', $locationsListUpdate['locationKey'])) {
                    $data['placeId'] = $locationsListUpdate['locationKey']['placeId'];
                    $metadata['placeId'] = $locationsListUpdate['locationKey']['placeId'];
                } 
                if (array_key_exists('websiteUrl', $locationsListUpdate)) {
                    $data['websiteUrl'] = $locationsListUpdate['websiteUrl'];
                }
                if (array_key_exists('additionalPhones', $locationsListUpdate)) {

                    $data['additionalPhones'] = json_encode($locationsListUpdate['additionalPhones']);
                }
                if (array_key_exists('address', $locationsListUpdate)) {

                    $data['address'] = $locationsListUpdate['address']['addressLines'][0];
                    $data['city'] = $locationsListUpdate['address']['locality'];
                    $data['country'] = $locationsListUpdate['address']['locality'];
                    $data['postalCode'] = $locationsListUpdate['address']['postalCode'];
                }
                if (array_key_exists('latlng', $locationsListUpdate)) {

                    $data['latitude'] = $locationsListUpdate['latlng']['latitude'];
                    $data['longitude'] = $locationsListUpdate['latlng']['longitude'];
                }
                $day = null;
              
                if (array_key_exists('openInfo', $locationsListUpdate)) {
                    $data['OpenInfo_status'] = $locationsListUpdate['openInfo']['status'];
                    $data['OpenInfo_canreopen'] = $locationsListUpdate['openInfo']['canReopen'];
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
//var_dump($locationsListUpdate['openInfo']['openingDate']["year"]);
                        $data['OpenInfo_opening_date'] =  Carbon::parse($date)->translatedFormat('Y-m-d');
                    }
                }
         
                if (array_key_exists('labels', $locationsListUpdate)) {
                    $data['labels'] = json_encode($locationsListUpdate['labels']);
                }
                if (array_key_exists('adWordsLocationExtensions', $locationsListUpdate)) {
                    if (array_key_exists('adPhone', $locationsListUpdate['adWordsLocationExtensions'])) {
                        $data['adwPhone'] = $locationsListUpdate['adWordsLocationExtensions']["adPhone"];
                    }
                }
                $createfiche = Fiche::updateOrCreate(['name' => $locationsListUpdate['name']], $data);
                Ficheshistorique::updateOrCreate(['fiche_id'=>$createfiche->id,'name' => $locationsListUpdate['name']], $data);
                    $fiches = $createfiche->id;
         
            Ficheuser::updateOrCreate(['fiche_id' =>$fiches,'franchise_id'=> 1],['user_id'=>1]);
         
                if (array_key_exists('regularHours', $locationsListUpdate)) {
                    $i = 0;
                    
        
                    foreach ($locationsListUpdate['regularHours']['periods'] as $hour) {
                        $datahour['type'] = true;
                        $datahour['open_date'] = $this->dateToFrench($hour['openDay']);
                        $datahour['close_date'] = $this->dateToFrench($hour['closeDay']);
                        $datahour['open_time'] = $hour['openTime'];
                        $datahour['close_time'] = $hour['closeTime'];
                        $datahour["user_id"] = $user_id;
                        $datahour["fiche_id"] = $fiches;
                        $histhours= Fichehour::updateOrCreate($datahour);
                        FicheHourhistorique::updateOrCreate(['fichehours_id'=>$histhours->id],$datahour);
                        $i++;
                    }
                }   
             
                if (array_key_exists('serviceArea', $locationsListUpdate)) {
                    $i = 0;
           
   foreach ($locationsListUpdate['serviceArea']["places"]["placeInfos"]as $zon) {
 $response =  GooglePlaces::placeDetails($zon['placeId'])->get('result');

 $serviceArea['latitude']=$response['geometry']['location']['lat'];
 $serviceArea['longitude']=$response['geometry']['location']['lng'];
 $serviceArea['placeId']=$zon['placeId'];
 $serviceArea['name']=$zon['name'];
// $serviceArea['pays']=$zon['name'];
 $serviceArea['zone']=$zon['name'];
 $serviceArea['fiche_id']=$fiches;
 $serviceArea['businessType']=$locationsListUpdate['serviceArea']["businessType"];
   
      $metadataser= Servicearea::updateOrCreate($serviceArea);  
     
      Serviceareashistorique::updateOrCreate(['serviceareas_id'=>$metadataser->id],$serviceArea);
      
    
                        $i++;
                    }
              
            }
                if (array_key_exists('attributes', $locationsListUpdate)) {
                  
                    foreach( $locationsListUpdate["attributes"] as $attribtus){
                      
                        $dataattr['attributeId'] = $attribtus['attributeId'];
                        $dataattr['displayName'] = $attribtus['displayName'];
                        $dataattr['valueType'] = $attribtus['valueType'];
                      if(!empty($attribtus["values"])){
                        $dataattr['values'] = $attribtus["values"][0];
                      }
                      if(!empty($attribtus["repeatedEnumValue"])){
                        $dataattr['repeatedEnumValue'] = $attribtus["repeatedEnumValue"][0];
                      }
                        $dataattr['groupDisplayName']="";
                        if(array_key_exists('urlValues',$attribtus)){
                          
                             $dataattr['urlValues'] = $attribtus["urlValues"][0]['url'];
                            }
                      
                        $dataattr["fiche_id"] = $fiches;
                        $dataattr["user_id"] = $user_id;
                        
                        $atts = Attribute::updateOrCreate($dataattr);
                          Attributeshistorique::updateOrCreate(['attribute_id'=>$atts->id],$dataattr);
                              
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
                        $datahourspe["user_id"] = $user_id;
                        $datahourspe["fiche_id"] = $fiches;
                        $spehisthours= Fichehour::updateOrCreate($datahourspe);
                      FicheHourhistorique::updateOrCreate(['fichehours_id'=>$spehisthours->id],$datahourspe);
                        $i++;
                    }
                }
                if ($locationsListUpdate["primaryCategory"]) {
                    $categorie["type"] = "primaryCategory";
                    $categorie["displayName"] = $locationsListUpdate["primaryCategory"]["displayName"];
                    $categorie["categorieId"] = $locationsListUpdate["primaryCategory"]["categoryId"];
                    $categorie["user_id"] =1;
                    $categorie["fiche_id"] = $fiches;
                  
                    $cats =  Categorie::updateOrCreate($categorie);
                     Categorieshistorique::updateOrCreate(['categorie_id'=>$cats->id],$categorie);

                        $cat = $cats->id;
                 
                }

              

                if(isset($locationsListUpdate['moreHours'])){
                   
                    foreach ($locationsListUpdate['moreHours'] as $key=>$value) {
                        $i = 0;
                        foreach ($value['periods'] as $hour) {
                            $moreHours['morehoursId'] = $value["hoursTypeId"];
                            $moreHours['displayName'] = $value["hoursTypeId"];
                            $moreHours['type'] = true;
                            $moreHours['categorie_id'] =$cat;

                            $moreHours['openDay'] = $this->dateToFrench($hour['openDay']);
                            $moreHours['closeDay'] = $this->dateToFrench($hour['closeDay']);
                            $moreHours['openTime'] = $hour['openTime'];
                            $moreHours['closeTime'] = $hour['closeTime'];
                            $moreHours["user_id"] = $user_id;
                            $moreHours["fiche_id"] = $fiches;
                               
                        Morehours::updateOrCreate(
                                $moreHours);
                               
                            $i++;
                        }
                     
                    }
                 
            
                    }
               
                if (array_key_exists('serviceTypes', $locationsListUpdate["primaryCategory"])) {

                    foreach ($locationsListUpdate["primaryCategory"]["serviceTypes"] as $servicetype) {

                        $service['categorie_id'] = $cat;
                        $service["user_id"] = $user_id;
                        $service['serviceId'] = $servicetype["serviceTypeId"];
                        $service['displayName'] = $servicetype["displayName"];
                      
                        $servicety=Service::updateOrCreate($service);
                       Serviceshistorique::updateOrCreate(['service_id'=> $servicety->id],$service);
                        
                    }
                }
             
                if (array_key_exists('additionalCategories', $locationsListUpdate)) {
                    foreach ($locationsListUpdate["additionalCategories"] as $val)
                        $categorie["type"] = "additionalCategories";
                    $categorie["displayName"] = $val["displayName"];
                    $categorie["categorieId"] = $val["categoryId"];
                    $categorie["user_id"] =1;
                    $categorie["fiche_id"] = $fiches;
                    $cats =  Categorie::updateOrCreate($categorie);
                    Categorieshistorique::updateOrCreate(['categorie_id'=>$cats->id],$categorie);

                    $cat = $cats->id;
                
                }  
                if (array_key_exists('additionalCategories', $locationsListUpdate)) {
                    if (array_key_exists('serviceTypes', $locationsListUpdate["additionalCategories"])) {

                        foreach ($value["additionalCategories"]["serviceTypes"] as $servicetype) {

                            $service['categorie_id'] = $cat;
                            $service["user_id"] = $user_id;
                            $service['serviceId'] = $servicetype["serviceTypeId"];
                            $service['displayName'] = $servicetype["displayName"];
                             $servicecat= Service::updateOrCreate($service);
                             Serviceshistorique::updateOrCreate(['service_id'=> $servicecat->id],$service);
                        }
                    }
                }
                
                if (array_key_exists('mapsUrl', $locationsListUpdate["metadata"]) && array_key_exists('newReviewUrl', $locationsListUpdate["metadata"])) {
                    $metadata['locationName'] = $locationsListUpdate['locationName'];
                    $metadata['mapsUrl'] = $locationsListUpdate['metadata']["mapsUrl"];
                    $metadata['newReviewUrl'] = $locationsListUpdate['metadata']["newReviewUrl"];
                    $metadata['fiche_id'] = $fiches;
         
                    
                    $meta=  Metadata::updateOrCreate($metadata);
                }          
              
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
             catch (\Throwable $th) {
                            print_r($th->getMessage());exit;
            }
                

               
        }
    }
        
            } catch (\Throwable $th) {
               continue;
            }
         
            if(isset($locationsList->nextPageToken)){
               
             $nextPageToken=$locationsList->nextPageToken;

            }
     } while ($nextPageToken != null);
     
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

