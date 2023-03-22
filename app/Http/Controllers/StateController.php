<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\State;
use App\Models\User;
use App\Helper\Helper;
use Illuminate\Http\Request;
use mysql_xdevapi\Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use function PHPUnit\Framework\isTrue;
use App\Models\Franchise;
use App\Models\Iconfiche;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Postfiche;
use App\Models\Statistique;
use Carbon\Carbon;
use FontLib\Table\Type\post as TypePost;
use Illuminate\Support\Facades\DB;
use Psy\Command\WhereamiCommand;
use GoogleMyBusinessService;
use Google;
use App\Models\Ficheuser;
class StateController extends Controller
{

    public function __construct() {
  
    }
    public function verified()
    {

        try {
            $state = State::With('fiche:id,description,locationName,name,placeId,url_map')->get();
            $verified =$state->where('isVerified', 1);
            $demandeacces=$state->where('hasPendingVerification', 1);
            $codegoogle=$state->where('isGoogleUpdated', 1);
            $data=array("codegooge"=>$codegoogle,"demandeacces"=>$demandeacces,"personnaliser"=>$verified);
            $listfiche = Fiche::join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
                ->where('ficheusers.user_id', auth()->user()->id)
                ->get(['fiches.id', 'fiches.locationName', 'fiches.url_map', 'fiches.websiteUrl']);
            $filtrefiche = Fiche::join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
                ->join('categories', 'categories.fiche_id', '=', 'fiches.id')
                ->where('ficheusers.user_id', auth()->user()->id)

                ->get(['categories.categorieId', 'categories.displayName', 'categories.type'])->toArray();
            $outarray = array();
            foreach ($filtrefiche as $fiche) {
                if (empty($outarray)) {
                    $outarray[$fiche['type']][] = $fiche['displayName'];
                } else {
                    if (in_array($fiche['type'], $filtrefiche, true)) {
                        $outarray[$fiche['type']][] = $fiche['displayName'];
                    } else {
                        $outarray[$fiche['type']][] = $fiche['displayName'];
                    }
                }
            }
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'dataFiche' => $listfiche,
                    'datafiltre' => $outarray,
                    "message" => 'Liste Fiche',

                    'status' => 200
                ], 200);


        } catch (QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Fiche not found.',

                'status' => 400
            ], 400);
        }
    }


    public function show(State $state)
    {


        if (!$state) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, State not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'etiquette id ' . $state->id,
            'data' => $state,

            'status' => 200
        ], 200);
    }
    public function destroy(State $state)
    {


        try {

            $state->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'State could not be deleted',
                'status' => 500,

            ], 500);
        }
    }
      public function etatfiche(Request $request) {

        try {
            $list=array();
            $data=null;
            $datas=array();
          $franchise_id= $request->header('franchise');
          
          $listfichePersonnaliser=  Fiche::where('fiches.franchises_id',$franchise_id)
          ->leftJoin('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'fiches.id')
          //->leftJoin('states', 'states.fiche_id', '=', 'fiches.id')
          ->join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
          ->where('ficheusers.user_id', auth()->user()->id)
         ->wherenull('etiquetgroupes.fiche_id')
        ->where('fiches.state', 'LIKE', 'COMPLETED')
         ->select('fiches.locationName', 'fiches.id as idfiche', 'fiches.state', 'fiches.address','fiches.city as ville',
           DB::raw('count(*) as total'),DB::raw("DATE_FORMAT(fiches.closedatestrCode, '%d %b %Y') as closedatestrCode"))
          ->orderBy('fiches.locationName', 'ASC');
        /*  $listfichePersonnaliser = Fiche::where('fiches.franchises_id',$franchise_id)
                    ->leftJoin('states', 'states.fiche_id', '=', 'fiches.id')
                    ->join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
                ->where('ficheusers.user_id', auth()->user()->id)
                    ->where(function ($query) {
                    $query->where('states.isVerified', '=', 1)
                    ->orWhere('states.isPendingReview', 1);})
                    ->select('fiches.locationName', 'fiches.id as idfiche', 'fiches.state', 'fiches.address','fiches.city as ville',
                    'states.*', DB::raw('count(*) as total'),DB::raw("DATE_FORMAT(fiches.closedatestrCode, '%d %b %Y') as closedatestrCode"))
                    ->orderBy('fiches.state', 'ASC');*/
            $listfichecodegoogle = Fiche::where('fiches.franchises_id',$franchise_id)
                            ->leftJoin('states', 'states.fiche_id', '=', 'fiches.id')
                            ->Where('states.hasPendingVerification', 1)
                            ->join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
                ->where('ficheusers.user_id', auth()->user()->id)
                             ->select('fiches.locationName', 'fiches.id as idfiche', 'fiches.state', 'fiches.address','fiches.city as ville','states.*', DB::raw('count(*) as total'),
                                            DB::raw("DATE_FORMAT(fiches.closedatestrCode, '%d %b %Y') as closedatestrCode"))
                                            ->orderBy('fiches.state', 'ASC');
            $listfichedemande = Fiche::where('fiches.franchises_id',$franchise_id)
            ->leftJoin('states', 'states.fiche_id', '=', 'fiches.id')
            ->Where('states.isDuplicate', 1)
            ->join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
                ->where('ficheusers.user_id', auth()->user()->id)
            ->select('fiches.locationName', 'fiches.id as idfiche', 'fiches.state', 'fiches.address','fiches.city as ville','states.*', DB::raw('count(*) as total'),
            DB::raw("DATE_FORMAT(fiches.closedatestrCode, '%d %b %Y') as closedatestrCode"))
            ->orderBy('fiches.state', 'ASC');
           
            if($listfichecodegoogle->count()== 1){
                $listfichePerscode=$listfichecodegoogle->first();
                  $details=  ['locationName'=>$listfichePerscode->locationName,
                    'idfiche'=>$listfichePerscode->idfiche,
                       'address'=>$listfichePerscode->address,
                       'ville'=>$listfichePerscode->ville,
                       'closedatestrCode'=>$listfichePerscode->closedatestrCode,
                       'state'=>'Code Google'];
                $list[]=['state'=>'Code Google','nbfiche'=>$listfichecodegoogle->count(), 'nbfichetxt'=>StatistiqueController::number_format_short($listfichecodegoogle->count()),'details' => $details];
            
            }else{
                $listfichePerscode=$listfichecodegoogle->first();
                  $details=  ['locationName'=>$listfichePerscode->locationName,
                    'idfiche'=>$listfichePerscode->idfiche,
                       'address'=>$listfichePerscode->address,
                       'ville'=>$listfichePerscode->ville,
                       'closedatestrCode'=>$listfichePerscode->closedatestrCode,
                       'state'=>'Code Google'];
                $list[]=['state'=>'Code Google','nbfiche'=>$listfichecodegoogle->count(), 'nbfichetxt'=>StatistiqueController::number_format_short($listfichecodegoogle->count()),
                'details' => $details];
               
            }      
            
            
if($listfichedemande->count()== 1){
    $listfichePersdemande=$listfichedemande->first();
      $details=  ['locationName'=>$listfichePersdemande->locationName,
        'idfiche'=>$listfichePersdemande->idfiche,
           'address'=>$listfichePersdemande->address,
           'ville'=>$listfichePersdemande->ville,
           'closedatestrCode'=>$listfichePersdemande->closedatestrCode,
           'state'=>'Demande accés'];
    $list[]=['state'=>'Demande accés','nbfiche'=>$listfichedemande->count(),'nbfichetxt'=>StatistiqueController::number_format_short($listfichedemande->count()),'details' => $details];

}else{
   
    $listfichePersdemande=$listfichedemande->first();
      $details=  ['locationName'=>$listfichePersdemande->locationName,
        'idfiche'=>$listfichePersdemande->idfiche,
           'address'=>$listfichePersdemande->address,
           'ville'=>$listfichePersdemande->ville,
           'closedatestrCode'=>$listfichePersdemande->closedatestrCode,
           'state'=>'Demande accés'];
    $list[]=['state'=>'Demande accés','nbfiche'=>$listfichedemande->count(),
    'nbfichetxt'=>StatistiqueController::number_format_short($listfichedemande->count()),
    'details' => $details,$listfichePersonnaliser];
   
}



if($listfichePersonnaliser->count()== 1){
    $listfichePers=$listfichePersonnaliser->first();
      $details=  ['locationName'=>$listfichePers->locationName,
        'idfiche'=>$listfichePers->idfiche,
           'address'=>$listfichePers->address,
           'ville'=>$listfichePers->ville,
           'closedatestrCode'=>$listfichePers->closedatestrCode,
           'state'=>'Sans étiquette'];
    $list[]=['state'=>'Sans étiquette','nbfiche'=>$listfichePersonnaliser->count(),
    'nbfichetxt'=>StatistiqueController::number_format_short($listfichePersonnaliser->count()),'details' => $details];

}else{
    $listfichePers=$listfichePersonnaliser->first();
      $details=  ['locationName'=>$listfichePers->locationName,
        'idfiche'=>$listfichePers->idfiche,
           'address'=>$listfichePers->address,
           'ville'=>$listfichePers->ville,
           'closedatestrCode'=>$listfichePers->closedatestrCode,
           'state'=>'Sans étiquette'];
    $list[]=['state'=>'Sans étiquette','nbfichetxt'=>StatistiqueController::number_format_short($listfichePersonnaliser->count()),
    'nbfiche'=>$listfichePersonnaliser->count(), 'details' => $details];
   
}



         /*$state=null;
$nbPersonnaliser=0;
$nbcodegoogle=0;
$nbDemandeacces=0;
foreach($listfiche->toarray() as $fiche){
    if($fiche['isVerified']&& $fiche['isPendingReview']){
        $nbPersonnaliser ++;
        $state='Personnaliser';
        $listePersonnaliser[]=array('locationName'=>$fiche['locationName'],
        'idfiche'=>$fiche['idfiche'],
           'address'=>$fiche['address'],
           'ville'=>$fiche['ville'],
           'closedatestrCode'=>$fiche['closedatestrCode'],
           'state'=>$state,
           );
    }
      if($fiche['hasPendingVerification']){
        $listeCodeGoogle[]=array('locationName'=>$fiche['locationName'],
        'idfiche'=>$fiche['idfiche'],
           'address'=>$fiche['address'],
           'ville'=>$fiche['ville'],
           'closedatestrCode'=>$fiche['closedatestrCode'],
           'state'=>$state,
           );
        $state='Code Google';
        $nbcodegoogle++;
    }
    if($fiche['isDuplicate']){
        $listeDemandeacc[]=array('locationName'=>$fiche['locationName'],
        'idfiche'=>$fiche['idfiche'],
           'address'=>$fiche['address'],
           'ville'=>$fiche['ville'],
           'closedatestrCode'=>$fiche['closedatestrCode'],
           'state'=>$state,
           );
     $nbDemandeacces++;
     $state='Demande accés';
    }
   $liste[]=array('locationName'=>$fiche['locationName'],
    'idfiche'=>$fiche['idfiche'],
       'address'=>$fiche['address'],
       'ville'=>$fiche['ville'],
       'closedatestrCode'=>$fiche['closedatestrCode'],
       'state'=>$state,
       );
}
if($nbDemandeacces == 1){

}
$tbcount=0;
if(!empty($liste)){
    $datas=collect($liste)->sortBy('state')->values()->all();
  $tbcount=  count($liste);
        }*/
            return response()->json([
                        'success' => true,
                        'message' => 'Liste Fiche',
                        'data' => $list,
                       // 'nbcount'=>$tbcount,
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
        } catch (QueryException $ex) {
            return response()->json([
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400,
                            ], 400
            );
        }
    }
public function listficheencours(Request $request){
    $list=array();
    $data=null;
    $datas=array();
    $state=$request->state;
    try{
  $franchise_id= $request->header('franchise');
  $listfiche = Fiche::where('fiches.franchises_id',$franchise_id)
            
             ->join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
            ->where('ficheusers.user_id', auth()->user()->id)
           ->When($state === 'Code Google' ,function ($query)  {

                $query->leftJoin('states', 'states.fiche_id', '=', 'fiches.id')->Where('states.hasPendingVerification', 1);
                    })
             ->When($state=== 'Demande accés',function ($query)  {
                        $query->leftJoin('states', 'states.fiche_id', '=', 'fiches.id')->Where('states.isDuplicate', 1);
                            })
            ->When($state=== 'Sans étiquette' ,function ($query)  {
              
                $query->leftJoin('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'fiches.id')
            
               ->wherenull('etiquetgroupes.fiche_id')
               ->where('fiches.state', 'LIKE', 'COMPLETED');
        })
            ->select('fiches.locationName', 'fiches.id as idfiche', 'fiches.state', 'fiches.address','fiches.city as ville',
            'states.*',DB::raw("DATE_FORMAT(fiches.closedatestrCode, '%d %b %Y') as closedatestrCode"))
           ->get();
            foreach($listfiche as $fiche){
                $liste[]=array('locationName'=>$fiche->locationName,
                'idfiche'=>$fiche->idfiche,
                   'address'=>$fiche->address,
                   'ville'=>$fiche->ville,
                   'closedatestrCode'=>$fiche->closedatestrCode,
                   'state'=>$state,
                   );
          
            }
            return response()->json([
                'success' => true,
                'message' => 'Liste Fiche',
                'data' => $liste,
                'state'=>$state,
               'nbcount'=>$listfiche->count(),
                'status' => Response::HTTP_OK
                    ], Response::HTTP_OK);
} catch (QueryException $ex) {
    return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
                'status' => 400,
                    ], 400
    );
}
}
    public function verifipin(Request $request) {
        try {
            $data = $request->all();
            $fiche = Fiche::find($request->idfiche);
            $client = Helper::googleClient();
            $pinverification = new  Google\Service\MyBusinessVerifications\CompleteVerificationRequest($client); 
            $pinverification->pin=$request->codegoogle;
                try {


                  $verif =$this->verifications($fiche['name']);
               

                $locationName=$fiche['locationName'];
               
if(isset($verif)){
    $response = $this->complete($verif['verifications'][0]['name'],$pinverification);
         
    if(isset($response)){
   /* $locationsListUpdate = $this->locations->listAccountsLocations($verif['verifications'][0]['name'],
                   array('pageSize' => 1,
                       'filter' => "locationName=%22$locationName%22"));*/
       $states= State::where('fiche_id',$request->idfiche)->delete();
      // $datett['isVerified']=$locationsListUpdate["location"]["locationState"];
       $datett['isVerified']=1;
         $datett['fiche_id']=$request->idfiche;

    State::create($datett);
    return response()->json([
        'success' => true,
        'message' => "Code correct.",
        'status' => 200,
        'data' => $response,
    ], 200);
    }else{
        return response()->json([
            'success' => false,
            'message' => "Code incorrect. Veuillez réessayer",
            'status' => 400,
            'data' => ''
        ], 400);
    }
}else{
    return response()->json([
        'success' => false,
        'message' => "Code incorrect. Veuillez réessayer",
        'status' => 400,
        'data' => ''
    ], 400);
             
               
}
                } catch (\Google_Service_Exception $e) {


                    return response()->json([
                            'success' => false,
                            'message' => "Code incorrect. Veuillez réessayer",
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
    public function storelocatore(Request $request){
        try{
            $liste=array();
        $data=null;
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
     $franchise_id=$request->header('franchise');
       $fiche_id= $request->fiche_id;
 

      

                $fiches = Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id')
                ->join('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
                ->where("fiches.franchises_id","=",$franchise_id)
                ->where("ficheusers.user_id","=",Auth()->user()->id)
                ->whereNotNull('fiches.latitude')
                ->where('fiches.state', 'LIKE', 'COMPLETED')
                ->when($fiche_id,function ($query) use($fiche_id){
                    $query->where('fiches.id', $fiche_id);
                    })
                        ->select('fiches.locationName','profilincompletes.logostorelocatore as logo', 'fiches.id as idfiche',  'fiches.address',
                        'fiches.latitude as lat',
                        'fiches.longitude as long',
                        'fiches.city','profilincompletes.TotalRate',
                        'profilincompletes.TotalAvis',
                        'profilincompletes.vuesearch',
                        'profilincompletes.vuemaps',
                        'profilincompletes.nombrejour',
                        'profilincompletes.statuspost as status',
                        DB::raw("DATE_FORMAT(fiches.closedatestrCode, '%d %b %Y') as closedatestrCode"),
                    //  DB::raw("JSON_PRETTY('profilincompletes.statestorelocatore') as state" ),
                'profilincompletes.statestorelocatore as state')
                 //   DB::raw('(JSON_PARSE(profilincompletes.vuesearch)) as vuesearch' ),)
  // DB::raw("toArray(profilincompletes.vuemaps) AS vuemaps" ))
   
                       ->get();
                       $datelast = Carbon::now();
                       
     foreach($fiches as $fiche){
        $intar=0;
        if($fiche['nombrejour']){
            $datefirst=Carbon::parse($fiche['nombrejour']);
            $intar = $datelast->diffInDays($datefirst);
        }
      
    $state=json_decode($fiche['state'],true);
$fiche['vuesearch']?$vuesearch= json_decode($fiche['vuesearch'],true):$vuesearch=[["pourcentage"=>0,"status"=>"positif","couleur"=>"#B1CD45"]];
$fiche['vuemaps']?$vuemaps= json_decode($fiche['vuemaps'],true):$vuemaps=[["pourcentage"=>0,"status"=>"positif","couleur"=>"#B1CD45"]];
$fiche['logo']?$logo= $fiche['logo']:$logo='https://api-wallpost.bforbiz-dev.com/public/icon/icon-map-franchise.png';
    $liste[]=array('locationName'=>$fiche['locationName'],
    'idfiche'=>$fiche['idfiche'],
       'address'=>$fiche['address'],
       'city'=>$fiche['city'],
       'lat'=>$fiche['lat'],
       'long'=>$fiche['long'],
       'closedatestrCode'=>$fiche['closedatestrCode'],
       'state'=>$state,
       'logo'=>$logo,
       'TotalAvis'=>$fiche['TotalAvis'],
       'TotalRate'=>$fiche['TotalRate'],
      'nombrejour'=> $intar,
       'status'=>false,
       'vuesearch'=>$vuesearch,
       'vuemaps'=>$vuemaps,
    
       );
}
    

return response()->json([
    'success' => true,
    'message' => 'Liste Fiche',
    'data' => $liste,
    'status' => Response::HTTP_OK
        ], Response::HTTP_OK);
} catch (QueryException $ex) {
return response()->json([
    'success' => false,
    'message' => $ex->getMessage(),
    'status' => 400,
        ], 400
);
}

    }
    public function storelocatoreold(Request $request){
        try{
        $list=array();
        $liste=array();
        $data=null;
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
     $franchise_id=$request->header('franchise');
       $fiche_id= $request->fiche_id;
       $start = Carbon::now()->subMonth()->toDateString();
       $end= carbon::now()->toDateString();
   $Pstart=  Carbon::parse($start)->subMonth(1)->toDateString();
   $Pend=  Carbon::parse($end)->subMonth()->toDateString();


            $listfiche = Fiche::leftjoin('states','states.fiche_id','fiches.id')
                ->where("franchises_id",$franchise_id)
                ->join('ficheusers','fiches.id','=','ficheusers.fiche_id')
                ->where('ficheusers.user_id', auth()->user()->id)
                ->whereNotNull('fiches.latitude')
                ->where(function ($query) {
                    $query->where('states.isVerified', '=', 1)
                    ->orWhere('states.hasPendingVerification', 1)
                    ->orWhere('states.isGoogleUpdated', 1)
                    ->orWhere('states.isDuplicate',1)
                    ->orWhere('states.isPendingReview', 1)
                    ->orWhere('states.isPublished', 1)
                    ->orWhere('states.isPendingReview', 1);
                })
                ->when($fiche_id,function ($query) use($fiche_id){
                    $query->where('fiches.id', $fiche_id);
                    })
                        ->select('fiches.locationName','fiches.logo', 'fiches.id as idfiche', 'fiches.state', 'fiches.address',
                        'fiches.latitude',
                        'fiches.longitude',
                        'fiches.city as ville',
                        'states.*',
                        DB::raw("DATE_FORMAT(fiches.closedatestrCode, '%d %b %Y') as closedatestrCode"))
                        ->limit(500)
                       ->get();

     $state=[];

foreach($listfiche->toarray() as $fiche){
    if($fiche['isVerified']&& $fiche['isPublished']){
        $iconfiche= Iconfiche::where('code','isPublished')->first();
        $state=["state"=>'Valider','couleur'=>'#0080ff','icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path];
      
    }
if($fiche['isVerified']&& $fiche['isPendingReview']){
    $iconfiche= Iconfiche::where('code','isPendingReview')->first();
    $state=["state"=>'Personnaliser','couleur'=>'#008000','icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path];
}
  if($fiche['hasPendingVerification']){
    $iconfiche= Iconfiche::where('code','hasPendingVerification')->first();
    
    $state=["state"=>'Code Google','couleur'=>'#ff00ff','icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path];
}
if($fiche['isDuplicate']){
    $iconfiche= Iconfiche::where('code','isDuplicate')->first();
 $state=["state"=>'Demande accés','couleur'=>'#FFFF00','icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path];
}
$intar=0;
$datastatistique=array();
$datapost=['status'=>false,'nombrejour'=>$intar];
$post=Postfiche::where('fiche_id',$fiche['idfiche'])->first();
$datelast = Carbon::now();
                        if($post){
                            $datefirst=Carbon::parse($post->created_at);
                            $intar = $datelast->diffInDays($datefirst);
                        $datapost=['status'=>true,'nombrejour'=>$intar];
                        }
                $start = Carbon::now()->subMonth()->toDateString();
                $end= carbon::now()->toDateString();
            $Pstart=  Carbon::parse($start)->subMonth(1)->toDateString();
            $Pend=  Carbon::parse($end)->subMonth()->toDateString();
           
              if ($start || $end) {
              
                $statistique  = Statistique::where('fiche_id',$fiche['idfiche'])
                ->whereBetween('date', [$start, $end]);

                    $viewsSearch =$statistique->sum('statistiques.viewsSearch');
                    $viewsMaps =$statistique->sum('statistiques.viewsMaps');
                   }
                if ($Pstart || $Pend) {
                    $statistiquep  = Statistique::where('fiche_id',$fiche['idfiche'])
                  ->whereBetween('statistiques.date', [$Pstart, $Pend]);
                    $Psearch =$statistiquep->sum('statistiques.viewsSearch');
                    $Pmaps =$statistiquep->sum('statistiques.viewsMaps');
                }
                $datastatistique=[];
  
                        
$avis = DB::table('avis')->select(DB::raw('count(*) as rating_count, rating'))
->where('fiche_id',$fiche['idfiche'])
->orderBy('rating', 'desc')
->groupBy('rating')
->get();

$total = array_sum(array_column($avis->toArray(), 'rating_count'));
$data['ListAvis'] = null;

$data = ['TotalAvis' => 0, 'TotalRate' => 0];
if ($total > 0) {
$totalRating = 0;
$ListAvis = [];

foreach ($avis as $key => $value) {
  
    $totalRating += ((int) $value->rating_count * (int) $value->rating);
}


$data = ['TotalAvis' => $total, 'TotalRate' => number_format((float) $totalRating / $total, 1, '.', '')];
}
$photos=Photo::Where('category','LOGO')->Where('fiche_id',$fiche['idfiche']);
$photosprofil=Photo::Where('category','PROFILE')->Where('fiche_id',$fiche['idfiche']);
if($photos->exists()){
    $photo=$photos->first();
    $logo=$photo->file;
}elseif($photosprofil->exists()){
    $photopro=$photosprofil->first();
    $logo=$photopro->file;
}
    else {
    $franchise= Franchise::find($franchise_id);
    $logo=\Illuminate\Support\Facades\URL::to('/') .'/'.$franchise->logo;
}
$liste[]=array('locationName'=>$fiche['locationName'],
'idfiche'=>$fiche['idfiche'],
   'address'=>$fiche['address'],
   'city'=>$fiche['ville'],
   'lat'=>$fiche['latitude'],
   'long'=>$fiche['longitude'],
   'closedatestrCode'=>$fiche['closedatestrCode'],
   'state'=>$state,
   'logo'=>$logo,
   'TotalAvis'=>$data['TotalAvis'],
   'TotalRate'=>$data['TotalRate'],
  'nombrejour'=> $datapost['nombrejour'],
   'status'=>$datapost['status'],
   'vuesearch'=>[StatistiqueController::calculpourcentage($viewsSearch,$Psearch)],
   'vuemaps'=> [StatistiqueController::calculpourcentage($viewsMaps,$Pmaps)]

   );
}
return response()->json([
    'success' => true,
    'message' => 'Liste Fiche',
    'data' => $liste,
    'status' => Response::HTTP_OK
        ], Response::HTTP_OK);
} catch (QueryException $ex) {
return response()->json([
    'success' => false,
    'message' => $ex->getMessage(),
    'status' => 400,
        ], 400
);
}

    }

    public static function complete($location,$pinverification)
    {
        try {
         

                try {
                    $client = Helper::googleClient();
                    $serviceLocation = new Google\Service\MyBusinessVerifications($client);   
                    $list_complete = $serviceLocation->locations_verifications->complete($location,$pinverification);
                  
                        return $list_complete;
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

// verifier compte

    public function verifications($locations){

       
   
        $client = Helper::googleClient();
        $serviceLocation = new Google\Service\MyBusinessVerifications($client); 
        $list_verifications = $serviceLocation->locations_verifications->listLocationsVerifications($locations);
        return  $list_verifications;
                       
      }
     
      

}

