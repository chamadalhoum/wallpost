<?php

namespace App\Http\Controllers;

use App\Models\Paramater;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helper\Helper;
use App\Models\Etiquetgroupe;
use App\Models\Fichehour;
use App\Models\FicheHourhistorique;
use App\Models\Ficheuser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JWTAuth;
use App\Models\Fiche;
use App\Models\User;
use App\Models\Franchise;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\Accountagence;
use App\Models\Attribute;
use App\Models\Attributeshistorique;
use App\Models\Pay;
use App\Models\Categorie;
use App\Models\Categorieshistorique;
use App\Models\Ficheshistorique;
use App\Models\Iconfiche;
use App\Models\Metadata;
use App\Models\Notification;
use App\Models\Photo;
use App\Models\Photohistorie;
use App\Models\Service;
use App\Models\Servicearea;
use App\Models\Serviceareashistorique;
use App\Models\Serviceshistorique;
use App\Models\State;
use Exception as GlobalException;
use Google\Service\ServiceControl;
use phpDocumentor\Reflection\PseudoTypes\True_;

class ParamaterController extends Controller
{


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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   public static function  dimanche_paques($annee)
    {
        return date("Y-m-d", easter_date($annee));
    }
    public static function vendredi_saint($annee)
    {
        $dimanche_paques = self::dimanche_paques($annee);
        return date("Y-m-d", strtotime("$dimanche_paques -2 day"));
    }
    public static  function lundi_paques($annee)
    {
        $dimanche_paques = self::dimanche_paques($annee);
        return date("Y-m-d", strtotime("$dimanche_paques +1 day"));
    }
    public static function jeudi_ascension($annee)
    {
        $dimanche_paques = self::dimanche_paques($annee);
        return date("Y-m-d", strtotime("$dimanche_paques +39 day"));
    }
    public static  function  lundi_pentecote($annee)
    {
        $dimanche_paques = self::dimanche_paques($annee);
        return date("Y-m-d", strtotime("$dimanche_paques +50 day"));
    }
    
    
   public static function  jours_feries($annee, $alsacemoselle=false)
    {
        $jours_feries = array
        (   ["date"=>self::dimanche_paques($annee),'nom_jour_ferie'=>"Dimanche de Pâques"]
        ,   ["date"=>self::lundi_paques($annee),'nom_jour_ferie'=>"Lundi de Pâques"]
        ,  ["date"=>  self::jeudi_ascension($annee),'nom_jour_ferie'=>"Ascension"]
        ,  ["date"=> self::lundi_pentecote($annee),'nom_jour_ferie'=>"Lundi de Pentecôte"],
            ["date"=>"$annee-01-01",'nom_jour_ferie'=>"Jour de l'an"]
        ,    ["date"=>"$annee-05-01" ,'nom_jour_ferie'=>"Fête du travail"]
        ,    ["date"=>"$annee-05-08" ,'nom_jour_ferie'=>"Armistice 1945"]
        ,    ["date"=>"$annee-05-15",'nom_jour_ferie'=>"Assomption"]
        ,    ["date"=>"$annee-07-14",'nom_jour_ferie'=>"Fête Nationale"]
        ,    ["date"=>"$annee-11-11" ,'nom_jour_ferie'=>"Armistice 1918"]
        ,    ["date"=>"$annee-11-01" ,'nom_jour_ferie'=> "Toussaint"]
        ,    ["date"=>"$annee-12-24" ,'nom_jour_ferie'=> "Réveillon de Noël"]
        ,    ["date"=>"$annee-12-25" ,'nom_jour_ferie'=>  "Noël"]
        ,    ["date"=>"$annee-12-31" ,'nom_jour_ferie'=>  "Réveillon de la Saint-Sylvestre"]
        );
        if($alsacemoselle)
        {
            $jours_feries[] = "$annee-12-26";
            $jours_feries[] = self::vendredi_saint($annee);
        }
        sort($jours_feries);
        return $jours_feries;
    }
    function est_ferie($jour, $alsacemoselle=false)
    {
        $jour = date("Y-m-d", strtotime($jour));
        $annee = substr($jour, 0, 4);
        return in_array($jour, $this->jours_feries($annee, $alsacemoselle));
    }

