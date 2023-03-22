<?php

namespace App\Http\Controllers;


use App\Models\Fiche;
use Illuminate\Http\Request;
use App\Helper\Helper;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\QueryException;
use App\Http\Controllers\MorehoursController;
use GooglePlaces;
use GoogleMyBusinessService;
use Google;

$mybusinessService;
$token;

class GoogleController extends Controller
{
    public function __construct() {
    }
    public function address(Request $request)
    {
        $adresse = $request->adresse;
        $region = $request->region;
        $ville = $request->ville;
        $codepostal = $request->codepostal;

        try {
            $response = \GoogleMaps::load('geocoding')
                    ->setParam(['address' => $adresse . $codepostal . $ville, 'region' => $region])
                    ->get('results.geometry.location');

            foreach ($response as $res) {
                $loction = array('adresse' => $adresse . $codepostal . $ville . $region, 'location' => $res[0]['geometry']['location']);
            }
            foreach ($response as $res) {
                $loction = array('location' => $res[0]['geometry']['location']);
            }
            return response()->json([
                        'success' => true,
                        'message' => 'Opération terminer avec succes',
                        'data' => $loction,
                        'status' => 200
                            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, attribut not found.',
                        'status' => 400
                            ], 400);
        }
    }

    public function locality(Request $request)
    {
        $adresse = $request->adresse;
        $ville = $request->ville;
        $pays=$request->pays;
        // $pays="FR";
        $locality=array();
        $codepostal = $request->codepostal;
        $adressesearch = $request->adresse  . $request->codepostal . ' ' . $pays . ' ' . $request->ville;


        try {


         //  $googlePlaces = new PlacesApi('AIzaSyDqDuXCfclW4ewsBwsRJczSA1l5MX8-mZc');
            $response = GooglePlaces::placeAutocomplete($adressesearch, array("types"=>'geocode',"regions.country"=>'FR-RE'
                ))->get('predictions');
            $i=0;

            // var_dump($response);
            foreach ($response as $rep) {
                $details =GooglePlaces::placeDetails($rep["place_id"], array("regions.country"=>'FR-RE'))->get('result');

                foreach ($details["address_components"] as  $j=>$product) {
                    if ($product["types"][0]=='postal_code') {
                        $codepostal= $product["long_name"];
                    }
                    $j++;
                }
                $locality[] = array('adresse' => $rep["description"],
                        'ville' => $details['vicinity'],
                        'codepostal' =>$codepostal
                        );
            }
            return response()->json([
                        'success' => true,
                        'message' => 'Opération terminer avec succes',
                        'data' => $locality,
                        'status' => 200
                            ], 200);
        } catch (\Google_Service_Exception $ex) {
            return response()->json(
                [
                                'success' => false,
                                'message' => $ex->getMessage(),
                                'status' => 400,
                            ]
            );
        }
    }

    // List Categories
   

    // list categories migration



    public function categories(Request $request)
    {

        $searchTerm = $request->searchTerm;
    
          
            
        $opt = array(
        "regionCode" => "FR",
        "languageCode" => "Fr",
        "view" => "FULL",
        'filter'=>'displayName='.$searchTerm
    );
 
       
    
    
    $datacat=array();
    $client = Helper::googleClient();
    $service = new Google\Service\MyBusinessBusinessInformation($client);
        try {
      
            try {
                
               
                $list =  $service->categories->listcategories($opt);
                foreach($list as $categorige){
                   
                   // foreach($categorige as $tabcat){
                      
                      $datacat[]= ["categoryId"=>$categorige['name'],
                         "displayName"=>$categorige['displayName']
                   
                     ];
                    // }
                    
                 }
             

                return response()->json([
                    'success' => true,
                    'message' => 'Opération terminer avec succes',
                    'data' => $datacat,
                    'data1' => $categorige,
                    'status' => Response::HTTP_OK
                        ], Response::HTTP_OK);
            } catch (\Google_Service_Exception $e) {
                return array([
                            'success' => false,
                            'message' => "La requête contient un argument invalide",
                            'status' => $e->getCode(),
                            'data' => ''
                        ], $e->getCode());
            }
        } catch (QueryException $ex) {
            return response()->json(
                [
                            'success' => false,
                            'message' => $ex->getMessage(),
                            'status' => 400,
                        ]
            );
        }
    }

