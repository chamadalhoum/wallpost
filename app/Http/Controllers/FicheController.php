<?php

namespace App\Http\Controllers;
use App\Models\profilincomplete;
use App\Helper\Helper;
use App\Models\Ficheuser;
use Carbon\Carbon;
use App\Models\Fiche;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\Accountagence;
use App\Models\Attribute;
use App\Models\Attributeshistorique;
use App\Models\Pay;
use App\Models\Categorie;
use App\Models\Ficheshistorique;
use App\Models\Metadata;
use App\Models\State;
use GoogleMyBusinessService;
use Google;
use Exception as GlobalException;

class FicheController extends Controller {

    public $mybusinessService;
    public $placeID;
    public $locations;
    public $accounts;
    public $googleLocations;
    public $lists;
    public $media;
    public $locationas;
    public $mediaphoto;

    public function __construct() {
       /* $this->mybusinessService = Helper::GMB();
        $this->placeID = Helper::GMBcreate();
        $this->accounts = $this->mybusinessService->accounts;
        $this->locations = $this->mybusinessService->accounts_locations;
        $this->googleLocations = $this->mybusinessService->googleLocations;
        $this->lists = $this->accounts->listAccounts()->getAccounts();
        $this->locationas = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_LocationAssociation();
        $this->media = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_MediaItem();
        $this->mediaphoto = $this->mybusinessService->accounts_locations_media;*/
    }

