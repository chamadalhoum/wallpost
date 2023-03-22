<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Categorie;
use App\Models\Fiche;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Accountagence;
use PhpParser\Node\Expr\Cast\Unset_;
use GoogleMyBusinessService;
use Google;

class ServiceController extends GoogleController
{
     

    public function __construct() {
     
        
    }
    public function index()
    {

        try {
            $services = Service::query();
            $s = request('search');
            if ($s) {
                $servicesearch = $services->where('serviceId', 'LIKE', '%' . $s . '%')->
                orWhere('displayName', 'LIKE', '%' . $s . '%')->
                orWhere('categorie_id', 'LIKE', '%' . $s . '%')->
                orWhere('user_id', 'LIKE', '%' . $s . '%')
                    ->get();

                if ($servicesearch->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => $servicesearch,

                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Désole, Service not found.',

                        'status' => 200
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => $services->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Service not found.',

                'status' => 400
            ], 400);
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $messages = [
            'serviceId.required' => 'Vérifier Votre service Id!',
            'displayName.required' => 'Vérifier Votre Display Name!',
            'user_id.required' => 'Vérifier Votre User!',
            'categorie_id.required' => 'Vérifier Votre Categorie!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "serviceId" => 'required|max:45',
                "displayName" => 'required|max:45',
                "user_id" => 'exists:users,id',
                "categorie_id" => 'exists:categorie,id',
            ], $messages
        );
        if ($validator->fails()) {
            return response()->json([
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422,
            ],
                422);
        }
        if ($validator->passes()) {
            try {
                $data = $request->all();

                $document = Service::create($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Service ajouté avec succès',
                    'data' => $document,
                    'status' => Response::HTTP_OK,

                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400,

                    ],
                    400
                );
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Service $service
     * @return \Illuminate\Http\Response
     */
    public function show(Service $service)
    {

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Service not found.',

                'status' => 400
            ], 400);
        }
        $categorie = Categorie::where('id', $service->categorie_id)->get();
        $detailsservice = array('serviceId' => $service->serviceId,
            'description_service' => $service->description,
            'fiche_id' => $categorie[0]->fiche_id,
            'idCat' => $categorie[0]->categorieId,
            'prix_service' => $service->prix,
            'type_service' => $service->typeservice,
            'user_id' => $service->user_id,
            'name' => $service->displayName,
            'id' => $service->id);

        return response()->json([
            'success' => true,
            'message' => 'Service id ' . $service->id,
            'data' => $detailsservice,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Service $service
     * @return \Illuminate\Http\Response
     */
    public function edit(Service $service)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Service $service
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Service $service)
    {

        $messages = [
            'serviceId.required' => 'Vérifier Votre serviceId!',
            'displayName.required' => 'Vérifier Votre display Name!',
            'user_id.required' => 'Vérifier Votre User!',
            'categorie_id.required' => 'Vérifier Votre categorie!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "serviceId" => 'max:45',
                "displayName" => 'max:45',
                "user_id" => 'exists:users,id',
                "categorie_id" => 'exists:categorie,id',


            ], $messages
        );

        if ($validator->fails()) {

            return response()->json([
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422,
            ],
                422);
        }
        if ($validator->passes()) {
            try {
                /* if ($request->serviceId) {
                     $service->serviceId = $request->serviceId;
                 }*/
                if ($request->name) {
                    $service->displayName = $request->name;
                }
                if ($request->user_id) {
                    $service->user_id = $request->user_id;
                }
                if ($request->categorie_id) {
                    $service->categorie_id = $request->categorie_id;
                }
                if ($request->description_service) {
                    $service->description = $request->description_service;
                }
                if ($request->prix_service) {
                    $service->prix = $request->prix_service;
                }
                if ($request->type_service) {
                    $service->typeservice = $request->type_service;
                }

                $service->update();
                $categorie = Categorie::where('id', $service->categorie_id)->get();
                $fiche = Fiche::find($categorie[0]->fiche_id);
                $detailsservice = array('serviceId' => $service->serviceId,
                    'description_service' => $service->description,
                    'fiche_id' => $categorie[0]->fiche_id,
                    'idCat' => $categorie[0]->categorieId,
                    'prix_service' => $service->prix,
                    'type_service' => $service->typeservice,
                    'user_id' => $service->user_id,
                    'name' => $service->displayName,
                    'id' => $service->id);
                            $tabcategoriP = Categorie::where('fiche_id', $fiche->id)
                            ->where('type','primaryCategory')->first();
           if(Service::Where('categorie_id',$tabcategoriP->id)->exists()){
            $listcatservice=Service::where('categorie_id',$tabcategoriP->id)->get();
            $it=0;
            foreach($listcatservice as $lists){
                $servicetabs[]= array('categoryId' => $tabcategoriP->categorieId,
                'displayName' => $lists->displayName,
                'description' =>$lists->description,
                'serviceTypeId'=>$lists->serviceId,
                 'price' => array("currencyCode" => "EUR",
                 "units" => $lists->prix,
                 "nanos" => $lists->prix
            ));
            $it++;
            }
         } 
         $tabcategoriadd = Categorie::where('fiche_id', $fiche->id)
         ->where('type','additionalCategories')->get();
        if($tabcategoriadd->count()>0){
            foreach($tabcategoriadd as $catadd){
                if(Service::Where('categorie_id',$catadd->id)->exists()){
                    $listcatservice=Service::where('categorie_id',$catadd->id)->get();
                    $ii=0;
                    foreach($listcatservice as $list){
                        $servicetabs[]= array('categoryId' => $catadd->categorieId,
                        'displayName' => $list->displayName,
                        'description' =>$list->description,
                        'serviceTypeId'=>$list->serviceId,
                        'price' => array("currencyCode" => "EUR",
                        "units" => $list->prix,
                        "nanos" => $list->prix
                   ));
                    $ii++;
                    }
                 }
            }
        }
        
             $result = $this->updateservice($servicetabs, $fiche->name);

                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $detailsservice,
'ccc'=>$servicetabs,
                    'status' => Response::HTTP_OK
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json([
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400,

                    ]
                );
            }
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Service $service
     * @return \Illuminate\Http\Response
     */
    public function destroy(Service $service)
    {

        try {
            $service->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Service could not be deleted',
                'status' => 500,

            ], 500);
        }

    }

