<?php

namespace App\Http\Controllers;

use App\Models\Role;
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

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $roles = Role::query();
            $u = request('search');
            if ($u) {
                $roles->where('name', 'LIKE', '%' . $u . '%')
                ->get();

                if($roles->count()>0){
                    return response()->json([
                        'success' => true,
                        'message' =>  $roles->where('name', 'LIKE', '%' . $u . '%')
                            ->get(),

                        'status' => 200
                    ], 200);
                }else{ return response()->json([
                    'success' => true,
                    'message' =>'Désole, role not found.',

                    'status' => 200
                ], 200);}
            }else{
                return response()->json([
                    'success' => true,
                    'message' => $roles->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        }
        catch(Exception $ex){
            return response()->json([
                'success' => false,
                'message' => 'Désole, role not found.',

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
            'name.required' => 'Vérifier Votre role!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "name" => 'required|unique:roles,name|max:45',

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

                $role= Role::create($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Role ajouté avec succès',
                    'data' => $role,
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
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, role not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role id ' . $role->id,
            'data' => $role,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {

        $messages = [
            'name.required' => 'Vérifier Votre role!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];
        $validator = Validator::make($request->all(),
            [
                "name" => 'required|string|unique:roles,name,' . $role->id,
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

                $role->name = $request->name;
                $role->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $role,

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
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {



            try {

            $role->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Role could not be deleted',
                'status' => 500,

            ], 500);
        }


    }
}
