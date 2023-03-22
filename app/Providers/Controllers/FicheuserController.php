<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Database\QueryException;
use App\Helper\Helper;
use App\Models\Role;
use App\Models\Ficheuser;
use GoogleMyBusinessService;
use Google;

class FicheuserController extends Controller
{
    public $mybusinessService;
    public $placeID;
    public $admins;

    public function __construct() {
      /*  $this->mybusinessService = Helper::GMB();
        $this->admins = $this->mybusinessService->accounts_locations_admins;
        $this->placeID = Helper::AdminAction();*/
    }
    

    
    public function dissocierfiche(Request $request){
        try{
          
            $fiche= Ficheuser::Where('fiche_id',$request->fiche_id)
            ->where('user_id',$request->user_id)
            ->where('franchise_id',$request->header('franchise'));
            if($fiche->exists()){
                $fiche=$fiche->first();
                //$this->admins->delete($fiche->namefiche);
                Ficheuser::Where('fiche_id',$request->fiche_id)
                ->where('user_id',$request->user_id)
                ->where('franchise_id',$request->header('franchise'))->delete();
                return response()->json([
                    'success' => true,
                    'message' => "La profil à état retiré de la liste",
                    'status' => 200,
        
                ], 200);
            }
           
            
        } catch (QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
                'status' => $ex->getCode(),

            ], $ex->getCode());
        }
       



    }
    public function deletefiche(Request $request){
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try{
            $input=[];

            $messages = [
                
                'fiche_id.required' => 'Vérifier Votre Fiche!',
                'user_id.required' => 'Vérifier Votre User!',
            ];
            $input=[
                
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
            $fiche= Ficheuser::Where('fiche_id',$input->fiche_id)
            ->where('user_id',$input->user_id)
            ->where('franchise_id',$request->header('franchise'));
            if($fiche->exists()){
               $fiche=$fiche->first();
      
               $fiches=Fiche::find($input->fiche_id);
               $arrayName= explode( '/', $fiches->name);
               $Name= $arrayName[2] . '/' . $arrayName[3];
               $AdminsName= explode( '/', $fiche->namefiche);
               $NameAdmin= $fiche->namefiche;
           //    $NameAdmin= $AdminsName[4] . '/' . $AdminsName[5];
           if($NameAdmin && $fiche->role_id != 1){
            try {
                $client = Helper::googleClient();
                $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);  
         
                $delete = $serviceAccount->accounts_admins->delete($NameAdmin,array());
                
                Ficheuser::Where('fiche_id',$request->fiche_id)
                ->where('user_id',$request->user_id)
                ->where('franchise_id',$request->header('franchise'))->delete();
              
   return response()->json([
            'data'=> [],
        'success' => true,
        'message' => "Supprimer avec succès",
        'status' => 200,

    ], 200);
  
                  } 
                  catch (\Google_Service_Exception $ex) {
      
                      return response()->json([
                                  'success' => false,
                                  'message' => "La requête contient un argument invalide",
                                  'status' => 400,
                                      ], $ex->getCode()
                      );
                  }
                }else if( $fiche->role_id != 1){
                    Ficheuser::Where('fiche_id',$request->fiche_id)
                    ->where('user_id',$request->user_id)
                    ->where('franchise_id',$request->header('franchise'))->delete();
                  
       return response()->json([
            'data'=> [],
            'success' => true,
            'message' => "Supprimer avec succès",
            'status' => 200,
    
        ], 200);
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de retiré ce profil administrateur principal',
                        'status' => 400,
                    ],400);
                }
         
        }
        } catch (QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
                'status' => $ex->getCode(),

            ], $ex->getCode());
        }
       



    }
    public function updaterole(Request $request) {
        $input=[];

        $messages = [
            
            'fiche_id.required' => 'Vérifier Votre Fiche!',
            'user_id.required' => 'Vérifier Votre User!',
            'email.required' => 'Vérifier Votre Email!',
            'lastname.required' => 'Vérifier Votre User name!',
            'Role.required' => 'Vérifier Votre Role!',
        ];
        $input=[
            
            "user_id" => $request->userid,
            "fiche_id" => $request->fiche_id,
            "email" => $request->email,
            "lastname" => $request->lastname,
            "type" => $request->type,

        ];
        $validator = Validator::make($input,
            [
               
                "user_id" => 'exists:users,id',
                "fiche_id" => 'exists:fiches,id',
                "email" => 'min:5',
                "type" => 'min:5',
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

                $fiche = Fiche::find($input->fiche_id);
                $role = Role::where('name', $input->type)->first();
              
                try {
                    $usersfiche=Ficheuser::Where('user_id',$input->user_id)
                    ->Where('fiche_id',$input->fiche_id);
                    $client = Helper::googleClient();
                    $accountsAdmins = new  Google\Service\MyBusinessAccountManagement\Admin($client); 
                    if($usersfiche->exists()){
                       $users= $usersfiche->first();
                       $users->role_id=$role->id;
                       $accountsAdmins->name=$users->namefiche;
                       $accountsAdmins->admin=$input->email;
                       $accountsAdmins->role=$role->nameenglais;
                   
                       $updateMask= ["updateMask"=>'role'];
                       $USERSSS=  $this->patchUser($users->namefiche,$updateMask,$accountsAdmins);
                       $users->update();
                       return response()->json([
                        'success' => true,
                        'message' => 'Mise a jour traitée avec succes',
                        'data' => $users->namefiche,
                        'status' => Response::HTTP_OK
                            ], Response::HTTP_OK);
                    }else{
                        $accountsAdmins->admin=$input->email;
                        $accountsAdmins->role=$role->nameenglais;
                        $accountsAdmins->pendingInvitation=true;
                        //$postData=['admin'=>$input->email,'role'=>$role->nameenglais,'pendingInvitation'=>true];
                        $USERSSS=$this->CreateUser($fiche->name,$accountsAdmins);
                        $data['fiche_id']=$input->fiche_id;
                        $data['franchise_id']=$input->franchise_id;
                        $data['user_id']=$input->user_id;
                        $data['namefiche']=$USERSSS->name;
                        $data['role_id']=$role->id;
                        Ficheuser::create($data);
                        return response()->json([
                            'success' => true,
                            'message' => 'Mise a jour traitée avec succes',
                            'data' => $data,
                            'status' => Response::HTTP_OK
                                ], Response::HTTP_OK);
                    }
                   /* $users = User::find($request->userid);
                    $users->role_id = $role->id;

                    $users->update();*/
                } catch (\Google_Service_Exception $e) {

                    return response()->json([
                            'success' => false,
                            'message' => "Impossible de modifier ce rôle, en attendant l'acceptation de l'invitation ",
                            'status' => 400,
                            'data' => ''
                        ], 400);
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
    }
    public function userfiche(Request $request) {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        $input=[];

            $messages = [
                'role.required' => 'Vérifier Votre Role!',
                'fiche_id.required' => 'Vérifier Votre Fiche!',
                'email.required' => 'Vérifier Votre User!',
            ];
            $input=[
                "role" => $request->role,
                "email" => $request->email,
                "fiche_id" => $request->fiche_id,
            ];
            $validator = Validator::make($input,
                [
                   
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

                $fiche = Fiche::find($input->fiche_id);

            //    $this->placeID->setName($request->lastname);
           
                $role = Role::where('name', $input->role)->first();
              //  $postData=['admin'=>$input->email,'role'=>$role->nameenglais,'pendingInvitation'=>true];
                $client = Helper::googleClient();
                $accountsAdmins = new  Google\Service\MyBusinessAccountManagement\Admin($client); 
                $accountsAdmins->admin=$input->email;
                $accountsAdmins->role=$role->nameenglais;
                $accountsAdmins->pendingInvitation=true;
                try {
               
                     $userid= User::updateOrCreate(['email'=>$input->email,'franchises_id'=>$request->header('franchise')],['lastname'=>$input->email]);
                    $tabuser= User::where('id',$userid->id)->whereNotIN('role_id',[4,5,6,7]);
                     if($tabuser->exists()){
                        $tabuser=  $tabuser->first();
                        $role_id=$tabuser->role_id;
                    }
                    else{  
                        $role_id=$role->id;
                                       }
                        $USERSSS=$this->CreateUser($fiche->name,$accountsAdmins);
                       
                           
                                $data['fiche_id']=$input->fiche_id;
                                $data['franchise_id']=$request->header('franchise');
                                $data['user_id']=$userid->id;
                                $data['namefiche']=$USERSSS['name'];
                                $data['role_id']=$role_id;
                              $data['pendingInvitation']=true;
                                Ficheuser::create($data);
                               
                       
                        
                      
                       
                       return response()->json([
                            'success' => true,
                            'message' => 'Mise a jour traitée avec succes',
                            'data' => $data,
                            'status' => Response::HTTP_OK
                                ], Response::HTTP_OK);
               
                } catch (\Google_Service_Exception $e) {

                    return response()->json([
                            'success' => false,
                            'message' => "Verifier votre noms ou l'adresse e-mail'                            ",
                            'status' => 400,
                            'data' => ''
                        ], 400);
                }
                return response()->json([
                            'success' => true,
                            'message' => 'Mise a jour traitée avec succes',
                            'data' => $USERSSS,
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

    public static function CreateUser($location,$admins){
       
        $arrayName= explode( '/', $location );
        $Name= $arrayName[2] . '/' . $arrayName[3];
        $client = Helper::googleClient();
        $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);  
          try {
            $add_accounts = $serviceAccount->accounts_admins->create($Name,$admins);
            return $add_accounts;

                } catch (\Google_Service_Exception $ex) {
    
                    return response()->json([
                                'success' => false,
                                'message' => "La requête contient un argument invalide",
                                'status' => 400,
                                    ], $ex->getCode()
                    );
                }
    }
    public static function patchUser($location,$updateMask,$admins){
        
        $arrayName= explode( '/', $location );
    //  $Name= $arrayName[2] . '/' . $arrayName[3].'/'.$arrayName[4] . '/' . $arrayName[5];
      $client = Helper::googleClient();
        $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);  
          try {
            $patch_accounts = $serviceAccount->accounts_admins->patch($location,$admins,$updateMask);
        
            return $patch_accounts;

                } catch (\Google_Service_Exception $ex) {
    
                    return response()->json([
                                'success' => false,
                                'message' => "La requête contient un argument invalide",
                                'status' => 400,
                                    ], $ex->getCode()
                    );
                }
    }
    public static function getUser($location){
        $listadmin=array();
      /*  $accessToken = Helper::GMBServiceToken();
        $token_acces = json_decode($accessToken, true);*/
    
        $arrayName= explode( '/', $location );
      $Name= $arrayName[2] . '/' . $arrayName[3];
     
            $client = Helper::googleClient();
        $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);  
          try {
            $tab = $serviceAccount->accounts_admins->listAccountsAdmins($Name,array());
              
            foreach($tab["admins"] as $list){
                $listadmin[]=$list;
           }
            
       
       return $listadmin;

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
