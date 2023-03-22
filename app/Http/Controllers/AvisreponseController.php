<?php

namespace App\Http\Controllers;

use App\Models\Avisreponse;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
class AvisreponseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $avisreponse = Avisreponse::with('user:id,lastName,firstName','avis:id,code,title','fiches:id,locationName,name');
            $s = request('search');
            if ($s) {
                $avisearch= $avisreponse->where('contents', 'LIKE', '%' . $s . '%')->
                orWhere('avis_id', 'LIKE', '%' . $s . '%')->

                orWhere('user_id', 'LIKE', '%' . $s . '%')->

                orWhere('fiche_id', 'LIKE', '%' . $s . '%')->get();

                if ($avisearch->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => $avisearch,

                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Désole, Avis reponse not found.',

                        'status' => 200
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => $avisreponse->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Avis reponse not found.',

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
            'contents.required' => 'Vérifier Votre content!',
            'avis_id.required' => 'Vérifier Votre Avis!',
            'user_id.required' => 'Vérifier Votre user!',
            'fiche_id.required' => 'Vérifier Votre fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];
        $validator = Validator::make($request->all(),
            [
                "contents" => 'required|max:45',
                "avis_id" => 'exists:avis,id',
                "user_id" => 'exists:users,id',
                "fiche_id" => 'exists:fiches,id',

            ], $messages
        );
        if ($validator->fails()) {
            return response()->json([
                'succes'=>false,
                'message'=>$validator->errors()->toArray(),
                'status'=>422,
                'token'=>$token],
                422);
        }
        if($validator->passes()){
            try {
                $data=$request->all();
                $avis= Avisreponse::create($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Avis ajouté avec succès',
                    'data' => $avis,
                    'status' => Response::HTTP_OK,
                    'token'=>$token
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json(
                    [
                        'success' => false,
                        'message' =>  $ex->getMessage(),
                        'status' => 400,
                        'token'=>$token
                    ],
                    400
                );
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Avisreponse  $avisreponse
     * @return \Illuminate\Http\Response
     */
    public function show(Avisreponse $avisreponse)
    {


        $avisreponses= Avisreponse::with('user:id,lastName,firstName','avis:id,code,title','fiches:id,locationName,name')->find($avisreponse->id);
        if (!$avisreponses) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Avis reponse not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avis reponse id ' . $avisreponse->id,
            'data' => $avisreponses,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Avisreponse  $avisreponse
     * @return \Illuminate\Http\Response
     */
    public function edit(Avisreponse $avisreponse)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Avisreponse  $avisreponse
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Avisreponse $avisreponse)
    {


        $messages = [
            'contents.required' => 'Vérifier Votre content!',
            'avis_id.required' => 'Vérifier Votre Avis!',
            'user_id.required' => 'Vérifier Votre user!',
            'fiche_id.required' => 'Vérifier Votre fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];
        $validator = Validator::make($request->all(),
            [
                "contents" => 'required|max:45',
                "avis_id" => 'exists:avis,id',
                "user_id" => 'exists:users,id',
                "fiche_id" => 'exists:fiches,id',

            ], $messages
        );

        if ($validator->fails()) {

            return response()->json([
                'succes'=>false,
                'message'=>$validator->errors()->toArray(),
                'status'=>422,
                'token'=>$token],
                422);
        }
        if ($validator->passes()) {
            try {

                $avisreponse->contents = $request->contents;
                $avisreponse->avis_id = $request->avis_id;
                $avisreponse->user_id = $request->user_id;
                $avisreponse->fiche_id = $request->fiche_id;
                $avisreponse->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $avisreponse,

                    'status' => Response::HTTP_OK
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json([
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400,

                    ]
                );
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Avisreponse  $avisreponse
     * @return \Illuminate\Http\Response
     */
    public function destroy(Avisreponse $avisreponse)
    {


        try {

            $avisreponse->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Avis reponse could not be deleted',
                'status' => 500,

            ], 500);
        }

    }
}
