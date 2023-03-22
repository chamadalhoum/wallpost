<?php

namespace App\Http\Controllers;

use App\Models\Acce;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;
use JWTAuth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class AcceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $acces = Acce::with('role:id,name');
            $s = request('search');
            if ($s) {
                $accessearch= $acces->where('name', 'LIKE', '%' . $s . '%')->
                orWhere('role_id', 'LIKE', '%' . $s . '%')
                    ->get();

                if ($accessearch->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => "Liste d'acces",
                        'data'=>$accessearch,
                        'token' => 'Bearer '.$token,
                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Désole, Acces not found.',
                        'token' => 'Bearer '.$token,
                        'status' => 200
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message'=>"Liste d'acces",
                    'data' => $acces->orderBy('id', 'DESC')->paginate(10),
                    'token' => 'Bearer '.$token,
                    'status' => 200
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Acces not found.',
                'token' => 'Bearer '.$token,
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
            'name.required' => 'Vérifier Votre Nom!',
            'role_id.required' => 'Vérifier Votre role!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'required|unique:roles,name|max:45',
                "role_id" => 'exists:roles,id',

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
                $data=$request->all();

                $acces= Acce::create($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Acces ajouté avec succès',
                    'data' => $acces,
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
     * @param  \App\Models\Acces  $acces
     * @return \Illuminate\Http\Response
     */
    public function show(Acce $acces)
    { dd($acces);

        if (!$acces) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Acces not found.',
                'token' => 'Bearer '.$token,
                'status' => 400
            ], 400);
        }
        return response()->json([
            'success' => true,
            'message' => 'Acces id ' . $acces->id,
            'data' => $acces,
            'token' => 'Bearer '.$token,
            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Acces  $acces
     * @return \Illuminate\Http\Response
     */
    public function edit(Acces $acces)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Acces  $acces
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Acces $acces)
    {

        $messages = [
            'name.required' => 'Vérifier Votre Nom!',
            'role_id.required' => 'Vérifier Votre role!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'required|unique:roles,name|max:45',
                "role_id" => 'exists:roles,id',

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
        if ($validator->passes()) {
            try {

                $acces->name = $request->name;
                $acces->role_id = $request->role_id;
                $acces->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $acces,
                    'token' => 'Bearer '.$token,
                    'status' => Response::HTTP_OK
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json([
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400,
                        'token' => 'Bearer '.$token,
                    ]
                );
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Acces  $acces
     * @return \Illuminate\Http\Response
     */
    public function destroy(Acces $acces)
    {

        try {
            $acces->delete();

            return response()->json([
                'success' => true,
                'token' => 'Bearer '.$token,
                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Acces could not be deleted',
                'status' => 500,
                'token' => 'Bearer '.$token,
            ], 500);
        }

    }
}