    public function fiche() {

        try {
            
            $fiches = Fiche::leftJoin('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id');
            $u = request('search');

            if ($u) {
                $fiche = $fiches->Where('fiches.locationName', 'LIKE', '%' . $u . '%')
                        ->where('ficheusers.user_id', '=', Auth()->user()->id)
                        ->select(['fiches.locationName', 'fiches.id'])
                        ->get();
                if ($fiches->count() > 0) {
                    return response()->json([
                                'success' => true,
                                'message' => 'Liste de fiches',
                                'data' => $fiche,
                                'status' => 200
                                    ], 200);
                } else {
                    return response()->json([
                                'success' => true,
                                'message' => 'Désole, fiche not found.',
                                'status' => 200
                                    ], 200);
                }
            } else {
                return response()->json([
                            'success' => true,
                            'data' => Fiche::leftJoin('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
                                    ->where('ficheusers.user_id', '=', Auth()->user()->id)
                                    ->select(['fiches.locationName', 'fiches.id'])
                                    ->orderBy('id', 'DESC')->get()
                            ,
                            "message" => 'Operation avec succes',
                            'status' => 200
                                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, fiches not found.',
                        'status' => 400
                            ], 400);
        }
    }


    public function createfiche(Request $request) {
        $arraycat = array();
        $payscode = '';
        $adresse = array(
            "regionCode" => '',
            "languageCode" => '',
            "postalCode" => $request->codepostal,
            "locality" => $request->ville,
            "addressLines" => [$request->adresse]
        );
        $opt = array(
            "regionCode" => "FR",
            "languageCode" => "Fr",
            "searchTerm" => $request->categorie
        );
       
      $list=GoogleController::ListCategories($request->categorie);

        try {
            $pays = Pay::where('pays', $request->pays)->select('alpha2')->get();
            if ($pays->count() > 0) {
                $adresse = array(
                    "regionCode" => $pays[0]['alpha2'],
                    "languageCode" => $pays[0]['alpha2'],
                    "postalCode" => $request->codepostal,
                    "locality" => $request->ville,
                    "addressLines" =>  [$request->adresse]
                );
            $data['address'] = $request->adresse;
            $data['city'] = $request->ville;
            $data['postalCode'] = $request->codepostal;
                $response = \GoogleMaps::load('geocoding')
                        ->setParam(['address' => $request->adresse . $request->codepostal . ' ' . $request->ville, 'region' => $request->pays])
                        ->get('results.geometry.location');
                foreach ($response as $res) {
                    $loction = array('location' => $res[0]['geometry']['location']);
                }
                foreach ($loction as $loc) {
                   
                    $data['latitude'] = $loc['lat'];
                    $data['longitude'] = $loc['lng'];
                }
                $payscode = $pays[0]['alpha2'];
            }
            if (count($list) > 0) {
                $arraycat = array(
                    "displayName" => $list[0]['displayName'],
                    "name" => $list[0]['categoryId']
                   );
                        $categorie["displayName"] = $list[0]['displayName'];
                        $categorie["categorieId"] = $list[0]['categoryId'];
              
            }
           $data['country'] = $request->pays;
            $data['OpenInfo_status'] = 'OPEN';
            $data['state'] = "Validation requise";
            $data['OpenInfo_canreopen'] = 1;
            $data['email'] = $request->email;
            $date['franchises_id'] = '1';
            $data['labels'] =$request->labels;
            $data['websiteUrl'] = $request->websiteUrl;
               
            
            if ($request->phone[0] === '0') {
              $primaryPhone = $request->phone;
            } else {
                $primaryPhone = '0' . $request->phone;
            }
            $client = Helper::googleClient();
            $service = new Google\Service\MyBusinessBusinessInformation($client); 
      
            $Location= new Google\Service\MyBusinessBusinessInformation\Location($client); 
            $Location->name=$request->name;
            $Location->languageCode=$payscode;
            $Location->storeCode=$request->codemagasin;
            $Location->title=$request->locationName;
            $Location->phoneNumbers=['primaryPhone'=>$primaryPhone];
            $Location->categories=['primaryCategory'=>$arraycat];
            $Location->storefrontAddress=$adresse;
            $Location->websiteUri=$request->websiteUrl;
            $tabplace=['title'=>$request->locationName,
        'languageCode'=>$payscode,
           'storeCode'=>$request->codemagasin,
           
           'phoneNumbers'=>['primaryPhone'=>$primaryPhone],
          'categories'=>['primaryCategory'=>$arraycat],
        'storefrontAddress'=>$adresse,
           'websiteUri'=>$request->websiteUrl];
      
            $it = 0;
            try {
                $nameaccount = Accountagence::where("franchise_id", $request->header('franchise'))->first();
             
              $Location= new Google\Service\MyBusinessBusinessInformation\Location($client); 
               // $account->name=$nameaccount->name;
               $Location->languageCode=$payscode;
               $Location->storeCode=$request->codemagasin;
               $Location->title=$request->locationName;
               $Location->phoneNumbers=['primaryPhone'=>$primaryPhone];
               $Location->categories=['primaryCategory'=>$arraycat];
               $Location->storefrontAddress=$adresse;
               $Location->websiteUri=$request->websiteUrl;
               /*   $list_accounts_response = $serviceAccount->accounts->create($account,array());
      
                  var_dump($list_accounts_response);exit;
                  return json_decode($list_accounts_response,1);*/
                $result = $this->createlocation($nameaccount->account, $Location);
               
            
                if ($result['title']) {
                    try{
                       
                        $data['name'] = $result['name'];
                        $data['locationName'] = $result['title'];
                        $data['primaryPhone'] = $result['phoneNumbers']['primaryPhone'];
                        
                        $fichess = Fiche::create($data);
        $fiches = $fichess->id;
        $dates['user_id'] = Auth()->user()->id;
        $dates['franchise_id'] = '1';
        $dates['fiche_id'] = $fiches;
        Ficheuser::create($dates);

        if ($result["categories"]['primaryCategory']) {
            $categorie["type"] = "primaryCategory";
          
            $categorie["user_id"] = Auth()->user()->id;
            $categorie["fiche_id"] = $fiches;
            Categorie::create($categorie);
        }
        $datett['fiche_id'] = $fiches;
    
     //   $datett['canModifyServiceList'] = $result["metadata"]['canModifyServiceList'];
        $datett['canUpdate'] = true;
        $datett['canDelete'] = $result["metadata"]['canDelete'];
        $datett['isDisconnected'] = true;
        State::create($datett);
        $metadata['locationName'] = $result['title'];
        if($fiches){
            $metadata['fiche_id'] = $fiches;
          Metadata::create($metadata);
        
        }

                    }catch(QueryException $e){
                        return response()->json([
                            'success' => false,
                            'message' =>$e->getMessage(),
                            'data' => '',
                            'status' => 400
                                ], 400);
                         

                    }
                    return response()->json([
                                'success' => true,
                                'message' => 'Opération terminer avec succes',
                                'data' => $result,
                                'status' => Response::HTTP_OK
                                    ], Response::HTTP_OK);
                }
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
                                'status' => 400
                            ], 400);
        }
    }

    


    public function show(Fiche $fiche) {
        $otherlocation = array();
        if ($fiche->otheradress) {
            foreach (json_decode($fiche->otheradress) as $other) {
                $otherlocation[] = ['ligne_value' => $other];
            }
        }
        $addresse = ['adresse' => $fiche->address,
            'codepostal' => $fiche->postalCode,
            "pays" => $fiche->country,
            'ville' => $fiche->city,
            'location' => ["lat" => $fiche->latitude,
                "lng" => $fiche->longitude],
            "listligne" => $otherlocation];

        return response()->json([
                    'success' => true,
                    'message' => 'Adresse',
                    'data' => $addresse,
                    'status' => 200
                        ], 200);
    }

    public function update(Request $request, Fiche $fiche) {
        try {

            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
                    ]);
            }
         
