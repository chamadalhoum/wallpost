<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Etiquetgroupe;
use App\Models\Fichehour;
use App\Models\FicheHourhistorique;
use App\Models\Ficheuser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JWTAuth;
use App\Models\Fiche;
use App\Models\User;
use App\Models\Franchise;
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
use App\Models\Photo;
use App\Models\Photohistorie;
use App\Models\Service;
use App\Models\Servicearea;
use App\Models\Serviceareashistorique;
use App\Models\State;
use Exception as GlobalException;
use phpDocumentor\Reflection\PseudoTypes\True_;

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
        $this->mybusinessService = Helper::GMB();
        $this->placeID = Helper::GMBcreate();
        $this->accounts = $this->mybusinessService->accounts;
        $this->locations = $this->mybusinessService->accounts_locations;
        $this->googleLocations = $this->mybusinessService->googleLocations;
        $this->lists = $this->accounts->listAccounts()->getAccounts();
        $this->locationas = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_LocationAssociation();
        $this->media = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_MediaItem();
        $this->mediaphoto = $this->mybusinessService->accounts_locations_media;
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
        $serviceArea = array();
        $attributes = array();
        $searchTerm = $request->categorie;
        $latlng = array(
            "latitude" => '',
            "longitude" => ''
        );
        $payscode = '';
        $adresse = array(
            "regionCode" => '',
            "languageCode" => '',
            "postalCode" => $request->codepostal,
            "locality" => $request->ville,
            "addressLines" => $request->adresse
        );
        $opt = array(
            "regionCode" => "FR",
            "languageCode" => "Fr",
            "searchTerm" => $request->categorie
        );
        $cats = $this->mybusinessService->categories;
        $list = $cats->listCategories($opt)->getCategories();
        try {
            $pays = Pay::where('pays', $request->pays)->select('alpha2')->get();
            if ($pays->count() > 0) {
                $adresse = array(
                    "regionCode" => $pays[0]['alpha2'],
                    "languageCode" => $pays[0]['alpha2'],
                    "postalCode" => $request->codepostal,
                    "locality" => $request->ville,
                    "addressLines" => $request->adresse
                );
                $response = \GoogleMaps::load('geocoding')
                        ->setParam(['address' => $request->adresse . $request->codepostal . ' ' . $request->ville, 'region' => $request->pays])
                        ->get('results.geometry.location');


                foreach ($response as $res) {

                    $loction = array('location' => $res[0]['geometry']['location']);
                }
                foreach ($loction as $loc) {
                    $latlng = array(
                        "latitude" => $loc['lat'],
                        "longitude" => $loc['lng']
                    );
                }
                
                $payscode = $pays[0]['alpha2'];
            }
            if (count($list) > 0) {
                $arraycat = array(
                    "displayName" => $list[0]['displayName'],
                    "categoryId" => $list[0]['categoryId'],
                    "serviceTypes" => array("serviceTypeId" => '',
                        "displayName" => $list[0]['displayName']),
                    "moreHoursTypes" => array(
                        "hoursTypeId" => '',
                        "localizedDisplayName" => ''));

                $attributes = array("attributeId" => $request->attributeId,
                    "valueType" => $request->valueType,
                    "values" => $request->values,
                    "unsetValues" =>
                    $request->unsetValues
                    ,
                    "urlValues" => array('url' => $request->url)
                );
            }
            $this->placeID->labels = $request->labels;
            $this->placeID->name = $request->name;
            $this->placeID->languageCode = $payscode;
            $this->placeID->storeCode = $request->codemagasin;
            $this->placeID->locationName = $request->locationName;
            if ($request->phone[0] === '0') {
                $this->placeID->primaryPhone = $request->phone;
            } else {
                $this->placeID->primaryPhone = '0' . $request->phone;
            }
            $this->placeID->additionalPhones = $request->addphone;
            $this->placeID->address = $adresse;
            $this->placeID->primaryCategory = $arraycat;
            $this->placeID->additionalCategories = $request->additionalCategories;
            $this->placeID->websiteUrl = $request->websiteUrl;
            $this->placeID->regularHours = $request->regularHours;
            $this->placeID->specialHours = $request->specialHours;
            $this->placeID->locationKey = $request->locationKey;
            $this->placeID->adWordsLocationExtensions = $request->adWordsLocationExtensions;
            $this->placeID->latlng = $latlng;
            $this->placeID->openInfo = $request->openInfo;
            $this->placeID->locationState = $request->locationState;
            $this->placeID->metadata = $request->metadata;
            $this->placeID->priceLists = $request->priceLists;
            $this->placeID->profile = $request->profil;
            $this->placeID->relationshipData = $request->relationshipData;
            $this->placeID->moreHours = $request->moreHours;
            $this->placeID->metadata = $request->metadata;
            $this->placeID->priceLists = $request->priceLists;
            $email = $request->email;
            $it = 0;
            try {
                $nameaccount = Accountagence::leftjoin('ficheusers','ficheusers.franchise_id','=','accountagences.franchise_id')->where("ficheusers.user_id", Auth()->user()->id)->first();
                $result = $this->mybusinessService->accounts_locations->create($nameaccount->account, $this->placeID, array('requestId' => md5(microtime())));
                if ($result['locationName']) {
                    try{
                        $this->ficheceer($this->placeID, $result, $email, $request->pays);

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

    public function ficheceer($value, $result, $email, $pays) {
    
        $data['name'] = $result['name'];
        $data['locationName'] = $result['locationName'];
        $data['primaryPhone'] = $result['primaryPhone'];
        $data['websiteUrl'] = $result['websiteUrl'];
        $data['additionalPhones'] = json_encode($result['additionalPhones']);
        $data['address'] = $value['address']['addressLines'];
        $data['city'] = $value['address']['locality'];
        $data['country'] = $pays;
        $data['postalCode'] = $value['address']['postalCode'];
        $data['latitude'] = $value['latlng']['latitude'];
        $data['longitude'] = $value['latlng']['longitude'];
        $data['OpenInfo_status'] = 'OPEN';
        $data['state'] = "Validation requise";
        $data['OpenInfo_canreopen'] = 1;
        $data['email'] = $email;
        $date['franchises_id'] = '1';
        $data['labels'] = json_encode($result['labels']);
        $fichess = Fiche::create($data);
        $fiches = $fichess->id;
        $dates['user_id'] = Auth()->user()->id;
        $dates['franchise_id'] = '1';
        $dates['fiche_id'] = $fiches;
        Ficheuser::create($dates);

        if ($value["primaryCategory"]) {
            $categorie["type"] = "primaryCategory";
            $categorie["displayName"] = $value["primaryCategory"]["displayName"];
            $categorie["categorieId"] = $value["primaryCategory"]["categoryId"];
            $categorie["user_id"] = Auth()->user()->id;
            $categorie["fiche_id"] = $fiches;
            Categorie::create($categorie);
        }
        $datett['fiche_id'] = $fiches;
        $datett['canModifyServiceList'] = true;
        $datett['canUpdate'] = true;
        $datett['canDelete'] = true;
        $datett['isDisconnected'] = true;
        State::create($datett);
    if($result['locationName']){
        $metadata['locationName'] = $result['locationName'];
        }
        if($fiches){
            $metadata['fiche_id'] = $fiches;
          Metadata::create($metadata);
        
        }
    
        
    return $fichess;

       
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
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
            ];
         
    
            $validator = Validator::make($request->all(), [
                        [
                            "franchises_id" => 'exists:franchises,id',
                        ], $messages
            ]);

            if ($validator->fails()) {

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }

            $updateMask = null;
            if ($validator->passes()) {
                try {
                    $locationName = $fiche->name;

                    if ($request->description) {
                        $fiche->description = $request->description;
                        $this->placeID->profile = array("description" => $request->description);
                        $updateMask .= "profile";
                    }
                    if ($request->locationName) {
                        $fiche->locationName = $request->locationName;
                        $this->placeID->locationName = $request->locationName;
                        $updateMask .= ",locationName";
                    } else {
                        $this->placeID->locationName = $fiche->locationName;
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
                    if ($request->storeCode) {

                        $fiche->storeCode = $request->storeCode;
                        $this->placeID->storeCode = $request->storeCode;
                        $updateMask .= ",storeCode";
                    }
                    $phone = null;
                    if ($request->numerotel != Null) {
                        $numerotel = $request->numerotel;

                        $i = 0;
                        foreach ($numerotel as $numero) {
                           
                            foreach ($numero as $num) {
                                if ($num['nationalNumber'][0] === '0') {
                                    $numtel = $num['nationalNumber'];
                                } else {
                                    $numtel = '0' . $num['nationalNumber'];
                                }
                                if ($i == 0) {
                                    $fiche->primaryPhone = $numtel;
                                    $this->placeID->primaryPhone = $numtel;
                                    $updateMask .= ",primaryPhone";
                                    $fiche->additionalPhones = $phone;
                                } else {

                                    $phone[] = $numtel;
                                }
                                $i++;
                            }
                        }
                        if ($phone) {
                           
                            if (count($phone) > 0) {
                                $updateMask .= ",additionalPhones";
                                $fiche->additionalPhones = $phone;
                                $this->placeID->additionalPhones = $phone;
                               
                            }
                        }else {
                            
                                $updateMask .= ",additionalPhones";
                                $this->placeID->additionalPhones = [];
                            }
                        
                    }
                    if ($request->adwPhone != Null) {
                        $adwPhone = $request->adwPhone;

                        if ($adwPhone['nationalNumber'][0] === '0') {
                            $fiche->adwPhone = $adwPhone['nationalNumber'];
                            $this->placeID->adWordsLocationExtensions = array("adPhone" => $adwPhone['nationalNumber']);
                        } else {
                            $fiche->adwPhone = '0' . $adwPhone['nationalNumber'];
                            $this->placeID->adWordsLocationExtensions = array("adPhone" => '0' . $adwPhone['nationalNumber']);
                        }
                        $updateMask .= ",adWordsLocationExtensions";
                    }
                    if ($request->websiteUrl != Null) {
                        $fiche->websiteUrl = $request->websiteUrl;
                        $this->placeID->websiteUrl = $request->websiteUrl;
                        $updateMask .= ",websiteUrl";
                    }
                    if ($request->etatwebsite != Null) {
                        $fiche->etatwebsite = $request->etatwebsite;
                    }
                    if ($request->email != Null) {
                        $fiche->email = $request->email;
                    }
                    if ($request->latitude != Null) {
                        $fiche->latitude = $request->latitude;
                    }
                    if ($request->OpenInfo_status != Null) {
                        $fiche->OpenInfo_canreopen = true;
                        $fiche->OpenInfo_status = $request->OpenInfo_status;
                        $this->placeID->openInfo = array("status" => $request->OpenInfo_status, "canReopen" => true);
                        $updateMask .= "openInfo";
                    }
                    if ($request->adresse != Null) {
                        $response = \GoogleMaps::load('geocoding')
                                ->setParam(['address' => $request->adresse . $request->code_postale . ' ' . $request->ville, 'region' => $request->pays])
                                ->get('results.geometry.location');
                        foreach ($response as $res) {
                            $location = array('location' => $res[0]['geometry']['location']);
                        }
                        foreach ($location as $loc) {
                            $latlng = ["latitude" => $loc['lat'],
                                "longitude" => $loc['lng']
                            ];
                        }
                        $otheraddresse = array();
                        $updateMask .= "address";
                        $pays = Pay::where('pays', $request->pays)->select('alpha2')->get();
                        $fiche->address = $request->adresse;
                        $addressLines[] = $request->adresse;
                        $fiche->city = $request->ville;
                        $fiche->country = $request->pays;
                        $fiche->postalCode = $request->code_postale;
                        foreach ($request->listligne as $other) {
                            $otheraddresse[] = $other['ligne_value'];
                        }
                        $fiche->otheradress = $otheraddresse;
                        $adresse = array(
                            "regionCode" => $pays[0]['alpha2'],
                            "languageCode" => $pays[0]['alpha2'],
                            "postalCode" => $request->code_postale,
                            "locality" => $request->ville,
                            "addressLines" => array_merge($addressLines, $otheraddresse)
                        );
                        $this->placeID->address = $adresse;
                    }
                    if ($request->OpenInfo_opening_date != Null) {
                        $date = Carbon::parse($request->OpenInfo_opening_date['Annee'] . '-' . $request->OpenInfo_opening_date['Mois']['value'] . '-' . $request->OpenInfo_opening_date['Jours']);
                        $fiche->OpenInfo_opening_date = $date;
                        $fiche->OpenInfo_canreopen = true;
                        $openingDate = ["year" => $request->OpenInfo_opening_date['Annee'],
                            "month" => $request->OpenInfo_opening_date['Mois']['value'],
                            "day" => $request->OpenInfo_opening_date['Jours']];
                        $this->placeID->openInfo = array("openingDate" => $openingDate, "canReopen" => true);

                        $updateMask .= "openInfo";
                    }
                    if ($request->otheradress) {
                        $fiche->otheradress = $request->otheradress;
                    }
                    if ($request->franchises_id) {
                        $fiche->franchises_id = $request->franchises_id;
                    }
                    if ($request->listlibelle) {
                        foreach ($request->listlibelle as $labels) {
                            $i = 0;
                            foreach ($labels as $num) {
                                $label[] = $num;
                                $i++;
                            }
                        }
                        $fiche->labels = $label;
                        $this->placeID->labels = $label;
                        $updateMask .= ",labels";
                    }
                    try {
                        $this->locations->patch($locationName,
                                $this->placeID, array('updateMask' => $updateMask,
                            'validateOnly' => false, 'attributeMask' => $updateMask));
                        $fiche->update();
                        //Ficheshistorique::create($fiche);
                        return response()->json([
                                    'success' => true,
                                    'message' => 'Mise a jour traitée avec succes',
                                    'data' => $fiche,
                                    'status' => Response::HTTP_OK
                                        ], Response::HTTP_OK);
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

    public function notificationfichebyid(Request $request) {
        $fiche_id=$request->fiche_id;
        $primaryPhone=0;
        $websiteUrl=0;
        $locationName=0;
        $OpenInfo_opening_date=0;
        $additionalPhones=0;
        $address=0;
        $email=0;
        $prise_rendez=0;
        $attribute=0;
        $zone=0;
        $nbnotif=0;
        $categorie=0;
        $fiches = FicheHourhistorique::where('state', 'inactif')->get();
        $totalfiche = fiche::leftJoin('fichehours', 'fichehours.fiche_id', '=', 'fiches.id')->
                leftJoin('fiche_hourhistoriques', 'fiche_hourhistoriques.fichehours_id', '=', 'fichehours.id')
                ->select(DB::raw('count(*) as fiche_count,fichehours.fiche_id'))
                ->where('fiche_hourhistoriques.state', 'inactif')
               // ->where('fiche_hourhistoriques.user_id', auth()->user()->id)
                ->groupBy('fichehours.fiche_id')
                ->get();
                $fiche=Fiche::find($fiche_id);
                $fichehistorique=Ficheshistorique::where("fiche_id",$fiche_id)->first();
                if($fiche->primaryPhone != $fichehistorique->primaryPhone){
                    $primaryPhone=$primaryPhone++;
                    $nbnotif=$nbnotif++;
                }
                if($fiche->websiteUrl != $fichehistorique->websiteUrl){
                    $websiteUrl=$websiteUrl++;
                    $nbnotif=$nbnotif++;
                }
                if($fiche->locationName != $fichehistorique->locationName){
                    $locationName=$locationName++;
                    $nbnotif=$nbnotif++;
                }
                if($fiche->OpenInfo_opening_date != $fichehistorique->OpenInfo_opening_date){
                    $OpenInfo_opening_date=$OpenInfo_opening_date++;
                    $nbnotif=$nbnotif++;
                }
                if($fiche->additionalPhones != $fichehistorique->additionalPhones){
                    $additionalPhones=$additionalPhones++;
                    $nbnotif=$nbnotif++;
                }
                if($fiche->address != $fichehistorique->address){
                    $address=$address++;
                    $nbnotif=$nbnotif++;
                }
                if($fiche->email != $fichehistorique->email){
                    $email=$email++;
                    $nbnotif=$nbnotif++;
                }
                $attribute=Attribute::where('fiche_id',$fiche_id)->get();
                
                foreach($attribute as $attri){

                    $historiqueattribute=Attributeshistorique::where('fiche_id',$fiche_id)->where('attribute_id',$attri['id'])->first();
                    if($historiqueattribute->urlValues !=$attri['urlValues']){
                        $prise_rendez=$prise_rendez++;
                        $nbnotif=$nbnotif++;
                    }
                    if($historiqueattribute->attributeId !=$attri['attributeId']){
                        $attribute=$attribute++;
                        $nbnotif=$nbnotif++;
                    }

                }
                $zonedever=Servicearea::where('fiche_id',$fiche_id)->get();
                foreach($zonedever as $der){
                  $hist=  Serviceareashistorique::where('serviceareas_id',$der['id'])->first();
                  if($hist->name !=$der['name']){
                    $zone=$zone++;
                    $nbnotif=$nbnotif++;
                }
                }

$histcat=Categorie::join('categorieshistoriques','categories.id','=',"categorieshistoriques.categorie_id")
->where('categories.fiche_id',$fiche_id)
->where('categorieshistoriques.categorieId','!=','categories.categorieId')->exists();
                if($histcat){
                    $categorie=$categorie++;
                    $nbnotif=$nbnotif++;
                }
               

        $totalcategorie = Fiche::leftJoin('fichehours', 'fichehours.fiche_id', '=', 'fiches.id')->
                leftJoin('fiche_hourhistoriques', 'fiche_hourhistoriques.fichehours_id', '=', 'fichehours.id')
                ->select(DB::raw('count(*) as fiche_count,fichehours.fiche_id,fiches.locationName'))
                ->where('fiche_hourhistoriques.state', 'inactif')
              //  ->orwhere('fiche_hourhistoriques.user_id', auth()->user()->id)
                ->groupBy('fichehours.fiche_id', 'fiches.locationName')
                ->orderBy('fiche_hourhistoriques.id', 'DESC')
                ->get();
        $i = 0;
       
     /*   foreach ($totalcategorie as $total) {
            $fichehours = Fiche::leftjoin('fichehours', 'fichehours.fiche_id', 'fiches.id')->where('fichehours.fiche_id', $total->fiche_id)->select('fichehours.type', 'fichehours.open_time', 'fichehours.close_time', 'fiches.primaryPhone')->get();
            foreach ($fichehours as $hours) {
                $tab[] = array("open_time" => Carbon::parse($hours->open_time)->format('H:i'), "close_time" => Carbon::parse($hours->close_time)->format('H:i'));
            }
            $totals[] = array('fiche_count' => $total->fiche_count, 'fiche_id' => $total->fiche_id,
                'locationName' => $total->locationName, "details" => $tab, "primaryPhone" => $fichehours[0]['primaryPhone']);
            $i++;
        }*/

       
            return response()->json([
                        'success' => true,
                        'message' => 'Liste notification fiche',
                        'totalfiche' => $totalfiche->count(),
                        'data' => $nbnotif,
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
      

    
    }
    public function notificationfiche() {
     //   $fiche_id=1;
        $fiches = FicheHourhistorique::where('state', 'inactif')->get();
        $totalfiche = fiche::leftJoin('fichehours', 'fichehours.fiche_id', '=', 'fiches.id')->
                leftJoin('fiche_hourhistoriques', 'fiche_hourhistoriques.fichehours_id', '=', 'fichehours.id')
                ->select(DB::raw('count(*) as fiche_count,fichehours.fiche_id'))
                ->where('fiche_hourhistoriques.state', 'inactif')
               // ->where('fiche_hourhistoriques.user_id', auth()->user()->id)
                ->groupBy('fichehours.fiche_id')
                ->get();
        $totalcategorie = Fiche::leftJoin('fichehours', 'fichehours.fiche_id', '=', 'fiches.id')->
                leftJoin('fiche_hourhistoriques', 'fiche_hourhistoriques.fichehours_id', '=', 'fichehours.id')
                ->select(DB::raw('count(*) as fiche_count,fichehours.fiche_id,fiches.locationName'))
                ->where('fiche_hourhistoriques.state', 'inactif')
              //  ->orwhere('fiche_hourhistoriques.user_id', auth()->user()->id)
                ->groupBy('fichehours.fiche_id', 'fiches.locationName')
                ->orderBy('fiche_hourhistoriques.id', 'DESC')
                ->get();

              //  $fiche=Fiche::find(1);

        $i = 0;
        foreach ($totalcategorie as $total) {
            $fichehours = Fiche::leftjoin('fichehours', 'fichehours.fiche_id', 'fiches.id')->where('fichehours.fiche_id', $total->fiche_id)->select('fichehours.type', 'fichehours.open_time', 'fichehours.close_time', 'fiches.primaryPhone')->get();
            foreach ($fichehours as $hours) {
                $tab[] = array("open_time" => Carbon::parse($hours->open_time)->format('H:i'), "close_time" => Carbon::parse($hours->close_time)->format('H:i'));
            }
            
            $totals[] = array('fiche_count' => $total->fiche_count, 'fiche_id' => $total->fiche_id,
                'locationName' => $total->locationName, "details" => $tab, "primaryPhone" => $fichehours[0]['primaryPhone']);
            $i++;
        }

        if ($fiches->count() > 0) {
            return response()->json([
                        'success' => true,
                        'message' => 'Liste notification fiche',
                        'totalfiche' => $totalfiche->count(),
                        'data' => $totals,
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
        }

        return response()->json([
                    'success' => true,
                    'message' => 'Désole,  aucun fiche .',
                    'status' => Response::HTTP_OK
                ], Response::HTTP_OK);
    }


    public function codegoogle(Request $request) {

        $data = $request->all();

        $fiche = Fiche::find($request->idfiche);
        $currentDateTime = Carbon::now();
        try {
            if ($data['codegoogle']) {
                // $fiche['state'] = "En attente d'examen";
                $fiche['state'] = "personnaliser";

                $fiche['storeCode'] = $request->codegoogle;
                $fiche['closedatestrCode'] = Carbon::now()->addDays(7);
                $fiche->update();
                return response()->json([
                            'success' => true,
                            'message' => 'Mise a jour traitée avec succes',
                            'data' => $fiche,
                            'status' => Response::HTTP_OK
                                ], Response::HTTP_OK);
            } else {
                return response()->json([
                            'success' => false,
                            'message' => 'Code invalide',
                            'status' => 404
                                ], Response::HTTP_OK);
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

 

    public function ficheadministre(Request $request) {
    //   $franchises= $request->franchise_id;
       $franchises= 1;
        try {
            $listfiche = Fiche::
                    leftjoin('metadatas', 'metadatas.fiche_id', 'fiches.id')
                    ->select('metadatas.metadatasId', 'metadatas.mapsUrl',
                            'metadatas.newReviewUrl', 'fiches.locationName',
                            'fiches.websiteUrl', 'fiches.id')
                            ->where('fiches.franchises_id',$franchises)
                    ->leftJoin('states', 'states.fiche_id', '=', 'fiches.id')
                    ->Where('states.isVerified', 1)
                   ->orWhere('states.isPublished', 1)
                    ->get();
           
            $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                            ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                            ->whereNull('etiquetgroupes.fiche_id')
                            //->where('ficheusers.user_id', '=', Auth()->user()->id)
                            ->get(['etiquettes.name as Nom_etiquette',
                                'etiquettes.state',
                                'etiquetgroupes.etiquette_id',
                                'groupes.id as idgroupe',
                                'groupes.name as Name_groupe',
                                'etiquetgroupes.id as etiquettegroupe',
                                'etiquetgroupes.state as etiquettegroupestate',
                                'etiquetgroupes.fiche_id',
                                'groupes.color',
                            ])->toArray();
            return response()->json([
                        'success' => true,
                        'message' => 'Mise a jour traitée avec succes',
                        'data' => $listfiche,
                        'datafiltre' => GroupeController::group_byfiche('Name_groupe', $filtrefiche, $id = null),
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
     public function filtre(Request $request){
        try {
            $etiquette=array();
            
$tab=["listGroupe"=>
[[
    "id_groupe"=> 1,
    "Name_groupe"=> "LOCALISATION",
    "couleur_groupe"=> "#0081c7",
    "ettiquettes"=>[[
    "Nom_etiquette"=>"Ile-de-France",
    "Value_etiquette"=> 2,
    "status"=> true,
    "etiquettegroupe"=> 23
 ],[
    "Nom_etiquette"=> "Nord",
    "Value_etiquette"=> 1,
    "status"=> true,
    "etiquettegroupe"=> 73
 ],[
    "Nom_etiquette"=> "Sud",
    "Value_etiquette"=> 1,
    "status"=> true,
    "etiquettegroupe"=>76
 ],[
    "Nom_etiquette"=> "dddd",
    "Value_etiquette"=>3,
    "status"=> true,
    "etiquettegroupe"=>83
 ]
    ],
    "etatActivat"=> false
 ],[
    "id_groupe"=> 2,
    "Name_groupe"=> "SPÉCIFICITÉS",
    "couleur_groupe"=> "#cc0070",
    "ettiquettes"=>[[
    "Nom_etiquette"=> "Balai d'essue galce",
    "Value_etiquette"=>1,
    "status"=> true,
    "etiquettegroupe"=> 74
    ]
    ],
    "etatActivat"=>false
 ]
]];
$searchfiltre=$request->searchFiche;

$isGoogleUpdated=$request->Modification;
                    foreach ($tab['listGroupe'] as $groupe) {
                        $tabs = ['groupe_id' => $groupe['id_groupe']];
                        $i = 0;
                        foreach ($groupe['ettiquettes'] as $etiquette) {
                            if (array_key_exists('status', $etiquette)) {
                            if ($etiquette['status'] === true) {
                                $etiq = Etiquetgroupe::find($etiquette['etiquettegroupe']);
                                $etiquette[] = $etiq->etiquette_id;
                          }
                        }
                            $i++;
                        }
                    }
               
                    $fiches = Etiquetgroupe::leftjoin('fiches', 'etiquetgroupes.fiche_id', '=', 'fiches.id')
                                    ->whereNotNull('etiquetgroupes.fiche_id')
                                    ->where('etiquetgroupes.state', 1)
                                    ->when($etiquette !="",function ($query) use($etiquette){
                                        $query->whereIN('etiquetgroupes.etiquette_id', $etiquette);
                                        })
                    
                                    ->leftjoin('metadatas', 'metadatas.fiche_id', 'fiches.id')
                                   ->select('metadatas.metadatasId', 'metadatas.mapsUrl',
                                            'metadatas.newReviewUrl', 'fiches.locationName',
                                            'fiches.websiteUrl', 'fiches.id')
                                    ->leftJoin('states', 'states.fiche_id', '=', 'fiches.id')
                                    ->when($searchfiltre!="",function ($query) use($searchfiltre) {
                                        $query->where('fiches.locationName', 'LIKE', '%'.$searchfiltre.'%');
                                        })
                                        ->when($isGoogleUpdated!= "",function ($query) use($isGoogleUpdated){
                                            $query->Where('states.isGoogleUpdated', $isGoogleUpdated);
                                            })
                               
                             ->Where('states.isPublished', 1)
                            ->groupBy('fiches.id', 'fiches.name','metadatas.metadatasId', 'metadatas.mapsUrl',
                                    'metadatas.newReviewUrl', 'fiches.locationName',
                                    'fiches.websiteUrl')->get();
            return response()->json([
                        'success' => true,
                        'message' => 'Liste filtre',
                        'data' => $fiches,
                      
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
    public function updatefichephoto(Request $request){
        try {
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
            ];

            $validator = Validator::make($request->all(), [
                        [
                            "franchises_id" => 'exists:franchises,id',
                        ], $messages
            ]);

            if ($validator->fails()) {

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }

            $updateMask = null;
            if ($validator->passes()) {
               
                try {
                    $message=null;
                  $listfiche=$request->listfiche;
               
                    foreach($listfiche as $fiches){
                     
                       if($fiches['status']){
                        try{
                       $id= $fiches['id'];

                        $fiche = Fiche::find($id);
                      
                        $file=$request->photo;
              
                              $message="Photo logo ajouter avec succes";
                            $image_64 = $file;
    
                            $time = time();
                            $new_data = explode(";", $image_64);
                            $type = $new_data[0];
                            $extension = explode('/', $type);
                            $datap = explode(",", $new_data[1]);
                            $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];
                            Storage::disk('public')->put($imageName, base64_decode(str_replace("%2B", "+", $datap[1])));
    
                            $data['file'] = $imageName;
                       
                            $this->media->setMediaFormat("PHOTO");
                            $this->locationas->setCategory("LOGO");
                            $this->media->setLocationAssociation($this->locationas);
                            $this->media->setSourceUrl(\Illuminate\Support\Facades\URL::to('/') . "/app/public/photos/" . $imageName);
                    
                      
                        $result = $this->mediaphoto->create($fiche->name, $this->media)->getGoogleUrl();
                       
    
                        Storage::disk('public')->delete(\Illuminate\Support\Facades\URL::to('/') . "/app/public/photos/" . $imageName);
                        
                        $data['file'] = $result;
                        $data['user_id'] = Auth()->user()->id;
                        $data['fiche_id'] = $id;
                        $fiche->logo= $result;
                        $fiche->update();
                        $photo = Photo::create($data);
                        $dataF['photo_id'] = $photo->id;
                        $dataF['modifType'] = 'You have created photo';
                        $dataF['newContent'] = $photo;
                        $dataF['state'] = 'inactif';
                        $dataF['user_id'] = $photo->user_id;
                        Photohistorie::create($dataF);
                            
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
    public function updateficherendezvous(Request $request){
        $messages = [
            'franchises_id' => 'Vérifier Votre franchises!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
        ];
        $input = [
            'franchises_id' => $request->franchises_id,
           
        ];
        $validator = Validator::make($input,
                        [
                            "franchises_id" => 'exists:franchises,id',
                        ], $messages
        );
        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422, ],
                            422);
        }
            $updateMask = null;
            if ($validator->passes()) {
               
                try {
                    $message=null;
                    $listfiche=$request->listfiche['listfiche'];
               
                    
                    foreach($listfiche as $fiches){
                        
                       if($fiches['urlvalues']){
                        try{
                       $id= $fiches['id'];

                        $fiche = Fiche::find($id);
                      
                            $attributeId="url_appointment";
                            $valueType="URL";
                            $urlValues=$fiches['urlvalues'];

                            $message="Liens pour prise rendez-vous avec succes";
                       
                           $attributes = ['attributeId' =>$attributeId,
                            "valueType" =>$valueType,
                            'urlValues'=>['url'=>$fiches['urlvalues']]];
                            $data['attributeId'] = "url_appointment";
                            $data['valueType'] =$valueType;
                            $data['urlValues'] = $fiches['urlvalues'];
                            $data['fiche_id']=$fiche->id;
                            $data['user_id']=Auth()->user()->id;
                         
                            $atts= Attribute::create($data);
                            $data['attribute_id']=$atts->id;
                            $data['state']='Inactif';
                            Attributeshistorique::create($data);
                            $updateMask = 'attributes';
                            $this->placeID->attributes = $attributes;
                            $fiches = Fiche::find($request->fiche_id);
                            $this->placeID->locationName = $fiche->locationName;
                         
                            $result= $this->locations->patch($fiche->name,
                            $this->placeID, array('updateMask' => $updateMask,
                        'validateOnly' => false, 'attributeMask' => $updateMask));
               
    
                            
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
        
    }
    public function updateficheurlsite(Request $request){
        try {
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
            ];

            $validator = Validator::make($request->all(), [
                        [
                            "franchises_id" => 'exists:franchises,id',
                        ], $messages
            ]);

            if ($validator->fails()) {

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }

            $updateMask = null;
            if ($validator->passes()) {
               
                try {
                    $message=null;
                  $listfiche=$request->listfiche['listsitefiche'];
                
                    foreach($listfiche as $fiches){
                     
                       if($fiches['websiteUrl']){
                        try{
                       $id= $fiches['id'];

                        $fiche = Fiche::find($id);
                            $fiche->websiteUrl = $fiches['websiteUrl'];
                            $this->placeID->websiteUrl = $fiches['websiteUrl'];
                            $updateMask .= "websiteUrl"; 
                            $message="Site ajouter avec succes";
                            $fiche->update();
                            $result= $this->locations->patch($fiche->name,
                            $this->placeID, array('updateMask' => $updateMask,
                        'validateOnly' => false, 'attributeMask' => $updateMask));
                     
                
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
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
            ];

            $validator = Validator::make($request->all(), [
                        [
                            "franchises_id" => 'exists:franchises,id',
                        ], $messages
            ]);

            if ($validator->fails()) {

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }

            $updateMask = null;
            if ($validator->passes()) {
               
                try {
                    $message=null;
                  $listfiche=$request->listfiche['listnumerofiche'];
                
                    foreach($listfiche as $fiches){
                       if($fiches['numerotel']){
                        try{
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
                                        $this->placeID->primaryPhone = $numtel;
                                        $updateMask .= "primaryPhone";
                                        $result= $this->locations->patch($fiche->name,
                                        $this->placeID, array('updateMask' => $updateMask,
                                    'validateOnly' => false, 'attributeMask' => $updateMask));
                                    $fiche->update();
                        
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
    public function updateficheservice(Request $request){
        try {
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
            ];

            $validator = Validator::make($request->all(), [
                        [
                            "franchises_id" => 'exists:franchises,id',
                        ], $messages
            ]);

            if ($validator->fails()) {

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }

            $updateMask = null;
            if ($validator->passes()) {
               
                try {
                    $message="Service ajouter avec succes";
                //  $listfiche=$request->listfiche;
                  $listfiche=$request->listfiche;
                  $listServices=$request->listServices;
               
                    foreach($listfiche as $fiches){
                        
                       if($fiches['status']){
                        try{
                       $id= $fiches['id'];

                        $fiche = Fiche::find($id);
                      
                   
                    $result=array();
                 
                
                   // $this->placeID->locationName = $fiche->locationName;
                   
                    $i = 0;
                    foreach ($listServices as $list) {
                       
                       
                        if($list['status']){
                            
                          
                             $categorieexit = Categorie::where('fiche_id', $fiche->id)
                                 ->where('categorieId',  $list['categorieid'])
                                 ->exists();
                              
                             if ($categorieexit) {

                                $tabcategorie = Categorie::where('fiche_id', $fiche->id)
                                ->where('categorieId',$list['categorieid'])->first();
                              
                              
                               //  $categorie = $categorieexit[0];
                            //    $idsup[]= $categorieexit[0]->categorieId;
                              
                                
                             
                             } else {
                              
                             $datacat=['type' => 'additionalCategories','displayName'=>$list['displayNamecateg'],'categorieId' =>$list['categorieid'],
                                 'user_id' => Auth()->user()->id,'fiche_id'=>$fiche->id];
                                 $CatSup= array("displayName" =>$list['displayNamecateg'],
                        "categoryId" => $list['categorieid']);
                              
                                 $updateMask = 'additionalCategories';
                                 $idsupt[]= $list['categorieid'];
                                 $this->placeID->additionalCategories =$CatSup;
                              
                                 

                                 $result = $this->locations->patch($fiche->name,
                                 $this->placeID, array('updateMask' => $updateMask,
                             'validateOnly' => false, 'attributeMask' => $updateMask));
                           
                                 $tabcategorie = Categorie::create($datacat);
                             
                             }
                            
                         
                            
                             //  
                            
                              
                         try {
                       
                       
                         /*    if($idsupt){
                             array_push($idsup,$idsupt);
                             $idsup=array_values($idsup);
                             }  */
                          
                          /*             
                              if(count($idsup)>0){
                               
                                 Categorie::where('fiche_id', $fiche->id)
                                 ->where('type','!=','primaryCategory')
                                 ->whereNotIn('categorieId',$idsup)->delete();
                            
                             }*/
                            
                             $message="Service ajouter avec succes";
                             
                             $Verifservice=Service::where('categorie_id',$tabcategorie->id)->where('serviceId',$list['serviceId'])->exists();
                      if(!$Verifservice){
                       
                        $data['serviceId'] = $list['serviceId'];
                        $data['displayName'] = $list['name'];
                        $data['user_id'] =Auth()->user()->id;
                        $data['categorie_id'] = $tabcategorie->id;
                       
                      Service::create($data);
                         }
                         $servicetab[] = array('categoryId' => $tabcategorie->categorieId,
                         'displayName' => $list['name'],
                         'description' => '',
                         'serviceTypeId'=>$list['serviceId'],
                        'price' => array("currencyCode" => "EUR",
                         "units" => "",
                          "nanos" => ""
                 ));
               
                            
                             if(Service::Where('categorie_id',$tabcategorie->id)->exists()){
                                 $servicetabs[] = CategoriesController::listServices($tabcategorie->id,$tabcategorie->categorieId);
                              
                             }
                           ServiceController::updateservice($servicetab, $fiche->name);
                        
                           $i++;
                         }catch (\Google_Service_Exception $ex) {
             
                                     return response()->json([
                                                 'success' => false,
                                                 'message' => "La requête contient un argument invalidess",
                                                 'status' => 400,
                                                     ]
                                     );
                                 }
                 
                    }
                  
                   
                }
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
    public function updatefichehoraire(Request $request){
        try {
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
            ];

            $validator = Validator::make($request->all(), [
                        [
                            "franchises_id" => 'exists:franchises,id',
                        ], $messages
            ]);

            if ($validator->fails()) {

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }

            $updateMask = null;
            if ($validator->passes()) {
               
                try {
                    $message=null;
             
                         $listfiche=$request->listfiche;
               
                  $listfichelisthoraire=$request->horaire;
                    foreach($listfiche as $fiches){
                        
                       if($fiches['status']){
                        try{
                       $id= $fiches['id'];

                        $fiche = Fiche::find($id);
                      
                    $message ='Horiare ajouter avec succes';
                    $updateMask = 'regularHours';
                    $i = 0;
                       $periods=array();
                       $fichehours=array();
                    foreach ($listfichelisthoraire['Listhoraire'] as $horaire) {
                     
                        $data['open_date'] = $horaire['jours'];
                        $data['close_date'] = $horaire['jours'];
    
                        $data['fiche_id'] = $fiche->id;
                        $data['user_id'] = Auth()->user()->id;
                        $more = Fichehour::where('open_date', $horaire['jours'])
                            ->where('close_date', $horaire['jours'])
                            ->where('fiche_id', $fiche->id)
                            ->where('user_id',Auth()->user()->id)->get();
                        foreach ($more as $morehou) {
                            $delt = Fichehour::find($morehou['id']);
                            $delt->delete();
    
                        }
                        if ($horaire['etat'] === 'true' || $horaire['etat'] === true) {
                            foreach ($horaire['horaire'] as $hor) {
                            
                                $periods[] = array('openDay' => FichehourController::dateToAnglash($horaire['jours']),
                                    "openTime" => Carbon::parse($hor['heurdebut'])->format('H:i'),
                                    'closeDay' => FichehourController::dateToAnglash($horaire['jours']),
                                    "closeTime" => Carbon::parse($hor['heurfin'])->format('H:i'));
                                $data['type'] = "true";
                                $data['open_time'] = $hor['heurdebut'];
                                $data['close_time'] = $hor['heurfin'];
                                $fichehours[] = $data;
                               
                            }
    
                        } 
                     
                        
                        $i++;
                    } 
                
                   $this->placeID->regularHours = array('periods' => $periods);
                 
                    $this->placeID->locationName = $fiche->locationName;
                
                   Fichehour::insert($fichehours);

                    $this->locations->patch($fiche->name,
                   $this->placeID, array('updateMask' => $updateMask,
               'validateOnly' => false, 'attributeMask' => $updateMask));
              
               
                            
                            
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
    public function updatefichehorairexecep(Request $request){
        try {
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
            ];

            $validator = Validator::make($request->all(), [
                        [
                            "franchises_id" => 'exists:franchises,id',
                        ], $messages
            ]);

            if ($validator->fails()) {

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }

            $updateMask = null;
            if ($validator->passes()) {
               
                try {
                    $message=null;
                  $listfiche=$request->listfiche;
                  $horaireexp=$request->Listhoraireexexceptionnels['Listhoraire'];
                
                    foreach($listfiche as $fiches){
                     
                       if($fiches['status']){
                        try{
                       $id= $fiches['id'];

                        $fiche = Fiche::find($id);
                      
                
                    $message ='Horiare exceptionnels  ajouter avec succes';
                    $updateMask = 'specialHours';
                    $i = 0;
                    $periodsexp=array();
                       $fichehours=array();
                     
                    foreach ($horaireexp as $horaire) {
                       
                           
                        $data['specialhours_start_date'] = $horaire['date'];
                        $data['specialhours_end_date'] = $horaire['date'];
                        $dates= ParamaterController::listeJourferiesbydate($horaire['date']);
                        $message='';
                        if(!empty($dates)){
                         foreach($dates as $dat){
                             $message=$dat['nom_jour_ferie'];
                            }
                          
                        }
                        $data['fiche_id'] = $fiche->id;
                        $data['user_id'] = Auth()->user()->id;
                        $more = Fichehour::where('specialhours_start_date', $horaire['date'])
                            ->where('specialhours_end_date', $horaire['date'])
                            ->where('fiche_id', $fiche->id)
                            ->where('user_id',Auth()->user()->id)->get();
                        foreach ($more as $morehou) {
                            $delt = Fichehour::find($morehou['id']);
                            $delt->delete();
    
                        }
                        if ($horaire['etat'] =='true') {
                            foreach ($horaire['horaire'] as $hor) {
                              
                                $specialperiod= Helper::SpecialHourPeriod();
                                $specialperiod->setStartDate($this->specialHourPeriod($horaire['date'], Helper::DateAction()));
                              
                                $specialperiod->setEndDate($this->specialHourPeriod($horaire['date'], Helper::DateAction()));
                              // $specialperiod->setIsClosed($horaire['isClosed']);
                               if($hor['heurdebut']){
                                $specialperiod->setOpenTime($hor['heurdebut']);
                               }
                                if($hor['heurfin']){
                                    $specialperiod->setCloseTime($hor['heurfin']);
                                }
                               
                                $periodsexp[] =$specialperiod;

                                $data['type'] = "true";
                                $data['specialhours_open_time'] = $hor['heurdebut'];
                                $data['specialhours_close_time'] = $hor['heurfin'];
                               $data['new_content'] = $message;
                                $fichehours[] = $data;
                            }
    
                        } 
                       
                        $i++;
                    }
                       
                   $this->placeID->specialHours = array('specialHourPeriods' => $periodsexp);
        
                    $this->placeID->locationName = $fiche->locationName;
                   Fichehour::insert($fichehours);
                   $result= $this->locations->patch($fiche->name,
                   $this->placeID, array('updateMask' => $updateMask,
               'validateOnly' => false, 'attributeMask' => $updateMask));
              

                            
                            
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
    
   
    
    public function specialHourPeriod($date, $datedebut) {
        $datedebut->setDay(Carbon::parse($date)->translatedFormat('j'));
        $datedebut->setYear(Carbon::parse($date)->translatedFormat('Y'));
        $datedebut->setMonth(Carbon::parse($date)->Format('m'));
        return($datedebut);
    }

}
