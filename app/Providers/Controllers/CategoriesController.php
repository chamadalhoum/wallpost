<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Categorie;
use App\Models\Categorieshistorique;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use App\Models\Fiche;
use App\Models\Notification;
use App\Models\Serviceshistorique;
use Facade\Ignition\Tabs\Tab;
use Google\Api\Service as ApiService;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use GoogleMyBusinessService;
use Google;

class CategoriesController extends Controller
{
  //  public $client;
  //  public $service;
  


    public function __construct() {
     //   $this->client = Helper::googleClient();
     //   $this->service = new Google\Service\MyBusinessBusinessInformation($client); 
      
    }

    public function store(Request $request)
    {
        $input=[];

        $messages = [
            'Name_cat.required' => 'Vérifier Votre categorie Id!',
            'listServices.required' => 'Vérifier Votre SErvice!',
            
            'user_id.required' => 'Vérifier Votre User!',
            'fiche_id.required' => 'Vérifier Votre Fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];
        $input=[
            'Name_cat'=>$request->Name_cat,
            "listServices" => $request->listServices,
            "user_id" => $request->user_id,
            "fiche_id" => $request->fiche_id,
        ];
        $validator = Validator::make($input,
            [
             
                "user_id" => 'exists:users,id',
                "fiche_id" => 'exists:fiches,id',
            ], $messages
        );
        $updateMask = null;
        if ($validator->fails()) {
            return response()->json([
                'succes' => false,
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
                $result=array();
                $tabcategorie = Categorie::leftjoin('fiches', 'fiches.id', '=', 'categories.fiche_id')
                    ->where('fiche_id', $input->fiche_id)
                    ->select('categories.*', 'fiches.locationName','fiches.name')->get();
                $locationName = $tabcategorie[0]->name;
              //  $this->placeID->locationName = $tabcategorie[0]->locationName;
                $updateMask = null;
                if (count($tabcategorie) === 0) {
                    $data['type'] = 'primaryCategory';
                    $cattype = 'primaryCategory';
                    $dataposttab=['primaryCategory'=> array("displayName" =>$input->Name_cat['displayName'],
                    "name" => $input->Name_cat['categoryId'])];
                    $dataposttab=['primaryCategory'=> array("displayName" =>$input->Name_cat['displayName'],
                    "name" => $input->Name_cat['categoryId'])];

                } else {
                    $data['type'] = 'additionalCategories';
                    $cattype = 'additionalCategories';
                  
                    
                }

                $data['categorieId'] = $input->Name_cat['categoryId'];
                $categorieexit = Categorie::where('fiche_id', $input->fiche_id)
                    ->where('categorieId', $input->Name_cat['categoryId'])->get();
                if (count($categorieexit) > 0) {
                    $data['categorie_id'] = $categorieexit[0]->id;
                    $categorie = $categorieexit[0];
                } else {
                    $data['displayName'] = $input->Name_cat['displayName'];
                    if($cattype==='additionalCategories'){
                        $datatab =  collect($dataposttab)->put('additionalCategories',$CatSup)->all();
                        $Location->categories=$datatab;
                    }else{
                        $Location->categories=$dataposttab;
                    }
                     try {
                    $updateMask = 'categories';
                  
                  FicheController::patchlocation($locationName,$updateMask,$Location);
                    $categorie = Categorie::create($data);
                  }
                    catch (\Google_Service_Exception $ex) {

                        return response()->json([
                                    'success' => false,
                                    'message' => $ex->getMessage(),
                                    'status' => 400,
                                        ]
                        );
                    }
                    $data['categorie_id'] = $categorie->id;
                }
                $i = 0;
                if($input->listServices){
                    foreach ($input->listServices as $list) {
               
                        $data['user_id'] = $input->user_id;
                      
                         Service::updateOrCreate(['categorie_id' => $categorie->id,'displayName'=>$list['name'],'serviceId' => $list['serviceId']], 
                        $data);
                       
                        $i++;
                    }
                $tabcategorie = Categorie::where('fiche_id', $input->fiche_id)->get();
                foreach($tabcategorie as $cat){
                    if(Service::Where('categorie_id',$cat->id)->exists()){
                    $servicetabs[] = CategoriesController::listServices($cat->id,$cat->categorieId);
                    }
                   
                }
               

   $servicetab[] = array('categoryId' => $input->Name_cat['categoryId'],
                        'displayName' => $list['name'],
                        'description' => '',
                        'serviceTypeId'=>$list['serviceId'],
                       'price' => array("currencyCode" => "EUR",
                        "units" => "",
                         "nanos" => ""
                ));
        //    var_dump($servicetabs);exit;
               $result = $this->updateservice($servicetabs, $locationName);
            }
                return response()->json([
                    'success' => true,
                    'message' => 'Categorie ajouté avec succès',
                    'data' => $result,
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
    public static function listServices($id,$categorieid){
        $PostData=array();
        $listcatservice=Service::Where('categorie_id',$id)->get();
foreach($listcatservice as $list){
    if ($list->serviceId) {
            
        $PostData[] = ['structuredServiceItem'=> array("serviceTypeId" =>$list->serviceId,
        "description" => $list->description)];
    

} else {
    $PostData[] = array(
        /*'price' => array("currencyCode" => "EUR",
        "units" => $$units,
"nanos" => $nanos),*/
        "freeFormServiceItem" => array(
            'category' => $categorieid,
            'label' => array('displayName' => $list->displayName,
                'description' =>  $list->description,
                'languageCode' => 'fr'
        ),
           /*'price' => array("currencyCode" => "EUR",
                "units" => 100,
    "nanos" => "100")*/));
}
   $tabrest[]= array('categoryId' => $categorieid,
    'displayName' => $list->displayName,
    'description' =>$list->description,
    'serviceTypeId'=>$list->serviceId,
     'price' => array("currencyCode" => "EUR",
     "units" => "",
     "nanos" => ""
));
}


return $tabrest;
    }
    public function categorieup(Request $request)
    {
       $input=[];

        $messages = [
            'Catprincipal.required' => 'Vérifier Votre Catégorie principale!',
            'listCat.required' => 'Vérifier Votre Liste Catégorie !',
            'user_id.required' => 'Vérifier Votre User!',
            'fiche_id.required' => 'Vérifier Votre Fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];
        $input=[
            'listCat'=>$request->listCat,
            "Catprincipal" => $request->Catprincipal,
            "user_id" => $request->user_id,
            "fiche_id" => $request->fiche_id,
        ];
        $validator = Validator::make($input,
            [
               
                "user_id" => 'exists:users,id',
                "fiche_id" => 'exists:fiches,id',
            ], $messages
        );
        if ($validator->fails()) {
            return response()->json([
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422
            ],
                422);
        }
        $input = (object) $input;
        if ($validator->passes()) {
            try {
                $client = Helper::googleClient();
                  
                $Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
            $data=array();
            $CatSup=array();
            $CatSupt=array();
            $idsup=array();
            $idsupt=array();
            $updateMask = 'categories';
            $tabcategorie = Categorie::where('fiche_id', $input->fiche_id)
                    ->where('type','primaryCategory')->first();

                $fiche=  Fiche::find($input->fiche_id);
                $locationName = $fiche->name;
                $cattype = 'primaryCategory';
                $tabcategorie->type = $request->Catprincipal['type'];
                $tabcategorie->displayName = $input->Catprincipal['displayName'];
                $tabcategorie->categorieId = $input->Catprincipal['categoryId'];
                $tabcategorie->update();
                
                
              $dataposttab=['primaryCategory'=> array("displayName" =>$input->Catprincipal['displayName'],
                        "name" => $input->Catprincipal['categoryId'])];
               if($input->listCat){
                 
               foreach($input->listCat as $listcat){
                    $cattype = 'additionalCategories';
               
                $categorieexit = Categorie::where('fiche_id', $input->fiche_id)
                    ->where('categorieId', $listcat['categoryId'])
                    ->where('type', 'additionalCategories')->get();
                   
                if (count($categorieexit) > 0) {
                    $categorie = $categorieexit[0];
                   $idsup[]= $categorieexit[0]->categorieId;
                 
                
                } else {
                $data[]=['type' => 'additionalCategories','displayName'=>$listcat['displayName'],'categorieId' => $listcat['categoryId'],
                    'user_id' => $input->user_id,'fiche_id'=>$input->fiche_id];
                
                    $idsupt[]= $listcat['categoryId'];
                }
            
                $CatSup[]= array("displayName" =>$listcat['displayName'],
                        "name" => $listcat['categoryId']);
            }
        
            try {

             
                if($idsupt){
                array_push($idsup,$idsupt);
                $idsup=array_values($idsup);
                }       
              
                $datatab =  collect($dataposttab)->put('additionalCategories',$CatSup)->all();
                $Location->categories=$datatab;
                FicheController::patchlocation($locationName,$updateMask,$Location);
              
                try {
                               
             
                 foreach($data as $datas){
                    $categorie = Categorie::create($datas);
             
                 }
              
                 if(isset($idsup)){
                    try{
                    $tabs=Categorie::where('fiche_id', $input->fiche_id)
                    ->where('type','!=','primaryCategory')
                    ->whereNotIn('categorieId',$idsup)->delete();
              
                  
                } catch (QueryException $ex) {
                    return response()->json(
                        [
                            'success' => true,
                            'message' => 'Mise a jour traite avec succès',
                            'data'=>$data,
                            'status' => 400,
    
                        ],
                        400
                    );
                }
                }
             
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traite avec succès',
                    'data' => $data,
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
                

            }catch (\Google_Service_Exception $ex) {

                        return response()->json([
                                    'success' => false,
                                    'message' => "La requête contient un argument invalide",
                                    'status' => 400,
                                        ]
                        );
                    }
          
        } else{   
            
            $categorieexit = Categorie::where('fiche_id', $input->fiche_id)
                ->where('type', 'additionalCategories')->get();
                foreach($categorieexit as $categorie){
                    $categorie = Categorie::where('id',$categorie->id)
                    ->where('fiche_id', $input->fiche_id)
                    ->where('type','!=','primaryCategory')->delete();

                 
        }
        $datatab =  collect($dataposttab)->put('additionalCategories',[])->all();
        $Location->categories=$datatab;
        FicheController::patchlocation($locationName,$updateMask,$Location);

        return response()->json([
            'success' => true,
            'message' => 'Mise a jour traite avec succès',
            'data' => $tabcategorie,
            'status' => Response::HTTP_OK,

        ], Response::HTTP_OK);
    }
            

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
     * @param \App\Models\Categorie $categorie
     * @return \Illuminate\Http\Response
     */
    public function show(Categorie $categorie)
    {

        $categories = Categorie::with('fiche:id,locationName,description,name', 'user:id,lastname,firstname')->find($categorie->id);
        if (!$categorie) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Categorie not found.',

                'status' => 400
            ], 400);
        }
        return response()->json([
            'success' => true,
            'message' => 'Categorie id ' . $categorie->id,
            'data' => $categories,

            'status' => 200
        ], 200);
    }


    public function update(Request $request)
    {
      

        $messages = [
            'categorieId.required' => 'Vérifier Votre categorie Id!',
            'displayName.required' => 'Vérifier Votre display Name!',
            'type.required' => 'Vérifier Votre type!',
            'user_id.required' => 'Vérifier Votre User!',
            'fiche_id.required' => 'Vérifier Votre Fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];
        $validator = Validator::make($request->all(),
            [
                "categorieId" => 'max:45',
                "displayName" => 'max:45',
                "type" => 'max:45',
                "user_id" => 'exists:users,id',
                "fiche_id" => 'exists:fiches,id',
            ], $messages
        );
        if ($validator->fails()) {
            return response()->json([
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422
            ],
                422);
        }
        if ($validator->passes()) {
            try {
            $data=array();
            $CatSup=array();
            $idsup=array();
            $updateMask = null;
            $tabcategorie = Categorie::where('fiche_id', $request->fiche_id)
                    ->where('type','primaryCategory')->first();

                $fiche= \App\Models\Fiche::find($request->fiche_id);
                $locationName = $fiche->name;
                $this->placeID->locationName = $fiche->locationName;
                $cattype = 'primaryCategory';
                $tabcategorie->type = $request->Catprincipal['type'];
                $tabcategorie->displayName = $request->Catprincipal['displayName'];
                $tabcategorie->categorieId = $request->Catprincipal['categoryId'];
                $tabcategorie->update();
                $updateMask = $cattype;


                try {
               $this->placeID->$cattype =array("displayName" =>$request->Catprincipal['displayName'],
                        "categoryId" => $request->Catprincipal['categoryId']);
                $upcat = $this->locations->patch($locationName,
                                $this->placeID, array('updateMask' => $updateMask,
                            'validateOnly' => false, 'attributeMask' => $updateMask));
                    }
                catch (\Google_Service_Exception $ex) {

                        return response()->json([
                                    'success' => false,
                                    'message' => "La requête contient un argument invalide primaryCategory",
                                    'status' => 400,
                                        ]
                        );
                }
               
                if($request->listCat){
               foreach($request->listCat as $listcat){
                    $cattype = 'additionalCategories';
               
                $categorieexit = Categorie::where('fiche_id', $request->fiche_id)
                    ->where('categorieId', $listcat['categoryId'])
                    ->where('type', 'additionalCategories')->get();
                   
                if (count($categorieexit) > 0) {
                    $categorie = $categorieexit[0];
                    $idsup[]= $categorieexit[0]->id;
                } else {
                $data[]=['type' => 'additionalCategories','displayName'=>$listcat['displayName'],'categorieId' => $listcat['categoryId'],
                    'user_id' => $request->user_id,'fiche_id'=>$request->fiche_id];
                    $CatSup[]= array("displayName" =>$listcat['displayName'],
                        "categoryId" => $listcat['categoryId']);
                    $updateMask = 'additionalCategories';
                }
            
               
            }
        
            try {
              
                $this->placeID->$cattype = $CatSup;
                $result = $this->locations->patch($locationName,
                                $this->placeID, array('updateMask' => $updateMask,
                            'validateOnly' => false, 'attributeMask' => $updateMask));
                try {
                               
                 $categorie = Categorie::insert($data);
                 if(count($idsup)>0){
                    try{
                    Categorie::whereNotIn('id',$idsup)
                    ->where('fiche_id', $request->fiche_id)
                    ->where('type','!=','primaryCategory')->delete();
                } catch (QueryException $ex) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => $ex->getMessage(),
                            'data'=>count($idsup),
                            'status' => 400,
    
                        ],
                        400
                    );
                }
                }
             
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traite avec succès',
                    'data' => $data,
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
                

            }catch (\Google_Service_Exception $ex) {

                        return response()->json([
                                    'success' => false,
                                    'message' => "La requête contient un argument invalide",
                                    'status' => 400,
                                        ]
                        );
                    }
          
        } else{   
            
            $updateMask = 'additionalCategories';
            $categorieexit = Categorie::where('fiche_id', $request->fiche_id)
                ->where('type', 'additionalCategories')->get();
                foreach($categorieexit as $categorie){
                    $categorie = Categorie::where('id',$categorie->id)
                    ->where('fiche_id', $request->fiche_id)
                    ->where('type','!=','primaryCategory')->delete();
                    $this->placeID->$updateMask = [];
                    $result = $this->locations->patch($locationName,
                    $this->placeID, array('updateMask' => $updateMask,
                'validateOnly' => false, 'attributeMask' => $updateMask));
                 
        }

        return response()->json([
            'success' => true,
            'message' => 'Mise a jour traite avec succès',
            'data' => $tabcategorie,
            'status' => Response::HTTP_OK,

        ], Response::HTTP_OK);
    }
            

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
    public function updateservice($request,$locationName) {

                try {

                    $updateMask='serviceItems';
                    $client = Helper::googleClient();
                  
                $Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
                    $PostData=array();
                        $prix = 0;
                        foreach ($request as $reqt) {
                            foreach ($reqt as $req) {
                            $data['name'] = $locationName . '/serviceList';

                            if (array_key_exists('units', $req)) {
                                $prix = $req['units'];
                            }
                            if (isset($req['serviceTypeId'])) {
                                $PostData[] = array(
                                    "structuredServiceItem" => array(
                                        'serviceTypeId' => $req['serviceTypeId'],
                                        'description' => $req['description'],
                                  /* 'price' => array("currencyCode" => "EUR",
                                            "units" => '100',
                                            "nanos" => "100")*/
                                            )
                                        );
                            } else {
                                $PostData[] = array(
                                    "freeFormServiceItem" => array(
                                        'category' => $req['categoryId'],
                                        'label' => array('displayName' => $req['displayName'],
                                            'description' => $req['description'],
                                            'languageCode' => 'fr'
                                    ),
                                       /*'price' => array("currencyCode" => "EUR",
                                            "units" => 100,
                                "nanos" => "100")*/));
                            }
                        }
                        }
                        $Location->serviceItems=$PostData;
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

        
    }



    public function delete(Request $request)
    {   
        $input=[];

        $messages = [
            'categorie.required' => 'Vérifier Votre Catégorie!',
            'service.required' => 'Vérifier Votre service!',
            'fiche_id.required' => 'Vérifier Votre Fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];
        $input=[
            'categorie'=>$request->categorie,
            'service'=>$request->service,
            "user_id" => $request->user_id,
            "fiche_id" => $request->idfiche,
        ];
        $validator = Validator::make($input,
            [
               
                //"user_id" => 'exists:users,id',
                "fiche_id" => 'exists:fiches,id',
            ], $messages
        );
        if ($validator->fails()) {
            return response()->json([
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422
            ],
                422);
        }
        $input = (object) $input;
        try {
            $client = Helper::googleClient();
                  
                $Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
            $idcategorie = $input->categorie;
            $servicetab=array();
            $idfiche = $input->fiche_id;
            $servicetab=array();
            $idservice = $input->service;
            $categorie = Categorie::where('categorieId', $idcategorie)->where('fiche_id', $idfiche)->first();
            $categorieprincipal=Categorie::where('type', 'primaryCategory')->where('fiche_id', $idfiche)->first();
            $fiche=Fiche::find($idfiche);
            $servicetabs=array();
            if ($idservice) {
            $service = Service::where('categorie_id', $categorie->id)
            ->where('id', $idservice)
            ->delete();
            if(Service::Where('categorie_id',$categorieprincipal->id)->exists()){
                $listcatservice=Service::where('categorie_id',$categorieprincipal->id)->get();
                $it=0;
                foreach($listcatservice as $lists){
                    $servicetabs[]= array('categoryId' => $categorieprincipal->categorieId,
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
           
            ServiceController::updateservice($servicetabs, $fiche->name);
                    return response()->json([
                        'success' => true,
                        'tavca'=>$categorie,
                        'message' => 'Service Supprimer avec succées',
                        'status' => 200,
                    ]);
            } else {
                if ($categorie->type != "primaryCategory") {
                    $categorie->delete();
                    $updateMask='categories';
                    $dataposttab=['primaryCategory'=> array("displayName" =>$categorieprincipal->displayName,
                    "name" => $categorieprincipal->categorieId)];

                    $categoriesecon=Categorie::where('type', $categorie->type )->where('fiche_id', $idfiche)->get();
if($categoriesecon->count()>0){
    foreach($categoriesecon as $cate){
        $CatSup[]=array("displayName" =>$cate->displayName,
        "name" => $cate->categorieId);
    }
    $datatab =  collect($dataposttab)->put('additionalCategories',$CatSup)->all();
}else{
    $datatab = ['primaryCategory'=> array("displayName" =>$categorieprincipal->displayName,
    "name" => $categorieprincipal->categorieId)];
}
                    
                    $Location->categories=$datatab;
                    FicheController::patchlocation($fiche->name,$updateMask,$Location);
                  /*  $service = Service::where('categorie_id', $categorie->id)->get();
                    if (count($service) > 0) {

                        $service->delete();
                    }
                    */
           return response()->json([
                        'success' => true,

                        'message' => 'Categorie Supprimer avec succées',
                        'status' => 200,
                    ]);

                } else {
                    return response()->json([
                        'success' => false,

                        'message' => 'Impossible de Supprimer categorie ' . $categorie->displayName . ' de type ' . $categorie->type,
                        'status' => 400,
                    ],400);
                }
            }


        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Categorie could not be deleted',
                'status' => 500,

            ], 500);
        }

    }
    public static function categorieservice($id)
    {
        $etatservold=true;
        $etatservo=true;
        $tabcat = Categorie::leftjoin('services', 
        'services.categorie_id', 'categories.id')
            ->where('categories.fiche_id', $id)
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
                    ->where('categories.fiche_id', $id)
                    //->where('categories.user_id', '=', Auth()->user()->id)
                    ->where('services.state','Inactif')->
                        exists();
                        if($tabcatexit){
                            $etatservo=false;
                        }
        $tabb = $tabcat->toarray();
        $outarray = array();
        $etatservolds=true;
       // $tabservice=array();
        foreach ($tabb as $fiche) {
            $tabservice=array();
            if($fiche['state']=='Inactif'){

            }
            $tabcategories= Service::where('categorie_id', $fiche['categorie_id'])
                ->select('serviceId', 'displayName as name','id','state')->get();
   
    
        foreach($tabcategories as $tab){
            $servold=Null;
           
        
       if($tab->state=='Inactif'){
        $etatservold=false;
       }
       $dataattribute= 
       ['categorie_id'=>(int)$fiche['categorie_id'],
        'serviceId'=>$tab->serviceId,
        "displayName"=>$tab->name
        ];
        
        if(Notification::where("diffMask",'service')->Where('newobject', 'LIKE', '%' .collect($dataattribute)->toJson(JSON_UNESCAPED_UNICODE).'%')->where('state','Inactif')->where('fiche_id',$id)->exists()){
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
        
            $outarray[] = array('idCat' => $fiche['categorieId'], 'nameCat' => $fiche['displayName'],
                'type' =>$fiche['type'],
                'Services' => $tabservice,'etatvalidation'=>$etatservolds);
        }
        return ["listservices"=>$outarray,'etatvalidation'=>$etatservo];
        //   return collect($outarray)->put('etatvalidation',$etatservold)->all();
    }
    public static function categorie($id)
    {
        $tabcatlist=array();
        $tabcat  = Categorie::where('fiche_id', $id)->select( 'displayName',
            'type','categorieId','state')
           ->orderBy('type', 'DESC')->
                get();
       ;
     
        foreach($tabcat as $serv){
            $catold=Null;
            $etatcatold=true;
            /*if($serv->state=='Inactif'){
                $etatcatold=false;
            }*/
            $datacat=['type'=> $serv->type,
            'displayName'=> $serv->displayName,
            'categorieId'=> $serv->categorieId,
            "fiche_id"=>(int)$id];
               
                if(Notification::where('diffMask',$serv->type)->Where('newobject', 'LIKE', '%' .collect($datacat)->toJson(JSON_UNESCAPED_UNICODE).'%')->where('state','Inactif')->where('fiche_id',$id)->exists()){
                    $etatcatold=false;  
                }else{
                    $etatcatold=true;
                }
         $tabcatlist[]=['displayName'=>$serv->displayName,'type'=>$serv->type,'categorieId'=>$serv->categorieId,
         'etatvalidation'=>$etatcatold];
        }
    
    return $tabcatlist;
  //  collect($tabcatlist)->put('etatvalidation',$etatcatold)->all();
        
    }
    public function updateficheservice(Request $request){
        try {
            $input=[];

            $messages = [
                'listServices.required' => 'Vérifier Votre Service!',
                'fiche_id.required' => 'Vérifier Votre Fiche!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following types: :values',
            ];
            $input=[
                "listServices" => $request->listServices,
                "fiche_id" => $request->fiche_id,
                "listfiche" => $request->listfiche,
            ];
            $validator = Validator::make($input,
                [
                 
                   
                    //"fiche_id" => 'exists:fiches,id',
                ], $messages
            );
            $updateMask = null;
            if ($validator->fails()) {
                return response()->json([
                    'succes' => false,
                    'message' => $validator->errors()->toArray(),
                    'status' => 422,
                ],
                    422);
            }
            $input = (object) $input;

            $updateMask = null;
            $id=null;
            if ($validator->passes()) {
               
                try {
                    $message="Service ajouter avec succes";
                  $listfiche=$input->listfiche;
                  $listServices=$input->listServices;
               if($listfiche){  
                   foreach($listfiche as $fiches){
                if($request->fiche_id && $fiches['status'] ==false){
                    $id= $request->fiche_id;
                    
                 $this->serviceup($id,$listServices);
                 }
                 else if($fiches['status']){
                   $id= $fiches['id'];
                  
                   $this->serviceup($id,$listServices);
                }
        } 
     }else{
        $id= $input->fiche_id;
        $this->serviceup($id,$listServices);
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
    public function serviceup($id,$listServices){
      
            try{
                $client = Helper::googleClient();
                  
                $Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
            $fiche = Fiche::find($id);
        $result=array();
        $tabcategoriP = Categorie::where('fiche_id', $fiche->id)
                    ->where('type','primaryCategory')->first();
     
        $i = 0;
        $servicetabs=array();
        foreach ($listServices as $list) {
          
            if($list['status']){
                 $categorieexit = Categorie::where('fiche_id', $fiche->id)
                     ->where('categorieId',  $list['categorieid'])
                     ->exists();
                 if ($categorieexit) {
                    $tabcategorie = Categorie::where('fiche_id', $fiche->id)
                    ->where('categorieId',$list['categorieid'])->first();
                 } else {
                 $datacat=['type' => 'additionalCategories',
                 'displayName'=>$list['displayNamecateg'],
                 'categorieId' =>$list['categorieid'],
                 'user_id' => Auth()->user()->id,
                 'fiche_id'=>$fiche->id];
                     $CatSup= array("displayName" =>$list['displayNamecateg'],
                     "name" => $list['categorieid']);
                     $updateMask = 'categories';
                     $idsupt[]= $list['categorieid'];
                  $dataposttab=['primaryCategory'=> array("displayName" =>$tabcategoriP->displayName,
                  "name" => $tabcategoriP->categorieId)];
                   $datatab =  collect($dataposttab)->put('additionalCategories',[$CatSup])->all();
                   $Location->categories=$datatab;
                   FicheController::patchlocation($fiche->name,$updateMask,$Location);
                   $tabcategorie = Categorie::create($datacat);
                 }
                 $message="Service ajouter avec succes";
                 $Verifservice=Service::where('categorie_id',$tabcategorie->id)->where('serviceId',$list['serviceId'])->exists();
                 if(!$Verifservice){
                   $data['serviceId'] = $list['serviceId'];
                   $data['displayName'] = $list['name'];
                   $data['user_id'] =Auth()->user()->id;
                   $data['categorie_id'] = $tabcategorie->id;
                   Service::create($data);
                    }
             $i++;
      }
      
       
   
      $i++;
   }      
   
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

 

         ServiceController::updateservice($servicetabs, $fiche->name);
            return true;
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
