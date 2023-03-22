<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Attribute;
use App\Models\Fiche;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use App\Models\profilincomplete;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Accountagence;
use App\Models\Attributeshistorique;
use App\Models\Categorie;
use App\Models\Notification;
use Google\Api\Expr\V1alpha1\EvalState\Result;
use PhpParser\Node\Stmt\Static_;
use GoogleMyBusinessService;
use Google;

class AttributeController extends Controller {

 
    public function __construct() {
      
    }
    public function store(Request $request) {

        $messages = [

            'user_id.required' => 'Vérifier Votre User!',
            'fiche_id.required' => 'Vérifier Votre fiche!',

        ];
        $input = [
          // 'user_id' => $request->user_id,
            'fiche_id' => $request->fiche_id,
           'listeAttributs'=>$request->listeAttributs

           
        ];
        $validator = Validator::make($input,
                        [
                        //    "user_id" => 'exists:users,id',
                          //  "fiche_id" => 'exists:fiches,id',
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
        if ($validator->passes()) {
            try {
              if($input->fiche_id){
                  $fiche=Fiche::find($input->fiche_id);
                $test=  $this->addatribute($fiche,$request->listeAttributs);
              }elseif($request->listfiche){
foreach($request->listfiche as $fiches){
    if($fiches['status']==true){
        $fiche=Fiche::find($fiches['id']);
        $test= $this->addatribute($fiche,$request->listeAttributs);
    }
}
              }
              return response()->json([
                'success' => true,
                'message' => 'Atrribute ajouter avec succes',
                'data' => $test,
                'status' => Response::HTTP_OK
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
    public static function addatribute($fiches,$listeAttributs){
        $updateMask = null;
        $client = Helper::googleClient();
        $attributes=array();
        $service = new Google\Service\MyBusinessBusinessInformation($client); 
        if($listeAttributs==='attributes/url_appointment'){
            $attributes[] = ['name' => 'attributes/url_appointment',
            "valueType" => 'URL']; 
            $updateMask ='attributes/url_appointment';
        }
            $datas = array();
            $i = 0;
            $data['fiche_id'] = $fiches->id;
            $location = $fiches->name;
            $arrayName= explode( '/', $location );
            $Name= $arrayName[2] . '/' . $arrayName[3];
           $attrs=Attribute::where('fiche_id','=',$fiches->id)
           ->where('attributeId','=',"attributes/url_appointment")->whereNotNull('urlValues')->get();
            foreach($attrs as $attr){
                $etat=false;
             if($attr['values']==="true"){
                 $etat=true;
             }
               $attributes[] = ['name' => $attr['attributeId'],
               "valueType" => $attr['valueType'],
               'uriValues'=>[['uri'=>$attr['urlValues']]]
           ];
           $updateMask = $attr['attributeId'].',';
            }
            if(isset($listeAttributs) && $listeAttributs!='attributes/url_appointment')
         {
            foreach ($listeAttributs as $attribute) {
                $data['groupDisplayName'] = $attribute['groupeattribute'];
                foreach ($attribute['details'] as $attr) {
                    
                    if ($attr['etat'] ==='true'|| $attr['etat'] ==='Closed') {
                       
                        if ($attr['etat'] === "true") {
                            $etat = true;
                            $data['displayName'] = $attr['displayName'];
                        } else {
                            $etat = false;
                            $categorie = Categorie::where('fiche_id', $fiches->id)
                            ->where('type', 'primaryCategory')->first();
                             $list = $this->getAttribute($categorie->categorieId);
                            if (in_array($attr['displayName'], $list)) {
                                foreach ($list as $val) {

                                    if (array_key_exists('valueMetadata', $val)) {
                                        foreach ($val['valueMetadata'] as $vals) {
                                            if ($vals['value'] === false) {
                                                $name = $vals['displayName'];
                                            }
                                        }
                                    }
                                    $data['displayName'] = $attr['displayName'];
                                }
                            }
                        }
                       if($attr['attributeId']!="attributes/url_appointment") {
                            $attributes[] = ['name' => $attr['attributeId'],
                            "valueType" => $attr['valueType'],
                            'values' => [$etat]
                     ];
                     $updateMask .= $attr['attributeId'].',';
                        }
                      
                    
                        $data['attributeId'] = $attr['attributeId'];
                        $data['displayName'] = $attr['displayName'];
                        $data['valueType'] = $attr['valueType'];
                        if (array_key_exists('repeatedEnumValue', $attr)) {
                            $data['repeatedEnumValue'] = array("setValues" => $attr['setValues'], "unsetValues" => $attr['unsetValues']);
                        }
                        if (array_key_exists('urlValues', $attr)) {
                            $data['urlValues'] = array("url" => $attr['urlValues']);
                        }
                        $data['values'] = $attr['etat'];
                        $datas[] = $data;
                    }else if($attr['etat'] ==='false') {
                        if(Attribute::where ('attributeId' , $attr['attributeId'])->where('fiche_id',$fiches->id)->exists()){
                        Attribute::where ('attributeId' , $attr['attributeId'])
                        ->where('displayName',$attr['displayName'])
                        ->where('valueType', $attr['valueType'])
                        ->where('fiche_id',$fiches->id)->delete();
                        }
                        
                    }
                }
                $i++;
            }
        }
        try {
                    
                    
                      try {
                        $attribute =  new Google\Service\MyBusinessBusinessInformation\Attributes();
                        $attribute->attributes= $attributes;
                        $attributeMask= ['attributeMask'=>$updateMask];
                        $list_attributes_response = $service->locations->updateAttributes($Name."/attributes",$attribute,$attributeMask);
        foreach($datas as  $dataatr){
            $dataatr['state']='Actif';
            $atts = Attribute::updateOrCreate($dataatr,['user_id'=> Auth()->user()->id]);
        } 
        $dataprofil['attributes']=false;
        if(Attribute::where ('attributeId' ,'NOT Like','attributes/url_appointment')->where('fiche_id',$fiches->id)->exists()){
        $dataprofil['attributes']=true;
        }            profilincomplete::updateOrCreate(['fiche_id'=>$fiches->id],$dataprofil);
                       UserController::totalprofilincomplet($fiches->id);
        return ['name'=>$Name.'/attributes','attributes'=>$attributes];
            }
            catch (\Google_Service_Exception $ex) {
                return response()->json([
                            'success' => false,
                            'message' => "La requête contient un argument invalide",
                            'status' => 400,
                                ],$ex->getCode()
                );
            }
         } catch (\Google_Service_Exception $ex) {

                return response()->json([
                            'success' => false,
                            'message' => "La requête contient un argument invalide",
                            'status' => 400,
                                ],$ex->getCode()
                );
            }
        
    }

    public function destroy(Attribute $attribute) {
        try {
            $attribute->delete();
            return response()->json([
                        'success' => true,
                        'message' => 'Supprimer avec succées',
                        'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                        'success' => false,
                        'message' => 'Attribute could not be deleted',
                        'status' => 500,
                            ], 500);
        }
    }

    public function listattributs(Request $request) {
        $fiches = Fiche::find($request->fiche_id);
        $name = $fiches->locationName;
        $nameaccount = Accountagence::leftjoin('ficheusers','ficheusers.franchise_id','=','accountagences.franchise_id')
        ->where("ficheusers.user_id", Auth()->user()->id)
        ->first();
        $lists = $this->accounts->listAccounts()->getAccounts();
        $listsupplementaires = array();
                $locationsList=$this->locations->get($name);
                foreach ($locationsList["locations"] as $value) {
                    if (array_key_exists('moreHoursTypes', $value['primaryCategory'])) {
                        $listsupplementaires = $value['primaryCategory']['moreHoursTypes'];

                        $x = 0;
                        if (array_key_exists('moreHours', $value)) {

                            foreach ($value['moreHours'] as $typehour) {
                                foreach ($listsupplementaires as $values) {

                                    if (array_search($typehour['hoursTypeId'], $values)) {
                                        $x++;
                                    } else {
                                        $tab[] = $values;
                                        $x++;
                                    }
                                }
                            }
                        } else {
                            $tab[] = $listsupplementaires;
                        }


                        return response()->json([
                                    'success' => true,
                                    'message' => 'Liste des services spécifiques ou des offres spéciales',
                                    'data' => $tab,
                                    'details' => MorehoursController::morehours($request->fiche),
                                    'status' => 200]);
                    }
                }
      

    }

    public static function detailsattribute($id) {
        $etastate=true;
   
        $tabcat = Attribute::where('fiche_id', $id)
                        ->where('valueType','<>','URL')
                        ->where('attributeId','<>','attributes/url_appointment')
                        ->select(DB::raw('count(*) as attribute_count,groupDisplayName'))
                        ->groupBy('groupDisplayName')->get();
      $tabb = $tabcat->toarray();
        $outarray = array();
        $tabattribute=array();
       
        $tabcatd= Attribute::where('fiche_id', $id)
         ->where('state','Inactif')
         ->exists();
         if( $tabcatd){
            $etatservold=false;
         }
        foreach ($tabb as $fiche) {
            $attribute=array();
                    $tabcategorie= Attribute::where('fiche_id', $id)
                    ->where('groupDisplayName', $fiche['groupDisplayName'])
                       
                        ->where(function ($query) {
                            $query->where('values', 'true')
                            ->orwhere('values', 'Closed');
                           
                        })
                        ->where('valueType','<>','URL')
                        ->where('attributeId','<>','attributes/url_appointment')
                    ->select('id', 'attributeId', 'displayName', 'values', 'valueType', 'urlValues','state')
                    ->get();
                    $tabattribute=array();
                foreach($tabcategorie as $tab){
                    $etatservold=true;
                   if($tab->state=='Inactif'){
                    $etatservold=false;
                   }
                 $dataattribute= 
                  ['displayName'=>$tab->displayName,
                   'groupDisplayName'=>$fiche['groupDisplayName'],
                 "attributeId"=>$tab->attributeId,
                   'valueType'=>$tab->valueType,
                   //'urlValues'=>$tab->urlValues,
                  'values'=>$tab->values, "fiche_id"=>(int)$id
                   ];
                   if(Notification::where("diffMask",'attributes')->Where('newobject', 'LIKE', '%' .collect($dataattribute)->toJson(JSON_UNESCAPED_UNICODE).'%')->where('state','Inactif')->where('fiche_id',$id)->exists()){
                    $etatservold=false; 
                    $etastate=false;
                }
                    $tabattribute[]=['id'=>$tab->id,
                    'attributeId'=>$tab->attributeId,'displayName'=>$tab->displayName,
                    'values'=>$tab->values,
                    'valueType'=>$tab->valueType,
                    'urlValues'=>$tab->urlValues,
                    'etatvalidation'=>$etatservold,
                  
                    ];
                }
            $outarray[] = array('groupDisplayName' => $fiche['groupDisplayName'],
                'Services' => $tabattribute);
        }
        return ["listattribute"=>$outarray,'etatvalidation'=>$etastate];
      // return $outarray;
    }
    
    public static function priserendez($id) {
        $tabattributes=array();
        $etatservold=true;
            $tabcategorie = Attribute::where('fiche_id', $id)
                    ->where('valueType','=','URL')
                    ->where('attributeId','=','attributes/url_appointment')
                    ->select('attributeId', 'displayName', 'values', 'valueType', 'urlValues','state')->first();
                      $url='';
                      if($tabcategorie){
                        $url=$tabcategorie->urlValues;
                     
                    /*  if($tabcategorie->state =='Inactif'){
                        $etatservold=false;
                      }*/
                      $dataattribute=  '"attributeId":"'.$tabcategorie->attributeId;
                      if(Notification::where("diffMask",'attributes')->Where('newobject', 'LIKE', '%' .$dataattribute.'%')->where('state','Inactif')->where('fiche_id',$id)->exists()){
                        $etatservold=false; 
                    }else{
                        $etatservold=true;
                    }
                      $tabattributes=[
                        
                        'urlValues'=>$url,
                        'etatvalidation'=>$etatservold,
                        ];
                    }
         //   $tabattribute=$attribute;
        return $tabattributes;
    }
    public function attribute(Request $request) {
        $fiches = Fiche::find($request->fiche_id);
        $categorie = \App\Models\Categorie::where('fiche_id', $request->fiche_id)
                        ->where('type', 'primaryCategory')->get()->toarray();
        $opt = array(
            "languageCode" => "fr",
            'categoryId' => $categorie[0]['categorieId'],
             'pageSize' => 100,
        );

        try {
            $list =$this->getAttribute($categorie[0]['categorieId']);
           $id=$request->fiche_id;
            if ($request->searchAttribut) {
                $search=$request->searchAttribut;
                $itemCollection = collect($list);
                $filtered = $itemCollection->filter(function ($item) use ($search) {
                    return stripos($item['groupDisplayName'], $search) !== false;
                });
                $filteres = $itemCollection->filter(function ($item) use ($search) {
                    return stripos($item['displayName'], $search) !== false;
                });
                $list =array_merge($filtered->all(), $filteres->all());
            } 
            $result = array();
            $tab=array();
            foreach ($list as $val) {
                
                $valueMetadata=null;
                          /* if($val['isRepeatable']==true){
                            $valueMetadata = true;
                            }
                             else{
                                $valueMetadata=true;
                             }*/
             
                   
                $attribut = Attribute::where("displayName", $val['displayName'])->where('fiche_id', $id);
                $etat = "false";
                    if ($attribut->exists()) {
                        $attribut=$attribut->first();
                        if( $attribut->values=='true' ||  $attribut->values===1){
                            $etat = 'true';
                        }elseif( $attribut->values=== 'Closed' ||$attribut->values=== 0 ){
                            $etat = 'Closed';
                        }
                    }
                  //  if ($valueMetadata === true) {
                        $result[$val["groupDisplayName"]][] = 
                        array("attributeId" => $val['parent'],
                            "attributeId" => $val['parent'],
                            "valueType" => $val['valueType'],
                            "displayName" => $val['displayName'],
                            'values' => $valueMetadata,
                            "etat" => $etat
                        );
                  //  }
              
            
        }
            foreach ($result as $key => $value) {
                $tab[] = array('groupeattribute' => $key, 'details' => $value);
            }
            return response()->json([
                        'success' => true,
                        'message' => 'Opération terminer avec succes',
                        'data' => $tab,
                        'datakey' => $list,
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
        } catch (\Google_Service_Exception $ex) {
            return response()->json(
                            [
                                'success' => false,
                                'message' => $ex->getMessage(),
                                'status' => 400,
                            ],400
            );
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
            'franchises_id' => $request->header('franchise'),
           
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
            if ($validator->passes()) {
               
                try {
                    $listfiche=$request->listfiche;
                    $id=$request->fiche_id;
                    if($listfiche){
                        $listfiche= $request->listfiche['listfiche'];
                        foreach($listfiche as $fiches){
                            if($fiches['urlvalues']){
                           $id=$fiches['id'];
                           $this->priserdv($fiches['urlvalues'],$id);
                           return response()->json([
                            'success' => true,
                            'message' => "Liens pour prise rendez-vous ajoute avec succes",
                            'data' => $this->priserdv($fiches['urlvalues'],$id),
                            'status' => Response::HTTP_OK
                                ], Response::HTTP_OK);
                         }
                    }
              }  
                else{
                    $url=null;
                    $prd=null;
                    if (array_key_exists('websiteUrl', $request->liens[0])) {
                    $url =$request->liens[0]['websiteUrl'];
                    }
                    $prd=$request->liens[0]['lienrdv'];
                    $this->priserdv($prd,$id,$url) ;
                    return response()->json([
                        'success' => true,
                        'message' => "Liens pour prise rendez-vous ajoute avec succes",
                        'data' =>  $this->priserdv($prd,$id,$url),
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
                }
                return response()->json([
                    'success' => true,
                    'message' => "Liens pour prise rendez-vous ajoute avec succes",
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
    public function priserdv($urlvalues,$id,$url=null){
        try{

            $client = Helper::googleClient();
                  
            $Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
                $updateMask =null;
                 $fiche = Fiche::find($id);
                 
                 $location = $fiche->name;
                 $arrayName= explode( '/', $location );
                 $Name= $arrayName[2] . '/' . $arrayName[3];
              
                 // ajouter lien prise de rdv

                 if($urlvalues){

                    $attrs=Attribute::where('fiche_id','=',$fiche->id)
                    ->where('attributeId','!=',"attributes/url_appointment")->get();
                     foreach($attrs as $attr){
                         $etat=false;
                      if($attr['values']==="true"){
                          $etat=true;
                      }
                      $attributes[] = ['name' => $attr['attributeId'],
                      "valueType" => $attr['valueType'],
                      'values' => [$etat]
                    ];
                    $updateMask = $attr['attributeId'].',';
                     }
                    
                 $attributeId="attributes/url_appointment";
                 $valueType="URL";
                // $data['attributeId'] = "attributes/url_appointment";
                 $data['valueType'] =$valueType;
                 $data['urlValues'] = $urlvalues;
                 $data['fiche_id']=$fiche->id;
                 //$data['user_id']=Auth()->user()->id;
                
                 $updateMask .= $attributeId;
                 $client = Helper::googleClient();
                 $service = new Google\Service\MyBusinessBusinessInformation($client); 
                 $attributes =  new Google\Service\MyBusinessBusinessInformation\Attributes();
              //   $this->getAttributesfiche($Name);
                 $attribute= [
                    'name' => "attributes/url_appointment",
                    'valueType' => $valueType,
                    'uriValues'=>[['uri'=>$urlvalues]]];
                  
                   $attributeMask= ['attributeMask'=>$attributeId];
            
                      try {
                       
                        $attributes->attributes= $attribute;
               
                        $list_attributes_response = $service->locations->updateAttributes($Name."/attributes",$attributes,$attributeMask);
                        Attribute::updateOrCreate(['fiche_id' => $id,'attributeId'=>$attributeId],$data);
                        $dataprofil['attributesUrl']=true;
                        profilincomplete::updateOrCreate(['fiche_id'=>$id],$dataprofil);
                        UserController::totalprofilincomplet($id);
                        return $list_attributes_response;
                
                       }
                         catch (\Google_Service_Exception $ex) {
                return response()->json([
                            'success' => false,
                            'message' => "La requête contient un argument invalide",
                            'status' => 400,
                                ],$ex->getCode()
                );
            }








                 }
                 if($url){
                     //ajouter site web
                     $fiche->websiteUrl=$url;
                     $fiche->update();
                     $Location->websiteUr=$url;
                     $updateMask .= ",websiteUri";
                     FicheController::patchlocation($location,$updateMask,$Location);
                     $dataprofil['attributesUrl']=true;
                     profilincomplete::updateOrCreate(['fiche_id'=>$id],$dataprofil);
                     UserController::totalprofilincomplet($id);
                 }
                  
                  return TRUE;
             } catch (\Google_Service_Exception $ex) {
                 return response()->json([
                             'success' => false,
                             'message' => "La requête contient un argument invalide",
                             'status' => 400,
                                 ], $ex->getCode()
                 );
             }

    }
    public Static function getAttribute($searchTerm)
    {
   $datcat=array();
        try {

            try {
                $client = Helper::googleClient();
                $service = new Google\Service\MyBusinessBusinessInformation($client); 
                $params=["regionCode"=>"FR","languageCode"=>"fr","showAll"=>false,'categoryName'=>$searchTerm];
                $list= $service->attributes->listAttributes($params);
              
            
                  return $list;
               
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
  public static function getAttributesfiche ($name)
    {
   $datcat=array();
        try {
            $client = Helper::googleClient();
          $tab=$client->getAccessToken();

         
            try {
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
             return json_decode($response,1);
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

}
