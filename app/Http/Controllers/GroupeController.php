<?php

namespace App\Http\Controllers;

use App\Models\Etiquetgroupe;
use App\Models\Etiquette;
use App\Models\Fiche;
use App\Models\Groupe;
use App\Models\Postfichestag;
use App\Models\User;
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


class GroupeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $groupes = Groupe::query();
            $s = request('search');
            if ($s) {
                $Search = $groupes->where('name', 'LIKE', '%' . $s . '%')->
                orWhere('color', 'LIKE', '%' . $s . '%')->
                orWhere('state', 'LIKE', '%' . $s . '%')
                    ->get();

                if ($Search->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Liste des groupes',
                        'data' => $Search,

                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Désole, Groupe not found.',

                        'status' => 200
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Liste des groupes',
                    'data' => $groupes->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, groupe not found.',

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
                $datas = $request->listgroupe;
                $state=1;
                           
                $etiquettegroupe_id=null;
                foreach ($datas as $req) {
                    if (array_key_exists("id_groupe", $req)) {
                        $groupe = Groupe::find($req['id_groupe']);
                        $groupe->name = strtoupper($req["Name_groupe"]);
                        $groupe->color = $req["couleur_groupe"];
                        $groupe->update();
                        if($req['ettiquettes']){
                        foreach ($req['ettiquettes'] as $etiquette) {

                            if (array_key_exists("etiquettegroupe", $etiquette)) {
                        $etiquettegroupe= Etiquetgroupe::find($etiquette['etiquettegroupe']);

                        $etiq = Etiquette::find($etiquettegroupe->etiquette_id);
                        $etiq->name=$etiquette['Nom_etiquette'];
                        $etiq->state=$etiquette['status'];
                        $etiq->update();
                        $etiquette_id = $etiq->id;
                        $etiquettegroupe_id= $etiquette['etiquettegroupe'];

                        } else {

                                $data['name'] = $etiquette['Nom_etiquette'];
                                $data['status'] = $etiquette['status'];
                              
                                if(Etiquette::where('name',$etiquette['Nom_etiquette'])->exists()){
                               $etiqt=Etiquette::where('name',$etiquette['Nom_etiquette'])->first();
                               $etiquette_id = $etiqt->id;
                               if(Etiquetgroupe::where('etiquette_id',$etiquette_id)->where('groupe_id',$req['id_groupe'])->whereNULL('fiche_id')->exists()){
                                $etiquettegroup=Etiquetgroupe::where('etiquette_id',$etiquette_id)->where('groupe_id',$req['id_groupe'])
                                ->whereNULL('fiche_id')->first();
                               $etiquettegroupe_id= $etiquettegroup->id;
                           
                               }    else{
                                $etiquettegroupe_id= null;
                               }
                                }else{
                                    $etiq = Etiquette::create($data);
                                    $etiquette_id = $etiq->id;
                                    $etiquettegroupe_id= null;
                                }

                             
                                  $dataG = array('state' => true, 'etiquette_id' => $etiquette_id, 'groupe_id' => $req['id_groupe'],
                            'id' => $etiquettegroupe_id, 'fiche_id' => NULL);
                          
             
                            $createetiq= EtiquetgroupeController::etiquettegroupe($dataG);



                            }
                                
                          /*  $dataG = array('state' => $state, 'etiquette_id' => $etiquette_id, 'groupe_id' => $req['id_groupe'],
                                'id' => $etiquettegroupe_id, 'fiche_id' => $request->fiche_id);*/
                            $etiquettegroupe= Etiquette::where('etiquettes.id',$etiquette_id)->leftjoin('etiquetgroupes','etiquettes.id','=','etiquetgroupes.etiquette_id')
                        ->select('etiquetgroupes.*')->get();
                            foreach($etiquettegroupe as $etiq){

               $dataG = array('state' => true, 'etiquette_id' => $etiq['etiquette_id'], 'groupe_id' => $req['id_groupe'],
                            'id' => $etiq['id'], 'fiche_id' => $etiq['fiche_id']);
                          EtiquetgroupeController::etiquettegroupe($dataG);
                             
                           }
                           $groupetiq[]=[$etiquette_id];

                        }

                    }

                    } else {
                        $etiquette_id=null;
                        $data['name'] = strtoupper($req["Name_groupe"]);
                        $data['color'] = $req["couleur_groupe"];
                        $data['state'] = 1;
                       $req= Groupe::create($data);
                         $dataG = array('state' => 1, 'etiquette_id' => $etiquette_id, 'groupe_id' => $req->id,
                                'id' => "", 'fiche_id' => $request->fiche_id);
                        //EtiquetgroupeController::etiquettegroupe($dataG);
                    }

                }
                EtiquetgroupeController::corbeille($groupetiq);

              $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
            ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
            ->whereNull('etiquetgroupes.fiche_id')
                      ->orderby('etiquetgroupes.groupe_id','ASC')
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
                    'message' => 'Mise à jour traité avec succès',
                    'data' => $this->group_byfiche('Name_groupe', $filtrefiche,$id=null),
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


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Groupe $groupe
     * @return \Illuminate\Http\Response
     */
    public function show(Groupe $groupe)
    {


        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Groupe not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Groupe id ' . $groupe->id,
            'data' => $groupe,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Groupe $groupe
     * @return \Illuminate\Http\Response
     */
    public function edit(Groupe $groupe)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Groupe $groupe
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Groupe $groupe)
    {

        $messages = [
            'name.required' => 'Vérifier Votre groupe!',
            'color.required' => 'Vérifier Votre couleur!',
            'state.required' => 'Vérifier Votre etat!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'required|max:45',
                "color" => 'required|max:45',
                "state" => 'numeric|min:1|max:11',

            ], $messages
        );

        if ($validator->fails()) {

            return response()->json([
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422],
                422);
        }
        if ($validator->passes()) {
            try {
                $groupe->name = $request->name;
                $groupe->color = $request->color;
                $groupe->state = $request->state;
                $groupe->update();
           $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
            ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
            ->whereNull('etiquetgroupes.fiche_id')
                   ->orderby('etiquetgroupes.groupe_id','ASC')
            //->where('ficheusers.user_id', '=', Auth()->user()->id)

            ->get(['etiquettes.name as Nom_etiquette',
                'etiquettes.state',
                'etiquetgroupes.etiquette_id',
                'groupes.id as idgroupe',
                'groupes.name as Name_groupe',
                'etiquetgroupes.state as etiquettegroupestate',
                'etiquetgroupes.id as etiquettegroupe' ,
                'etiquetgroupes.fiche_id',
                'groupes.color',
            ])->toArray();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $this->group_byfiche('Name_groupe', $filtrefiche,$id=null),

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
     * @param \App\Models\Groupe $groupe
     * @return \Illuminate\Http\Response
     */
    public function destroy(Groupe $groupe)
    {


        try {

            $groupe->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe could not be deleted',
                'status' => 500,

            ], 500);
        }
    }
    public function deletegroupe(Request $request)
    {


        try {
          $etiquette=  Etiquetgroupe::where('groupe_id',$request->groupe['id_groupe'])
            ->whereNotNull('fiche_id')
            ->where(function ($query) {
                $query->whereNotNull('fiche_id')
                ->orWhereNotNull('etiquette_id');
            })->exists();
          
            if($etiquette){
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette groupe',
                    'status' => 400,
    
                ], 400);
            }else{
           Groupe::where('id',$request->groupe['id_groupe'])->delete();
            }
            
            return response()->json([
                'success' => true,

                'message' => 'Groupe Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe could not be deleted',
                'status' => 500,

            ], 500);
        }
    }