    // List adresse associate


    public function associateadresse(Request $request)
    {
        $input=[];
        $messages = [
           
            'codepostal.required' => 'Vérifier Votre code posta!',
            'locality.required' => 'Vérifier Votre  Ville!',
            'codepostal.required' => 'Vérifier Votre code postal!',
            'adresse.required' => 'Vérifier Votre Adresse!',
            'pays.required' => 'Vérifier Votre pays!',
        ];

        $input = [
            'codepostal' => $request['codepostal'],
            'locality' => $request['ville'],
            'codepostal' => $request['codepostal'],
            'adresse' => $request['adresse'],
            'pays' => $request['pays'],
        ];
        $validator=Validator::make($input,
        [
                'codepostal' => 'min:5|max:6',
                'locality' => 'required|max:20',
                'codepostal' => 'required|max:100',
                'adresse' => 'required|max:100',
                'pays' => 'required|20',
                   ],
        $messages);

        
   /*    if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }*/
        $input = (object) $input;

        try {
        
            $it = 0;
         $adresse = $input->adresse;

            $codepostal = $input->codepostal;
            $pays = $input->pays;
            $locality = $input->locality;
            $client = Helper::googleClient();
            $serviceLocation = new Google\Service\MyBusinessBusinessInformation($client); 
            $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);  
                $list_accounts = $serviceAccount->accounts->listAccounts();
            
                foreach ($list_accounts->accounts as $keyAccount => $account) {
                
                    $accountsList[]=$account;
                }
                $params=["readMask"=>"title,name,metadata","filter"=>"storefrontAddress.postalCode=%22$codepostal%22+AND+storefrontAddress.locality=%22$locality%22"];
                foreach ($accountsList as $keyLocation => $account) {
                
                    do {
                        try {
                        $list_locations = $serviceLocation->accounts_locations->listAccountsLocations($account->name,$params);
                
                        if(isset($list_locations["locations"])){ 
                            $i=0;
                                foreach ($list_locations["locations"] as $value) {
                                    if(isset($value["metadata"]["hasGoogleUpdated"])){ 
                                    if ($value["metadata"]["hasGoogleUpdated"] === true) {
                                        $list[] = array('locationname' => $value['title']
                                        );
                                    }
            
                                    $i++;
                                }
                              
                           
                        }
                        return response()->json([
                            'success' => true,
                            'message' => 'Opération terminer avec succes',
                            'data' => $list,
                            'status' => Response::HTTP_OK
                                ], Response::HTTP_OK);
                    }else{
                                return response()->json([
                                            'success' => true,
                                            'message' => "Aucun fichier de même adresse",
                                            'data' => array(array('locationname' => "Aucun fichier de même adresse")),
                                       
                                            'status' => 200
                                                ], 200);
                                            }
                                
                            
                         } catch (\Google_Service_Exception $e) {
                             return array([
                                         'success' => false,
                                         'message' => "La requête contient un argument invalide",
                                         'status' => $e->getCode(),
                                         'data' => ''
                                     ], $e->getCode());
                         }
                    } while ($nextToken != null);
                }

            
        } catch (\Google_Service_Exception $ex) {
            return response()->json(
                [
                                'success' => false,
                                'message' => $ex->getMessage(),
                                'status' => 400,
                            ]
            );
        }
    }
    
    public function gerefiche(Request $request)
    {
        $searchTerm = $request->searchTerm;
    
   

    
        try {
            $googleLocations= new Google\Service\MyBusinessBusinessInformation\SearchGoogleLocationsRequest(); 
              
            $googleLocations->query = $searchTerm;
         
            try {
            $list=array();
             $client = Helper::googleClient();
             $service = new Google\Service\MyBusinessBusinessInformation($client);
             $tab =$service->googleLocations->search($googleLocations);
         
                if (isset($tab)) {
                    foreach($tab as $rep){
                     //  foreach ($tabgoogle as $rep) {
              $addresse=$rep['location']['storefrontAddress']["addressLines"][0].' ,'.$rep['location']['storefrontAddress']["postalCode"].' '.$rep['location']['storefrontAddress']["locality"];
                                   $list[] = array('locationName' => $rep['location']["title"],
                                   'adresse' => $addresse,
                                       'locationKey' =>$rep['location']["metadata"]["placeId"]);
                              // }
                    }
                } else {
                    $locationKey = '';
                    $adresse = $request->adresse;
                    $locationName = $request->searchTerm;
                    $list[] = array('locationName' => $request->searchTerm,
                        'adresse' => '',
                        'locationKey' => '');
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Opération terminer avec succes',
                    'data' => $list,
                    'status' => Response::HTTP_OK
                        ], Response::HTTP_OK);
              
            } catch (\Google_Service_Exception $e) {
                return array([
                            'success' => false,
                            'message' => "La requête contient un argument invalide",
                            'status' => $e->getCode(),
                            'data' => ''
                        ], $e->getCode());
            }
        } catch (QueryException $ex) {
            return response()->json(
                [
                            'success' => false,
                            'message' => $ex->getMessage(),
                            'status' => 400,
                        ]
            );
        }
    
    }

    public function googlelocation(Request $request)
    {
      
                $searchTerm = $request->locationName;
    
           
                        $googleLocations= new Google\Service\MyBusinessBusinessInformation\SearchGoogleLocationsRequest(); 
              
                        $googleLocations->location = ['title'=>$searchTerm,"metadata"=>array("placeId"=>$request->locationKey)];
                     
                        try {
                        $list=array();
                         $client = Helper::googleClient();
                         $service = new Google\Service\MyBusinessBusinessInformation($client);
                         $tab =$service->googleLocations->search($googleLocations);
                        if ($tab) {
                            foreach ($tab as $acces) {
                           //   foreach($googlecces as $acces){

                                    $list = array('demandeacces' => $acces["requestAdminRightsUri"], 
                                    'mapsurl' => $acces["location"]['metadata']['mapsUri'],
                                        'phoneinter' => $acces["location"]['phoneNumbers']['primaryPhone']
                                       // , 'urlsite' =>$acces["location"]['websiteUri'],
                                    );
                              //  }
                              
                            }
                        }
                 
            return response()->json([
                        'success' => true,
                        'message' => 'Opération terminer avec succes',
                        'data' => $list,
                        'data0'=>$tab,
                        'loca'=>$acces,
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
        } catch (\Google_Service_Exception $e) {
            return response()->json(
                [
                                'success' => false,
                                'message' => json_decode($e->getMessage(), true),
                                'status' => 400
                            ],
                400
            );
          } catch (QueryException $ex) {
            return response()->json(
                [
                            'success' => false,
                            'message' => $ex->getMessage(),
                            'status' => 400,
                        ]
            );
        }
    }
   

    public function adressememe(Request $request)
    {
        $mybusinessService = Helper::GMB();

        $accounts = $mybusinessService->accounts;

        $locations = $mybusinessService->accounts_locations;

        $lists = $accounts->listAccounts()->getAccounts();
        $googleLocations = $mybusinessService->googleLocations;
        try {
            $adresse = $request->adresse;
            foreach ($lists as $ac) {
                $locationsList = $locations->listAccountsLocations($ac['name'], array('pageSize' => 10, 'filter' => "address.administrativeArea=%22$add%22"));

                $i = 0;

                $locationsName = array();
                foreach ($locationsList["locations"] as $value) {
                    if (array_key_exists("isVerified", $value["locationState"]) === true) {
                        $locationsName[] = array('locationName' => $value['locationName'], 'adresse' => array(
                                "regionCode" => $value['address']['regionCode'],
                                "languageCode" => $value['address']['languageCode'],
                                "codepostal" => $value['address']['postalCode'],
                                "locality" => $value['address']['locality'],
                                "addressLines" => $value['address']['locality']
                            )
                        );
                    }
                    $i++;
                }
            }
            $Searchlocation = Helper::GMBSearch();

            $response = GooglePlaces::textSearch($request->locationName)->get('results');
            if ($response->count() > 0) {
                foreach ($response as $rep) {
                    $adressearray = array(
                        'addressLines' => $rep["formatted_address"]);

                    $list[] = array('locationname' => $rep["name"],
                        'adresse' => $rep["formatted_address"],
                        'locationKey' => $rep["place_id"]);
                }
            } else {
                $list[] = array('locationname' => $request->locationName,
                    'adresse' => '',
                    'locationKey' => '');
            }

            $placeID = Helper::GMBcreate();

            $placeID->locationName = $request->locationName;

            $placeID->address = $adresse;
            $placeID->locationKey = array('placeId' => $request->placeid);

            $Searchlocation->setLocation($placeID);

            if ($request->placeid) {
                $list1 = $googleLocations->search($Searchlocation, array());

                $locatins = $list1->googleLocations;
            }
            return response()->json([
                        'success' => true,
                        'message' => 'Opération terminer avec succes',
                        'data' => $list,
                        'googlelocation' => $locatins,
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
        } catch (\Google_Service_Exception $e) {
            return response()->json(
                [
                                'success' => false,
                                'message' => $e->getMessage(),
                                'status' => 400
                            ],
                400
            );
        }
    }



    public static function dateToFrench($date)
    {
        $english_days = array('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY');
        $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
        return str_replace($english_days, $french_days, $date);
    }

    public static function dateToAnglash($date)
    {
        $english_days = array('MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY');
        $french_days = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
        return str_replace($french_days, $english_days, $date);
    }

    public function listsupplement(Request $request)
    {
        try {
         
            $fiches = Fiche::find($request->fiche);
            $name = $fiches->locationName;
            $arrayName= explode( '/', $fiches->name);
            $Name= $arrayName[2] . '/' . $arrayName[3];
            $client = Helper::googleClient();
            $service = new Google\Service\MyBusinessBusinessInformation($client); 
            $params=["readMask"=>"moreHours,categories"];
         
            $loc = $service->locations->get($Name,$params);
           
          
            $listsupplementaires = array();
         
            $usedMoreHoursIDs=array();
            $usedMoreHours=array();
            if (isset($loc['moreHours'])) {
                foreach ($loc['moreHours'] as $key => $value) {
                    $usedMoreHoursIDs[]=$value["hoursTypeId"];
                    $usedMoreHours[]=$value;
                }
                foreach ($loc['categories']['primaryCategory']['moreHoursTypes'] as $TypeMoreHours) {
                    if (!in_array($TypeMoreHours["hoursTypeId"], $usedMoreHoursIDs)) {
                        $unusedMorehours[]=$TypeMoreHours;
                    }
                }
            } else {
                $unusedMorehours = $loc['categories']['primaryCategory']['moreHoursTypes'];
            }
 
            return response()->json([
            'success' => true,
            'message' => 'Liste des services spécifiques ou des offres spéciales',
            'data' => $unusedMorehours,
            'details' => MorehoursController::morehours($request->fiche),
          'status' => 200]);
        } catch (\Google_Service_Exception $e) {
            return response()->json(
                [
                                'success' => false,
                                'message' => "La requête contient un argument invalide",
                                'status' => 400
                            ],
                400
            );
        }
    }

    
    public function horaire()
    {
        $step = 30 * 60;   // 30 minutes
        $limit = 24 * 60 * 60;  // 24 heures
        $heure = array();
        $heure = array('24h/24');

        for ($timestamp = 0; $timestamp < $limit; $timestamp += $step) {
            $heure[] = date('H:i', $timestamp);
        }
        return response()->json([
                    'success' => true,
                    'message' => 'Liste horaire ',
                    'data' => $heure,
                    'status' => 200]);
    }


    public  static function ListCategories($searchTerm)
    {
        $opt = array(
            "regionCode" => "FR",
            "languageCode" => "Fr",
            "view" => "FULL",
            'filter'=>'displayName='.$searchTerm
        );
     
         
        
        
        $datacat=array();
        $client = Helper::googleClient();
        $service = new Google\Service\MyBusinessBusinessInformation($client);
            
          
                try {
                    
                   
                    $list =  $service->categories->listcategories($opt);
                    
                    foreach($list as $categorige){
                   
                       // foreach($categorige as $tabcat){
                          
                          $datacat[]= ["categoryId"=>$categorige['name'],
                             "displayName"=>$categorige['displayName']
                       
                         ];
                        // }
                        
                     }
                return $datacat;
            } catch (\Google_Service_Exception $e) {
                return array([
                            'success' => false,
                            'message' => "La requête contient un argument invalide",
                            'status' => $e->getCode(),
                            'data' => ''
                        ], $e->getCode());
            }
        }
}