    public function listeserivcebycat()
    {
       // $mybusinessService = Helper::GMB();
        $idcategorie = request('id_cat');
        $fiche_id = request('idfiche');
        $fiche = Fiche::find($fiche_id);

        try {
          
            $params=["regionCode"=>"FR","languageCode"=>"fr","view"=>"FULL",'names'=>[$idcategorie]];
            $datcat=array();
            $client = Helper::googleClient();
            $service = new Google\Service\MyBusinessBusinessInformation($client);
            $tab =$service->categories->batchGet($params);

                try {

             
                      
                        $arrayName= explode( '/', $fiche->name);
                        $Name= $arrayName[2] . '/' . $arrayName[3];
                        $params=["readMask"=>"serviceItems"];
                        $tabService= $service->locations->get($Name,$params);
                        $tabs=array();
                    foreach($tab as $tabb){
                        $tabs[]=$tabb;
                    }
                    $tabs=$tab;
                        $rest = array();
                        if (count($tab['categories'][0]) > 0) {
                            $i = 0;
                         
                                if (isset($tabService['serviceItems'])) {
                            foreach ($tabService['serviceItems'] as $service) {


                                if (isset($service['structuredServiceItem'])) {

                                    if (isset($tab['categories'][0]['serviceTypes'])) {
                                    $key = array_search($service['structuredServiceItem']['serviceTypeId'], 
                                    array_column($tab['categories'][0]['serviceTypes'], 'serviceTypeId'));
                                    $keytab[]=['id'=>$key];
                                 // if (false != $key) {
                                   
                                     //   unset($tabs[0]['serviceTypes'][$key]);
                                       
                                 // }
 }
                                }

                                $i++;
                            }
                                }
                                if ( isset($keytab)){
                                    foreach($keytab as $keys){
                                  
                                        unset($tabs[0][$keys['id']]); 
                                    }
                                }
                                
                                
                             if (isset($tabs[0])) {
                            foreach ($tabs[0] as $serviceitem) {
                                $rest[] = array('serviceId' => $serviceitem['serviceTypeId'],
                                 'name' => $serviceitem['displayName'],
                                  'id' => '');
                            }
                             }
                        }

                    return response()->json([
                        'success' => true,
                        'message' => 'Liste Services',
                        'data' => $rest,
                        'status' => 200
                    ], 200);


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
    public static function listeserivce(Request $request)
    {   $idcategorie = $request->id_cat;
        $rest = array();

        try {
            $client = Helper::googleClient();
            $service = new Google\Service\MyBusinessBusinessInformation($client); 
           
                try {

                    $params=["regionCode"=>"FR","languageCode"=>"fr","view"=>"FULL",'names'=>[$idcategorie]];
                    $tab = $service->categories->batchGet($params);
                     

                       
                        if(count($tab)>0){
                            if (count($tab['categories'][0]) > 0) {
                                $i = 0;
    
                                 if (isset($tab['categories'][0]['serviceTypes'])) {
                                foreach ($tab['categories'][0]['serviceTypes'] as $serviceitem) {
                                    $rest[] = array('serviceId' => $serviceitem['serviceTypeId'],
                                    'displayNamecateg'=>$tab['categories'][0]['displayName'], 
                                    'categorieid'=>$tab['categories'][0]['name'],'name' => $serviceitem['displayName'],
                                     'id' => '','status'=>false);
                            
                                    }
                                 }
                            }
                        }
                       

                    return response()->json([
                        'success' => true,
                        'message' => 'Liste Services',
                        'data' => $rest,

                        'status' => 200
                    ], 200);


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
    public static function listeserivcefiche($fiches)
    {
        $collection = collect();

            $accessToken = Helper::GMBServiceToken();
            $token_acces = json_decode($accessToken, true);
            $listtab=array();
        
           foreach($fiches as $fiche){
 
            $client = Helper::googleClient();
            $service = new Google\Service\MyBusinessBusinessInformation($client); 
            $params=["regionCode"=>"FR","languageCode"=>"fr","view"=>"FULL",'names'=>[$fiche['categorieId']]];
            $tab = $service->categories->batchGet($params);
                        $rest = array();
                        if (count($tab['categories'][0]) > 0) {
                            $i = 0;

                             if (isset( $tab['categories'][0]['serviceTypes'])) {
                            foreach ($tab['categories'][0]['serviceTypes'] as $serviceitem) {
                                $listtab[] = array('serviceId' => $serviceitem['serviceTypeId'],'displayNamecateg'=>$tab['categories'][0]['displayName'], 'categorieid'=>$tab['categories'][0]['name'],'name' => $serviceitem['displayName'], 'id' => '','status'=>false);
                           }
                             }
                        }
                    }
$collectiont = collect($listtab);
$filtered = $collectiont->filter(function ($value, $key) {
    return $value != null;
});
$unique = $filtered->unique('serviceId');
return $unique->values()->all();

}
  
    public  static function updateservice($request,$locationName) {

        try {
            $units='';
            $nanos='';
            $PostData=array();
                try {

                        if(isset($request)){
                            foreach ($request as $reqt) {
                              
                                if (isset($reqt['serviceTypeId'])) {
                                    if (isset($reqt['price']['units']) && isset($reqt['price']['nanos'])) {
                                        $units=$reqt['price']['units'];
                                        
                                        $nanos=$reqt['price']['nanos'];
                                        $serviceprice=["currencyCode" => "EUR","units" => $units,"nanos" => $nanos];
                                        $dataposttab=['structuredServiceItem'=> array("serviceTypeId" =>$reqt['serviceTypeId'],
                                        "description" => $reqt['description'])];
                                        $PostData[] =  collect($dataposttab)->put('price',$serviceprice)->all();
                                        }else{
                                            $PostData[] = ['structuredServiceItem'=> array("serviceTypeId" =>$reqt['serviceTypeId'],
                                            "description" => $reqt['description'])];
                                        }
                     
                                } else {
                                        $PostData[] = array(
                                            /*'price' => array("currencyCode" => "EUR",
                                            "units" => $$units,
                                "nanos" => $nanos),*/
                                            "freeFormServiceItem" => array(
                                                'category' => $reqt['categoryId'],
                                                'label' => array('displayName' => $reqt['displayName'],
                                                    'description' => $reqt['description'],
                                                    'languageCode' => 'fr'
                                            ),
                                               /*'price' => array("currencyCode" => "EUR",
                                                    "units" => 100,
                                        "nanos" => "100")*/));
                                }
                            }
                        }
else{
    $PostData[] = array(
    "structuredServiceItem" => array(
        'serviceTypeId' => '',
        'description' => '',
            )
        );
}
$client = Helper::googleClient();
                  
$Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
$Location->serviceItems=$PostData;
                        $updateMask='serviceItems';
                        FicheController::patchlocation($locationName,$updateMask,$Location);
                       return true;
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
    
}
