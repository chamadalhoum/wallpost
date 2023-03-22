<?php

namespace App\Http\Controllers;
use App\Models\profilincomplete;
use Illuminate\Support\Facades\DB;
use App\Mail\SendMail;
use App\Models\Fiche;
use Illuminate\Support\Facades\Mail;
use JWTAuth;
use App\Models\Etiquetgroupe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Lcobucci\JWT\Token;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Database\QueryException;
use App\Helper\Helper;
use App\Models\Role;
use App\Models\Categorie;
use App\Models\Fichehour;
use App\Models\Ficheuser;
use App\Models\Iconfiche;
use App\Models\Paramater;
use App\Models\Service;
use App\Models\Attribute;
use App\Models\Morehours;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Postfiche;
use App\Models\Servicearea;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Crypt\Hash as CryptHash;
use Tymon\JWTAuth\Facades\JWTAuth as FacadesJWTAuth;
use Tymon\JWTAuth\JWTAuth as JWTAuthJWTAuth;

class UserController extends Controller {

    public $mybusinessService;
    public $placeID;
    public $admins;

    public function __construct() {
       /* $this->mybusinessService = Helper::GMB();
        $this->admins = $this->mybusinessService->accounts_locations_admins;
        $this->placeID = Helper::AdminAction();*/
    }
    public function store(Request $request)
    {

        $messages = [
            'firstname.required' => 'Vérifier Votre Nom et prénom!',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée',
            'email.required' => 'Vérifier Votre email',
           'role.exists' => 'Vérifier Votre Role',
           'password.required' => 'Vérifier Votre mot de passe',
        ];

        $validator = Validator::make($request->profil,
            [
               
                "firstname" => 'required|max:45',
                "email" => 'required|string|email|unique:users,email',
                'password' => 'required|min:6',
             
                "role.role" => 'required|exists:roles,id'
            ], $messages
        );

        if ($validator->fails()) {
            return response()->json([
                        'succes' => false,
                        'message' =>'Ce adresse e-mail est déjà utilisée',
                        'status' => 400],
                            400);
        }
        if ($validator->passes()) {
            try {
                if (!is_numeric($request->header('franchise'))) {
                    return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
                    ]);
                }
             $franchise_id=$request->header('franchise');
             if($request->profil['role']['name']== "Administrateur Principal"){
                  if(User::where('franchises_id', $request->header('franchise'))->where('role_id',1)->exists()){
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de ajouter ce profil, il faut choisir un autre role',
                    'status' => 400,
                ],400);
             }
            }
                if ($request->profil['photo']) {
                    $time = time();
                    $new_data = explode(";", $request->profil['photo']);
                    $type = $new_data[0];
                    $extension = explode('/', $type);
                    $datap = explode(",", $new_data[1]);
                    $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];
                    Storage::disk('public')->put($imageName, base64_decode(str_replace("%2B", "+", $datap[1])));  
                    $data['photo'] = $imageName;
                }
                $data['email'] = $request->profil['email'];
                $data['password'] = bcrypt($request->profil['password']);
                $role= Role::find($request->profil['role']['role']);
                $data['role_id'] = $role->id;
                $nameusers = explode(" ", $request->profil['firstname']);
                if(array_key_exists('1',$nameusers)){
                $data['firstname']=$nameusers[1];
                }
                $data['state']=1;
                $data['franchises_id']=$franchise_id;
                $data['lastname']=$nameusers[0];
                $users = User::create($data);
                $users->password=$request->profil['password'];
                $fiche= Ficheuser::leftjoin('users',"users.id",'=','ficheusers.user_id')
                 ->where('ficheusers.franchise_id',$franchise_id)
                 ->where('users.role_id',1)->get();
               
                 foreach($fiche as $list){
                     $datafiche['user_id']=$users->id;
                     $datafiche['fiche_id']=$list->fiche_id;
                     $datafiche['franchise_id']=$franchise_id;
                     $datafiche['role_id']=$role->id;
                     Ficheuser::create($datafiche);
                 }
                $token =Str::random(8);
                $subject=$users->lastname. ' '.$users->firstname.": Confirmation d'inscription" ;
                $path='Email.inscription';
               Mail::to($users->email)->send(new SendMail($token,$users,$subject,$path));
                return response()->json([
                    'success' => true,
                    'message' => 'Profil ajoute avec succes',
                    'data' => $fiche,
                    'status' => Response::HTTP_OK,

                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400
                    ],
                    400
                );
            }

        }
    }

   
    public function listprofil(Request $request) {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        $offest=($request->Page*4)-4;
     $franchise_id=$request->header('franchise');
     $role=$request->role_id;
          $user_id=Auth()->user()->id;
        $listUser=array();
        $datas=array();
          try {
              $user = User::Where('users.franchises_id',$franchise_id)
              ->leftjoin('roles','roles.id','=','users.role_id')
              ->when($role!= 1,function ($query) use($user_id)  {
                $query->where('users.id', '=', $user_id);
                })
                ->when($role== 1,function ($query) use($user_id)  {
                    $query->whereIN('users.role_id', [1,2,3]);
                    })
              ->select('users.*',"roles.id as roles","roles.name as role_type",'roles.nameenglais')
              ->orderby('users.role_id','ASC');
              $nbcountprofil = $user->count();
            // $users=$user->limit(4,$offest)->get();
            $users=$user->get();
              foreach($users AS $us){
                 
             if($us->photo){
            $userlogo=  (\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $us->photo);
             }else{
              $userlogo=null;
 
             }
             if($us->firstname){
                $firstname=$us->firstname;
             }
             else{
                $firstname='';
             }
                  $listUser[]=['firstname'=>$firstname,
                  'lastname'=>$us->lastname,
                  'role'=>$us->role_type,
                   'email'=>$us->email,
                  'password'=>'0000000',
                  'user_id'=>$us->id,
                  'franchise_id'=>$us->franchises_id,
                  'photo'=>$userlogo,
              ];
              }
        
              $roles= Role::select('roles.name','roles.id as role')->whereIN("roles.id",[1,2,3])->get();
              $datas=['user'=>$listUser,
              'role'=>$roles,
            'nbcountprofil'=>$nbcountprofil];
         
              return response()->json([
                  'success' => true,
                  'message' => 'List profil utilisateur',
                  'data' => $datas,
                  'status' => 200
                      ], 200);
          } catch (QueryException $ex) {
              return response()->json([
                          'success' => false,
                          'message' => 'Désole, user not found.',
                          'status' => 400
                              ], 400);
          }
      }
    public function show(User $user) {

        if (!$user) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, user not found.',
                        'status' => 400
                            ], 400);
        }
        if($user->photo){
            $userlogo=  (\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $user->photo);
             }else{
              $userlogo=null;
 
             }
           
        $listUser=['firstname'=>$user->firstname .$user->lastname,
     
        'role'=>Role::select('name')->where('id',$user->role_id)->first(),
        'email'=>$user->email,
        'password'=>'0000000',
        'user_id'=>$user->id,
        'franchise_id'=>$user->franchises_id,
        'photo'=>$userlogo,
    ];
   
        return response()->json([
                    'success' => true,
                    'message' => 'User id ' . $user->id,
                    'data' => $listUser,
                    'status' => 200
                        ], 200);
    }

 
    public function update(Request $request, User $user) {

   
   
        $messages = [
            'firstname.required' => 'Vérifier Votre Nom et prénom!',
            'email.required' => 'Vérifier Votre email',
         
            'role.exists' => 'Vérifier Votre Role',
           'password.required' => 'Vérifier Votre mot de passe',
        ];

        $validator = Validator::make($request->profil,
            [
               
                "firstname" => 'required|max:45',
                "email" => 'required|string|email',
                'password' => 'min:6'
            ], $messages
        );

        if ($validator->fails()) {

            return response()->json([
                'succes' => false,
                'message' =>'Vérifier Votre email Ou Votre Nom et prénom!',
                'status' => 422,
                ],
                422);
        }
        if ($validator->fails()) {
            return response()->json([
                        'succes' => false,
                        'message' => $validator->errors(),
                        'status' => 422],
                            422);
        }
        if ($validator->passes()) {
            try {  
                if (!is_numeric($request->header('franchise'))) {
                    return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
                    ]);
                }
             $franchise_id=$request->header('franchise');
                    if (strpos($request->profil['photo'], 'data:image/')!==false){
                       $time = time();
                       $new_data = explode(";", $request->profil['photo']);
                       $type = $new_data[0];
                       $extension = explode('/', $type);
                       $datap = explode(",", $new_data[1]);
                       $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];
                       Storage::disk('public')->delete(\Illuminate\Support\Facades\URL::to('/') . "/app/public/photos/" . $user->photo);
                       Storage::disk('public')->put($imageName, base64_decode(str_replace("%2B", "+", $datap[1])));
   
                     
                       $user->photo = $imageName;
                    }else if($request->profil['photo']== NULL){
                        Storage::disk('public')->delete(\Illuminate\Support\Facades\URL::to('/') . "/app/public/photos/" . $user->photo);
                        $user->photo = null;
                    }
               
                   $user->email= $request->profil['email'];
                   if($request->profil['password'] != "0000000"){
                   $user->password = bcrypt($request->profil['password']);
                   }
                if(is_array($request->profil['role'])){
                    $users= User::where('role_id','!=',1)->where('id',auth()->user()->id)->exists();
                    if ($users) {
                        return response()->json([
                            'success' => false,
                            'message' =>'Impossible de traiter cette demande',
                            'status' => 400
                                ], 400);
                 }
                    if($user->role_id == 1 || $request->profil['role']['role'] === 1 ){
                        $usersexit= User::where('id','!=',$user->id)->where('role_id',1)->where('franchises_id',$franchise_id);
                      if($usersexit->exists()){
                      $users= $usersexit->first();
                      $roles= Role::where('name','Administrateur Secondaire')->first();
                      $users->role_id=$roles->id;
                      $users->update();
                      auth()->refresh();
                      }else{
                        return response()->json([
                            'success' => false,
                            'message' =>'Impossible de modifier ce profil, il faut choisir un utilisateur principal',
                           
                            'status' => 400
                                ], 400);
                      }
                       }
                       $user->role_id = $request->profil['role']['role'];
              }
           
                   $nameusers = explode(" ", $request->profil['firstname']);
                   /*if(array_key_exists('1',$nameusers)){
                   
                    }*/
             //      $user->firstname= $request->profil['firstname'];
                  $user->lastname=$request->profil['firstname'];
                  /* $token =Str::random(8);
                   $subject=$user->lastname. ' '.$user->firstname.": Confirmation d'inscription" ;
                   $path='Email.inscription';
                // Mail::to($user->email)->send(new SendMail($token,$user,$subject,$path));
                */
                $user->update();
                if($user->id === auth()->user()->id && ($request->profil['password'] != "0000000")){
                    JWTAuth::invalidate($request->token);

                    return response()->json([
                        'success' => true,
                        'message' => 'Mise a jour traitée avec succes ,Votre session est expiré',
                        'status' => 400
                    ],400);  
                }else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Mise a jour traitée avec succes',
                        'data' => $user,
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
                }
               
            } catch (QueryException $ex) {
                return response()->json([
                            'success' => false,
                            'message' => $ex->getMessage(),
                            'status' => 400,
                                ],400
                );
            }
        }
    }
   
   
    public function destroy(Request $request,$id) {

        try {
           $user=User::find($id);

            Storage::disk('public')->delete(\Illuminate\Support\Facades\URL::to('/') . "/app/public/photos/" . $user->photo);
            if($user->role_id==1){
                if(User::Where('role_id',1)->where('id','!=',$user->id)->where('franchises_id',$request->franchise_id)->exists()){
                    $user->delete();
                return response()->json([
                            'success' => true,
                            'message' => 'Supprimer avec succées',
                            'status' => 200
                ]);  
                }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce profil principal',
                    'status' => 400,
                        ], 400);
         }
         } else{
 
            $user->delete();
            return response()->json([
                        'success' => true,
                        'message' => 'Supprimer avec succées',
                        'status' => 200
            ]);  
            }
         
           
           
        } catch (TokenInvalidException $exception) {
            return response()->json([
                        'success' => false,
                        'message' => 'Impossible de supprimer ce utilisateur',
                        'status' => 400,
                            ], 400);
        }
    }

   

   

    public function indexadmin(Request $request) {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
     $franchise_id=$request->header('franchise');
      $listUser=array();
      $datas=array();
        try {
            $user = User::Where('users.franchises_id',$franchise_id)
            ->leftjoin('roles','roles.id','=','users.role_id')
            ->select('users.*',"roles.id as roles","roles.name as role_type",'roles.nameenglais')
            ->orderby('users.role_id','ASC')->get();
       
            foreach($user AS $us){
               
           if($us->photo){
          $userlogo=  (\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/user/' . $us->photo);
           }else{
            $userlogo=null;

           }
     
                $listUser[]=['firstname'=>$us->firstname,
                'lastname'=>$us->lastname,
                'role'=>$us->role_type,
                 'email'=>$us->email,
             
                'password'=>NULL,
                'user_id'=>$us->id,
                'franchise_id'=>$us->franchises_id,
                'photo'=>$userlogo,
            ];
            }
            $roles= Role::select('roles.name','roles.id as role')->get();
            $datas=['usersSS'=>$listUser,
            'rolesSS'=>$roles];
       
            return response()->json([
                'success' => true,
                'message' => 'List profil utilisateur',
                'data' => $datas,
                'status' => 200
                    ], 200);
        } catch (QueryException $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, user not found.',
                        'status' => 400
                            ], 400);
        }
    }
    public function profilincomplet(Request $request) {
        try {
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
          $filtre=  ['etat'=>'All',"namegroupe"=>"Toutes les fiches","status"=>true,'list'=>[
          ['path'=>\Illuminate\Support\Facades\URL::to('/') .'/'."icon/complet.svg",'etat'=>'complet',"namedr"=> "2/2 groupes d'étiquettes","status"=>true],
        
          ['path'=>\Illuminate\Support\Facades\URL::to('/') .'/'."icon/manque.svg",'etat'=>'manque',"namedr"=> "1/2 groupes d'étiquettes","status"=>true],
          ['path'=>\Illuminate\Support\Facades\URL::to('/') .'/'."icon/aucune.svg",'etat'=>'aucune',"namedr"=> "0/2 groupes d'étiquettes","status"=>true]
          ]];
         $franchise_id=$request->header('franchise');
           $fiche_id=$request->fiche_id;
           $nbcount=$request->nbcount;
           // $fiche_id=124;


           $fiche = Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id')
           ->join('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
           ->where("fiches.franchises_id","=",$request->header('franchise'))
           ->where("ficheusers.user_id","=",Auth()->user()->id)
           ->where('fiches.state', 'LIKE', 'COMPLETED')
           
           ->select('profilincompletes.etat','profilincompletes.fiche_id as id','profilincompletes.title as locationName','profilincompletes.total')
            ->when($fiche_id,function ($query) use($fiche_id){
               $query->where('profilincompletes.fiche_id', $fiche_id);
               })->orderby('total');
     $fichestotals =Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id')
     ->join('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
     ->where("fiches.franchises_id","=",$request->header('franchise'))
     ->where("ficheusers.user_id","=",Auth()->user()->id)
     ->where('fiches.state', 'LIKE', 'COMPLETED')
               ->select(DB::raw("SUM(profilincompletes.totalfiche) as count"))
            
               ->when($fiche_id,function ($query) use($fiche_id){
                  $query->where('profilincompletes.fiche_id', $fiche_id);
                  });
            if  (empty($request->drapeaux) && ($request->drapeaux !="")){  
                $fiches=$fiche->get();
                $fichestotal=  $fichestotals->first();

            }
            else if ($request->drapeaux ===""){ 
                $fiche->where('profilincompletes.etat', \Illuminate\Support\Facades\URL::to('/') .'/'.'icon/aucune.svg');
                $fiches=$fiche->get();
                $fichestotal=  $fichestotals->first();
             }else{
                foreach($request->drapeaux as $Drapeaux ){
         if($Drapeaux['status']){
            $drapauxlist[]=$Drapeaux['path'];
         }
         $Drapeaux['status']==true?  $status=$Drapeaux['status']: $status=false;
             
         $listdrapeux[]=['path'=> \Illuminate\Support\Facades\URL::to('/') .'/icon/'.$Drapeaux['etat'].".svg",'etat'=>$Drapeaux['etat'],'namedr'=>$Drapeaux['namedr'],"status"=>$status];
         $filtre=['etat'=>'All',"namegroupe"=>"Toutes les fiches","status"=>false,'list'=>$listdrapeux];

        }
                   $fiche->when($drapauxlist,function ($query) use($drapauxlist){
                        $query->whereIN('profilincompletes.etat', $drapauxlist);
                        }) ;
                                $fiches=$fiche->get();     
                       $fichestotals->when($drapauxlist,function ($query) use($drapauxlist){
                        $query->whereIN('profilincompletes.etat', $drapauxlist);
                                    }) ;
              
                                            $fichestotal=  $fichestotals->first();
                                            
                                       
                            
            }
             
           
     $total = 0;
     $data=array();
     if (count($fiches)> 0) {
        $totalp=   $fichestotal->count*100/count($fiches);
         $dates[] = array('profil' => $fiches,  'totalnbcount' =>$this->shortNumber(count($fiches)),'totalprofil' =>(int) $totalp,'nbcount'=>count($fiches),"nbfichee"=>count($fiches)
        );
     } else {$data=[[
        'etat'=>null, 'id'=>0,'locationName'=>null,'total'=>0]];
         $dates[] = array('profil' => $data, 'totalprofil' => number_format(0, 0, ',', ''),'nbcount'=>0,'totalnbcount' =>0);
     }
     return response()->json([
                 'success' => true,
                 'message' => 'List profil utilisateur',
                 'data' => $dates,
                 'filtre'=> $filtre,
                 'status' => 200
                     ], 200);
 } catch (Exception $ex) {
     return response()->json([
                 'success' => false,
                 'message' => 'Désole, profil incomplet not found.',
                 'status' => 400
                     ], 400);
 }
}
public function shortNumber($num)
{
    $units = ['', 'K', 'M', 'B', 'T'];
    for ($i = 0; $num >= 1000; ++$i) {
        $num /= 1000;
    }

    return round($num, 1) . $units[$i];
}

public function profilstat(Request $request) {
    try {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
      $filtre=  ['etat'=>'All',"namegroupe"=>"Toutes les fiches","status"=>true,'list'=>[
      ['path'=> \Illuminate\Support\Facades\URL::to('/') .'/'."icon/complet.svg",'etat'=>'complet',"namedr"=> "2/2 groupes d'étiquettes","status"=>true],

      ['path'=>\Illuminate\Support\Facades\URL::to('/') .'/'."icon/manque.svg",'etat'=>'manque',"namedr"=> "1/2 groupes d'étiquettes","status"=>true],
      ['path'=>\Illuminate\Support\Facades\URL::to('/') .'/'."icon/aucune.svg",'etat'=>'aucune',"namedr"=> "0/2 groupes d'étiquettes","status"=>true],
      ]];
     $franchise_id=$request->header('franchise');
       $fiche_id=$request->fiche_id;
       $nbcount=$request->nbcount;
       // $fiche_id=124;
       $fiche =profilincomplete::query()
 
       ->where('states','=',1)
       ->select('etat','fiche_id as id','title as locationName','total')
        ->when($fiche_id,function ($query) use($fiche_id){
           $query->where('fiche_id', $fiche_id);
           })->orderby('total');
 $fichestotals =profilincomplete::query()
           ->select(DB::raw("SUM(totalfiche) as count"))
          ->where('states','=',1)
           ->when($fiche_id,function ($query) use($fiche_id){
              $query->where('fiche_id', $fiche_id);
              });
        
            $fiches=$fiche->get();
            $fichestotal=  $fichestotals->first();
      
       
       
 $total = 0;
 $data=array();
 if (count($fiches)> 0) {
    $totalp=   $fichestotal->count*100/count($fiches);
     $dates[] = array('profil' => $fiches, 'totalprofil' => (int) $totalp,'nbcount'=>count($fiches),"nbfichee"=>count($fiches)
    );
 } else {$data=[[
    'etat'=>null, 'id'=>0,'locationName'=>null,'total'=>0]];
     $dates[] = array('profil' => $data, 'totalprofil' => number_format(0, 0, ',', ''),'nbcount'=>0);
 }
 return response()->json([
             'success' => true,
             'message' => 'List profil utilisateur',
             'data' => $dates,
             'filtre'=> $filtre,
             'status' => 200
                 ], 200);
} catch (Exception $ex) {
 return response()->json([
             'success' => false,
             'message' => 'Désole, profil incomplet not found.',
             'status' => 400
                 ], 400);
}
}


   
    public function suggestion(Request $request) {
        try {
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
         $franchise_id=$request->header('franchise');
         $fiche_id=$request->fiche_id;

    
        $photos= Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id') ->rightjoin('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
        ->where("fiches.franchises_id","=",$request->header('franchise'))->where("ficheusers.user_id","=",Auth()->user()->id)->where('fiches.state', 'LIKE', 'COMPLETED')
        ->when($fiche_id,function ($query) use($fiche_id){
           $query->where('fiches.id', $fiche_id);
           })->where('profilincompletes.Photo','=',0)->count();
     if($photos){
    $iconfiche= Iconfiche::where('code','logo')->first();
    $listfiche[]=['name'=>$iconfiche->name,'icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path,'total'=>StatistiqueController::number_format_short($photos)];
    
}            
       $primaryPhone= Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id') ->rightjoin('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
       ->where("fiches.franchises_id","=",$request->header('franchise'))->where("ficheusers.user_id","=",Auth()->user()->id)->where('fiches.state', 'LIKE', 'COMPLETED')
       ->when($fiche_id,function ($query) use($fiche_id){
          $query->where('fiches.id', $fiche_id);
          })->where('profilincompletes.primaryPhone','=',0)->count();
if($primaryPhone){
    $iconfiche= Iconfiche::where('code','primaryPhone')->first();
    $listfiche[]=['name'=>$iconfiche->name,'icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path,'total'=>StatistiqueController::number_format_short($primaryPhone)];
   
   
}

  $websiteUrl= Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id') ->rightjoin('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
  ->where("fiches.franchises_id","=",$request->header('franchise'))->where("ficheusers.user_id","=",Auth()->user()->id)->where('fiches.state', 'LIKE', 'COMPLETED')
  ->when($fiche_id,function ($query) use($fiche_id){
     $query->where('fiches.id', $fiche_id);
     })->where('profilincompletes.websiteUrl','=',0)->count();
if($websiteUrl){
    $iconfiche= Iconfiche::where('code','websiteUrl')->first();
    $listfiche[]=['name'=>$iconfiche->name,'icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path,'total'=>StatistiqueController::number_format_short($websiteUrl)];
}
           
$attribute=Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id') ->rightjoin('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
->where("fiches.franchises_id","=",$request->header('franchise'))->where("ficheusers.user_id","=",Auth()->user()->id)->where('fiches.state', 'LIKE', 'COMPLETED')
->when($fiche_id,function ($query) use($fiche_id){
   $query->where('fiches.id', $fiche_id);
   })->where('profilincompletes.attributes','=',0)->count();
   if($attribute){
            $iconfiche= Iconfiche::where('code','urlValues')->first();
            $listfiche[]=['name'=>$iconfiche->name,'icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path,'total'=>StatistiqueController::number_format_short($attribute)];
   }
            $service=Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id') ->rightjoin('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
            ->where("fiches.franchises_id","=",$request->header('franchise'))->where("ficheusers.user_id","=",Auth()->user()->id)->where('fiches.state', 'LIKE', 'COMPLETED')
            ->when($fiche_id,function ($query) use($fiche_id){
               $query->where('fiches.id', $fiche_id);
               })->where('profilincompletes.Service','=',0)->count();
     if($service){
                $iconfiche= Iconfiche::where('code','services')->first();
                $listfiche[]=['name'=>$iconfiche->name,'icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path,'total'=>StatistiqueController::number_format_short($service)];
      }
         
           $specialHours= Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id') ->rightjoin('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
            ->where("fiches.franchises_id","=",$request->header('franchise'))->where("ficheusers.user_id","=",Auth()->user()->id)->where('fiches.state', 'LIKE', 'COMPLETED')
            ->when($fiche_id,function ($query) use($fiche_id){
               $query->where('fiches.id', $fiche_id);
               })->where('profilincompletes.specialHours','=',0)->count();

               if($specialHours){
                $iconfiche= Iconfiche::where('code','specialhours_start_date')->first();
                $listfiche[]=['name'=>$iconfiche->name,'icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path,'total'=>StatistiqueController::number_format_short($specialHours)];
               }
       
               $regularHours=   Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id') ->rightjoin('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
            ->where("fiches.franchises_id","=",$request->header('franchise'))->where("ficheusers.user_id","=",Auth()->user()->id)->where('fiches.state', 'LIKE', 'COMPLETED')
            ->when($fiche_id,function ($query) use($fiche_id){
               $query->where('fiches.id', $fiche_id);
               })->where('profilincompletes.regularHours','=',0)->count();
           if($regularHours){
            $iconfiche= Iconfiche::where('code','OpenInfo_status')->first();
            $listfiche[]=['name'=>$iconfiche->name,'icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path,'total'=>StatistiqueController::number_format_short($regularHours)];
            }
            $tabs=array();
           
                $collection = collect($listfiche);
                $sorted = $collection->sortby('name');
                $tabs=$sorted->values()->all();
           
        
            return response()->json([
                'success' => true,
                'message' => 'List suggestions',
                'data' => $tabs,
                'status' => 200
                    ], 200);
           
                
          
    
        return response()->json([
            'success' => true,
            'message' => 'List suggestions',
            'data' => $tabs,
            'status' => 200
                ], 200);
            }catch (Exception $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, profil incomplet not found.',
                        'status' => 400
                            ], 400);
        }
    }
    public function detailsuggestion(Request $request) {
        try {
       if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
         $franchise_id=$request->header('franchise');
         $fiche_id=$request->fiche_id;
        $fiches = Fiche::query()
       ->join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
       ->where('ficheusers.user_id', auth()->user()->id)
       ->leftJoin('categories', 'categories.fiche_id', '=', 'fiches.id')
       ->where('fiches.franchises_id', $franchise_id)
       ->when($fiche_id,function ($query) use($fiche_id){
        $query->where('fiches.id', $fiche_id);
        })
       ->where('categories.type', 'primaryCategory')
       ->where('fiches.state', 'COMPLETED')
       ->select('fiches.*', 'categories.displayName', 'categories.categorieId','categories.id as categorie_id', 'categories.type')
       ->get();
$total = 0;
$tab=array();
$listfiche=array();
       $type=$request->type;
     
       $listfiche=array();
       if (count($fiches) > 0) {
          $i = 0;
          foreach ($fiches as $fiche) {
           
           if($type=="Ajouter un logo"){
            $photo = Photo::where('fiche_id', $fiche['id'])
            ->where('category','=','PROFILE')->doesntExist();
            if ($photo) {
                       
                $listfiche[$type][]=['id' => $fiche['id'], 'locationName' => $fiche['locationName'],'status'=>false];
             }
           }
         if($type=='Ajouter un numéro de téléphone'){
                        if (!$fiche['primaryPhone']) {
                            $listfiche[$type][]=['id' => $fiche['id'], 'locationName' => $fiche['locationName'],'status'=>false,'numerotel'=>''];
                         
                          }
                     }
                     if($type=='Ajouter un site'){
                      if (!$fiche['websiteUrl']) {
                        $listfiche[$type][] =['id' => $fiche['id'], 'locationName' => $fiche['locationName'],'status'=>false,'websiteUrl'=>''];
                      }
                    }
                    if($type=='Liens pour prise rendez-vous'){
                        $priserendez = Attribute::where('fiche_id', $fiche['id'])
                        ->where('valueType','=','URL')->doesntExist();
                        if ($priserendez) {
                          $listfiche[$type][]=['id' => $fiche['id'], 'locationName' => $fiche['locationName'],'status'=>false,'validate'=>false,'urlvalues'=>''];
                        }
                      }
                      if($type=='Services'){
                        $tabcategoriadds = Categorie::where('fiche_id', $fiche['id'])
                        ->get('id');
                        foreach($tabcategoriadds as $addcat){
                            $datacat[] =  $addcat->id;
                        }
                       // $displayNameservice=false;
                       $displayNameservice = Service::whereIN('categorie_id', $tabcategoriadds)->doesntExist();
                        if ($displayNameservice) {
                         

                          $listfiche[$type][]=['id' => $fiche['id'], 'locationName' => $fiche['locationName'],'YYY'=>$tabcategoriadds,'status'=>false];
                           
                        }
                      }
                      if($type=="Horaires d'ouverture exceptionnels"){
                       
                       $specialhours = Fichehour::where('fiche_id', $fiche['id'])
                             ->whereNull('open_date')
                             ->whereNull('close_date')
                             ->whereNotNull('specialhours_start_date')
                             ->whereNotNull('specialhours_end_date')->doesntExist();
                        if ($specialhours) {
                          $listfiche[$type][]=['id' => $fiche['id'], 'locationName' => $fiche['locationName'],'status'=>false];
                       
                        }
                      }
                   
                    if($type=='Horaires'){
                      $fichehours = Fichehour::where('fiche_id', $fiche['id'])
                        ->whereNotNull('open_date')
                        ->whereNotNull('close_date')
                        ->whereNull('specialhours_start_date')
                        ->whereNull('specialhours_end_date')->doesntExist();
                     
                      if ($fichehours) {
                 
                        $listfiche[$type][]=['id' => $fiche['id'], 'locationName' => $fiche['locationName'],'status'=>false];
                      }
                    }
                   $i++;
                  }
                 
                  $sumArray = array();
                  $lishoraires = array();
                  $tabs=null;
                  foreach ($listfiche as $k=>$subArray) {
                      if($k ==='Horaires' ){
                        $lishoraires=FichehourController::listdays();
                        $tabs="Listhoraire";
                      }
                      if($k==="Horaires d'ouverture exceptionnels"){
                        $lishoraires=FichehourController::listdaysExpec();
                     
                        $tabs="Listhoraireexexceptionnels";
                      }
                      if($k ==='Services'){
                        if ($fiche_id) {
                            $lishoraires=ServiceController::listeserivcefiche($fiches);
                        }
                        else{
                            $lishoraires=ServiceController::listeserivcefiche($fiches);
                        }
                       
                        $tabs="listServices";
                      }
                    foreach ($subArray as $value) {
                       
                      $sumArray=['total'=>count($subArray),'name'=>$type,"listfiche"=>$subArray, $tabs=>$lishoraires];
                    }
                    $tab[]=$sumArray;
                  }
                 
              }
              return response()->json([
                          'success' => true,
                          'message' => 'List fiche suggestions',
                          'data' => $tab,
                          'status' => 200
                              ], 200);
          } catch (Exception $ex) {
              return response()->json([
                          'success' => false,
                          'message' => 'Désole, profil incomplet not found.',
                          'status' => 400
                              ], 400);
          }
    }
public static function totalprofilincomplet($id){
  $profil=  profilincomplete::where('fiche_id',$id)->first();
    $pr =Paramater::where('name','profilincomplet')->first();
    $prs= (int)$pr->value;
    !$profil->storeCode ? $prs--: '' ;
    !$profil->description ? $prs--: '' ;
    !$profil->websiteUrl ? $prs--: '' ;
    !$profil->adwPhone ? $prs--: '' ;
    !$profil->locationName ? $prs--: '' ;
    !$profil->regularHours ? $prs--: '' ;
    !$profil->serviceArea ? $prs--: '' ;
    !$profil->Service ? $prs--: '' ;
    !$profil->attributes ? $prs--: '' ;
    !$profil->moreHours ? $prs--: '' ;
    !$profil->primaryPhone ? $prs--: '' ;
    !$profil->specialHours ? $prs--: '' ;
    !$profil->address ? $prs--: '' ;
    !$profil->attributesUrl ? $prs--: '' ;
    !$profil->Photo ? $prs--: '' ;
    !$profil->Post ? $prs--: '' ;
    !$profil->labels ? $prs--: '' ;
    $prc = $prs * 100 /(int)$pr->value;
    $profil->total=(int) $prc;
    $profil->totalfiche=number_format((float)($prs /(int)$pr->value),2);
    
    $profil->update();
    return true;

}
}
