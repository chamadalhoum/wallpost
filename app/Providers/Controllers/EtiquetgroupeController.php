<?php

namespace App\Http\Controllers;

use App\Models\Etiquetgroupe;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Etiquette;
use phpDocumentor\Reflection\Types\Null_;
use Illuminate\Support\Facades\DB;

class EtiquetgroupeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $token = JWTAuth::parseToken()->refresh();
        User::where('id', auth()->user()->id)->update(['remember_token' => $token]);
        try {
            $etiquetgroupe = Etiquetgroupe::with('groupe:id,name','etiquette:id,name','fiche:id,locationName');
            $s = request('search');
            if ($s) {
                $etiquette=  $etiquetgroupe->where('state', 'LIKE', '%' . $s . '%')->
                orWhere('groupe_id', 'LIKE', '%' . $s . '%')->
                orWhere('etiquette_id', 'LIKE', '%' . $s . '%')->
                orWhere('fiche_id', 'LIKE', '%' . $s . '%')
                    ->get();
                if($etiquette->count()>0){
                    return response()->json([
                        'success' => true,
                        'message' =>  "Liste d'etiquette groupe",
                        'data' =>  $etiquette,

                        'status' => 200
                    ], 200);
                }else{ return response()->json([
                    'success' => true,
                    'message' =>'Désole, Etiquette groupe not found.',

                    'status' => 200
                ], 200);}
            }else{
                return response()->json([
                    'success' => true,
                    'message' =>  "Liste d'etiquette groupe",
                    'data' => $etiquetgroupe->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        }
        catch(Exception $ex){
            return response()->json([
                'success' => false,
                'message' => 'Désole, Etiquette groupe not found.',

                'status' => 400
            ], 400);
        }
    }


public static function etiquettegroupe($dataG){

      if($dataG['id']){
         
         if(Etiquetgroupe::where("id",$dataG['id'])->exists()){
          $etiquetgroupe=  Etiquetgroupe::where("id",$dataG['id'])->first();
          if($etiquetgroupe->fiche_id){
          $etiquetgroupe->state=$dataG['state'];
          $etiquetgroupe->groupe_id=$dataG['groupe_id'];
          $etiquetgroupe->etiquette_id=$dataG['etiquette_id'];
          $etiquetgroupe->fiche_id=$dataG['fiche_id'];
          $etiquetgroupe->update();
          }else{
            $etiquetgroupe->state=$dataG['state'];
            $etiquetgroupe->groupe_id=$dataG['groupe_id'];
            $etiquetgroupe->etiquette_id=$dataG['etiquette_id'];
            $etiquetgroupe->fiche_id=$dataG['fiche_id'];
            $etiquetgroupe->update();
           // $etiquetgroupe= Etiquetgroupe::create($dataG);
          }
      }
      }
      else {
       
         $etiquetgroupe= Etiquetgroupe::create($dataG);

      }

    return $etiquetgroupe;

}

    public function byfiche($idfiche)
    {
       /* $token = JWTAuth::parseToken()->refresh();
        User::where('id', auth()->user()->id)->update(['remember_token' => $token]);*/
        try {
            $etiquetgroupe = Etiquetgroupe::with('groupe:id,name','etiquette:id,name','fiche:id,locationName');
            $s = request('search');
            if ($s) {
                $etiquette=  $etiquetgroupe->where('fiche_id', $idfiche)->get();
                if($etiquette->count()>0){
                    return response()->json([
                        'success' => true,
                        'message' =>  "Liste d'etiquette groupe",
                        'data' =>  $etiquette,

                        'status' => 200
                    ], 200);
                }else{ return response()->json([
                    'success' => true,
                    'message' =>'Désole, Etiquette groupe not found.',

                    'status' => 200
                ], 200);}
            }else{
                return response()->json([
                    'success' => true,
                    'message' =>  "Liste d'etiquette groupe",
                    'data' =>$etiquetgroupe->where('fiche_id', $idfiche)->
                    orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        }
        catch(Exception $ex){
            return response()->json([
                'success' => false,
                'message' => 'Désole, Etiquette groupe not found.',

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $messages = [
            'state.required' => 'Vérifier Votre Type!',
            'id_groupe.required' => 'Vérifier Votre groupe!',
            'Fiche.required' => 'Vérifier Votre Fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "state" => 'numeric|min:1|max:20',
                "id_groupe" => 'exists:groupes,id',
                "etiquette_id" => 'exists:etiquettes,id',
                "fiche_id" => 'exists:fiches,id',


            ], $messages
        );


        if ($validator->fails()) {
            return response()->json([
                'succes'=>false,
                'message'=>$validator->errors()->toArray(),
                'status'=>422,
                ],
                422);
        }

        if($validator->passes()){
            try {
                 /*$etiquetgroupes=array();
                 DB::table('etiquetgroupes')->
                   where(function () use($request){
                DB::table('etiquetgroupes')->where("groupe_id",$request->id_groupe)
                    ->orWhere('etiquette_id',$request->etiquette_id);
                })->Where('fiche_id',$request->fiche_id);*/
                //foreach($request->etiquettegroupe as $etiquette){
                 
                    $etiquetgroupes=array();
                  if(is_array($request->etiquettegroupe)){
                   
                foreach($request->etiquettegroupe['ettiquettes'] as $etq){
                    $etiquettesilocalisation=  Etiquetgroupe::
                    Where('fiche_id',$request->fiche_id)->Where('state',1)->Where('groupe_id',1)->exists();
                    if($request->etiquettegroupe['id_groupe']==='1' && $etiquettesilocalisation === true){
                       
                            return response()->json([
                                'success' => false,
                                'message' => "Impossible d'ajouter une autre étiquette à ce groupe",
                               'data' => $request->etiquettegroupe['id_groupe'],
                                'status' => 400,
            
                            ], 400);
                       
                    }
                    $etq['status']== true?$state=1:$state=0;
                    $etiquettesi=  Etiquetgroupe::where("groupe_id",$request->etiquettegroupe['id_groupe'])
                            ->Where('id',$etq['etiquettegroupe'])->first();
                            $msg='Etiquette groupe ajouté avec succès';
                            if( $etq['status']){
                                $etiquetgroupes= Etiquetgroupe::updateOrCreate(['groupe_id'=>$request->etiquettegroupe['id_groupe'],
                                'etiquette_id'=>$etiquettesi->etiquette_id,
                                'fiche_id'=>$request->fiche_id],['state'=>$state]);
                                
                            }else{
                                Etiquetgroupe::where("groupe_id",$request->etiquettegroupe['id_groupe'])
                            ->Where('id',$etq['etiquettegroupe'])->Where('fiche_id',$request->fiche_id)->delete();
                            }
     
                }
            }else{
                $msg='Etiquette groupe supprimer avec succès';
                $request->status== true?$states=1:$states=0;
                $etiquettesit=  Etiquetgroupe::where("groupe_id",$request->id_groupe)
               ->where('fiche_id',$request->fiche_id) ->Where('id',$request->etiquettegroupe)->delete();
/*$etiquetgroupes= Etiquetgroupe::updateOrCreate(['groupe_id'=>$request->id_groupe,
'etiquette_id'=>$etiquettesit->etiquette_id,
'fiche_id'=>$request->fiche_id],['state'=>$states]); */
            }
       
          
          
               // }
            //    if($request->etatActivat=== true || $request->etatActivat=== false){
                   /*  $etiquettes= Etiquetgroupe::where("groupe_id",$request->id_groupe)
                             ->WhereNull('fiche_id')->get()->toarray();
                     $etiquetgroupes=$etiquettes;
                     $i=0;
             foreach ($etiquettes as $req){


            if($req->status){
                     
                
                $etiquettesi=  Etiquetgroupe::where("groupe_id",$request->id_groupe)
                             ->Where('fiche_id',$request->fiche_id)->Where('etiquette_id',$req['etiquette_id']);


                 if($etiquettesi->exists()){
                 $etiquettesi=$etiquettesi->first();

          $etiquette= Etiquetgroupe::find($etiquettesi->id);
          $etiquette->state= $req->status;
          $etiquette->groupe_id=$request->id_groupe;
          $etiquette->etiquette_id=$etiquette->etiquette_id;
          $etiquette->fiche_id=$request->fiche_id;


          $etiquette->update();
          $etiquetgroupes=$etiquette;
             }
             else {
                $etiquettesilocalisation=  Etiquetgroupe::where("groupe_id",$request->id_groupe)
                ->Where('fiche_id',$request->fiche_id)->Where('groupe_id',1);
                if($etiquettesilocalisation->exists()){
                    return response()->json([
                        'success' => false,
                        'message' => "Impossible d'ajouter une autre étiquette à ce groupe",
                        'data' => $etiquetgroupes,
                        'status' => 400,
    
                    ], 400);
                }

          $etiquetgroupe['state']=$req->status;
          $etiquetgroupe['groupe_id']=$req['groupe_id'];
          $etiquetgroupe['etiquette_id']=$req['etiquette_id'];
          $etiquetgroupe['fiche_id']=$request->fiche_id;
          $etiquetgroupes= Etiquetgroupe::create($etiquetgroupe);
 }
             }
            $i++;
                
                }
               // }

            /*    else{

         //   $etiquettess=$etiquettess->Where('etiquette_id',$etiquette->etiquette_id);
                    if($request->etiquettegroupe &&  $request->status===true ){

          $etiquette= Etiquetgroupe::find($request->etiquettegroupe);
          $etiquetgroupe['state']=$request->status;
          $etiquetgroupe['groupe_id']=$request->id_groupe;
          $etiquetgroupe['etiquette_id']=$etiquette->etiquette_id;
          $etiquetgroupe['etiquetgroupe_id']=$request->etiquettegroupe;
          $etiquetgroupe['fiche_id']=$request->fiche_id;

          $etiquettesilocalisation=  Etiquetgroupe::where("groupe_id",$request->id_groupe)
          ->Where('fiche_id',$request->fiche_id)->Where('groupe_id',1);
          if($etiquettesilocalisation->exists()){
            return response()->json([
                'success' => false,
                'message' => "Impossible d'ajouter une autre étiquette à ce groupe",
                'data' => $etiquetgroupes,
                'status' => 400,

            ], 400);
        }
         $etiquetgroupes= Etiquetgroupe::create($etiquetgroupe);
                }

          else{
          //   $vab= $etiquettess->get();

          $etiquette= Etiquetgroupe::find($request->etiquettegroupe);
          $etiquette->state=$request->status;
          $etiquette->groupe_id=$request->id_groupe;
          $etiquette->etiquette_id=$etiquette->etiquette_id;
          $etiquette->fiche_id=$request->fiche_id;


          $etiquette->update();
          $etiquetgroupes=$etiquette;
                }
                }
*/
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'data' => $etiquetgroupes,
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
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Etiquetgroupe  $etiquetgroupe
     * @return \Illuminate\Http\Response
     */
    public function show($etiquetgroupe)
    {

        $token = JWTAuth::parseToken()->refresh();
        User::where('id', auth()->user()->id)->update(['remember_token' => $token]);
       $etiquetgroupes= Etiquetgroupe::with('groupe:id,name','etiquette:id,name','fiche:id,locationName')->find($etiquetgroupe);
        if (!$etiquetgroupes) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Etiquette groupe not found.',

                'status' => 400
            ], 400);
        }
        return response()->json([
            'success' => true,
            'message' => 'Etiquette groupe id ' . $etiquetgroupes->id,
            'data' => $etiquetgroupes,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Etiquetgroupe  $etiquetgroupe
     * @return \Illuminate\Http\Response
     */
    public function edit(Etiquetgroupe $etiquetgroupe)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Etiquetgroupe  $etiquetgroupe
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $etiquetgroupe = Etiquetgroupe::find($id);
        $messages = [
            'state.required' => 'Vérifier Votre Type!',
            'groupe_id.required' => 'Vérifier Votre groupe!',
            'Fiche.required' => 'Vérifier Votre Fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "state" => 'numeric|min:1|max:20',

                "groupe_id" => 'exists:groupes,id',
                "etiquette_id" => 'exists:etiquettes,id',
                "fiche_id" => 'exists:fiches,id',


            ], $messages
        );

        if ($validator->fails()) {

            return response()->json([
                'succes'=>false,
                'message'=>$validator->errors()->toArray(),
                'status'=>422],
                422);
        }
        if ($validator->passes()) {
            try {
                   $etiquetgroupes=array();
                if($request->etatActivat){
                     $etiquettes= Etiquetgroupe::where("groupe_id",$request->id_groupe)
                             ->WhereNull('fiche_id')->get()->toarray();
                     $i=0;
             foreach ($etiquettes as $req){

                 $etiquettess= Etiquetgroupe::where("groupe_id",$request->id_groupe)
                             ->Where('fiche_id',$request->fiche_id)
                         ->Where('etiquette_id',$req['etiquette_id'])->get();
                 $etiquetgroupes=$etiquettess;
                 if(count($etiquettess)===0){
          $etiquetgroupe['state']=$request->etat;
          $etiquetgroupe['groupe_id']=$req['groupe_id'];
          $etiquetgroupe['etiquette_id']=$req['etiquette_id'];
          $etiquetgroupe['fiche_id']=$request->fiche_id;
          $etiquetgroupes= Etiquetgroupe::create($etiquetgroupe);
             }
             $i++;
                 }

                }
                else{
                    $etiquettess=$etiquettess->Where('etiquette_id',$etiquette->etiquette_id)->exists();

          $etiquette= Etiquetgroupe::find($request->etiquettegroupe);
          $etiquetgroupe['state']=$request->etat;
          $etiquetgroupe['groupe_id']=$request->id_groupe;
          $etiquetgroupe['etiquette_id']=$etiquette->etiquette_id;
          $etiquetgroupe['fiche_id']=$request->fiche_id;


         $etiquetgroupes= Etiquetgroupe::create($etiquetgroupe);

                }

                $etiquetgroupe->state = $request->state;
                $etiquetgroupe->groupe_id = $request->groupe_id;
                $etiquetgroupe->etiquette_id = $request->etiquette_id;
                $etiquetgroupe->fiche_id = $request->fiche_id;
                $etiquetgroupe->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $etiquetgroupe,

                    'status' => Response::HTTP_OK
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json([
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400,

                    ], $ex->getCode()
                );
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Etiquetgroupe  $etiquetgroupe
     * @return \Illuminate\Http\Response
     */
    public function destroy($etiquetgroupe)
    {
        $token = JWTAuth::parseToken()->refresh();
        User::where('id', auth()->user()->id)->update(['remember_token' => $token]);
        $etiquetgroupe = Etiquetgroupe::find($etiquetgroupe);
        try {

            $etiquetgroupe->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Etiquette groupe could not be deleted',
                'status' => 500,

            ], 500);
        }

    }
      public function deplacement(Request $request)
    {


        $messages = [
            'name' => 'Vérifier Votre groupe!',
            'color' => 'Vérifier Votre couleur!',

            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'max:45',
                "color" => 'max:45',


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

                $datas = $request->listgroupe['etiquette'];
                $groupedata=$request->listgroupe['nouveaugroupe'];
             if(Etiquette::where('name',$datas['Nom_etiquette'])->exists()){
                $etiquettea=Etiquette::where('name',$datas['Nom_etiquette'])->first();
                  $etiquettegroupe= Etiquetgroupe::where('etiquette_id',$etiquettea->id)
                ->get()->toarray();
                        if(count($etiquettegroupe)>0){
                       foreach($etiquettegroupe as $etiq){
                     

               $dataG = array('state' => true, 'etiquette_id' => $etiq['etiquette_id'], 'groupe_id' => $groupedata['id_groupe'],
                            'id' => $etiq['id'], 'fiche_id' => $etiq['fiche_id']);
                             $this->etiquettegroupe($dataG);
                           }

                        }
                        else {
                            $dataG = array('state' => true, 'etiquette_id' => $etiquettea->id, 'groupe_id' => $groupedata['id_groupe'],
                            'id' =>NULL, 'fiche_id' =>Null);
                             $this->etiquettegroupe($dataG);
                        }
                    }else{
                            $ettiquetgroup=null;
                            $data['name'] = $datas['Nom_etiquette'];
                            $data['status'] = true;
                            $etiqt = Etiquette::create($data);
                            $dataG = array('state' => true, 'etiquette_id' => $etiqt->id, 'groupe_id' => $groupedata['id_groupe'],
                            'id' => $ettiquetgroup, 'fiche_id' => null);
                             $this->etiquettegroupe($dataG);
                        }
              $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
            ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
            ->whereNull('etiquetgroupes.fiche_id')
            //->where('ficheusers.user_id', '=', Auth()->user()->id)

            ->get(['etiquettes.name as Nom_etiquette',
                'etiquettes.state',
                'etiquetgroupes.etiquette_id',
                'groupes.id as idgroupe',
                'groupes.name as Name_groupe',
                'etiquetgroupes.id as etiquettegroupe',
                'etiquetgroupes.state as etiquettegroupestate',
                'etiquetgroupes.fiche_id',
                'groupes.color',
            ])->toArray();

                return response()->json([
                    'success' => true,
                    'message' => 'Déplacer avec succès',
                    'data' => GroupeController::group_byfiche('Name_groupe', $filtrefiche,$id=null),
                    'status' => Response::HTTP_OK
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
       public function restaurer(Request $request)
    {

        $messages = [

            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'max:45',
                "color" => 'max:45',


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
                $datas = $request->etiquette;


                $etiquettegroupe= Etiquette::where('name',$datas['Nom_etiquette'])->leftjoin('etiquetgroupes','etiquettes.id','=','etiquetgroupes.etiquette_id')
                        ->select('etiquetgroupes.*')->get();
                foreach($etiquettegroupe as $etiq){

               $dataG = array('state' => true, 'etiquette_id' => $etiq['etiquette_id'], 'groupe_id' => $etiq['groupe_id'],
                            'id' => $etiq['id'], 'fiche_id' => $etiq['fiche_id']);
                             $this->etiquettegroupe($dataG);
                           }
              $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
            ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
            ->whereNull('etiquetgroupes.fiche_id')
            //->where('ficheusers.user_id', '=', Auth()->user()->id)

            ->get(['etiquettes.name as Nom_etiquette',
                'etiquettes.state',
                'etiquetgroupes.etiquette_id',
                'groupes.id as idgroupe',
                'groupes.name as Name_groupe',
                'etiquetgroupes.id as etiquettegroupe',
                'etiquetgroupes.state as etiquettegroupestate',
                'etiquetgroupes.fiche_id',
                'groupes.color',
            ])->toArray();

                return response()->json([
                    'success' => true,
                    'message' => 'Sauvegardé avec succès',
                    'data' => GroupeController::group_byfiche('Name_groupe', $filtrefiche,$id=null),
                    'status' => Response::HTTP_OK
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
      public function deletetdefinitivement(Request $request)
    {

        $messages = [
            'name' => 'Vérifier Votre groupe!',
            'color' => 'Vérifier Votre couleur!',

            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'max:45',
                "color" => 'max:45',


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
                $datas = $request->etiquette;

                $etiquettegroupe= Etiquette::where('name',$datas['Nom_etiquette'])->leftjoin('etiquetgroupes','etiquettes.id','=','etiquetgroupes.etiquette_id')
                        ->select('etiquetgroupes.*')->get();
                foreach($etiquettegroupe as $etiq){
                 $etiquette_id= $etiq['etiquette_id'];
                $etiquettegroupe= Etiquetgroupe::find($etiq['id'])->delete();
                           }
             $etiquette= Etiquette::find($etiquette_id)->delete();
              $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
            ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
            ->whereNull('etiquetgroupes.fiche_id')
            //->where('ficheusers.user_id', '=', Auth()->user()->id)

            ->get(['etiquettes.name as Nom_etiquette',
                'etiquettes.state',
                'etiquetgroupes.etiquette_id',
                'groupes.id as idgroupe',
                'groupes.name as Name_groupe',
                'etiquetgroupes.id as etiquettegroupe',
                'etiquetgroupes.state as etiquettegroupestate',
                'etiquetgroupes.fiche_id',
                'groupes.color',
            ])->toArray();

                return response()->json([
                    'success' => true,
                    'message' => 'Supprimer avec succès',
                    'data' => GroupeController::group_byfiche('Name_groupe', $filtrefiche,$id=null),
                    'status' => Response::HTTP_OK
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
     public function delete(Request $request)
    {

        $messages = [
            'name' => 'Vérifier Votre groupe!',
            'color' => 'Vérifier Votre couleur!',

            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'max:45',
                "color" => 'max:45',


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
                $datas = $request->etiquette;


                $etiquettegroupe= Etiquetgroupe::find($datas['etiquettegroupe']);
                $etiquettegroupe= Etiquette::where('name',$datas['Nom_etiquette'])->leftjoin('etiquetgroupes','etiquettes.id','=','etiquetgroupes.etiquette_id')
                ->select('etiquetgroupes.*')->get();
        foreach($etiquettegroupe as $etiq){

       $dataG = array('state' => false, 'etiquette_id' => $etiq['etiquette_id'], 'groupe_id' => $etiq['groupe_id'],
                    'id' => $etiq['id'], 'fiche_id' => $etiq['fiche_id']);
                     $this->etiquettegroupe($dataG);
                   }
             /*  $dataG = array('state' =>$datas['status'], 'etiquette_id' => $etiquettegroupe->etiquette_id, 'groupe_id' =>$etiquettegroupe->groupe_id,
                                'id' => $datas['etiquettegroupe'], 'fiche_id' => $request->fiche_id);
                           EtiquetgroupeController::etiquettegroupe($dataG);*/
              $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
            ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
            ->whereNull('etiquetgroupes.fiche_id')
            //->where('ficheusers.user_id', '=', Auth()->user()->id)

            ->get(['etiquettes.name as Nom_etiquette',
                'etiquettes.state',
                'etiquetgroupes.etiquette_id',
                'groupes.id as idgroupe',
                'groupes.name as Name_groupe',
                'etiquetgroupes.id as etiquettegroupe',
                'etiquetgroupes.state as etiquettegroupestate',
                'etiquetgroupes.fiche_id',
                'groupes.color',
            ])->toArray();

                return response()->json([
                    'success' => true,
                    'message' => 'Groupe ajouté avec succès',
                    'data' => GroupeController::group_byfiche('Name_groupe', $filtrefiche,$id=null),
                    'status' => Response::HTTP_OK
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
    public static function corbeille($dataP){
      $tableau=  Etiquetgroupe::where('state',true)->whereNotIn('etiquette_id',$dataP)->get();
      foreach($tableau as $tab){
          $etiquettegroup= Etiquetgroupe::find($tab['id']);
          $etiquettegroup->state=false;
          $etiquettegroup->update();
      }
      return $tableau;

    }

}
