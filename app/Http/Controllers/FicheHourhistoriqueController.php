<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\FicheHourhistorique;
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

class FicheHourhistoriqueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $hourshistory = FicheHourhistorique::with('user:id,lastname,firstname', 'fichehours:id,type');
            $s = request('search');
            if ($s) {
                $Search = $hourshistory->where('modif_type', 'LIKE', '%' . $s . '%')->
                orWhere('old_content', 'LIKE', '%' . $s . '%')->
                orWhere('new_content', 'LIKE', '%' . $s . '%')->
                orWhere('fichehours_id', 'LIKE', '%' . $s . '%')->
                orWhere('user_id', 'LIKE', '%' . $s . '%')
                    ->get();

                if ($Search->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Liste historique horaire',
                        'data' => $Search,

                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Désole, Historique post not found.',

                        'status' => 200
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Liste historique horaire',
                    'data' => $hourshistory->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, histories post not found.',

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
            'modif_type.required' => 'Vérifier Votre modif Type!',
            'old_content.required' => 'Vérifier Votre old Content!',
            'new_content.required' => 'Vérifier Votre new Content!',
            'fichehours_id.required' => 'Vérifier Votre post!',
            'user_id.required' => 'Vérifier Votre user!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "modif_type" => 'max:45',
                "fichehours_id" => 'exists:fichehours,id',
                "user_id" => 'exists:users,id',

            ], $messages
        );
        if ($validator->fails()) {
            return response()->json([
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422,
                'token' => 'Bearer '.$token],
                422);
        }
        if ($validator->passes()) {
            try {
                $data = $request->all();
                $hourshistory = FicheHourhistorique::create($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Historique Photo ajouté avec succès',
                    'data' => $hourshistory,
                    'status' => Response::HTTP_OK,
                    'token' => 'Bearer '.$token
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400,
                        'token' => 'Bearer '.$token
                    ],
                    400
                );
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\FicheHourhistorique $ficheHourhistorique
     * @return \Illuminate\Http\Response
     */
    public function show($ficheHourhistorique)
    {

        $ficheHourhistorique = FicheHourhistorique::with('user:id,lastname,firstname', 'fichehours:id,type')->find($ficheHourhistorique);
        if (!$ficheHourhistorique) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Historique post not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Historique Horaire id ' . $ficheHourhistorique->id,
            'data' => $ficheHourhistorique,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\FicheHourhistorique $ficheHourhistorique
     * @return \Illuminate\Http\Response
     */
    public function edit(FicheHourhistorique $ficheHourhistorique)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\FicheHourhistorique $ficheHourhistorique
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $Hourhistorique)
    {

        $ficheHourhistorique = FicheHourhistorique::find($Hourhistorique->id);
        $messages = [
            'modif_type.required' => 'Vérifier Votre modif Type!',
            'old_content.required' => 'Vérifier Votre old Content!',
            'new_content.required' => 'Vérifier Votre new Content!',
            'user_id.required' => 'Vérifier Votre user!',
            'fichehours_id.required' => 'Vérifier Votre post!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "modif_type" => 'max:45',
                "fichehours_id" => 'exists:posts,id',
                "user_id" => 'exists:users,id',

            ], $messages
        );
        if ($validator->fails()) {

            return response()->json([
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422,
                'token' => 'Bearer '.$token],
                422);
        }
        if ($validator->passes()) {
            try {

                $ficheHourhistorique->modif_type = $request->modif_type;
                $ficheHourhistorique->old_content = $request->old_content;
                $ficheHourhistorique->new_content = $request->new_content;
                $ficheHourhistorique->fichehours_id = $request->fichehours_id;
                $ficheHourhistorique->user_id = $request->user_id;
                $ficheHourhistorique->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $ficheHourhistorique,

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
     * @param \App\Models\FicheHourhistorique $ficheHourhistorique
     * @return \Illuminate\Http\Response
     */
    public function destroy($hourhistorique)
    {

        $ficheHourhistorique = FicheHourhistorique::find($hourhistorique);
        try {
            $ficheHourhistorique->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Historique horaire could not be deleted',
                'status' => 500,

            ], 500);
        }
    }
    public function fiche($id){

        try {
            $totalfiche = Fiche::leftJoin('fichehours', 'fichehours.fiche_id', '=', 'fiches.id')
                ->leftJoin('fiche_hourhistoriques', 'fiche_hourhistoriques.user_id', '=', 'fichehours.id')
                ->where('fiche_hourhistoriques.state', 'inactif')
                ->orwhere('fichehours.fiche_id', $id)
                ->orwhere('fiche_hourhistoriques.user_id', auth()->user()->id)
                ->get(['fiches.locationName','fiches.description',
                    'fiches.name','fiche_hourhistoriques.modif_type','fiche_hourhistoriques.id',
                    'fiche_hourhistoriques.old_content','fiche_hourhistoriques.new_content',
                    'fichehours.*']);
            return response()->json([
                'success' => false,
                'message' => 'Galérie photo',
                'data' => $totalfiche,
                'status' => 500,

            ], 500);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Historique photo could not be deleted',
                'status' => 500,

            ], 500);
        }
    }
}

