<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Fiche;
use App\Models\Fichehour;
use App\Models\FicheHourhistorique;
use App\Models\Iconfiche;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Google\Service\Transcoder\Input;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Null_;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use GoogleMyBusinessService;
use Google;

class FichehourController extends Controller
{
    public $mybusinessService;
    public $placeID;
    public $locations;
    public $accounts;
    public $lists;

    public function __construct() {
        /*$this->mybusinessService = Helper::GMB();
        $this->placeID = Helper::GMBcreate();
        $this->accounts = $this->mybusinessService->accounts;
        $this->locations = $this->mybusinessService->accounts_locations;
        $this->lists = $this->accounts->listAccounts()->getAccounts();*/
    }
    public function store(Request $request)
    {  $input = [];

        $messages = [

            'fiche_id.required' => 'Vérifier Votre fiche!',
            'user_id.required' => 'Vérifier Votre User!',
            'Listhoraire.required' => 'Vérifier Votre Liste Horaire!'
        ];
        $input = [

            'fiche_id' => $request->fiche_id,
            'user_id' => $request->user_id,
            'Listhoraire'=>$request->Listhoraire,

        ];

        $validator = Validator::make($input,
            [

                "fiche_id" => 'exists:fiches,id',
                "user_id" => 'exists:users,id',
                "Listhoraire" => 'required',
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
       $user= User::whereNotIn('role_id',[1,2])->where('id',Auth()->user()->id)->exists();
       if ($user) {
        return response()->json([
            'succes' => false,
            'message' => 'impossible de traiter cette demande',
            'status' => 400,
        ],
            400);
    }
    $input = (object) $input;
        if ($validator->passes()) {
            try {
                   $id= $input->fiche_id;
                   $listfichelisthoraire['Listhoraire']=$input->Listhoraire;
                   // appel function create horaire 
                   $this->cretahour($id,$listfichelisthoraire);
             
                return response()->json([
                'success' => true,
                'message' => 'Mise a jour traitée avec succes',
               'data' =>  $this->cretahour($id,$listfichelisthoraire),
              'status' => Response::HTTP_OK
             ], Response::HTTP_OK);
            }
           catch (QueryException $ex) {
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

    public function destroy(Fichehour $fichehour)
    {
        try {

            $fichehour->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Fiche hour could not be deleted',
                'status' => 500,

            ], 500);
        }

    }
    public function updatefichehoraire(Request $request){
        try {
            $input = [];

        $messages = [

            'fiche_id.required' => 'Vérifier Votre fiche!',
           
            'horaire.required' => 'Vérifier Votre Liste Horaire!'
        ];
        $input = [
            'horaire' => $request->horaire,
            'listfiche'=>$request->listfiche,
            'fiche_id'=>$request->fiche_id,

        ];

        $validator = Validator::make($input,
            [
            //   "listfiche.id" => 'exists:fiches,id',
              // "fiche_id" => 'exists:fiches,id',
              // "horaire" => 'requried',
            ], $messages
        );
       
        if ($validator->fails()) {
            return response()->json(['succes' => false,
                        'message' => $validator->errors()->toArray(),
                        'status' => 422,
                            ],
                            422);
        }
            $user= User::whereNotIn('role_id',[1,2])->where('id',Auth()->user()->id)->exists();
            if ($user) {
             return response()->json([
                 'succes' => false,
                 'message' => 'impossible de traiter cette demande',
                 'status' => 400,
             ],
                 400);
         }
         $input = (object) $input;

            if ($validator->passes()) {
               
                try { 
                  $listfiche=$input->listfiche;
               
                  $listfichelisthoraire=$input->horaire;
                  $message ='Horiare ajouter avec succes';
         if($listfiche){
    foreach($listfiche as $fiches){
        $id=null;
        if($input->fiche_id && $fiches['status'] ==false){
            $id= $input->fiche_id;
     }
         else if($fiches['status']){
          
           $id= $fiches['id'];
      
        }
         // appel function create horaire  by liste fiche 
         if($id){
            $this->cretahour($id,$listfichelisthoraire);
         }
    
}  
}else {
      // appel function create horaire  raccourci fiche  
       $id= $input->fiche_id;
       $this->cretahour($id,$listfichelisthoraire);
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


            $input = [];

            $messages = [
    
                'fiche_id.required' => 'Vérifier Votre fiche!',
                'Listhoraireexexceptionnels.required' => 'Vérifier Votre Liste Horaire!'
            ];
            $input = [
    
                'listfiche' => $request->listfiche,
                'Listhoraireexexceptionnels'=>$request->Listhoraireexexceptionnels,
                'fiche_id'=>$request->fiche_id,
    
            ];
    
            $validator = Validator::make($input,
                [
    
                   // "fiche_id" => 'exists:fiches,id',
                ], $messages
            );
            if ($validator->fails()) {

                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ],
                                422);
            }
            $user= User::whereNotIn('role_id',[1,2])->where('id',Auth()->user()->id)->exists();
            if ($user) {
             return response()->json([
                 'succes' => false,
                 'message' => 'impossible de traiter cette demande',
                 'status' => 400,
             ],
                 400);
         }

         

            $updateMask = null;
            $id='';
            $input = (object) $input;
            if ($validator->passes()) {
               
                try {
                    $message=null;
                  $listfiche=$input->listfiche;
                  if(array_key_exists("Listhoraireexexceptionnels",$request->toArray())){
                  $horaireexp=$input->Listhoraireexexceptionnels['Listhoraire'];
                if(isset($listfiche)){

                foreach($listfiche as $fiches){
                    $id=null;
                        $message ='Horiare exceptionnels ajouter avec succes';
                     if($input->fiche_id && $fiches['status'] ==false){
                        $id= $input->fiche_id;

                        
                     }
                     else if($fiches['status']){
                      
                       $id= $fiches['id'];
                    }
                    if($id){
                        $this->hourairexcep($id,$horaireexp);
                    }
                   
                    
                }  
            }
            else{
                $id=$input->fiche_id;
                $message ='Horiare exceptionnels ajouter avec succes';
                $this->hourairexcep($id,$horaireexp); 
            }
        }elseif(array_key_exists("horairexceptionnels",$request->toArray())){
         
            $id=$request->fiche_id;
            $message ='Horiare exceptionnels ajouter avec succes';
            $shourairexcep =$this->hourairexcep($id,$request->horairexceptionnels); 
        }
                return response()->json([
                    'success' => true,
                    'message' => $message,
                  'data'=>[],
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
    public function horairebyfiche(Request $request){
        $id=$request->fiche_id;
        $fichehours = FicheHourhistorique::where('fiche_hourhistoriques.fiche_id', $id);


           
        $day = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",
            "Samedi", "Dimanche");
        if ($fichehours->exists()) {
            $fichehours=  $fichehours->get();
            foreach ($fichehours as $hours) {
                $detailshoraire=Fichehour::join('fiche_hourhistoriques','fichehours.id','=','fiche_hourhistoriques.fichehours_id')
                ->where('fichehours.fiche_id',$id)
                ->where('fichehours.open_date',$hours->open_date)
                ->select('fichehours.open_date as open_datepre','fiche_hourhistoriques.open_date',
                'fichehours.open_time as open_timepre','fichehours.close_time as close_timepre')->first();
               
                $tab[] = $hours->open_date;
                if($hours->type == 1 ||$hours->type == 'true' ){
                    $etat='true';
                }else{
                    $etat='';
                }
              
                switch ($hours->open_date) {

                   

                    case "Lundi":
                       
                        $horairelu[]=$this->horaire($hours->open_time,$detailshoraire->open_timepre,$hours->close_time,$detailshoraire->close_timepre,$hours->id);
                        
                        $ht["Lundi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairelu);

                        break;
                    case "Mardi":
                        $horairema[]=$this->horaire($hours->open_time,$detailshoraire->open_timepre,$hours->close_time,$detailshoraire->close_timepre,$hours->id);
                        $ht["Mardi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairema);
                        break;
                    case "Mercredi":
                        $horairemec[]=$this->horaire($hours->open_time,$detailshoraire->open_timepre,$hours->close_time,$detailshoraire->close_timepre,$hours->id);
                        $ht["Mercredi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairemec);
                        break;
                    case "Jeudi":
                        $horairejeu[]=$this->horaire($hours->open_time,$detailshoraire->open_timepre,$hours->close_time,$detailshoraire->close_timepre,$hours->id);
                        $ht["Jeudi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairejeu);

                        break;
                    case "Vendredi":
                        $horaireven[]=$this->horaire($hours->open_time,$detailshoraire->open_timepre,$hours->close_time,$detailshoraire->close_timepre,$hours->id);
                        
                        $ht["Vendredi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horaireven);
                        break;
                    case "Samedi":
                        $horairesam[]=$this->horaire($hours->open_time,$detailshoraire->open_timepre,$hours->close_time,$detailshoraire->close_timepre,$hours->id);
                        $ht["Samedi"] =array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairesam);

                        break;
                    case "Dimanche":
                        $horairedim[]=$this->horaire($hours->open_time,$detailshoraire->open_timepre,$hours->close_time,$detailshoraire->close_timepre,$hours->id);
                        $ht["Dimanche"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairedim);

                        break;
                }
           }
            $i = 0;
            foreach ($day as $days) {

                if (in_array($days, $tab)) {
                    $htt[$i] = $ht[$days];
              } else {
                    $htt[$i] = array('jours' => $days,'etat'=>'', "horaire"=>array('heurdebut' => "", 'heurfin' => ""));
               }
                $i++;
            }
               $htt[$i][]=array('etatsm'=>true);
        } else {

            $i = 0;
            foreach ($day as $days) {

                $htt[$i] = array('jours' => $days,'etat'=>'', "horaire"=>array('heurdebut' => "", 'heurfin' => ""));

                $i++;
            }
            $htt[$i][]=array('etatsm'=>false);
        }
        return response()->json([
            'success' => true,
            'message' => 'Liste Horaire',
            'data' => $htt,
            'status' => Response::HTTP_OK
                ], Response::HTTP_OK);
        
    }
    public function horaire($open_time,$open_timepre,$close_time,$close_timepre,$id){
        if($open_time !=$open_timepre || $close_time !=$close_timepre){
          return array('heurdebut' =>Carbon::parse($open_time)->format('H:i'),'heurfin' =>Carbon::parse($close_time)->format('H:i'),'id'=> $id,
            'status'=>true,'heurdebutold' =>Carbon::parse($open_timepre)->format('H:i'),'heurfinold' =>Carbon::parse($close_timepre)->format('H:i'));
        }else{
            return array('heurdebut' =>Carbon::parse($open_time)->format('H:i'),'heurfin' =>Carbon::parse($close_time)->format('H:i'),'id'=> $id,
            );
        }

    }
    public  static function fichehoraire($id){
       // $id=11026;
        $statetat=false;
        $fichehours  =Fichehour::where('fiche_id', $id)
        ->whereNull('specialhours_start_date');
        $day = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",
            "Samedi", "Dimanche");
        
        if ($fichehours->count()>0) {
            $fichehoursn= $fichehours->get();
            $etatvalidation=true;
            foreach ($fichehoursn as $hours) {
                if(($hours->open_time=='00:00:00' && $hours->close_time=='00:00:00')|| ($hours->open_time=='00:00:00' && $hours->close_time=='24:00:00')){
                   $heurdebut ='24h/24';
                   $heurfin='24h/24';
                         
                }else{
                    $heurdebut =Carbon::parse($hours->open_time)->format('H:i');
                    $heurfin=Carbon::parse($hours->close_time)->format('H:i');
                }
                $tab[] = $hours->open_date;
                if($hours->type == 1 ||$hours->type == 'true' ){
                    $etat='true';
                }else{
                    $etat='';
                }
                $datahour=['type'=> true,
                'open_date'=> $hours->open_date,
                'close_date'=> $hours->close_date,
                'open_time'=> Carbon::parse($hours->open_time)->format('H:i'),
                'close_time'=>Carbon::parse($hours->close_time)->format('H:i'),
                'fiche_id'=>(int)$id
            ];
                   
                    if(Notification::where('diffMask','regularHours')->Where('newobject', 'LIKE', '%' .collect($datahour)->toJson(JSON_UNESCAPED_UNICODE).'%')->where('state','Inactif')->where('fiche_id','=',$id)->exists()){
                        $etatvalidation=false;  
                    }
                switch ($hours->open_date) {
                    case "Lundi":
                       
                            $horairelu[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,'etatvalidation'=>$etatvalidation
                              );
                       
                    $ht["Lundi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairelu,'etatvalidation'=>$etatvalidation);

                        break;
                    case "Mardi":
                        
                            $horairema[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,'etatvalidation'=>$etatvalidation
                              );
                          
                        $ht["Mardi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairema,'etatvalidation'=>$etatvalidation);
                        break;
                    case "Mercredi":
                        
                            $horairemec[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,'etatvalidation'=>$etatvalidation
                              );
                       
                        $ht["Mercredi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairemec,'etatvalidation'=>$etatvalidation);
                        break;
                    case "Jeudi":
                        
                           
                            $horairejeu[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,'etatvalidation'=>$etatvalidation
                              );
                          
                         $ht["Jeudi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairejeu,'etatvalidation'=>$etatvalidation);

                        break;
                    case "Vendredi":
                        
                            $horaireven[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,'etatvalidation'=>$etatvalidation
                              );
                          
                        $ht["Vendredi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horaireven,'etatvalidation'=>$etatvalidation);
                        break;
                    case "Samedi":
                        
                            $horairesam[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,'etatvalidation'=>$etatvalidation
                              );
                          
                        $ht["Samedi"] =array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairesam,'etatvalidation'=>$etatvalidation);

                        break;
                    case "Dimanche":
                        
                            $horairedim[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,'etatvalidation'=>$etatvalidation
                              );
                          
                        $ht["Dimanche"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairedim,'etatvalidation'=>$etatvalidation);

                        break;
                }
           }
            $i = 0;
            foreach ($day as $days) {

                if (in_array($days, $tab)) {
                    $htt[$i] = $ht[$days];
              } else {
                    $htt[$i] = array('jours' => $days,'etat'=>'', "horaire"=>array('heurdebut' => "", 'heurfin' => ""));
               }
                $i++;
            }
               $htt[$i][]=array('etatsm'=>true);
        } else {

            $i = 0;
            foreach ($day as $days) {

                $htt[$i] = array('jours' => $days,'etat'=>'', "horaire"=>array('heurdebut' => "", 'heurfin' => ""));

                $i++;
            }
            $htt[$i][]=array('etatsm'=>false);
        }
        $statetat=$fichehours->where('state','=','Inactif')->doesntExist();
        return ['listhoraire'=>$htt,'etatvalidation'=>$statetat];
    }
    public static  function byfiche($id){
        $htt=array();
        $tab=array();
        $fichehours  =Fichehour::where('fiche_id', $id)->where('state','Inactif')
        ->whereNull('specialhours_start_date');
        $day = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi",
            "Samedi", "Dimanche");
            if ($fichehours->count()>0) {
                $fichehoursn= $fichehours->get();
                $etatvalidation=true;
                foreach ($fichehoursn as $hours) {
                    $tab[] = $hours->open_date;
                    if(($hours->open_time=='00:00:00' && $hours->close_time=='00:00:00')|| ($hours->open_time=='00:00:00' && $hours->close_time=='24:00:00')){
                       $heurdebut ='24h/24';
                       $heurfin='24h/24';
                             
                    }else{
                        $heurdebut =Carbon::parse($hours->open_time)->format('H:i');
                        $heurfin=Carbon::parse($hours->close_time)->format('H:i');
                    }
                  
                    if($hours->type == 1 ||$hours->type == 'true' ){
                        $etat='true';
                    }else{
                        $etat='';
                    }
                        $etatvalidation=false;  
                    
                   
                     switch ($hours->open_date) {
                         case "Lundi":
                            
                                 $horairelu[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,
                                   );
                            
                         $ht["Lundi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairelu,'etatvalidation'=>$etatvalidation);
     
                             break;
                         case "Mardi":
                            
                                 $horairema[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,
                                );
                              
                             $ht["Mardi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairema,'etatvalidation'=>$etatvalidation);
                             break;
                         case "Mercredi":
                            
                                 $horairemec[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,
                                );
                              
                             $ht["Mercredi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairemec,'etatvalidation'=>$etatvalidation);
                             break;
                         case "Jeudi":
                            
                                 $horairejeu[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,
                                );
                              
                              $ht["Jeudi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairejeu,'etatvalidation'=>$etatvalidation);
     
                             break;
                         case "Vendredi":
                           
                                 $horaireven[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,
                                );
                          
                             $ht["Vendredi"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horaireven,'etatvalidation'=>$etatvalidation);
                             break;
                         case "Samedi":
                             
                                 $horairesam[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,
                                );
                             
                             $ht["Samedi"] =array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairesam,'etatvalidation'=>$etatvalidation);
     
                             break;
                         case "Dimanche":
                            
                                 $horairedim[]=array('heurdebut' =>$heurdebut,'heurfin' =>$heurfin,'id'=> $hours->id,
                                );
                              
                             $ht["Dimanche"] = array('jours' => $hours->open_date,'etat'=>$etat,'horaire'=>$horairedim,'etatvalidation'=>$etatvalidation);
     
                             break;
                     }
                    
                }
            }
                $i=0; 
            foreach ($day as $days) {
     
                if (in_array($days, $tab)) {
                    $htt[] = $ht[$days];
              } else {
               $i++;
              }
                $i++;
            }
                 return $htt;
    }
    public function specialHourPeriod($date) {
        $datedebut=array();
        $datedebut['day']=Carbon::parse($date)->translatedFormat('j');
        $datedebut['year']=Carbon::parse($date)->translatedFormat('Y');
        $datedebut['month']=Carbon::parse($date)->Format('m');
        return($datedebut);
    }

    public static function listdays(){
        $i = 0;

        $day = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi","Samedi", "Dimanche");
            foreach ($day as $days) {

                $htt[$i] = array('jours' => $days,'etat'=>'', "horaire" =>array('heurdebut' => "", 'heurfin' => ""));

                $i++;
            }
        return $htt;
    }
    public static function listdaysExpec(){
  
        $date=carbon::now()->toDateString();
    
       $dates= ParamaterController::listeJourferiesbydate($date);
       $message='';
       if(!empty($dates)){
        foreach($dates as $dat){
            $message=$dat['nom_jour_ferie'];
           }
         
       }
                $htt[]= array('date' => $date,'etat'=>'','nom_jour_ferie'=>$message,"horaire"=>array('heurdebut' => "", 'heurfin' => ""));

        return $htt;
    }

  public  static function horaireexp($id){
    $statetat=true; 
    $htt=array();
    $dates=array();
    

        $fichehours=Fichehour::where('fiche_id', $id)
        //->whereNotNull('specialhours_open_time')
        ->whereNotNull('specialhours_start_date')

        ->select('specialhours_open_time','state',
        'specialhours_start_date','specialhours_close_time','id')->orderBy('specialhours_start_date','ASC');
       
            
         
            if($fichehours->exists()){
               
             $detailshoraires=$fichehours->get();
             
             
             foreach($detailshoraires as  $hor){
                $etat='';
                 $specialhours_open_time='';
                 $specialhours_close_time='';
                 $tab[]=$hor->specialhours_start_date;
           
                            $state=true;
                            $etatvalidation=true;
                            if($hor->state=='Inactif'){
                                $etatvalidation=false;
                                $statetat=false; 
                            }
                          
                            if(isset($hor->specialhours_open_time)){
                                $etat=true;
                                $specialhours_open_time= Carbon::parse($hor->specialhours_open_time)->format('H:i');
                            }
                            if(isset($hor->specialhours_close_time)){
                                $specialhours_close_time= Carbon::parse($hor->specialhours_close_time)->format('H:i');
                            }
                            $datahour=['type'=> true,
                            'specialhours_start_date'=> $hor->specialhours_start_date,
                            'specialhours_end_date'=> $hor->specialhours_start_date,
                            'specialhours_open_time'=>$specialhours_open_time ,
                            'specialhours_close_time'=>$specialhours_close_time,
                            "fiche_id"=>(int)$id];
                               
                                if(Notification::where('diffMask','specialHours')->Where('newobject', 'LIKE', '%' .json_encode($datahour,JSON_FORCE_OBJECT).'%')->where('state','Inactif')->where('fiche_id',$id)->exists()){
                                    $etatvalidation=false;  
                                }else{
                                    $etatvalidation=true;
                                }
                            $horaireven["$hor->specialhours_start_date"][]=array( 'heurdebut' =>$specialhours_open_time,'heurfin' =>$specialhours_close_time,'etat'=>$etat,'id'=> $hor->id,'etatvalidation'=>$etatvalidation
                              );
                        
                      
                    $dates["$hor->specialhours_start_date"] = array('date' =>Carbon::parse($hor->specialhours_start_date)->format('d-m-Y'),'etat'=>$etat,'horaire'=>$horaireven["$hor->specialhours_start_date"],'etatvalidation'=>$etatvalidation);
    
                    //    break;
                   // }
                  
               }
                $i = 0;
               foreach($dates as $keys=>$data){
                   
                if(array_key_exists($keys,$dates)) {
                    $htt[] = $dates[$keys];
               }
            }
           
             }
             return ['listhoraireexceptionnelle'=>$htt,'etatvalidation'=>$statetat];  
     
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

    // liste horaire général
    public function hourgeneral(){
       
        $lishoraires=$this->listdays();

        return response()->json([
            'success' => true,
            'message' => 'Liste Horaire',
            'data' => $lishoraires,
            'status' => Response::HTTP_OK
                ], Response::HTTP_OK);
        }
          // liste horaire général execptionnel
        public function hourgeneralexp(){

            return response()->json([
                'success' => true,
                'message' => 'Liste Horaire',
                'data' => $this->listdaysExpec(),
                'status' => Response::HTTP_OK
                    ], Response::HTTP_OK);
          
        }
        // fonction create horaire 
         public function cretahour($id,$listfichelisthoraire){
           $fiche = Fiche::find($id);
           $locationName = $fiche->name;
            if($id){
           try{
        
        $updateMask = 'regularHours';
        $i = 0;
           $periods=array();
           $fichehours=array();
        foreach ($listfichelisthoraire['Listhoraire'] as $horaire) {
         
            $data['open_date'] = $horaire['jours'];
            $data['close_date'] = $horaire['jours'];

            $data['fiche_id'] = $id;
            $data['user_id'] = Auth()->user()->id;
            $more = Fichehour::where('open_date', $horaire['jours'])
                ->where('close_date', $horaire['jours'])
                ->where('fiche_id', $id)
               // ->where('user_id',Auth()->user()->id)
                ->get();
            foreach ($more as $morehou) {
                $delt = Fichehour::find($morehou['id']);
                $delt->delete();

            }
            if ($horaire['etat'] == 'true') {
                foreach ($horaire['horaire'] as $hor) {
                    $heurdebut=null;
                        $heurfin=null;
                    if(($hor['heurdebut']=='24h/24' && $hor['heurfin']=='') || ($hor['heurdebut']=='24h/24' &&$hor['heurfin']=='24h/24')){
                        $heurdebut="00:00";
                        $heurfin="24:00";
                    }else{
                        $heurdebut=$hor['heurdebut'];
                        $heurfin=$hor['heurfin'];
                    }
                    $periods[] = array('openDay' => FichehourController::dateToAnglash($horaire['jours']),
                        "openTime" =>['hours'=>Carbon::parse($heurdebut)->format('H'),'minutes'=>Carbon::parse($heurdebut)->format('i')],
                        'closeDay' => FichehourController::dateToAnglash($horaire['jours']),
                        "closeTime" => ['hours'=>Carbon::parse($heurfin)->format('H'),'minutes'=>Carbon::parse($heurfin)->format('i')]);
                    $data['type'] = 1;
                    $data['open_time'] = $heurdebut;
                    $data['close_time'] = $heurfin;
                    $fichehours[] = $data;
                   
                }

            } elseif ($horaire['etat'] =='false' ) {

                $more = Fichehour::where('open_date', $horaire['jours'])
                    ->where('close_date', $horaire['jours'])
                    ->where('fiche_id',$id)
                    //->where('user_id',Auth()->user()->id)
                    ->get();
                foreach ($more as $morehou) {
                    $delt = Fichehour::find($morehou['id']);
                    $delt->delete();

                }

            }
         
            
            $i++;
        } 
        $client = Helper::googleClient();
                  
       $Location= new Google\Service\MyBusinessBusinessInformation\Location($client); 
        $Location->regularHours=['periods' => $periods];
        FicheController::patchlocation($locationName,$updateMask,$Location);
    
        foreach($fichehours as $fichehou){
            $fichehour=Fichehour::create($fichehou);
            $fichehou['fichehours_id']=$fichehour->id;
            $fichehou['state']='Inactif';
        }
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
         // horaire exceptionnel
         public function hourairexcep($id,$horaireexp){
            {
                $fiche = Fiche::find($id);
            if($id){
                try{
           
            $updateMask = 'specialHours';
            $i = 0;
            $periodsexp=array();
            $fichehours=array();
            
            foreach ($horaireexp as $horaire) {
                $dateString = $horaire['date'];
            $dateString = preg_replace('/\(.*$/', '', $dateString);
             $date=Carbon::parse($dateString)->format('Y-m-d');
                $message='';
                   
                $data['specialhours_start_date'] = $date;
                $data['specialhours_end_date'] = $date;
               $dates= ParamaterController::listeJourferiesbydate($date);
            
                if(!empty($dates)){
                 foreach($dates as $dat){
                     $message=$dat['nom_jour_ferie'];
                    }
                  
                }
                $data['fiche_id'] = $fiche->id;
                $data['user_id'] = Auth()->user()->id;
                $more = Fichehour::where('specialhours_start_date', $date)
                    ->where('specialhours_end_date', $date)
                    ->where('fiche_id', $fiche->id)->get();
                foreach ($more as $morehou) {
                    $delt = Fichehour::find($morehou['id']);
                    $delt->delete();

                }
               
            
                    foreach ($horaire['horaire'] as $hor) {
                        $dateString = $horaire['date'];
                        $dateString = preg_replace('/\(.*$/', '', $dateString);
                         $datehoraire=Carbon::parse($dateString)->format('Y-m-d');
                        $specialperiod= array();
                        $specialperiod['startDate']=($this->specialHourPeriod($datehoraire));
                        $specialperiod['endDate']=($this->specialHourPeriod($datehoraire));
                       if($hor['heurdebut']){
                        $specialperiod['openTime']=['hours'=>Carbon::parse($hor['heurdebut'])->format('H'),'minutes'=>Carbon::parse($hor['heurdebut'])->format('i')];
                        $data['specialhours_open_time'] = $hor['heurdebut'];
                       }
                        if($hor['heurfin']){
                            $specialperiod['closeTime']=['hours'=>Carbon::parse($hor['heurfin'])->format('H'),'minutes'=>Carbon::parse($hor['heurfin'])->format('i')];
                            $data['specialhours_close_time'] = $hor['heurfin'];
                        }
                        $data['type'] = "true";
                        if($horaire['etat'] ==false || $hor['heurdebut']==""){
                            $data['type'] = $horaire['etat'];
                            $specialperiod['closed']=true;
                        }
                        $periodsexp[] =$specialperiod;
                      
                       
                       $data['new_content'] = $message;
                        $fichehours[] = $data;
                    }
                   
            
               
                $i++;
            }
               
            $listefichehoraire= Fichehour::where('fiche_id', $fiche->id)->whereNotNull('specialhours_start_date')->get();
            if($listefichehoraire->count()>0){
                $i=0;
                foreach($listefichehoraire as $horaires){
    
                  $specialperiod= array();
                  $specialperiod['startDate']=($this->specialHourPeriod(Carbon::parse($horaires['specialhours_start_date'])->translatedFormat('Y-m-d')));
                  $specialperiod['endDate']=($this->specialHourPeriod(Carbon::parse($horaires['specialhours_start_date'])->translatedFormat('Y-m-d')));
              if($horaires['specialhours_open_time']!=""){
                  $specialperiod['openTime']=['hours'=>Carbon::parse($horaires['specialhours_open_time'])->format('H'),'minutes'=>Carbon::parse($horaires['specialhours_open_time'])->format('i')];
              }
                  if($horaires['specialhours_close_time']!=""){
                      $specialperiod['closeTime']=['hours'=>Carbon::parse($horaires['specialhours_close_time'])->format('H'),'minutes'=>Carbon::parse($horaires['specialhours_close_time'])->format('i')];
                 }
                  if($horaires['type'] ===false || $horaires['type'] ==0 ||$horaires['specialhours_open_time']===""){
                      $specialperiod['closed']=true;
                  }
                  $periodsexp[] =$specialperiod;
                  $i++;
              }
            }
      
          foreach($fichehours as $fichehou){
               
           Fichehour::create($fichehou);
      
        }
        $client = Helper::googleClient();
                  
        $Location= new Google\Service\MyBusinessBusinessInformation\Location($client);
        
        $Location->specialHours=['specialHourPeriods'=> $periodsexp];
        FicheController::patchlocation($fiche->name,$updateMask,$Location);
                    
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

        }
    }