     // Liste de jour feries 
    public  static function listeJourferiesbydate($date){
        
        $annee=Carbon::createFromFormat('Y-m-d',$date)->format('Y');
        
        $list= ParamaterController::jours_feries($annee);
       $itemCollection = collect($list);
       $filtered = $itemCollection->filter(function ($item) use ($date) {
           return stripos($item['date'], $date) !== false;
       });
      return $filtered->all();
       
    }
    public function listeJourferies(Request $request){
        // $listfiche=$request->listfiche;
        try{
         $horaireexp=$request->Listhoraireexexceptionnels['Listhoraire'];
       $i=0;
           foreach ($horaireexp as $horaire) {
            
            
             
     
               $dates= ParamaterController::listeJourferiesbydate($horaire['date']);
               $message='';
               if(!empty($dates)){
                   foreach($dates as $dat){
                    $message=$dat['nom_jour_ferie'];
                   }
                  
               }
               $htt[]= array('date' => $horaire['date'],'etat'=>$horaire['etat'],'nom_jour_ferie'=>$message,"horaire"=>$horaire['horaire']);
 
            
              
               $i++;
           }
              
           return response()->json([
             'success' => true,
 
             'message' => 'Supprimer avec succées',
             'data'=>$htt,
             'status' => 200,
         ],200);
                   
                   
               } catch (\Google_Service_Exception $ex) {
 
                   return response()->json([
                               'success' => false,
                               'message' => "La requête contient un argument invalide",
                               'status' => 400,
                                   ], $ex->getCode()
                   );
               }
           
 
     }
 

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Paramater  $paramater
     * @return \Illuminate\Http\Response
     */
    public function show(Paramater $paramater)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Paramater  $paramater
     * @return \Illuminate\Http\Response
     */
    public function edit(Paramater $paramater)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Paramater  $paramater
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Paramater $paramater)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Paramater  $paramater
     * @return \Illuminate\Http\Response
     */
    public function destroy(Paramater $paramater)
    {
        //
    }
    public function validenotifs(Request $request){
        $fiche_id=$request->fiche_id;
        $typenotifs=$request->typesnotifs;
      $updateMask='';
        //$categorie=Categorie::where('fiche_id',$fiche_id)->first();
        $fichehistorique=Ficheshistorique::where('fiche_id',$fiche_id)->first();
        $fiche=Fiche::find($fiche_id);
        if($typenotifs== "codemagasin"){
          $fiche->storeCode=$fichehistorique->storeCode;
          $this->placeID->storeCode="$fichehistorique->storeCode";
          $updateMask="storeCode";
        }
        if ($typenotifs== "description") {
          $fiche->description = $fichehistorique->description;
          $this->placeID->profile = array("description" => $fichehistorique->description);
          $updateMask = "profile";
      }
      if ($typenotifs== "locationName") {
          $fiche->locationName = $fichehistorique->locationName;
          $this->placeID->locationName = $fichehistorique->locationName;
          $updateMask = "locationName";
      }
      if($typenotifs== "horaire"){
    
     $updateMaskhours = 'regularHours';
  
       $periods=array();
       $fichehours=array();
   
     
     Fichehour::where('fiche_id', $fiche_id)->whereNull('specialhours_start_date')->delete();
       
    

 
 
 
   $fichehours=FicheHourhistorique::where('fiche_id', $fiche_id)->whereNull('specialhours_start_date')->get();
    foreach($fichehours as $fichehou){
        $periods[] = array('openDay' => FichehourController::dateToAnglash($fichehou->open_date),
        "openTime" => Carbon::parse($fichehou->open_time)->format('H:i'),
        'closeDay' => FichehourController::dateToAnglash($fichehou->close_date),
        "closeTime" => Carbon::parse($fichehou->close_time)->format('H:i'));
        $fichehour=Fichehour::create($fichehou);
        $fichehou['fichehours_id']=$fichehour->id;
        $fichehou['state']='Inactif';
        FicheHourhistorique::updateorcreate($fichehou);

    }
   // $this->placeID->locationName = $fichehistorique->locationName;
    $this->placeID->regularHours = array('periods' => $periods);
    $this->locations->patch($fiche->name,$this->placeID->regularHours, array('updateMask' => $updateMaskhours,
    'validateOnly' => true, 'attributeMask' => $updateMaskhours));
            
        
      }
     
      if ($typenotifs== "telephone") {
                      $fiche->primaryPhone = $fichehistorique->primaryPhone;
                      $this->placeID->primaryPhone = $fichehistorique->primaryPhone;
                      $updateMask = "primaryPhone";
                      if($fichehistorique->additionalPhones){
                          $fiche->additionalPhones = $fichehistorique->additionalPhones;
                          $updateMask .= ",additionalPhones";
                          $this->placeID->additionalPhones = $fichehistorique->additionalPhones;
                      }else {
                          $updateMask .= ",additionalPhones";
                          $this->placeID->additionalPhones = [];
                      }
                     
          }
       
        
      if ($typenotifs== "adwPhone") {
              $fiche->adwPhone =$fichehistorique->adwPhone;
              $this->placeID->adWordsLocationExtensions = array("adPhone" =>$fichehistorique->adwPhone);
          
          $updateMask = "adWordsLocationExtensions";
      }
      if ($typenotifs== "websiteUrl") {
          $fiche->websiteUrl = $fichehistorique->websiteUrl;
          $this->placeID->websiteUrl = $fichehistorique->websiteUrl;
          $updateMask = "websiteUrl";
      }
     
      if ($typenotifs== "email") {
          $fiche->email = $fichehistorique->email;
      }
    
      if ($typenotifs== "OpenInfo_status") {
          $fiche->OpenInfo_canreopen = true;
          $dates['OpenInfo_canreopen']= true;
          $fiche->OpenInfo_status = $request->OpenInfo_status;
          $dates['OpenInfo_status'] = $request->OpenInfo_status;
          $this->placeID->openInfo = array("status" => $request->OpenInfo_status, "canReopen" => true);
          $updateMask = "openInfo";
      }
      if ($typenotifs== "lienprend") {
          $prend=Attributeshistorique::where('fiche_id',$fiche->id)->where('attributeId','url_appointment')->where('valueType','URL')->first();
   
          $atts=  Attribute::updateOrCreate(['fiche_id'=> $fiche->id,'attributeId'=>'url_appointment','valueType'=>'URL'],[
                  'urlValues'=>$prend->urlValues   
              ]);
             $prend->attribute_id=$atts->id;
              $prend->state='Actif';
              $prend->update();
             
          $updateMask = 'attributes';
          $this->placeID->attributes = ['attributeId'=>'url_appointment',"valueType" =>'URL','urlValues'=>['url'=>$prend->urlValues]];
      
      }
      if($typenotifs== "service"){
         Service::join('categories','categories.id','=','services.categorie_id')
          ->where('categories.fiche_id',$fiche_id)->delete(); 
          $tabcategorie=  Serviceshistorique::join('categories','categories.id','=','serviceshistoriques.categorie_id')
           ->select('categories.*','serviceshistoriques.serviceId','serviceshistoriques.displayName'
           ,'serviceshistoriques.user_id'
           ,'serviceshistoriques.description'
           ,'serviceshistoriques.state'
           ,'serviceshistoriques.prix'
           ,'serviceshistoriques.typeservice')
           ->where('categories.fiche_id',$fiche_id)->get();
           foreach($tabcategorie as $categorie){
             
               Service::updateOrCreate(['categorie_id' => $categorie->id,
               'displayName'=>$categorie->displayName,'serviceId' => $categorie->serviceId], 
              ['user_id'=>$categorie->user_id
              ,'description'=>$categorie->description
              ,'state'=>$categorie->state
              ,'prix'=>$categorie->prix
              ,'typeservice'=>$categorie->typeservice
          ]);
              Serviceshistorique::updateOrCreate(['categorie_id' => $categorie->id,
              'displayName'=>$categorie->displayName,'serviceId' => $categorie->serviceId], 
             ['user_id'=>$categorie->user_id
             ,'description'=>$categorie->description
             ,'state'=>$categorie->state
             ,'prix'=>$categorie->prix
             ,'typeservice'=>$categorie->typeservice
         ]);
        
         $tabrest[]= array('categoryId' => $categorie->categoryId,
         'displayName' => $categorie->displayName,
         'description' =>$categorie->description,
         'serviceTypeId'=> $categorie->serviceId,
          'price' => array("currencyCode" => "EUR",
          "units" => "",
          "nanos" => ""
     )); 
         
         
      }
     
  ServiceController::updateservice($tabrest, $fiche->name);
  return response()->json([
      'success' => true,
      'message' => $typenotifs.'Modifier avec succes',
      'data' => [],
      'status' => Response::HTTP_OK
          ], Response::HTTP_OK);
      }
      
     if ($typenotifs=="address") {
  
          $updateMask=null;
          $otheraddresse = array();
          $updateMask ="address";
          $pays = Pay::where('pays', $fichehistorique->country)->select('alpha2')->get();
          $fiche->address = $fichehistorique->address;
          $addressLines[]=$fichehistorique->address;
          $fiche->city = $fichehistorique->city;
          $fiche->country = $fichehistorique->country;
          $fiche->postalCode = $fichehistorique->postalCode;
          $fiche->otheradress = $fichehistorique->otheradress;
          $otheraddresse[]= $fichehistorique->otheradress;
          $adresse = array(
              "regionCode" => $pays[0]['alpha2'],
              "languageCode" => $pays[0]['alpha2'],
              "postalCode" => $fichehistorique->postalCode,
              "locality" => $fichehistorique->city,
              "addressLines" => array_merge($addressLines,$otheraddresse)
          );
         // $this->placeID->address= $adresse;
      }
     if ($typenotifs== "ouverture") {
          $fiche->OpenInfo_opening_date = $fichehistorique->OpenInfo_opening_date;
          $datetab=   explode('-',$fichehistorique->OpenInfo_opening_date);
          $fiche->OpenInfo_canreopen = true;
          $dates['OpenInfo_canreopen'] = true;
          $openingDate = ["year" => $datetab[0],
              "month" =>  $datetab[1],
              "day" =>  $datetab[2]];
          $this->placeID->openInfo = array("openingDate" => $openingDate, "canReopen" => true);
          $updateMask = "openInfo";
      }
      if ($typenotifs== "listlibelle") {
          $fiche->labels = $fichehistorique->labels;
          $this->placeID->labels = json_decode($fichehistorique->labels,1);
          $updateMask = "labels";
      }
  
        $fiche->update();
        $this->locations->patch($fiche->name,
        $this->placeID, array('updateMask' => $updateMask,'validateOnly' => false, 'attributeMask' => $updateMask));
       
      return response()->json([
          'success' => true,
          'message' => $typenotifs.' Modifier avec succes',
          'data' => [],
          'status' => Response::HTTP_OK
              ], Response::HTTP_OK);
    }
    public function notificationfiche(Request $request) {
        //$ficheid=$request->fiche_id;
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
       $franchises= $request->header('franchise');
      $totals=array();
      $nbnotif=0;
      
  
         $totalfiche=Fiche::where('franchises_id',$franchises)
         ->leftJoin('ficheusers', 'fiches.id', '=', 'ficheusers.fiche_id')
         ->where('ficheusers.user_id', '=', Auth()->user()->id)
         ->leftJoin('states', 'fiches.id', '=', 'states.fiche_id')
         ->where('states.isVerified', 1)
        
        ->select('fiches.*','fiches.id as fiche_id', DB::raw('count(*) as total'))
        ->join('notifications','fiches.id','=','notifications.fiche_id')
        ->where('notifications.state','=','Inactif')   
        ->groupBy('fiches.locationName')
      ->limit(100)
         ->get()->toarray();
        
                foreach($totalfiche as $fiches){
            $datas=ParamaterController::nombrenotif($fiches['fiche_id'],'notificationfiche');
               if($datas['fiche_count'] !=0){
                    $totals[] = ParamaterController::nombrenotif($fiches['fiche_id'],'notificationfiche');
                }
            }
            return response()->json([
                        'success' => true,
                        'message' => 'Liste notification fiche111',
                        'totalfiche' => count($totals),
                        
                        'data' => $totals,
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
      

    
    }
    public function notificationbyfiche(Request $request) {
        $ficheid=$request->fiche_id;

        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
       $franchises= $request->header('franchise');
        
          
            return response()->json([
                        'success' => true,
                        'message' => 'Liste notification fiche',
                        'data' => ParamaterController::nombrenotif($ficheid,'notificationbyfiche'),
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
     if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        $franchises= $request->header('franchise');
      
        try {
                    $etiquette=array();
                    $tab=$request->datafiltre;
            
                    $searchfiltre=$request->searchFiche;
                    
                    $isGoogleUpdated=$request->Modification;
                    $datafiltre=  GroupeController::group_byfiche('Name_groupe', $id = null);
                    if($tab){
                      
                        foreach ($tab as $groupe) {
                            $tabs = ['groupe_id' => $groupe['id_groupe']];
                            $i = 0;
                            foreach ($groupe['ettiquettes'] as $etiquettes) {
                                if (array_key_exists('status', $etiquettes)) {
                                if ($etiquettes['status'] == true) {
                                    $etiq = Etiquetgroupe::find($etiquettes['etiquettegroupe']);
                                    $etiquette[] = $etiq->etiquette_id;
                              }
                            }
                                $i++;
                            }
                        }
                        $datafiltre= $tab;

                    }
                    $listfiche =Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id') 
                    ->leftjoin('profilincompletes','profilincompletes.fiche_id','=','fiches.id')
                    ->where("fiches.franchises_id","=",$request->header('franchise'))
                    ->where("ficheusers.user_id","=",Auth()->user()->id)
                    ->where('fiches.state', 'LIKE', 'COMPLETED')
                   // ->where('profilincompletes.fiche_id',$input->Fiche_id)


                 ->join('metadatas',  'metadatas.fiche_id', '=','fiches.id')
                   // ->distinct('metadatas.fiche_id')
                                                           ->select('metadatas.metadatasId', 'metadatas.mapsUrl',
                                                                    'metadatas.newReviewUrl', 'fiches.locationName','profilincompletes.notification',
                                                                    'fiches.websiteUrl', 'fiches.id','fiches.city as region', DB::raw('count(*) as total'))
                                                     
                                                        ->when($searchfiltre!="",function ($query) use($searchfiltre) {
                                                                $query->where('fiches.locationName', 'LIKE', '%'.$searchfiltre.'%');
                                                                })
                                                                ->when(!empty($etiquette),function ($query) use($etiquette){
                                                                    $query->leftjoin('etiquetgroupes', 'etiquetgroupes.fiche_id', '=','fiches.id')
                                                                ->whereNotNull('etiquetgroupes.fiche_id')
                                                                ->where('etiquetgroupes.state', 1)
                                                                ->whereIN('etiquetgroupes.etiquette_id', $etiquette);
                                                                
                                                                })
                                                          ->groupBy('fiches.id');
                                               
                                                          $count=$listfiche->get()->count();  
                                                          $all_listfiche = $listfiche->get();   
                  /*  ->get(['profilincompletes.fiche_id','profilincompletes.etat',
                    'profilincompletes.fiche_id',
                    'fiches.locationName',
                  'fiches.franchises_id as franchise_id','ficheusers.user_id']);

         $listfiche = Fiche::where('fiches.franchises_id',$franchises)
                            ->when(!empty($etiquette),function ($query) use($etiquette){
                                $query->leftjoin('etiquetgroupes', 'etiquetgroupes.fiche_id', '=','fiches.id')
                            ->whereNotNull('etiquetgroupes.fiche_id')
                            ->where('etiquetgroupes.state', 1)
                            ->whereIN('etiquetgroupes.etiquette_id', $etiquette);
                            
                            })->leftJoin('ficheusers', 'fiches.id', '=', 'ficheusers.fiche_id')
         ->where('ficheusers.user_id', '=', Auth()->user()->id)
                                                            ->leftjoin('metadatas', 'metadatas.fiche_id', '=', 'fiches.id')->distinct('metadatas.fiche_id')
                                                           ->select('metadatas.metadatasId', 'metadatas.mapsUrl',
                                                                    'metadatas.newReviewUrl', 'fiches.locationName',
                                                                    'fiches.websiteUrl', 'fiches.id','fiches.city as region', DB::raw('count(*) as total'))
                                                         // ->leftJoin('states', 'states.fiche_id', '=', 'fiches.id')
                                                          //->Where('states.isVerified', 1)
                                                          ->where('fiches.state', 'LIKE', 'COMPLETED')
                                                            ->when($searchfiltre!="",function ($query) use($searchfiltre) {
                                                                $query->where('fiches.locationName', 'LIKE', '%'.$searchfiltre.'%');
                                                                })
                                                          ->groupBy('fiches.locationName');
                                               
                                                           
                                                        
                                                            $nbcount=150;
*/


           
           
                           
            return response()->json([
                        'success' => true,
                        'message' => 'Mise a jour traitée avec succes',
                        'Modification'=>$isGoogleUpdated,
                        'data' => $this->filtrefiche($all_listfiche ,$isGoogleUpdated),
                        'nbcount'=>$count,
                        'totalnbcount'=>$this->shortNumber($count),
                        'datafiltre' => $datafiltre,
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
    
      // lists des fiches 
   public static function filtrefiche($fichesliste,$isGoogleUpdated){
    $listfiche=array();
    $listfiches=array();
    foreach($fichesliste as $fiches){
    $fiches= collect($fiches)->put('status',false);
    $nbnotif=$fiches['notification'];
   $listfiches[]= collect($fiches)->put('notif',($nbnotif!=null ? true : false))->all();
   // $listfiches[]= collect($fiches)->put('notif',false)->all();
    }
   // return $listfiches;
    if($isGoogleUpdated){
        $i=0;
        $listfiche=array();
      foreach($listfiches as $list){
          if($list['notif'] == true){
            $listfiche[]=$list;
              $i++;
          }else{
            $i++;
         
          }

      }
     
      return collect($listfiche)->sortByDesc('notif')->values()->all();
    }else{
        return collect($listfiches)->sortByDesc('notif')->values()->all();
    }
    
   }
   public static function nombrenotif($ficheid,$type){

    $fiche = Fiche::Where('fiches.id', $ficheid)->first();
$nbnotif=0;
$msg="";
$ordernotif= ['["', '"]'];
$picto='';
$notification = explode('","', str_replace($ordernotif, '', $fiche->notification));
            $data=array();
            $fiche_id=$fiche->fiche_id;
   
            $fiches = Fiche::join('notifications','fiches.id','=','notifications.fiche_id')
            ->Where('fiches.id', $ficheid)->where('notifications.state','Inactif')
            ->select('notifications.diffMask')
            ->get();
            $colfich=collect($fiches);
            $search='primaryPhone';
            $colltel = $colfich->filter(function ($item) use ($search) {
                  return $item['diffMask']==$search;
            });
       if(count($colltel)>0){
                $numerotel[]= ['phone' => ['countryCode' => $fiche->country,
                    'dialCode' => '+33',
                    'e164Number' => '+33 '.$fiche->primaryPhone,
                    'internationalNumber' => '+33 '.$fiche->primaryPhone,
                    'nationalNumber' => $fiche->primaryPhone,
                    'number' => $fiche->primaryPhone,
                    'etatvalidation'=>false,
                    
                    ]];
                    $picto=Iconfiche::where('code','primaryPhone')->first();
                    $data['numerotel']=['total'=>1,'numerotel'=>$numerotel];
                    $msg="Un ou des utilisateurs ont modifié le Numéro principal de votre établissement";
                   
                  $nbnotif++; 
            }
    $search='additionalPhones';
    $collteladditionalPhones = $colfich->filter(function ($item) use ($search) {
          return $item['diffMask']==$search;
    });
if(count($collteladditionalPhones)>0){
    $order = ['[', ']', '"'];
  
  
      
    
   $array = explode(',', str_replace($order, '',$fiche->additionalPhones));
    foreach($array as $arr){
     $numerotel[] = ['phone' => ['countryCode' => $arr,
    'dialCode' => '+33',
    'e164Number' => '+33 '.$arr,
    'internationalNumber' => '+33 '.$arr,
    'nationalNumber' => $arr,
    'number' =>$arr,
    'etatvalidation'=>false,
    ]];
    }
    $picto=Iconfiche::where('code','primaryPhone')->first();
    $data['numerotel']=['total'=>1,'numerotel'=>$numerotel];
    $msg="Un ou des utilisateurs ont modifié le Numéro de téléphone supplémentaire de votre établissement";
  $nbnotif++;
}

      
   
 
        $searchlabels='labels';
        $colltlabels = $colfich->filter(function ($item) use ($searchlabels) {
              return $item['diffMask']==$searchlabels;
        });
    if(count($colltlabels)>0){
        $order = ['[', ']', '"'];
        $arraylabel = explode(',', str_replace($order, '', $fiche->labels));
        foreach ($arraylabel as $arrlab) {
                $listlibelle[] = ['libelle_value' => $arrlab,
                'etatvalidation'=>false];
            }
          
    $picto=Iconfiche::where('code','libelles')->first();
        $data['listlibelle']=['total'=>1,'listlibelle'=>$listlibelle];
        $msg="Un ou des utilisateurs ont modifié les libelles de votre établissement";
        $nbnotif++;
        }
  
    $searchwebsiteUrl='websiteUrl';
    $colltewebsiteUrl = $colfich->filter(function ($item) use ($searchwebsiteUrl) {
          return $item['diffMask']==$searchwebsiteUrl;
    });
if(count($colltewebsiteUrl)>0 && $fiche->websiteUrl !=NULL){
    
    $picto=Iconfiche::where('code','websiteUrl')->first();
        $data['websiteUrl']=['total'=>1,'websiteUrl'=>$fiche->websiteUrl,'websiteUrlold'=>''];
        $msg="Un ou des utilisateurs ont modifié le site web de votre établissement";
       $nbnotif++;
    }

        $searchstoreCode='storeCode';
        $colltstoreCode = $colfich->filter(function ($item) use ($searchstoreCode) {
              return $item['diffMask']==$searchstoreCode;
        });
    if(count($colltstoreCode)>0){
        $picto=Iconfiche::where('code','storecode')->first();
        $data['codemagasin']=['total'=>1,'codemagasin'=>$fiche->storeCode,'codemagasinold'=> ''];
        $msg="Un ou des utilisateurs ont modifié le code magasin de votre établissement";
       $nbnotif++;
    }
    $searchlocationName='locationName';
    $colltlocationName = $colfich->filter(function ($item) use ($searchlocationName) {
          return $item['diffMask']==$searchlocationName;
    });
if(count($colltlocationName)>0){
        $data['locationName']=['total'=>1,'locationNamenew'=>$fiche->locationName,'locationNameold'=> ''];
        $picto=Iconfiche::where('code','locationName')->first();
        $msg="Un ou des utilisateurs ont modifié le nom de votre établissement";
        $nbnotif++;
    }
    $searchopenInfo='openInfo.openingDate';
    $colltopenInfo= $colfich->filter(function ($item) use ($searchopenInfo) {
          return $item['diffMask']==$searchopenInfo;
    });
if(count($colltopenInfo)>0){
  
        $ouverture = null;
        $OpenInfo_opening_date = [];
        $etatOpenInfo=false;
        $fiches = $fiche->toarray();
        if (array_key_exists('OpenInfo_opening_date', $fiches)) {
            $ouverture = Carbon::parse($fiche->OpenInfo_opening_date)->translatedFormat('j F Y');
            $mois = Carbon::parse($fiche->OpenInfo_opening_date)->translatedFormat('F');
            $Annee = Carbon::parse($fiche->OpenInfo_opening_date)->translatedFormat('Y');
            $Jours = Carbon::parse($fiche->OpenInfo_opening_date)->translatedFormat('j');
            $nbmois = Carbon::parse($fiche->OpenInfo_opening_date)->Format('m');
            $OpenInfo_opening_date =
              ['OpenInfo_opening_date' => ['Annee' => $Annee,
                      'Mois' => ['Mois' => $mois, 'value' => $nbmois], 'Jours' => $Jours ]];
        }
        $picto=Iconfiche::where('code','OpenInfo_status')->first();
        $data['ouverture'] =['ouverture' => $ouverture,'ouvertureold'=>'','etatvalidation'=>$etatOpenInfo];
        $msg="Un ou des utilisateurs ont modifié le date d'ouverture de votre établissement";
        $data['OpenInfo_opening_date'] =['OpenInfo_opening_date' => $OpenInfo_opening_date,'etatvalidation'=>$etatOpenInfo];
        $nbnotif++;
    }
    
  
        $searchaddress='address.addressLines';
        $colltaddress= $colfich->filter(function ($item) use ($searchaddress) {
              return $item['diffMask']==$searchaddress;
        });
    if(count($colltaddress)>0){
        $data['address']=['total'=>1,'adresse'=>$fiche->address,'adresseold'=>''];
        $msg="Un ou des utilisateurs ont modifié l'adresse de votre établissement";
        $nbnotif++;
        $picto=Iconfiche::where('code','adresse')->first();
    }
    if($fiche->email != $fiche->emailpre){
        $data['email']=['total'=>1,'email'=>$fiche->email,'emailold'=> ''];
        $msg="Un ou des utilisateurs ont modifié le email de votre établissement";
        $picto=Iconfiche::where('code','email')->first();
         $nbnotif++;
    }
  
        $searchdescription='profile.description';
        $colltdescription= $colfich->filter(function ($item) use ($searchdescription) {
              return $item['diffMask']==$searchdescription;
        });
    if(count($colltdescription)>0){
        $data['description']=['total'=>1,'description'=>$fiche->description,'descriptionold'=>''];
        $msg="Un ou des utilisateurs ont modifié le description de votre établissement";
        $picto=Iconfiche::where('code','description')->first();
        $nbnotif++;

   }
   $searchadPhone='adWordsLocationExtensions.adPhone';
   $colltadPhone= $colfich->filter(function ($item) use ($searchadPhone) {
         return $item['diffMask']==$searchadPhone;
   });
if(count($colltadPhone)>0){
    $data['adwPhone']=['total'=>1,'adwPhone'=>$fiche->adwPhone,'adwPhoneold'=> ''];
    $msg="Un ou des utilisateurs ont modifié le Numéro de télephone pour les extensions de lieu Google Ads de votre établissement";
    $picto=Iconfiche::where('code','primaryPhone')->first();
    $nbnotif++;
}
    $lienprend=Attribute::where('fiche_id',$ficheid)
    ->where('valueType',"URL")
   ->WHERE('state','Inactif')
    ->get();
    
    if($lienprend->count()>0){
        $arraypriser=AttributeController::priserendez($ficheid);
        $data['lienprend']=['total'=>1,'lienprend'=>$arraypriser['urlValues']];
        $msg="Un ou des utilisateurs ont modifié le lien de votre établissement";
        $picto=Iconfiche::where('code','urlValues')->first();
       
        
          
     $nbnotif++;
   }
 
     $tabAttribute= Attribute::where('fiche_id', $ficheid)
         ->where('values', 'true')
         ->where('valueType','<>',"URL")
     ->WHERE('state','Inactif')
     ->get();
     
    if(($tabAttribute->count()>0)){
        if($type =="notificationfiche"){
        foreach($tabAttribute as  $fourn){
            $data['attribute'][]=['total'=>$tabAttribute->count(),
            'displayName'=>$fourn->displayName
            ];

        }
    }else{
        $data['fournis']=['total'=>1,'attributes' => AttributeController::detailsattribute($ficheid),'attributes' => AttributeController::detailsattribute($ficheid)];
         
    }  
     $picto=Iconfiche::where('code','fournis')->first();
      $msg="Un ou des utilisateurs ont modifié attributes de votre établissement";
        
    $nbnotif++;
   }
   $fichehours=Fichehour::where('fiche_id', $ficheid)->WHERE('state','Inactif') ->whereNull('specialhours_start_date')->get();
   if($fichehours->count()>0){
    if($type =="notificationfiche"){
        $data['horaire']=['total'=>1,'horaire'=>FichehourController::byfiche($ficheid),
               
        "msg"=>"Un ou des utilisateurs ont modifié les horaires de votre établissement"];
        $nbnotif++;
    }else{ 
        $data['horaire']=['total'=>1,"horaire"=>FichehourController::fichehoraire($ficheid)];
        $msg="Un ou des utilisateurs ont modifié les horaires de votre établissement";
        $nbnotif++;

    }
    $picto=Iconfiche::where('code','OpenInfo_status')->first();
   }
   $fichehourspecial=Fichehour::where('fiche_id', $ficheid)->whereNotNull('specialhours_start_date')->WHERE('state','Inactif')->get();
   if($fichehourspecial->count()>0){
 
        $data['horairespeciaux']=['total'=>1,"horaire"=>FichehourController::horaireexp($ficheid)];
        $picto=Iconfiche::where('code','specialhours_start_date')->first();
        $msg="Un ou des utilisateurs ont modifié les horaires d'ouverture exceptionnels de votre établissement";
        $nbnotif++;

    
   
   }
  

   $service=Service::join('categories','services.categorie_id','=','categories.id')
   
    ->where('categories.fiche_id',$ficheid)->where('services.state','Inactif')
    ->select("services.*")->get();

    if($service->count()>0){
        if($type =="notificationfiche"){
            $i=0;
        foreach($service as  $services){
            $data['service'][]=['total'=>$service->count(),
            'new'=>$services->displayName];
$i++;
        }
    }else{
        $etatservold=true;
        $etatservo=true;
        $tabcat = Categorie::leftjoin('services', 
        'services.categorie_id', 'categories.id')
            ->where('categories.fiche_id', $ficheid)
         //   ->where('categories.state','Inactif')
            //->where('categories.user_id', '=', Auth()->user()->id)
            ->select('categories.state','categories.id as categorie_id',DB::raw('count(*) as fiche_count,
             categories.displayName,
            categories.type,categories.categorieId'))
            ->groupBy('categories.categorieId', 'categories.id',
            'categories.displayName',
            'categories.type')->orderBy('categories.type', 'DESC')->
                get();
                $tabcatexit = Categorie::leftjoin('services', 
                'services.categorie_id', 'categories.id')
                    ->where('categories.fiche_id', $ficheid)
                    //->where('categories.user_id', '=', Auth()->user()->id)
                    ->where('services.state','Inactif')->
                        exists();
                        if($tabcatexit){
                            $etatservo=false;
                        }
        $tabb = $tabcat->toarray();
        $outarray = array();
        $etatservolds=true;
        foreach ($tabb as $fiches) {
            $tabservice=array();
            if($fiches['state']=='Inactif'){

            }
            $tabcategories= Service::where('categorie_id', $fiches['categorie_id'])
                ->select('serviceId', 'displayName as name','id','state')->get();
   
    
        foreach($tabcategories as $tab){
            $servold=Null;
           
        
       if($tab->state=='Inactif'){
        $etatservold=false;
       }
       $dataattribute= 
       ['categorie_id'=>$fiches['categorie_id'],
        'serviceId'=>$tab->serviceId,
        "displayName"=>$tab->name
        ];
        
        if(Notification::where("diffMask",'service')->Where('newobject', 'LIKE', '%' .collect($dataattribute)->toJson(JSON_UNESCAPED_UNICODE).'%')->where('state','Inactif')->where('fiche_id',$ficheid)->exists()){
         $etatservold=false; 
         $etatservolds=false;
     }else{
         $etatservold=true;
         $etatservolds=true;
     }
            $tabservice[]=['serviceId'=>$tab->serviceId,
            'name'=>$tab->name,'etatvalidation'=>$etatservold,
            
            'id'=>$tab->id];
        }
        
            $outarray[] = array('idCat' => $fiches['categorieId'], 'nameCat' => $fiches['displayName'],
                'type' =>$fiches['type'],
                'Services' => $tabservice,'etatvalidation'=>$etatservolds);
        }
       
        $data['service']=['total'=>1,'service'=>$outarray];
      
    }
    $picto=Iconfiche::where('code','services')->first();
       $msg="Un ou des utilisateurs ont modifié service de votre établissement";
      $nbnotif++;
   }
   
    $Servicearea =Servicearea::select('name as description',
    'placeId as place_id',
    'pays','businessType',
    'zone','state','fiche_id',
    'id')->where('fiche_id',$ficheid)->WHERE('state','Inactif')->get();
   
    if($Servicearea->count()>0){
        if($type =="notificationfiche"){
        foreach($Servicearea as  $zone){
            $data['zonedesservies'][]=['total'=>$Servicearea->count(),
            'zonedesserviesnew'=>$zone->description];
          
            
        }
        }else{
            $data['zonedesservies']=['total'=>1,'listnotif'=>ServiceareaController::servicebyfiche($ficheid),'zonedesservies'=>ServiceareaController::servicebyfiche($ficheid)];
        
        }
        $picto=Iconfiche::where('code','zonedesservies')->first();
     
        $msg="Un ou des utilisateurs ont modifié zone desserive d'établissement";
   
            
        
    $nbnotif++;
    }

$tabcat= Categorie::WHERE('state','Inactif')->where('fiche_id',$ficheid)->get();

  if($tabcat->count()>0){
    if($type =="notificationfiche"){
      foreach($tabcat as $cat){
        $data['categorie'][]=['total'=>$tabcat->count(),
        'categorienew'=>$cat->displayName];
    
      }
    }else{
        $data['categorie']=['total'=>1,'categorie'=>CategoriesController::categorie($ficheid)];
    }
    $picto=Iconfiche::where('code','categorie')->first();
     
        $msg="Un ou des utilisateurs ont modifié categorie de votre établissement";

   
      $nbnotif++;
    }

 $photo=Photohistorie::where('fiche_id',$ficheid)->where('state','Inactif');
 $nbphoto=0;
    if($photo->exists()){
        $detailsphoto=$photo->get();
        foreach($detailsphoto as  $pho){
            $data['photo'][]=['total'=>$detailsphoto->count(),'new'=>$pho->file,
            'old'=>'',"msg"=>"Un ou des utilisateurs ont ajoutés des photos de votre établissement","etatvalidation"=>false,"locationName"=>$fiche->locationName,'fiche_id'=>$ficheid];

        }
        $picto=Iconfiche::where('code','logo')->first();
        $nbphoto++;
    }
    if($type =="notificationbyfiche"){
        if($nbnotif==1){
            return ['notifphoto'=>$nbphoto,'notiffiche'=>$nbnotif,'details'=>$data,"msg"=>$msg,"locationName"=>$fiche->locationName,"etatvalidation"=>false,'fiche_id'=>$ficheid,'picto'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$picto->path];
        }else{
            if($picto !=Null){
              $url=\Illuminate\Support\Facades\URL::to('/') .'/'.$picto->path;
            }else{
               $url='';
            }
            return ['notifphoto'=>$nbphoto,'notiffiche'=>$nbnotif,'details'=>$data,"locationName"=>$fiche->locationName,'fiche_id'=>$ficheid,'msg'=>"un ou plussiers utilisateurs ont modifié des informations de votre fiche",
            'picto'=>$url];
        }
    }elseif($type =="notificationfiche"){
        return   ['fiche_count' => count($data), 'fiche_id' => $ficheid,
                'locationName' => $fiche->locationName,'details'=>$data,
                 "listnotif" => $data];
    }
        else{
        return $nbnotif;
    }
   }
   ///
   public static function nombrenotifs($ficheid,$type){

    $fiche = Fiche::Where('id', $ficheid)
    ->where('etat','Inactif')
    //->whereNotNull('notification')
    ->where(function ($query) {
         $query->where('notification', 'not LIKE', '[\"priceLists\"]') 
         ->orWhere('notification', 'not LIKE', '["latlng"]');

    })->exists();
    $nbnotif=0;
    if($fiche)
    {
        $nbnotif++;
        return $nbnotif;
    }
  
    }
    //notificationphoto
    public function notificationphoto(Request $request) {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $franchises = $request->header('franchise');
        $totals = [];
        $ficheid = $request->fiche_id;
        $end= Carbon::now()->toDateString();
        $start = Carbon::now()->subDays(30)->toDateString();
        $totalfiche = Fiche::leftJoin('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
        ->where('fiches.franchises_id', $franchises)
     ->where('ficheusers.user_id', '=', Auth()->user()->id)
        ->get('fiches.*');
                
                        $totals=array();
                        $nbnotif = 0;
        foreach ($totalfiche as $fiches) {
       
            $details = [];
            $fiche_id = $fiches->id;
          
         $photo = Photo::leftjoin('fiches', 'fiches.id', 'photos.fiche_id')
         ->select('fiches.*', 'photos.id as photo_id','photos.thumbnail',
         'photos.file')
         ->whereBetween('photos.created_at',["$start","$end"])
         ->where('photos.category','CUSTOMER')
             ->where('photos.fiche_id', $fiche_id)->exists();
         if ($photo) {
                $detailsphoto = Photo::leftjoin('fiches', 'fiches.id', 'photos.fiche_id')
                ->Leftjoin('users', 'user_id', '=', 'users.id')
                ->where('photos.fiche_id', $fiche_id)
                ->whereBetween('photos.created_at',["$start","$end"])
                ->where('photos.category','CUSTOMER')
                ->get(['photos.*','photos.id as idphoto','fiches.locationName', 'users.firstName', 'users.lastName', 'users.photo']);
              
                foreach ($detailsphoto as $pho) {
                
                $now = Carbon::now();
                $end = Carbon::parse($pho->created_at);

                if ($years = $end->diffInYears($now)) {
                    $dateleft = $years . ' ans';
                } elseif ($months = $end->diffInMonths($now)) {
                    $dateleft = $months . ' mois';
                } elseif ($weeks = $end->diffInWeeks($now)) {
                    $dateleft = $weeks . ' sem';
                } else {
                    $days = $end->diffInDays($now);
                    $dateleft = $days . ' jours';
                }
                $pho->date = $dateleft;
                $views= $pho->date;
                    $views=  $pho->views;
              if($pho->views){
              $views=$this->shortNumber($pho->views);
              }
              $details[] = collect($pho)->put('views',$views)->all();     
                }
                $totals[] = ['fiche_count' => $detailsphoto->count(),
                 'fiche_id' => $fiche_id, 'locationName' => $fiches->locationName,
                  'details' => $detailsphoto,
                'countnotif' => $detailsphoto->count()];
                ++$nbnotif;
         }
       
        }
        return response()->json([
                    'success' => true,
                    'message' => 'Liste notification photos',
                    'totalnotifphotos' =>$nbnotif,
                  
                    'data' => $totals,
                    'status' => 200,
                        ], 200);
    }
  
    public function shortNumber($num)
    {
        $units = ['', 'K', 'M', 'B', 'T'];
        for ($i = 0; $num >= 1000; ++$i) {
            $num /= 1000;
        }

        return round($num, 1) . $units[$i];
    }

   
}
   

