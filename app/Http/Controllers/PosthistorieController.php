<?php

namespace App\Http\Controllers;

use App\Models\Posthistorie;
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
use Illuminate\Database\Eloquent\ModelNotFoundException;
class PosthistorieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $posthistory = Posthistorie::with('user:id,lastname,firstname', 'post:id,genre,type,name');
            $s = request('search');
            if ($s) {
                $Search = $posthistory->where('modif_type', 'LIKE', '%' . $s . '%')->
                orWhere('old_content', 'LIKE', '%' . $s . '%')->
                orWhere('new_content', 'LIKE', '%' . $s . '%')->
                orWhere('post_id','LIKE','%' . $s . '%')->
                orWhere('user_id','LIKE','%' . $s . '%')
                    ->get();

                if ($Search->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Liste historique post',
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
                    'message' => 'Liste historique post',
                    'data' => $posthistory->orderBy('id', 'DESC')->paginate(10),

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $messages = [
            'modif_type.required' => 'Vérifier Votre modif Type!',
            'old_content.required' => 'Vérifier Votre old Content!',
            'new_content.required' => 'Vérifier Votre new Content!',
            'post_id.required' => 'Vérifier Votre post!',
            'user_id.required' => 'Vérifier Votre user!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "modif_type" => 'max:45',
                "post_id" => 'exists:posts,id',
                "user_id" => 'exists:users,id',

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
                $posthistory= Posthistorie::create($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Historique Photo ajouté avec succès',
                    'data' => $posthistory,
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
     * @param  \App\Models\Posthistorie  $posthistorie
     * @return \Illuminate\Http\Response
     */
    public function show($posthistorie)
    {


        $posthistories=Posthistorie::with('user:id,lastname,firstname', 'post:id,genre,type,name')->find($posthistorie->id);
        if (!$posthistories) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Historique post not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Historique post id ' . $posthistories->id,
            'data' => $posthistories,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Posthistorie  $posthistorie
     * @return \Illuminate\Http\Response
     */
    public function edit(Posthistorie $posthistorie)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Posthistorie  $posthistorie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $posthistorie)
    {
        $posthistories=Posthistorie::find($posthistorie);

        $messages = [
            'modif_type.required' => 'Vérifier Votre modif Type!',
            'old_content.required' => 'Vérifier Votre old Content!',
            'new_content.required' => 'Vérifier Votre new Content!',
            'user_id.required' => 'Vérifier Votre user!',
            'post_id.required' => 'Vérifier Votre post!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "modif_type" => 'max:45',

                "post_id" => 'exists:posts,id',
                "user_id" => 'exists:users,id',

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

                $posthistories->modif_type = $request->modif_type;
                $posthistories->old_content = $request->old_content;
                $posthistories->new_content = $request->new_content;
                $posthistories->post_id = $request->post_id;
                $posthistories->user_id = $request->user_id;
                $posthistories->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $posthistories,

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
     * @param  \App\Models\Posthistorie  $posthistorie
     * @return \Illuminate\Http\Response
     */
    public function destroy($posthistorie)
    {

            $posthistories=Posthistorie::find($posthistorie);
            $token = JWTAuth::parseToken()->refresh();
            User::where('id', auth()->user()->id)->update(['remember_token' => $token]);

            try {
                $posthistories->delete();
                return response()->json([
                    'success' => true,

                    'message' => 'Supprimer avec succées',
                    'status' => 200,
                ]);
            } catch (TokenInvalidException $exception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Historique post could not be deleted',
                    'status' => 500,

                ], 500);
            }
        }



}