// Liste etiquettes par groupe


    public function groupetiquette()
{

      /*  $filtrefiche = Groupe::leftjoin('etiquetgroupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
     //  $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
            ->leftjoin('etiquettes', 'etiquetgroupes.etiquette_id', '=','etiquettes.id')
            ->whereNull('etiquetgroupes.fiche_id')
            //->where('ficheusers.user_id', '=', Auth()->user()->id)
            //->where('etiquetgroupes.state', '=', 1)
            ->orderby('groupes.id','ASC')
            ->get(['etiquettes.name as Nom_etiquette',
                'etiquettes.state',
                'etiquetgroupes.etiquette_id',
                'groupes.id as idgroupe',
                'groupes.name as Name_groupe',
                'etiquetgroupes.id as etiquettegroupe',
                'etiquetgroupes.state as etiquettegroupestate',
                'etiquetgroupes.fiche_id',
                'groupes.color',
            ])->toArray();*/
            $filtrefiche = Groupe::
                   get([
                       'groupes.id as idgroupe',
                       'groupes.name as Name_groupe',
                       'groupes.color as couleur_groupe',
                   ])->toArray();

         $filtrefichecorbeille = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
            ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
            ->whereNull('etiquetgroupes.fiche_id')

                ->where('etiquetgroupes.state', '=', false)
            ->get(['etiquettes.name as Nom_etiquette',
                'etiquettes.state',
                'etiquetgroupes.etiquette_id',
                'groupes.id as idgroupe',
                'groupes.name as Name_groupe',
                'etiquetgroupes.id as etiquettegroupe' ,
                'etiquetgroupes.state as etiquettegroupestate',
                'etiquetgroupes.fiche_id',
                'groupes.color',
            ])->toArray();



        return response()->json([
            'success' => true,
            'message' => "Liste etiquette par groupe",
            'data' => $this->group_etiquette('Name_groupe', $filtrefiche,$id=null),
            'datacorbeille'=>$this->group_corbeille('Name_groupe', $filtrefichecorbeille,$id=null),
            'status' => 200,
        ],200);
    }

    public function nombrefiche($id)
    {

        $listgroupe = Etiquetgroupe::select(DB::raw('count (*) as fiche_count '))
            ->where('etiquetgroupes.fiche_id', $id)
            ->group_by('etiquetgroupes.fiche_id')
            ->get();
        return response()->json([
            'success' => true,
            'message' => "Liste etiquette par groupe",
            'data' => $listgroupe,

            'status' => 200,
        ],200);

    }

    public static function group_byfiche($key, $id)
    {

        $result = array();
        $status=false;
        $statesgroupe=false;
        $i=0;
        $data = Groupe::leftjoin('etiquetgroupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
        //  $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
               ->leftjoin('etiquettes', 'etiquetgroupes.etiquette_id', '=', 'etiquettes.id')
               ->whereNull('etiquetgroupes.fiche_id')
               //->where('ficheusers.user_id', '=', Auth()->user()->id)
               //->where('etiquetgroupes.state', '=', 1)
               ->orderby('groupes.id', 'ASC')
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
               
        foreach ($data as $val) {
            if($id){
                $filtreficheexit = Etiquetgroupe::where('etiquetgroupes.fiche_id',$id)
                ->where('etiquetgroupes.groupe_id',$val['idgroupe'])->where('etiquetgroupes.state',1);
         $etiquet = Etiquetgroupe::where('etiquetgroupes.groupe_id',$val['idgroupe'])->where('etiquetgroupes.state',1)
   ->whereNull('etiquetgroupes.fiche_id')->count();
         $fiche=$filtreficheexit->get()->count();
         $statesgroupe=false;
        if($fiche === $etiquet ){
         $statesgroupe=True;
        }
            }else{
                $statesgroupe=false;
            }

   // var_dump($val['etiquettegroupestate']);
            if($val['etiquette_id']){
                $filtrefiche = Etiquetgroupe::join('fiches', 'fiches.id', '=', 'etiquetgroupes.fiche_id')
                ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                ->whereNotNull('etiquetgroupes.fiche_id')
                //->where('ficheusers.user_id', '=', Auth()->user()->id)
               // ->where('etiquetgroupes.state', '=', 1)
                ->where('etiquetgroupes.groupe_id', '=', $val['idgroupe'])
                ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                ->select(DB::raw('count(*) as fiche_count'
                ))
                ->groupBy('etiquetgroupes.etiquette_id')
                ->get()->toArray();
             
                if($val['etiquettegroupestate'] ===1){
            

            if (array_key_exists($key, $val)) {
                if (count($filtrefiche) > 0) {
                    foreach ($filtrefiche as $filtre) {
                        $valueetiquette = $filtre['fiche_count'];
                    }

                } else {
                    $valueetiquette = 0;
                }
               

                    if ($id) {
                      
                                       $filtreficheexit = $filtreficheexit->
                        where('groupe_id',$val['idgroupe'])->
                        where('etiquette_id',$val['etiquette_id'])->
                                get();

                        if(count($filtreficheexit)>0){
                            if($filtreficheexit[0]['state'] && $filtreficheexit[0]['fiche_id']==$id){
                            $status = true;
                            }else{
                                $status = false;
                            }

                            $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                                'Value_etiquette' => $valueetiquette, 'status' => $status,'etiquettegroupe'=>$filtreficheexit[0]['id']);
                        }else{
                            $status = false;
                        $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                                'Value_etiquette' => $valueetiquette,
                            'status' => $status,
                            'etiquettegroupe'=>$val['etiquettegroupe']);
                    }
                    }
                    else{
                        $status = false;
                    $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                                'Value_etiquette' => $valueetiquette,
                        'status' => $status,
                        'etiquettegroupe'=>$val['etiquettegroupe']);
                }





                $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],

                    'ettiquettes' => $ettiquettes,
                    'statesgroupe'=>$statesgroupe
                );
                } else {
                $result[""][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],

                    'ettiquettes' => $ettiquettes,
                        'statesgroupe'=>$statesgroupe
                );
            }
        }
        else {
            $ettiquettes[$val[$key]] = array();

             $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],

                    'ettiquettes' =>$ettiquettes,
                        'statesgroupe'=>false
                );
        }

        } else {
            
            $ettiquettes[$val[$key]] = array();

             $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],

                    'ettiquettes' =>$ettiquettes,
                        'statesgroupe'=>false
                );
        }

        }

        $tab=array();
        foreach ($result as $key => $value) {

            $tab[] = array("id_groupe" => $value[0]['id_groupe'],
                "Name_groupe" => $value[0]['Name_groupe'],
                "couleur_groupe" => $value[0]['couleur_groupe'], 'ettiquettes' => $ettiquettes[$key],
                'etatActivat'=>$value[0]['statesgroupe']);
        }
        
        return $tab;
    }
   public static function group_post($key,$id,$post_id)
    {

        $result = array();
        $ettiquettes=array();
        $data = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
        ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
        ->whereNull('etiquetgroupes.fiche_id')
       ->orderby('etiquetgroupes.groupe_id', 'ASC')
        ->get(['etiquettes.name as Nom_etiquette',
            'etiquettes.state',
            'etiquetgroupes.state as etat',
            'etiquetgroupes.etiquette_id',
            'etiquetgroupes.id as etiquettegroupe',
            'etiquetgroupes.state as etiquettegroupestate',
            'groupes.id as idgroupe',
            'groupes.name as Name_groupe',
            'etiquetgroupes.fiche_id',
            'groupes.color',
        ])->toArray();
     
        foreach ($data as $val) {
          if ($val['etiquette_id']) {
      
           /* $filtrefiche = Etiquetgroupe::join('ficheusers', 'ficheusers.fiche_id', '=', 'etiquetgroupes.fiche_id')
                ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                ->whereNotNull('etiquetgroupes.fiche_id')
                ->where('ficheusers.user_id', '=', Auth()->user()->id)
                ->where('etiquetgroupes.groupe_id', '=', $val['idgroupe'])
                ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                ->select(DB::raw('count(*) as fiche_count'
                ))
                ->groupBy('etiquetgroupes.etiquette_id')
                ->get()->toArray();*/
                $filtrefiche =  Etiquetgroupe::join('fiches', 'fiches.id', '=', 'etiquetgroupes.fiche_id')
                ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                ->whereNotNull('etiquetgroupes.fiche_id')
                //->where('ficheusers.user_id', '=', Auth()->user()->id)
               // ->where('etiquetgroupes.state', '=', 1)
                ->where('etiquetgroupes.groupe_id', '=', $val['idgroupe'])
                ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                ->select(DB::raw('count(*) as fiche_count'
                ))
                ->groupBy('etiquetgroupes.etiquette_id')
                ->get()->toArray();
                if($val['etiquettegroupestate'] ===1){
            if (array_key_exists($key, $val)) {
                if (count($filtrefiche) > 0) {
                    foreach ($filtrefiche as $filtre) {
                        $valueetiquette = $filtre['fiche_count'];
                    }

                } else {
                    $valueetiquette = 0;
                }
                $status=false;
                $statesgroupe=false;
//$ettiquettes[$val[$key]]=array();
                    if ($id) {
                        $filtreficheexit = Etiquetgroupe::whereIN('etiquetgroupes.fiche_id',$id)
                                ->where('groupe_id',$val['idgroupe'])->where('state',1);
                               /* Postfichestag::where('post_id',$post_id)
                          ->where('etiquettes_id',$val['etiquette_id'])*/
                         $etiquet =Etiquetgroupe::whereIN('etiquetgroupes.fiche_id',$id)
                                ->where('groupe_id',$val['idgroupe'])->count();
                                
                         $fiche=$filtreficheexit->get()->count();
                         $posttagss=Postfichestag::leftjoin('etiquetgroupes','etiquetgroupes.etiquette_id','=','postfichestags.etiquettes_id')
                          ->where('postfichestags.post_id',$post_id)
                          ->where('postfichestags.etiquettes_id',$val['etiquette_id'])
                          ->where('etiquetgroupes.groupe_id',$val['idgroupe'])->count();

                        if($fiche=== $posttagss ){
                         $statesgroupe=true;
                        }
                        $filtreficheexit = $filtreficheexit->
                        where('groupe_id',$val['idgroupe'])->
                        where('etiquette_id',$val['etiquette_id'])->
                                get();
                                $ettiquettes[$val[$key]][]=array();
                        if(count($filtreficheexit)>0){
                            
                    /*    $postfichetags=Postfichestag::where('post_id',$post_id)
                      ->where('etiquettes_id',$val['etiquette_id']);
                       */  if($filtreficheexit[0]['state']){
                          if(Postfichestag::where('post_id',$post_id)
                          ->where('etiquettes_id',$val['etiquette_id'])->exists()){
      
                         $status = true;
                         }
                          }

                             $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                                'Value_etiquette' => $valueetiquette, 'status' => $status,'etiquettegroupe'=>$filtreficheexit[0]['id']);
                        }
                     else {
                         //   $ettiquettes[$val[$key]][] =[];
                            $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                            'Value_etiquette' => $valueetiquette, 'status' => false,'etiquettegroupe'=>$val['etiquettegroupe']);
                   
                        }
                   
                } else {
                    $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                        'Value_etiquette' => $valueetiquette, 'status' => $status, 'etiquettegroupe' => $val['etiquettegroupe']);
                }






                $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],

                    'ettiquettes' => $ettiquettes,
                    'statesgroupe'=>$statesgroupe
                );
            }
         } else {
            $ettiquettes[$val[$key]] = array();

            $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                   "Name_groupe" => $val['Name_groupe'],
                   "couleur_groupe" => $val['color'],

                   'ettiquettes' =>$ettiquettes,
                       'statesgroupe'=>false
               );
            }
        }else {
            $ettiquettes[$val[$key]] = array();

             $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],

                    'ettiquettes' =>$ettiquettes,
                        'statesgroupe'=>$statesgroupe
                );
        }
        }
        $tab=array();
   /*    foreach ($result as $key => $value) {

            $tab[] = array("id_groupe" => $value[0]['id_groupe'],
                "Name_groupe" => $value[0]['Name_groupe'],
                "couleur_groupe" => $value[0]['couleur_groupe'], 'ettiquettes' => $ettiquettes[$key],
                'etatActivat'=>$value[0]['statesgroupe']);
        }*/
     foreach ($result as $key => $value) {
      
            $collectiont = collect($ettiquettes[$key]);
$filtered = $collectiont->filter(function ($value, $key) {
    return $value != null;
});

            $tab[] = array("id_groupe" => $value[0]['id_groupe'],
                "Name_groupe" => $value[0]['Name_groupe'],'ettiquettes' =>$filtered->values()->all(),
                "couleur_groupe" => $value[0]['couleur_groupe'], 
                'etatActivat'=>$value[0]['statesgroupe']);
        }
        return $tab;
    }
    public static function group_etiq($key,$id,$post_id)
    {

        $result = array();
        $ettiquettes=array();
        $data = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
        ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
        ->whereNull('etiquetgroupes.fiche_id')
       ->orderby('etiquetgroupes.groupe_id', 'ASC')
        ->get(['etiquettes.name as Nom_etiquette',
            'etiquettes.state',
            'etiquetgroupes.state as etat',
            'etiquetgroupes.etiquette_id',
            'etiquetgroupes.id as etiquettegroupe',
            'etiquetgroupes.state as etiquettegroupestate',
            'groupes.id as idgroupe',
            'groupes.name as Name_groupe',
            'etiquetgroupes.fiche_id',
            'groupes.color',
        ])->toArray();
     
        foreach ($data as $val) {
          if ($val['etiquette_id']) {
      
            $filtrefiche =/* Etiquetgroupe::join('ficheusers', 'ficheusers.fiche_id', '=', 'etiquetgroupes.fiche_id')
                ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                ->whereNotNull('etiquetgroupes.fiche_id')
                ->where('ficheusers.user_id', '=', Auth()->user()->id)
                ->where('etiquetgroupes.groupe_id', '=', $val['idgroupe'])
                ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                ->select(DB::raw('count(*) as fiche_count'
                ))
                ->groupBy('etiquetgroupes.etiquette_id')
                ->get()->toArray();*/
                Etiquetgroupe::join('fiches', 'fiches.id', '=', 'etiquetgroupes.fiche_id')
                ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                ->whereNotNull('etiquetgroupes.fiche_id')
                //->where('ficheusers.user_id', '=', Auth()->user()->id)
               // ->where('etiquetgroupes.state', '=', 1)
                ->where('etiquetgroupes.groupe_id', '=', $val['idgroupe'])
                ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                ->select(DB::raw('count(*) as fiche_count'
                ))
                ->groupBy('etiquetgroupes.etiquette_id')
                ->get()->toArray();
                if($val['etiquettegroupestate'] ===1){
            if (array_key_exists($key, $val)) {
                if (count($filtrefiche) > 0) {
                    foreach ($filtrefiche as $filtre) {
                        $valueetiquette = $filtre['fiche_count'];
                    }

                } else {
                    $valueetiquette = 0;
                }
                $status=false;
                $statesgroupe=false;
//$ettiquettes[$val[$key]]=array();
                    if ($id) {
                        $filtreficheexit = Etiquetgroupe::whereIN('etiquetgroupes.fiche_id',$id)
                                ->where('groupe_id',$val['idgroupe'])->where('state',1);
                               /* Postfichestag::where('post_id',$post_id)
                          ->where('etiquettes_id',$val['etiquette_id'])*/
                         $etiquet =Etiquetgroupe::whereIN('etiquetgroupes.fiche_id',$id)
                                ->where('groupe_id',$val['idgroupe'])->count();
                                
                         $fiche=$filtreficheexit->get()->count();
              $posttagss=Postfichestag::leftjoin('etiquetgroupes','etiquetgroupes.etiquette_id','=','postfichestags.etiquettes_id')
 ->where('postfichestags.post_id',$post_id)
                          ->where('postfichestags.etiquettes_id',$val['etiquette_id'])
                          ->where('etiquetgroupes.groupe_id',$val['idgroupe'])->count();

                        if($fiche=== $posttagss ){
                         $statesgroupe=true;
                        }
                        $filtreficheexit = $filtreficheexit->
                        where('groupe_id',$val['idgroupe'])->
                        where('etiquette_id',$val['etiquette_id'])->
                                get();

                        if(count($filtreficheexit)>0){
                            
                    /*    $postfichetags=Postfichestag::where('post_id',$post_id)
                      ->where('etiquettes_id',$val['etiquette_id']);
                       */  if($filtreficheexit[0]['state']){
                          if(Postfichestag::where('post_id',$post_id)
                          ->where('etiquettes_id',$val['etiquette_id'])->exists()){
      
                         $status = true;
                         }
                          }

                             $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                                'Value_etiquette' => $valueetiquette, 'status' => $status,'etiquettegroupe'=>$filtreficheexit[0]['id']);
                        }
                      else {
                            $ettiquettes[$val[$key]][] =[];
                        }
                   
                } else {
                    $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                        'Value_etiquette' => $valueetiquette, 'status' => $status, 'etiquettegroupe' => $val['etiquettegroupe']);
                }

                $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],

                    'ettiquettes' => $ettiquettes,
                    'statesgroupe'=>$statesgroupe
                );
            }
         } else {
            $ettiquettes[$val[$key]] = array();

            $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                   "Name_groupe" => $val['Name_groupe'],
                   "couleur_groupe" => $val['color'],

                   'ettiquettes' =>$ettiquettes,
                       'statesgroupe'=>false
               );
            }
        }else {
            $ettiquettes[$val[$key]] = array();

             $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],

                    'ettiquettes' =>$ettiquettes,
                        'statesgroupe'=>$statesgroupe
                );
        }
        }
        $tab=array();
       
        foreach ($result as $key => $value) {
      
            $collectiont = collect($ettiquettes[$key]);
$filtered = $collectiont->filter(function ($value, $key) {
    return $value != null;
});
            $tab[] = array("id_groupe" => $value[0]['id_groupe'],
                "Name_groupe" => $value[0]['Name_groupe'],'ettiquettes' =>$filtered->all(),
                "couleur_groupe" => $value[0]['couleur_groupe'], 
                'etatActivat'=>$value[0]['statesgroupe']);
        }
        return $tab;
    }
     public static function group_by($key,$id)
    {

        $result = array();
        $data = \App\Models\Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
        ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
        ->whereNull('etiquetgroupes.fiche_id')
->orderby('etiquetgroupes.groupe_id', 'ASC')
        //->where('ficheusers.user_id', '=', Auth()->user()->id)
        ->get(['etiquettes.name as Nom_etiquette',
            'etiquettes.state',
            'etiquetgroupes.state as etat',
            'etiquetgroupes.etiquette_id',
            'etiquetgroupes.id as etiquettegroupe',
            'etiquetgroupes.state as etiquettegroupestate',
            'groupes.id as idgroupe',
            'groupes.name as Name_groupe',
            'etiquetgroupes.fiche_id',
            'groupes.color',
        ])->toArray();

        foreach ($data as $val) {
            $filtrefiche = /*Etiquetgroupe::join('ficheusers', 'ficheusers.fiche_id', '=', 'etiquetgroupes.fiche_id')
                ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                ->whereNotNull('etiquetgroupes.fiche_id')
                ->where('ficheusers.user_id', '=', Auth()->user()->id)
                ->where('etiquetgroupes.groupe_id', '=', $val['idgroupe'])
                ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                ->select(DB::raw('count(*) as fiche_count'
                ))
                ->groupBy('etiquetgroupes.etiquette_id')
                ->get()->toArray();*/
                Etiquetgroupe::join('fiches', 'fiches.id', '=', 'etiquetgroupes.fiche_id')
                ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                ->whereNotNull('etiquetgroupes.fiche_id')
                //->where('ficheusers.user_id', '=', Auth()->user()->id)
               // ->where('etiquetgroupes.state', '=', 1)
                ->where('etiquetgroupes.groupe_id', '=', $val['idgroupe'])
                ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                ->select(DB::raw('count(*) as fiche_count'
                ))
                ->groupBy('etiquetgroupes.etiquette_id')
                ->get()->toArray();

            if (array_key_exists($key, $val)) {
                if (count($filtrefiche) > 0) {
                    foreach ($filtrefiche as $filtre) {
                        $valueetiquette = $filtre['fiche_count'];
                    }


                $status=false;
                $statesgroupe=false;

                    if ($id) {
                        $filtreficheexit = Etiquetgroupe::where('etiquetgroupes.fiche_id',$id)
                                ->where('groupe_id',$val['idgroupe'])->where('state',1);
                         $etiquet = Etiquetgroupe::where('groupe_id',$val['idgroupe'])
                   ->whereNull('etiquetgroupes.fiche_id')->count();
                         $fiche=$filtreficheexit->get()->count();

                        if($fiche=== $etiquet ){
                         $statesgroupe=True;
                        }
                                       $filtreficheexit = $filtreficheexit->
                        where('groupe_id',$val['idgroupe'])->
                        where('etiquette_id',$val['etiquette_id'])->
                                get();

                        if(count($filtreficheexit)>0){
                            if($filtreficheexit[0]['state']){
                            $status = true;
                            }

                            $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                                'Value_etiquette' => $valueetiquette, 'status' => $status,'etiquettegroupe'=>$filtreficheexit[0]['id']);
                        } else {
                        $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                            'Value_etiquette' => $valueetiquette, 'status' => $status, 'etiquettegroupe' => $val['etiquettegroupe']);
                    }
                } else {
                    $ettiquettes[$val[$key]][] = array('Nom_etiquette' => $val['Nom_etiquette'],
                        'Value_etiquette' => $valueetiquette, 'status' => $status, 'etiquettegroupe' => $val['etiquettegroupe']);
                }

                $result[$val[$key]][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],
                    'ettiquettes' => $ettiquettes,
                    'statesgroupe'=>$statesgroupe
                );

                }
                } else {
                $result[""][] = array("id_groupe" => $val['idgroupe'],
                    "Name_groupe" => $val['Name_groupe'],
                    "couleur_groupe" => $val['color'],

                    'ettiquettes' => $ettiquettes,
                        'statesgroupe'=>$statesgroupe
                );
            }
        }
        $tab=array();
        foreach ($result as $key => $value) {
            $tab[] = array("id_groupe" => $value[0]['id_groupe'],
                "Name_groupe" => $value[0]['Name_groupe'],
                "couleur_groupe" => $value[0]['couleur_groupe'], 'ettiquettes' => $ettiquettes[$key],
                'etatActivat'=>$value[0]['statesgroupe']);
        }
        return $tab;
    }

    public static function group_corbeille($key, $data,$id)
    {

        $result = array();
$ettiquettes=array();
        foreach ($data as $val) {

            if($val['etiquette_id']){
            $filtrefiche =/* Etiquetgroupe::join('ficheusers', 'ficheusers.fiche_id', '=', 'etiquetgroupes.fiche_id')
                ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                ->whereNotNull('etiquetgroupes.fiche_id')
                    ->where('etiquetgroupes.state',false)
                ->where('ficheusers.user_id', '=', Auth()->user()->id)
                ->where('etiquetgroupes.groupe_id', '=', $val['idgroupe'])
                ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                ->select(DB::raw('count(*) as fiche_count'
                ))
                ->groupBy('etiquetgroupes.etiquette_id')
                ->get()->toArray();*/
                Etiquetgroupe::join('fiches', 'fiches.id', '=', 'etiquetgroupes.fiche_id')
                ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                ->whereNotNull('etiquetgroupes.fiche_id')
                //->where('ficheusers.user_id', '=', Auth()->user()->id)
               // ->where('etiquetgroupes.state', '=', 1)
                ->where('etiquetgroupes.groupe_id', '=', $val['idgroupe'])
                ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                ->select(DB::raw('count(*) as fiche_count'
                ))
                ->groupBy('etiquetgroupes.etiquette_id')
                ->get()->toArray();

            if (array_key_exists($key, $val)) {
                if (count($filtrefiche) > 0) {
                    foreach ($filtrefiche as $filtre) {
                        $valueetiquette = $filtre['fiche_count'];
                    }

                } else {
                    $valueetiquette = 0;
                }
                $status=false;
                $statesgroupe=false;

                    if ($id) {
                        $filtreficheexit = Etiquetgroupe::where('etiquetgroupes.fiche_id',$id)
                                ->where('groupe_id',$val['idgroupe'])->where('state',1);
                         $etiquet = Etiquetgroupe::where('groupe_id',$val['idgroupe'])
                   ->whereNull('etiquetgroupes.fiche_id')->count();
                         $fiche=$filtreficheexit->get()->count();

                        if($fiche=== $etiquet ){
                         $statesgroupe=True;
                        }
                                       $filtreficheexit = $filtreficheexit->
                        where('groupe_id',$val['idgroupe'])->
                        where('etiquette_id',$val['etiquette_id'])->
                                get();

                        if(count($filtreficheexit)>0){
                            if($filtreficheexit[0]['state']){
                            $status = true;
                            }

                            $ettiquettes[] = array('Nom_etiquette' => $val['Nom_etiquette'],
                                'Value_etiquette' => $valueetiquette, 'status' => $status,
                                'etiquettegroupe'=>$filtreficheexit[0]['id']);
                        }else{
                        $ettiquettes[] = array('Nom_etiquette' => $val['Nom_etiquette'],
                                'Value_etiquette' => $valueetiquette,
                            'status' => $status,
                            'etiquettegroupe'=>$val['etiquettegroupe']);
                    }
                    }
                    else{
                    $ettiquettes[] = array('Nom_etiquette' => $val['Nom_etiquette'],
                                'Value_etiquette' => $valueetiquette,
                        'status' => $status,
                        'etiquettegroupe'=>$val['etiquettegroupe']);
                }






                }
        }
        }

        $tab=array();
        foreach ($result as $key => $value) {

            $tab[]=  ["ettiquettes"=>$ettiquettes[$key]];
        }

        return ["ettiquettes"=>$ettiquettes];
    }
    public static function group_etiquette($key, $data,$id)
    {

      foreach($data as $val){

       
               $tab[] = array("id_groupe" => $val['idgroupe'],
                "Name_groupe" => $val['Name_groupe'],
                "couleur_groupe" =>$val['couleur_groupe'], 
                'ettiquettes' =>GroupeController::etiquettenumbre($val['idgroupe']),
                'etatActivat'=>false);
            
    }
    return $tab;

}
public static function etiquettenumbre($idgroupe){
    $filtrefiche= Groupe::leftjoin('etiquetgroupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
    //  $filtrefiche = Etiquetgroupe::leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
           ->leftjoin('etiquettes', 'etiquetgroupes.etiquette_id', '=','etiquettes.id')
           ->whereNull('etiquetgroupes.fiche_id')
          ->where('groupes.id', '=', $idgroupe)
           ->where('etiquetgroupes.state', '=', 1)
           ->orderby('groupes.id','ASC')
           ->get(['etiquettes.name as Nom_etiquette',
               'etiquettes.state',
               'etiquetgroupes.etiquette_id',
              
               'etiquetgroupes.id as etiquettegroupe',
               'etiquetgroupes.state as etatActivat',
               'etiquetgroupes.fiche_id',
           
           ])->toarray();
           $ettiquettest=array();
           foreach ($filtrefiche as $val){
                $valueetiquette=0;
                $filtreficheS = /*Etiquetgroupe::join('ficheusers', 'ficheusers.fiche_id', '=', 'etiquetgroupes.fiche_id')
                    ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                    ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                    ->whereNotNull('etiquetgroupes.fiche_id')
                   //->where('etiquetgroupes.fiche_id','!=',NULL)
                    //->where('ficheusers.user_id', '=', Auth()->user()->id)
                    ->where('etiquetgroupes.groupe_id', '=', $idgroupe)
                    ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                    ->select(DB::raw('count(*) as fiche_counts'
                    ))
                    ->groupBy('etiquetgroupes.etiquette_id')
                    ->get();*/
    
                 Etiquetgroupe::join('fiches', 'fiches.id', '=', 'etiquetgroupes.fiche_id')
                    ->leftjoin('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
                    ->leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                    ->whereNotNull('etiquetgroupes.fiche_id')
                    //->where('ficheusers.user_id', '=', Auth()->user()->id)
                   // ->where('etiquetgroupes.state', '=', 1)
                    ->where('etiquetgroupes.groupe_id', '=', $idgroupe)
                    ->where('etiquetgroupes.etiquette_id', '=', $val['etiquette_id'])
                    ->select(DB::raw('count(*) as fiche_counts'
                    ))
                    ->groupBy('etiquetgroupes.etiquette_id')
                    ->get();



                
                    if (count($filtreficheS) > 0) {
                        foreach ($filtreficheS as $filtre) {
                           $valueetiquette = $filtre->fiche_counts;
                  //   $valueetiquette=$filtreficheS->count();
                        }
    
                    } else {
                        $valueetiquette = 0;
                    }
         
    $ettiquettest[] = array('Nom_etiquette' => $val['Nom_etiquette'],
    'Value_etiquette' => $valueetiquette, 'status' => false,
    'etiquettegroupe'=>$val['etiquettegroupe']);
   
}
  return $ettiquettest;
}
}