            $input = [];
             
          
            $input = [
                'description'=>$request->description,
                'locationName'=>$request->locationName,
                'storeCode'=>$request->storeCode,
                'websiteUrl'=>$request->websiteUrl,
                'adwPhone'=>$request->adwPhone,
                'listlibelle'=>$request->listlibelle,
                'OpenInfo_opening_date'=>$request->OpenInfo_opening_date,
                'numerotel'=>$request->numerotel,
                'OpenInfo_status'=>$request->OpenInfo_status,
                'etatwebsite'=>$request->etatwebsite,
                'email'=>$request->email,
                'latitude'=>$request->latitude,
                'adresse'=>$request->adresse,
                'code_postale'=>$request->code_postale,
                'ville'=>$request->ville,
                'pays'=>$request->pays,
                'listligne'=>$request->listligne];
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'locationName'=>'Vérifier Votre Nom!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
                'storeCode'=>'Vérifier Votre Code de magasin!',
                'websiteUrl.regex'=>'Vérifier Votre lien saisi est incorrect!',
                'websiteUrl.required_if' => 'Saisissez une URL valide (ex. : www.example.com)',
                'adwPhone'=>'Vérifier Votre Numéro de téléphone incorrect!',
                'listlibelle'=>'Vérifier Votre Libellés!',
                'OpenInfo_opening_date'=>"Vérifier Votre date de création!",
                'numerotel'=>"Vérifier Votre Numéro de téléphone!",
                'OpenInfo_status'=>"Verifier votre établissement"
            ];
         
       
    
            $validator = Validator::make($input, [
                        [
                          
                            "franchises_id" => 'exists:franchises,id',
                            "locationName"=>"max:200",
                            "storeCode"=>"max:45",
                            "websiteUrl"=>"nullable|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/",
                            "adwPhone"=>"max:14",
                        ], $messages
            ]);

         


            if ($validator->fails()) {
                foreach ($validator->errors()->toArray() as $key => $value) {
                    $message = $value[0];
                }
    

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }
            $input = (object) $input;
            $updateMask = null;
            $client = Helper::googleClient();
            $service = new Google\Service\MyBusinessBusinessInformation($client); 
           // var_dump($service);exit;
            $Location= new Google\Service\MyBusinessBusinessInformation\Location($client); 
            if ($validator->passes()) {
                try {
                    $locationName = $fiche->name;
                    if ($input->description) {
                        $fiche->description = $input->description;
                        $Location->profile=["description" => $input->description];
                        $updateMask .= "profile";
                        $dataprofil['description']=true;
                    }else if ($input->description ==='') {
                        $fiche->description = null;
                        $Location->profile=["description" => null];
                        $updateMask .= "profile";
                        $dataprofil['description']=false;
                    }
                    if ($input->locationName) {
                        $fiche->locationName = $input->locationName;
                        $updateMask .= ",title";
                        $Location->title=$input->locationName;
                        $dataprofil['locationName']=true;
                        $dataprofil['title']=$input->locationName;
                    } else {
                        $Location->title=$input->locationName;
                      // $this->placeID->locationName = $fiche->locationName;

                    }
                    if ($request->state) {
                        $fiche->state = $request->state;
                    }
                    if ($request->placeId) {
                        $fiche->placeId = $request->placeId;
                        $updateMask .= ",placeId";
                    }
                    if ($request->url_map) {
                        $fiche->url_map = $request->url_map;
                    }
                    if ($input->storeCode ) {

                        $fiche->storeCode = $input->storeCode;
                        $Location->storeCode=$input->storeCode;
                        $updateMask .= ",storeCode";
                        $dataprofil['storeCode']=true;
                      
                    }else if($input->storeCode ===''){
                        $fiche->storeCode = NULL;
                        $Location->storeCode=$input->storeCode;
                        $updateMask .= ",storeCode";
                        $dataprofil['storeCode']=false;
                    }
                    $phone = null;
                    if ($input->numerotel != Null) {
                        $datatableau= ['primaryPhone'=>''];
                        $fiche->primaryPhone = null;
                        $numerotel = $input->numerotel;
                        $dataprofil['primaryPhone']=false;
                        $i = 0;
                        foreach ($numerotel as $numero) {
                           
                      
                            foreach ($numero as $num) {
                                if (isset($num['nationalNumber'])){
                                    if ($num['nationalNumber'][0] === "0") {
                                        $numtel = $num['nationalNumber'];
                                    } else {
                                        $numtel = '0' . $num['nationalNumber'];
                                    }
                                    if ($i == 0) {
                                        $fiche->primaryPhone = $numtel;
                                        $datatableau=['primaryPhone'=>$numtel];
                                     
                                        $updateMask .= ",phoneNumbers";
                                        $fiche->additionalPhones = $phone;
                                    } else {
    
                                        $phone[] = $numtel;
                                    }
                                    $i++;
                                }
                                
                            }
                        }
                       if ($phone) {
                            if (count($phone) > 0) {
                                $updateMask .= ",phoneNumbers";
                                $fiche->additionalPhones = $phone;
                           
                               $datatab =  collect($datatableau)->put('additionalPhones',[$phone])->all();
                       
                            }
                            $Location->phoneNumbers=$datatab;
                        }
                        else {
                            $updateMask .= ",phoneNumbers";
                            $Location->phoneNumbers=$datatableau;
                            }
                     
                    }
                    if ($input->adwPhone != Null) {
                        $adwPhone = $input->adwPhone;

                        if ($adwPhone['nationalNumber'][0] === '0') {
                            $fiche->adwPhone = $adwPhone['nationalNumber'];
                            $Location->adWordsLocationExtensions=["adPhone" => $adwPhone['nationalNumber']];
                        } else {
                            $fiche->adwPhone = '0' . $adwPhone['nationalNumber'];
                            $Location->adWordsLocationExtensions=["adPhone" => '0' .$adwPhone['nationalNumber']];
                        }
                        $updateMask .= ",adWordsLocationExtensions";
                        $dataprofil['adwPhone']=true;
                    }
                    elseif($input->adwPhone ===''){
                        $fiche->adwPhone = NULL;
                        $Location->adWordsLocationExtensions=["adPhone" =>[]];
                        $updateMask .= ",adWordsLocationExtensions";
                        $dataprofil['adwPhone']=false;
                    }
                    if ($input->websiteUrl != Null) {
                        $fiche->websiteUrl = $input->websiteUrl;
                        $Location->websiteUri=$input->websiteUrl;
                        $updateMask .= ",websiteUri";
                        $dataprofil['websiteUrl']=true;
                    }else if($input->websiteUrl ===''){
                        $fiche->websiteUrl = NULL;
                        $Location->websiteUri=NULL;
                        $updateMask .= ",websiteUri";
                        $dataprofil['websiteUrl']=false;
                    }
                    if ($input->etatwebsite != Null) {
                        $fiche->etatwebsite = $input->etatwebsite;
                    }
                    if ($input->email != Null) {
                        $fiche->email = $input->email;
                    }
                    if ($input->latitude != Null) {
                        $fiche->latitude = $input->latitude;
                      
                    }
                    
                  
                    if ($input->adresse != Null) {
                        $response = \GoogleMaps::load('geocoding')
                                ->setParam(['address' => $input->adresse . $input->code_postale . ' ' . $input->ville, 'region' => $input->pays])
                                ->get('results.geometry.location');
                        foreach ($response as $res) {
                            $location = array('location' => $res[0]['geometry']['location']);
                        }
                        $otheraddresse = array();
                        $updateMask .= "storefrontAddress";
                        $pays = Pay::where('pays', $input->pays)->select('alpha2')->get();
                        $fiche->address = $input->adresse;
                        $addressLines[] = $input->adresse;
                        $fiche->city = $input->ville;
                        $fiche->country = $input->pays;
                        $fiche->postalCode = $input->code_postale;
                        foreach ($input->listligne as $other) {
                            $otheraddresse[] = $other['ligne_value'];
                        }
                        $fiche->otheradress = $otheraddresse;
                        $adresse = array(
                            "regionCode" => $pays[0]['alpha2'],
                            "languageCode" => $pays[0]['alpha2'],
                            "postalCode" => $input->code_postale,
                            "locality" => $input->ville,
                            "addressLines" => array_merge($addressLines, $otheraddresse)
                        );
                        $Location->storefrontAddress=$adresse;
                        $dataprofil['address']=true;
                       
                    }  if ($input->OpenInfo_status != Null) {
                        $fiche->OpenInfo_canreopen = true;
                        $fiche->OpenInfo_status = $input->OpenInfo_status;
                        $Location->openInfo=["status" =>  $input->OpenInfo_status,"canReopen" => true];
                        $updateMask .= "openInfo";
                    }
                    if ($input->OpenInfo_opening_date != Null) {
                        $date = Carbon::parse($request->OpenInfo_opening_date['Annee'] . '-' . $input->OpenInfo_opening_date['Mois']['value'] . '-' . $input->OpenInfo_opening_date['Jours']);
                        $fiche->OpenInfo_opening_date = $date;
                        $fiche->OpenInfo_canreopen = true;
                        $openingDate = ["year" => $input->OpenInfo_opening_date['Annee'],
                            "month" => $input->OpenInfo_opening_date['Mois']['value'],
                            "day" => $input->OpenInfo_opening_date['Jours']];
                    
                            $Location->openInfo=["openingDate" => $openingDate,"status" => "OPEN"];
                        $updateMask .= "openInfo";
                    }
                    if ($request->otheradress) {
                        $fiche->otheradress = $request->otheradress;
                    }
                    if ($request->franchises_id) {
                        $fiche->franchises_id = $request->franchises_id;
                    }
                   
                    if($input->listlibelle || IS_ARRAY($input->listlibelle) ){
                      
                    if(count($input->listlibelle) === 0){
                        $fiche->labels= NULL;
                        $Location->labels=null;
                        $updateMask .= ",labels";
                        $dataprofil['labels']=false;
                    }
                   else {
                     
                     
                            foreach ($input->listlibelle as $labels) {
                           
                                $i = 0;
                                
                                     $label[] = $labels['libelle_value'];
                                     $i++;
                                 
                            }
                             $fiche->labels= '["'. collect($label)->implode('","').'"]';
                             $dates['labels']= '["'. collect($label)->implode('","').'"]';
                             
                             $Location->labels=$label;
                             $updateMask .= ",labels";
                      
                             $dataprofil['labels']=true;
                    }
                }
                    if($request->lien!= Null){
                 $url=null;
                 $attributeId="attributes/url_appointment";
                 $valueType="URL";
                 $data['attributeId'] = "attributes/url_appointment";
                 $data['valueType'] =$valueType;
                 $data['urlValues'] = $request->lien;
                 $data['fiche_id']=$fiche->id;
                 $data['user_id']=Auth()->user()->id;
                 $updateMask .= ',attributes';
                 $attributes[]=['attributeId'=>$attributeId,"valueType" =>$valueType,
                 'urlValues'=>['url'=>$request->lien]];
                 if($request->lien){ 
                    $data['state']='Actif';
                     Attribute::updateOrCreate(['fiche_id' => $fiche->id,'attributeId'=>$attributeId],$data);
                     AttributeController::addatribute($fiche,$list=null);
                     $dataprofil['attributesUrl']=true;
                     profilincomplete::updateOrCreate(['fiche_id'=>$fiche->id],$dataprofil);
                     UserController::totalprofilincomplet($fiche->id);
                    }else if($request->lien ===''){
                     Attribute::WHERE('fiche_id' , $fiche->id)->
                       WHERE('attributeId',"attributes/url_appointment")->delete();
                       AttributeController::addatribute($fiche,'attributes/url_appointment');
                       $dataprofil['attributesUrl']=false;
                       profilincomplete::updateOrCreate(['fiche_id'=>$fiche->id],$dataprofil);
                       UserController::totalprofilincomplet($fiche->id);
                    }
                    }else if($request->lien ===''){
                        Attribute::WHERE('fiche_id' , $fiche->id)->
                          WHERE('attributeId',"attributes/url_appointment")->delete();
                          AttributeController::addatribute($fiche,'attributes/url_appointment');
                          $dataprofil['attributesUrl']=false;
                          profilincomplete::updateOrCreate(['fiche_id'=>$fiche->id],$dataprofil);
                          UserController::totalprofilincomplet($fiche->id);
                       }
                    try {
                        if(!$request->lien  || !$request->lien ==''){
                          $this->patchlocation($locationName,$updateMask,$Location);
                            $fiche->update();
                           // $dataprofil['attributesUrl']=false;
                            profilincomplete::updateOrCreate(['fiche_id'=>$fiche->id],$dataprofil);
                            UserController::totalprofilincomplet($fiche->id);

                            }
                        return response()->json([
                                    'success' => true,
                                    'message' => 'Mise a jour traitée avec succes',
                                    'data' => $fiche,
                                    'status' => 200,
                                 
                                        ], 200);
                    } catch (\Google_Service_Exception $ex) {

                        return response()->json([
                                    'success' => false,
                                    'message' => "La requête contient un argument invalide",
                                    'status' => 400,
                                
                                        ], $ex->getCode()
                        );
                    }
                } catch (QueryException $ex) {
                    return response()->json([
                                'success' => false,
                                'message' => $ex->getMessage(),
                                'status' => 400,
                                    ],$ex->getCode()
                    );
                }
            }
        } catch (Exception $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, fiches not found.',
                        'status' => 400
                            ], 400);
        }
    }

    public function destroy(Fiche $fiche) {

        try {
            $fiche->delete();
            return response()->json([
                        'success' => true,
                        'message' => 'Supprimer avec succées',
                        'status' => 200
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                        'success' => false,
                        'message' => 'Fiche could not be deleted',
                        'status' => 500,
                            ], 500);
        }
    }

    // ajouter site Suggestions
    public function updateficheurlsite(Request $request){
        try {
           
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
                    ]);
            }
         
            $input = [];
            $input = [
               
                'listfiche'=>$request->listfiche['listsitefiche'],
                ];
                
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'websiteUrl.regex' => 'Vérifier Votre lien saisi est incorrect!',
            ];
            $validator = Validator::make($input, [
                        [
                            "franchises_id" => 'exists:franchises,id',
                            "listfiche.websiteUrl"=>"nullable|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/",
                        ], $messages
            ]);

            if ($validator->fails()) {
                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }
            $input = (object) $input;
           
            if ($validator->passes()) {
               
                try {
                    $client = Helper::googleClient();
                  
                    $Location= new Google\Service\MyBusinessBusinessInformation\Location($client); 
                    $message=null;
                    foreach($input->listfiche as $fiches){
                        $updateMask = null;
                       if($fiches['websiteUrl']!=""){
                        try{
                       $id= $fiches['id'];
                        $fiche = Fiche::find($id);
                            $fiche->websiteUrl = $fiches['websiteUrl'];
                            $Location->websiteUri=$fiches['websiteUrl'];
                            $updateMask .= "websiteUri";
                            $message="Site ajouter avec succes";
                            $fiche->update();
                            
                            $this->patchlocation($fiche->name,$updateMask,$Location);
                            $dataprofil['websiteUrl']=true;
                            profilincomplete::updateOrCreate(['fiche_id'=>$fiche->id],$dataprofil);
                            UserController::totalprofilincomplet($fiche->id);
                        } catch (\Google_Service_Exception $ex) {
                            return response()->json([
                                        'success' => false,
                                        'message' => "La requête contient un argument invalide",
                                        'status' => 400,
                                            ], $ex->getCode()
                            );
                        }
                    }
                }  
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [],
                    'status' => Response::HTTP_OK
                        ], Response::HTTP_OK);
                   
                } catch (QueryException $ex) {
                    return response()->json([
                                'success' => false,
                                'message' => $ex->getMessage(),
                                'status' => 400,
                                    ],$ex->getCode()
                    );
                }
            }
        } catch (GlobalException $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, fiches not found.',
                        'status' => 400
                            ], 400);
        } 
    }
    public function updatefichephone(Request $request){
        try {
           if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
                    ]);
            }
         
            $input = [];
             
          
            $input = [
               
                'listfiche'=>$request->listfiche['listnumerofiche'],
                ];
        

            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
               
                'listfiche.websiteUrl'=>'Vérifier Votre lien saisi est incorrect!',
                
            ];
         
    
            $validator = Validator::make($input, [
                        [
                          
                            "franchises_id" => 'exists:franchises,id',
                          
                            "listfiche.numerotel"=>"max:14",
                          
                        ], $messages
            ]);

            if ($validator->fails()) {

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
                            }
            $input = (object) $input;

            $updateMask = null;
            if ($validator->passes()) {
               
                try {
                    $message=null;
                
                
                    foreach($input->listfiche as $fiches){
                       if($fiches['numerotel']){
                        try{
                            $client = Helper::googleClient();
                  
                            $Location= new Google\Service\MyBusinessBusinessInformation\Location($client); 
                       $id= $fiches['id'];
                        $fiche = Fiche::find($id);
                            $message="Numéro de téléphone ajouter avec succes";
                            $numtel=$fiches['numerotel'];
                           
                                    if ($numtel[0] === '0') {
                                        $numtel = $numtel;
                                    } else {
                                        $numtel = '0' . $numtel;
                                    }
                                        $fiche->primaryPhone = $numtel;
                                        $updateMask = "phoneNumbers";
                                        $Location->phoneNumbers=['primaryPhone'=>$numtel];
                                        $this->patchlocation($fiche->name,$updateMask,$Location);
                                        $fiche->update();
                                        $dataprofil['primaryPhone']=true;
                                      
                                        profilincomplete::updateOrCreate(['fiche_id'=>$fiche->id],$dataprofil);
                                        UserController::totalprofilincomplet($fiche->id);
                              
                        
                        } catch (\Google_Service_Exception $ex) {
    
                            return response()->json([
                                        'success' => false,
                                        'message' => "La requête contient un argument invalide",
                                        'status' => 400,
                                            ], $ex->getCode()
                            );
                        }
                    }
                }  
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [],
                    'status' => Response::HTTP_OK
                        ], Response::HTTP_OK);
                   
                
            }catch (GlobalException $ex) {
                return response()->json([
                            'success' => false,
                            'message' => 'Désole, fiches not found.',
                            'status' => 400
                                ], 400);
            } 
        }
        }catch (GlobalException $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, fiches not found.',
                        'status' => 400
                            ], 400);
        } 
    }
    public static function patchlocation($location,$updateMask,$Location){
     
       
        $arrayName= explode( '/', $location );
      $Name= $arrayName[2] . '/' . $arrayName[3];
          try {
            $client = Helper::googleClient();
            $service = new Google\Service\MyBusinessBusinessInformation($client); 
            $postBody=['updateMask'=>$updateMask ,'validateOnly' => false];
           $list_accounts_response = $service->locations->patch($Name,$Location,$postBody);
                return $list_accounts_response;
                } catch (\Google_Service_Exception $ex) {
    
                    return response()->json([
                                'success' => false,
                                'message' => "La requête contient un argument invalide",
                                'status' => 400,
                                    ], $ex->getCode()
                    );
                }
    }
 

    public static function createlocation($Name,$Location){
      
      
          try {
          $client = Helper::googleClient();
          $service = new Google\Service\MyBusinessBusinessInformation($client);
          $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);  
         
            $list_accounts_response = $service->accounts_locations->create($Name,$Location);

           
            return $list_accounts_response;
             

                } catch (\Google_Service_Exception $ex) {
    
                    return response()->json([
                                'success' => false,
                                'message' => "La requête contient un argument invalide",
                                'status' => 400,
                                    ], $ex->getCode()
                    );
                }
    }
   
}
