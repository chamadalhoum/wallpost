<?php

namespace App\Http\Controllers;

use App\Models\Posttag;
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
class PosttagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $posttags= Posttag::with('post:id,genre','tag:id,name');
            $s = request('search');
            if ($s) {
                $Search = $posttags->where('post_id', 'LIKE', '%' . $s . '%')->
                orWhere('tag_id', 'LIKE', '%' . $s . '%')
                    ->get();

                if ($Search->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Liste Post Tags',
                        'data' => $Search,

                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Désole, Post Tags not found.',

                        'status' => 200
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Liste Post Tags',
                    'data' => $posttags->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Post Tags not found.',

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
            'post_id.required' => 'Vérifier Votre Post!',
            'tag_id.required' => 'Vérifier Votre Tag!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];
        $validator = Validator::make($request->all(),
            [
                "post_id" => 'exists:posts,id',
                "tag_id" => 'exists:users,id',
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

                $postag= Posttag::create($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Post Tags ajouté avec succès',
                    'data' => $postag,
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
     * @param  \App\Models\Posttag  $posttag
     * @return \Illuminate\Http\Response
     */
    public function show(Posttag $posttag)
    {


        $posttags= Posttag::with('post:id,genre','tag:id,name')->find($posttag->id);
        if (!$posttag) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Post Tags not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post Tags id ' . $posttag->id,
            'data' => $posttags,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Posttag  $posttag
     * @return \Illuminate\Http\Response
     */
    public function edit(Posttag $posttag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Posttag  $posttag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Posttag $posttag)
    {

        $messages = [
            'post_id.required' => 'Vérifier Votre Post!',
            'tag_id.required' => 'Vérifier Votre Tag!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "post_id" => 'exists:posts,id',
                "tag_id" => 'exists:users,id',

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

                $posttag->post_id = $request->post_id;
                $posttag->tag_id = $request->tag_id;

                $posttag->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $posttag,

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
     * @param  \App\Models\Posttag  $posttag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Posttag $posttag)
    {


        try {

            $posttag->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Post Tags could not be deleted',
                'status' => 500,

            ], 500);
        }

    }
}
