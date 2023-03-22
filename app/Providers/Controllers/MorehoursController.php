<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Fiche;
use App\Models\Morehours;
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
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Helper\Helper;
use App\Models\Fichehour;
use App\Models\Notification;
use GoogleMyBusinessService;
use Google;

class MorehoursController extends Controller
{
    public $mybusinessService;
    public $placeID;
    public $locations;
    public $accounts;
    public $googleLocations;
    public $lists;

    public function __construct() {
    /*    $this->mybusinessService = Helper::GMB();
        $this->placeID = Helper::GMBcreate();
        $this->accounts = $this->mybusinessService->accounts;
        $this->locations = $this->mybusinessService->accounts_locations;
        $this->googleLocations = $this->mybusinessService->googleLocations;
        $this->lists = $this->accounts->listAccounts()->getAccounts();*/
    }
   
    public function store(Request $request)
    {

       
        $input = [];

        $messages = [

            'user_id.required' => 'Vérifier Votre user!',
            'fiche_id.required' => 'Vérifier Votre fiche!',
            'categorie_id.required' => 'Vérifier Votre categorie!',
        ];
        $input = [

            'user_id' => $request->user_id,
            'categorie_id'=>$request->categorie_id,
            'displayName'=>$request->displayName,
            'Listhoraire'=>$request->Listhoraire,
            'hoursTypeId'=>$request->hoursTypeId,
            'fiche_id'=>$request->fiche_id,

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
                'status' => 422,
            ],
                422);
        }
        $input = (object) $input;
        if ($validator->passes()) {
            try {
             //   $date = $request->all();
                $i = 0;  
                $periods=array();
                $moreHourst = array();
                 $updateMask="moreHours";
                 $periodhour=array();
                foreach ($input->Listhoraire as $horaire) {
                    $data['openDay'] = $horaire['jours'];
                    $data['closeDay'] = $horaire['jours'];
                    if($input->displayName){
                    $data['morehoursId'] = $input->displayName;
                    $hoursTypeId=$input->displayName;
                    }else{
                    $data['morehoursId'] = $input->hoursTypeId;
                    $hoursTypeId=$input->hoursTypeId;
                    }
                    
                    $data['displayName'] =$input->hoursTypeId; 
                    $data['fiche_id'] = $input->fiche_id;
                    $data['user_id'] = $input->user_id;
                    $cat = Categorie::where('categories.fiche_id', $input->fiche_id)->where
                    ('categories.type', 'primaryCategory')
                        //->where('categories.user_id', $request->user_id)
                        ->get();
                    $more = Morehours::where('morehoursId', $input->hoursTypeId)
                        ->where('openDay', $horaire['jours'])
                        ->where('closeDay', $horaire['jours'])
                        ->where('categorie_id', $cat[0]['id'])
                        ->where('user_id', $request->user_id)->get();
                    foreach ($more as $morehou) {
                        $delt = Morehours::find($morehou['id']);
                        $delt->delete();

                    }
                    if ($horaire['etat'] === 'true'|| $horaire['etat'] === true) {

                        foreach ($horaire['horaire'] as $hor) {

                            $data['categorie_id'] = $cat[0]['id'];
                                $data['type'] = 'true';
                                $data['openTime'] = $hor['heurdebut'];
                                $data['closeTime'] = $hor['heurfin'];
                               
                                $moreHourst[]= $data;
                                $periodhour[]= array('openDay' => $this->dateToAnglash($horaire['jours']),
                                "openTime" =>['hours'=>Carbon::parse($hor['heurdebut'])->format('H'),'minutes'=>Carbon::parse($hor['heurdebut'])->format('i')],
                                'closeDay' => $this->dateToAnglash($horaire['jours']),
                                "closeTime" =>['hours'=>Carbon::parse($hor['heurfin'])->format('H'),'minutes'=>Carbon::parse($hor['heurfin'])->format('i')]);
                                
                            }
                              $periods[]=["hoursTypeId"=>$input->hoursTypeId,
                                 "periods"=>$periodhour];
                             
                        

                    } elseif ($horaire['etat'] === 'false'|| $horaire['etat'] === false) {
                       
                        $data['openDay'] = $horaire['jours'];
                        $data['closeDay'] = $horaire['jours'];
                        $data['morehoursId'] = $input->hoursTypeId;
                        $data['displayName'] = $input->hoursTypeId;
                       
                        $data['fiche_id'] = $input->fiche_id;
                        $data['user_id'] = $input->user_id;
                        $more = Morehours::where('morehoursId', $input->hoursTypeId)
                            ->where('openDay', $horaire['jours'])
                            ->where('closeDay', $horaire['jours'])
                            ->where('categorie_id', $cat[0]['id'])
                            ->where('user_id', $input->user_id)->get();
                        foreach ($more as $morehou) {
                            $delt = Morehours::find($morehou['id']);
                            $delt->delete();

                        }
                    }
                    $i++;
                }
                $h=array();
                $moreList = Morehours::where('morehoursId','!=',$input->hoursTypeId)
                ->where('fiche_id', $input->fiche_id)->get();
                if($moreList){
                    foreach ($moreList as $key=> $value) {
                        $h[$value->morehoursId]["hoursTypeId"]=$value->morehoursId;
                        $h[$value->morehoursId]["periods"][]=array("openDay"=>$this->dateToAnglash($value->openDay),
                        "openTime"=>['hours'=>Carbon::parse($value->openTime)->format('H'),'minutes'=>Carbon::parse($value->openTime)->format('i')],
                        "closeDay"=>$this->dateToAnglash($value->closeDay),"closeTime"=>['hours'=>Carbon::parse($value->closeTime)->format('H'),'minutes'=>Carbon::parse($value->closeTime)->format('i')]);
                      }
                }
               
                array_push($h,array("hoursTypeId"=>$hoursTypeId,"periods"=>$periodhour));
                $h=array_values($h);
                 

                  $fiches=Fiche::find($input->fiche_id);
                   $client = Helper::googleClient();
                  
                  $Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
                  $Location->moreHours=$h;
                  $locationName=$fiches->name;
                  try {
                    FicheController::patchlocation($locationName,$updateMask,$Location);
                    Morehours::insert($moreHourst);
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
               
                    'data' => $this->morehours($request->fiche_id),
                    'status' => Response::HTTP_OK
                ], Response::HTTP_OK);
                    } catch (\Google_Service_Exception $ex) {

                        return response()->json([
                                    'success' => false,
                                    'message' =>$ex->getMessage(),
                                    'messages' =>$h,
                                    'status' => 400,
                                        ], 400
                        );
                    }
                    return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $this->morehours($request->fiche_id),
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
    // supprimer les horaires

    public function deletehoraire(Request $request)
    {
        $input = [];

        $messages = [

            'user_id.required' => 'Vérifier Votre user!',
            'fiche_id.required' => 'Vérifier Votre fiche!',
            'hoursTypeId.required' => "Vérifier Votre Type d'heures!",
        ];
        $input = [

            'user_id' => $request->user_id,
            'hoursTypeId'=>$request->hoursTypeId,
            'fiche_id'=>$request->fiche_id,

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
                'status' => 422,
            ],
                422);
        }
        $input = (object) $input;
        try {
           
            $i = 0;

            $data['morehoursId'] = $input->hoursTypeId;

            $data['fiche_id'] = $input->fiche_id;
            $data['user_id'] = $input->user_id;
            $cat = Categorie::where('categories.fiche_id', $input->fiche_id)->where
            ('categories.type', 'primaryCategory')->first();


            $more = Morehours::where('morehoursId', $input->hoursTypeId)
                ->where('categorie_id', $cat->id)
                ->where('user_id', $input->user_id)->get();
            foreach ($more as $morehou) {
                $delt = Morehours::find($morehou['id']);
                $delt->delete();

            }
            $h=array();
            $moreList = Morehours::where('fiche_id', $input->fiche_id)->get();
            if($moreList){
                foreach ($moreList as $key=> $value) {
                    $h[$value->morehoursId]["hoursTypeId"]=$value->morehoursId;
                    $h[$value->morehoursId]["periods"][]=array("openDay"=>$this->dateToAnglash($value->openDay),
                    "openTime"=>['hours'=>Carbon::parse($value->openTime)->format('H'),'minutes'=>Carbon::parse($value->openTime)->format('i')],
                    "closeDay"=>$this->dateToAnglash($value->closeDay),"closeTime"=>['hours'=>Carbon::parse($value->closeTime)->format('H'),'minutes'=>Carbon::parse($value->closeTime)->format('i')]);
             }
            }

            $h=array_values($h);
             
              $fiches=Fiche::find($input->fiche_id);
           
              $locationName=$fiches->name;

                 try {
              $updateMask="moreHours";
          $client = Helper::googleClient();
          $Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
          $Location->moreHours=$h;
      FicheController::patchlocation($locationName,$updateMask,$Location);
               return response()->json([
                'success' => true,
                'message' => 'Mise a jour traitée avec succes',
                'data' => $this->morehours($input->fiche_id),

                'status' => Response::HTTP_OK
            ], Response::HTTP_OK);
                    
               } catch (\Google_Service_Exception $ex) {

                        return response()->json([
                                    'success' => false,
                                    'message' => $ex->getMessage(),
                                    'status' => 400,
                                        ],$ex->getCode()
                        );
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

    /**
     * Display the specified resource.
     *
     * @param \App\Models\MoreHours $moreHours
     * @return \Illuminate\Http\Response
     */
    public function show(MoreHours $moreHours)
    {

        if (!$moreHours) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Document not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document id ' . $moreHours->id,
            'data' => $moreHours,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\MoreHours $moreHours
     * @return \Illuminate\Http\Response
     */
    public function edit(MoreHours $moreHours)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\MoreHours $moreHours
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MoreHours $moreHours)
    {

        $messages = [
            'morehoursId.required' => 'Vérifier Votre morehoursId!',
            'displayName.required' => 'Vérifier Votre displayName!',
            'localized.required' => 'Vérifier Votre localized!',
            'user_id.required' => 'Vérifier Votre user!',
            'categorie_id.required' => 'Vérifier Votre categorie!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];
        $validator = Validator::make($request->all(),
            [
                "morehoursId" => 'required|max:45',
                "displayName" => 'required|unique:roles,name|max:45',
                "localized" => 'required|unique:roles,name|max:45',
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

                $moreHours->morehoursId = $request->morehoursId;
                $moreHours->displayName = $request->displayName;
                $moreHours->localized = $request->localized;
                $moreHours->user_id = $request->user_id;
                $moreHours->categorie_id = $request->categorie_id;
                $moreHours->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $moreHours,

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

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\MoreHours $moreHours
     * @return \Illuminate\Http\Response
     */
    public function destroy(MoreHours $moreHours)
    {
        try {

            $moreHours->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'More Hours could not be deleted',
                'status' => 500,

            ], 500);
        }

    }
    public  static function horaireexp($id){
        $statetat=true; 
        $output = null;
 $TABS =array();
   $sources= Morehours::leftjoin('categories', 'categories.id', 'morehours.categorie_id')
            ->where('categories.fiche_id', $id)->where
            ('categories.type','primaryCategory')
            ->select(DB::raw('morehours.morehoursId,
            morehours.categorie_id,morehours.user_id,morehours.displayName'))
            ->groupBy('morehours.morehoursId',
                'morehours.categorie_id','morehours.user_id','morehours.displayName')->get();
foreach($sources as $key => $source) {
        $day[$source['morehoursId']] = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",
            "Samedi", "Dimanche");
    $output[$source['morehoursId']] = Morehours::where('morehoursId',$source['morehoursId'])
                    ->where('user_id',$source['user_id'])
                    ->where('categorie_id',$source['categorie_id'])
                    ->get();
}

$outputs = null;
if($output){
foreach($output as $key => $hourt) {
                              
   $outputs[$key] = $hourt->toarray();

}

foreach($outputs as $key => $source) {
    foreach($source as $item) {
        $etatvalidation=true;
           if($item['state']=='Inactif'){
            $etatvalidation=false;
                        }
                          $horairedcim[$key][$item['openDay']][] = array('heurdebut' => Carbon::parse($item['openTime'])->format('H:i'),
                           'heurfin' =>Carbon::parse($item['closeTime'])->format('H:i'), 'id' => $item['id'],'etatvalidation'=>$etatvalidation);
                           
    }
      $outputss[$key][]= array('hoursTypeId'=>$key,'horaire'=>$horairedcim[$key]);
  
}                   
 $x=0; 
      foreach ($day as $key=> $days) {
     $i=0;    
   foreach($days as $item) {
       if (array_key_exists($item, $outputss[$key][0]['horaire'])) {
                
                       $httd[$i]=    array('jours' => $item, 'etat'=>'true', 
                           'horaire' =>  $outputss[$key][0]['horaire'][$item]);

                        }
                        else {
                            $httd[$i] = array('jours' => $item,'etat'=>'', "horaire"=>array('heurdebut' => "", 'heurfin' => ""));
                        }  
     $i++;
                   
   } 
   $TABS[]= array('hoursTypeId'=>$key,'displayName'=>$key,'horaire'=>$httd); 
                     
                 $x++; 
                 } 
} 
                 return ['listhoraireexceptionnelle'=>$TABS,'etatvalidation'=>$statetat];  
         
      } 
  public static function morehours($id){
    
try{
 $output = null;
 $TABS =array();
   $sources= Morehours::leftjoin('categories', 'categories.id', 'morehours.categorie_id')
            ->where('categories.fiche_id', $id)->where
            ('categories.type','primaryCategory')
            ->select(DB::raw('morehours.morehoursId,
            morehours.categorie_id,morehours.user_id,morehours.displayName'))
            ->groupBy('morehours.morehoursId',
                'morehours.categorie_id','morehours.user_id','morehours.displayName')->get();
foreach($sources as $key => $source) {
        $day[$source['morehoursId']] = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",
            "Samedi", "Dimanche");
    $output[$source['morehoursId']] =Morehours::where('morehoursId',$source['morehoursId'])
                    ->where('user_id',$source['user_id'])
                    ->where('categorie_id',$source['categorie_id'])
                    ->get();
}
$outputs = null;
if($output){
foreach($output as $key => $hourt) {
   $outputs[$key] = $hourt->toarray();
}
foreach($outputs as $key => $source) {
    foreach($source as $item) {
           if($item['type']===1 ||$item['type']==='true' ){
                            $etat='true';
                        }else{
                            $etat='';
                        }
                          $horairedcim[$key][$item['openDay']][] = array('heurdebut' => Carbon::parse($item['openTime'])->format('H:i'), 'heurfin' =>Carbon::parse($item['closeTime'])->format('H:i'), 'id' => $item['id']);                     
    }
      $outputss[$key][]= array('hoursTypeId'=>$key,'horaire'=>$horairedcim[$key]);
}
                           
 $x=0; 
      foreach ($day as $key=> $days) {
     $i=0;    
   foreach($days as $item) {
       if (array_key_exists($item, $outputss[$key][0]['horaire'])) {
                
                       $httd[$i]= ['jours' => $item, 'etat'=>'true', 
                           'horaire' =>  $outputss[$key][0]['horaire'][$item]];
                        }
                        else {
                            $httd[$i] = ['jours' => $item,'etat'=>'', "horaire"=>array('heurdebut' => "", 'heurfin' => "")];
                        }  
     $i++;  
   } 
   $TABS[]= array('hoursTypeId'=>$key,'displayName'=>$key,'horaire'=>$httd); 
                 $x++; 
                 } 
}                 
return $TABS;
      } catch (QueryException $ex) {
          $TABS=array();
                return $TABS;
            }

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
