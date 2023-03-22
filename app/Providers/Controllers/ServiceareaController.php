<?php

namespace App\Http\Controllers;

use App\Models\Servicearea;
use Illuminate\Http\Request;
use SKAgarwal\GoogleApi\PlacesApi;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use GooglePlaces;
use Google;
use App\Helper\Helper;
use App\Models\Fiche;
use App\Models\Notification;
use App\Models\Serviceareashistorique;
use GoogleMyBusinessService;

class ServiceareaController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
     
                
    }
   
public static function servicebyfiche($id){
    $tabser=array();
    $etatser=true;
    
    $Servicearea =Servicearea::select('name as description',
            'placeId as place_id',
            'pays','businessType',
            'zone','state','fiche_id',
            'id')->where('fiche_id',$id)->get();
           
            $Serviceareaexit =Servicearea::where('fiche_id',$id)
            ->WHERE('state','Inactif')->exists();
            if($Serviceareaexit){
                $etatser=false;

            }
            foreach($Servicearea as $serv){
                $etatservold=true;
               $dataser= ["placeId"=>$serv->place_id,
                    "name"=>$serv->description,
                    "zone"=>$serv->zone,
                    "fiche_id"=>(int)$id,
                    "businessType"=>$serv->businessType];
                    if(Notification::where('diffMask','serviceArea')->Where('newobject', 'LIKE', '%' .collect($dataser)->toJson(JSON_UNESCAPED_UNICODE).'%')->where('state','Inactif')->where('fiche_id',$id)->exists()){
                        $etatservold=false; 
                    }
         /* if($serv->state=='Inactif'){
            $etatservold=false;
          }*/
          
           
                $tabser[]=['description'=>$serv->description,'place_id'=>$serv->place_id,'pays'=>$serv->pays,
                'zone'=>$serv->zone,'id'=>$serv->id,'etatvalidation'=>$etatservold,
                 ];
          
            }
            return ["listzone"=>$tabser,'etatvalidation'=>$etatser]; 
}
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
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
        $messages = [
            'fiche_id' => 'Vérifier Votre fiches!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
        ];
        $input = [
            'fiche_id' => $request->fiche_id,
            'zone'=>$request->zone
           
        ];
        $validator = Validator::make($input,
                        [
                            "fiche_id" => 'exists:fiches,id',
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
      
        if($validator->passes()){
            try {
           $metadata=array();
$fiche=Fiche::find($input->fiche_id);


           if(isset($fiche->address)){
            $businessType="CUSTOMER_AND_BUSINESS_LOCATION";
        }else{
            $businessType="CUSTOMER_LOCATION_ONLY";
        }
$data['fiche_id']=$input->fiche_id;
$id=array();
   $placeInfos[]= ["placeName" => "",
   "placeId" => ""];
   $serviceArea= ["businessType" => $businessType,"regionCode"=>'',
   "places" =>["placeInfos" =>$placeInfos ]
                
                  ];
if(count($input->zone)===0){
    Servicearea::where('fiche_id',$input->fiche_id)->delete();

}
else{
foreach($input->zone as $zon){

   $data['placeId']=$zon['place_id'];
   $data['name']=$zon['description'];
   $data['pays']=$zon['pays'];
   $data['zone']=$zon['zone'];
   $data['businessType']=$businessType;
   $placeInfos[]= ["placeName" => $zon['description'],
                            "placeId" => $zon['place_id']];
                          
                         
   $serviceArea= ["businessType" => $businessType,
                
                "places" =>["placeInfos" =>$placeInfos ] ];
 
   if(array_key_exists('id', $zon))
   {
        $id[]=$zon['id'];
    }
    else{$data['state']='Actif';
      $metadata= Servicearea::create($data);  
      
      $data['serviceareas_id']=$metadata->id;
       $id[]=$metadata->id;
    }
    
    
}

if($id){

 Servicearea::whereNotIn('id',$id)->where('fiche_id',$request->fiche_id)->delete();
}
}

//$this->updatefiche($serviceArea,$request->fiche_id);
                      $client = Helper::googleClient();
                      $Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
                      $Location->serviceArea=$serviceArea;
                        $updateMask = "serviceArea";
                        FicheController::patchlocation($fiche->name,$updateMask,$Location);

                return response()->json([
                    'success' => true,
                    'message' => 'Enregistrement terminé',
                    'data' => $metadata,
                    'status' => Response::HTTP_OK,
             
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json(
                    [
                        'success' => false,
                        'message' =>  $ex->getMessage(),
                        'status' => 400,
                        
                    ],
                    400
                );
            }
        }
        else{
            return response()->json([
                'succes'=>false,
                'message'=>$validator->errors()->toArray(),
                'status'=>422,
                ],
                422);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ServiceArea  $serviceArea
     * @return \Illuminate\Http\Response
     */
    public function show(ServiceArea $serviceArea) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ServiceArea  $serviceArea
     * @return \Illuminate\Http\Response
     */
    public function edit(ServiceArea $serviceArea) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServiceArea  $serviceArea
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ServiceArea $serviceArea) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ServiceArea  $serviceArea
     * @return \Illuminate\Http\Response
     */
    public function destroy(ServiceArea $serviceArea) {
        //
    }

    public function zonedesservies(Request $request) {

        try {
        $locality=array();
      $googlePlaces = new PlacesApi('AIzaSyB5yiQrmGBoW3kiAUIjg3DvKbIBPVt937U');
         $response = $googlePlaces->queryAutocomplete($request->ville)->get('predictions');
         
            $regions=['locality','sublocality','postal_code','country','colloquial_area','administrative_area_level_1','administrative_area_level_2','administrative_area_level_3'];
            foreach ($response as $resp) {
        foreach ($regions as $region) {
            if(array_key_exists('types',$resp) )
       { 
      if(in_array($region,$resp['types'])){
         
      if(count($resp['terms'])===1){
         $pays=$resp['terms'][0]['value'];
      }
      else if(count($resp['terms'])===3){ 
          $pays=$resp['terms'][2]['value'];
      }else {
          $pays=$resp['terms'][1]['value'];
      }
                $locality[] = ["description" => $resp["description"],
                    "place_id" => $resp['place_id'],
                    "pays" => $pays,
                    "zone" => $resp['terms'][0]['value']];
            }
        }
          
        }
            }
              return response()->json([
                        'success' => true,
                        'message' => 'Opération terminer avec succes',
                        'data' => $locality,
                        'status' => 200
                            ], 200);
        }catch (\Google_Service_Exception $ex) {
            return response()->json(
                            [
                                'success' => false,
                                'message' => $ex->getMessage(),
                                'status' => 400,
                            ]
            );
        }
    }



 
   public function updatefiche($request,$id) {

        try {
            $mybusinessService= \App\Helper\Helper::GMB();
            $accounts = $mybusinessService->accounts;
            $locations = $mybusinessService->accounts_locations;
            $lists = $accounts->listAccounts()->getAccounts();
            foreach ($lists as $ac) {
            //$locationName='chama test cron Create fiche wallpostt 008';
    $fiche=  Fiche::find($id);
       $locationName=  $fiche->locationName;
                try {


                    $locationsList = $locations->listAccountsLocations($ac['name'], array('pageSize' => 10, 'filter' => "locationName=%22$locationName%22"));
//var_dump($locationsList["locations"][0]);exit;
                    if (count($locationsList["locations"]) > 0) {

                        $placeIDs = Helper::GMBcreate();
                        
            $updateMask = null;
        
  
                       
                          $placeIDs->serviceArea =$request;
                     
                           //$updateMask='serviceArea';
                     $reslt=$mybusinessService->accounts_locations->patch($fiche->name,
                                $placeIDs, array('updateMask' => $updateMask,
                            'validateOnly' => false, 'attributeMask' => $updateMask));

                        return array([
                                'success' => true,
                                'message' => 'Opération terminer avec succes',
                                'data' => $reslt,
                                'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
                    } else {
                        return array([
                                'success' => false,
                                'message' => "Fiche n'existe pas au google my business",
                                'status' => 400
                            ], 400);
                    }
                } catch (\Google_Service_Exception $e) {

                    $rest = json_decode($e->getMessage(), true);

                    if ($e->getCode() === 400) {


                        $message = $rest['error']['message'];
                    } elseif ($e->getCode() === 429) {

                        $message = $rest['error']['message'];
                    } else {
                        $message = $e->getMessage();
                    }
                    return array([
                            'success' => false,
                            'message' => $message,
                            'status' => $e->getCode(),
                            'data' => ''
                        ], $e->getCode());
                }
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
