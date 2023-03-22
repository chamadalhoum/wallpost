<?php

namespace App\Http\Controllers;

use App\Models\Etiquette;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use mysql_xdevapi\Exception;
use JWTAuth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;


class EtiquetteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $etiquettes = Etiquette::query();
            $s = request('search');
            if ($s) {
              $etiquette=  $etiquettes->where('name', 'LIKE', '%' . $s . '%')->
                orWhere('state', 'LIKE', '%' . $s . '%')
                    ->get();

                if($etiquettes->count()>0){
                    return response()->json([
                        'success' => true,
                        'message' =>  "Liste etiquette",
                        'data' =>  $etiquette,

                        'status' => 200
                    ], 200);
                }else{ return response()->json([
                    'success' => true,
                    'message' =>'Désole, etiquettes not found.',

                    'status' => 200
                ], 200);}
            }else{
                return response()->json([
                    'success' => true,
                    'message' =>  "Liste etiquette",
                    'data' => $etiquettes->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        }
        catch(Exception $ex){
            return response()->json([
                'success' => false,
                'message' => 'Désole, Etiquette not found.',

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
            'name.required' => 'Vérifier Votre nom!',
            'state.required' => 'Vérifier Votre etat!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'required|max:45',
                "state" => 'numeric|min:1|max:20',

            ], $messages
        );
        if ($validator->fails()) {
            return response()->json([
                'succes'=>false,
                'message'=>$validator->errors()->toArray(),
                'status'=>422],
                422);
        }
        if($validator->passes()){
            try {
                $data=$request->all();

                $etiquette= Etiquette::create($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Etiquette ajouté avec succès',
                    'data' => $etiquette,
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
     * @param  \App\Models\Etiquette  $etiquettes
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {


        $etiquettes=Etiquette::find($id);
        if (!$etiquettes) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Etiquette not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'etiquette id ' . $etiquettes->id,
            'data' => $etiquettes,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Etiquette  $etiquettes
     * @return \Illuminate\Http\Response
     */
    public function edit(Etiquette $etiquettes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Etiquette  $etiquettes
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {

        $etiquettes=Etiquette::find($id);
        $messages = [
            'name.required' => 'Vérifier Votre nom!',
            'state.required' => 'Vérifier Votre etat!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'required|max:45',
                "state" => 'numeric|min:0|max:20',

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

                $etiquettes->name = $request->name;
                $etiquettes->state = $request->state;
                $etiquettes->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $etiquettes,

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
     * @param  \App\Models\Etiquette  $etiquettes
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $etiquettes=Etiquette::find($id);
        try {

            $etiquettes->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Etiquette could not be deleted',
                'status' => 500,

            ], 500);
        }

    }
}
