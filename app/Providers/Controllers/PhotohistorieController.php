<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Photohistorie;
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

class PhotohistorieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        try {
            $photohistory = Photohistorie::with('user:id,lastname,firstname', 'photo:id,category,file');
            $s = request('search');
            if ($s) {
                $Search = $photohistory->where('modifType', 'LIKE', '%' . $s . '%')->
                orWhere('oldContent', 'LIKE', '%' . $s . '%')->
                orWhere('newContent', 'LIKE', '%' . $s . '%')->
                orWhere('date', 'LIKE', '%' . $s . '%')->
                orWhere('post_id', 'LIKE', '%' . $s . '%')->
                orWhere('user_id', 'LIKE', '%' . $s . '%')
                    ->get();

                if ($Search->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => "Liste d'Historique photo",
                        'data' => $Search,

                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Désole, Historique photo not found.',

                        'status' => 200
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => "Liste d'Historique photo",
                    'data' => $photohistory->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, histories photo not found.',

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
            'modifType.required' => 'Vérifier Votre modif Type!',
            'oldContent.required' => 'Vérifier Votre old Content!',
            'newContent.required' => 'Vérifier Votre new Content!',
            'user_id.required' => 'Vérifier Votre user!',
            'post_id.required' => 'Vérifier Votre post!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "modifType" => 'max:45',
                "date" => 'date',
                "post_id" => 'exists:posts,id',
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
                $photohistory = Photohistorie::create($data);
                return response()->json([
                    'success' => true,
                    'message' => 'Historique Photo ajouté avec succès',
                    'data' => $photohistory,
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
     * @param \App\Models\Photohistorie $photohistorie
     * @return \Illuminate\Http\Response
     */
    public function show($photohistori)
    {


        $photohistorie = Photohistorie::with('user:id,lastname,firstname', 'photo:id,category,file')->find($photohistori);
        if (!$photohistorie) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Historique photo not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Historique photo id ' . $photohistorie->id,
            'data' => $photohistorie,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Photohistorie $photohistorie
     * @return \Illuminate\Http\Response
     */
    public function edit(Photohistorie $photohistorie)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Photohistorie $photohistorie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $photohistori)
    {
        $photohistorie = Photohistorie::find($photohistori);

        $messages = [
            'modifType.required' => 'Vérifier Votre modif Type!',
            'oldContent.required' => 'Vérifier Votre old Content!',
            'newContent.required' => 'Vérifier Votre new Content!',
            'name.required' => 'Vérifier Votre role!',
            'name.required' => 'Vérifier Votre role!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "modifType" => 'max:45',
                "date" => 'date',
                "post_id" => 'exists:posts,id',
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

                $photohistorie->modifType = $request->modifType;
                $photohistorie->oldContent = $request->oldContent;
                $photohistorie->newContent = $request->newContent;
                $photohistorie->modifType = $request->modifType;
                $photohistorie->date = $request->date;
                $photohistorie->post_id = $request->post_id;
                $photohistorie->user_id = $request->user_id;
                $photohistorie->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $photohistorie,

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
     * @param \App\Models\Photohistorie $photohistorie
     * @return \Illuminate\Http\Response
     */
    public function destroy($photohistori)
    {


        try {
            $photohistorie = Photohistorie::find($photohistori);
            $photohistorie->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Historique photo could not be deleted',
                'status' => 500,

            ], 500);
        }

    }

    public function galerie($id)
    {


        try {
            $totalfiche = Fiche::leftJoin('photos', 'photos.fiche_id', '=', 'fiches.id')
                ->leftJoin('photohistories', 'photohistories.photo_id', '=', 'photos.id')
                ->where('photohistories.state', 'inactif')
                ->orwhere('photos.fiche_id', $id)
                ->orwhere('photohistories.user_id', auth()->user()->id)
                ->get();
            foreach ($totalfiche as $total) {
                $pathDoc[] = storage_path('app/public/photos/' . $total->file);
            }

            return response()->json([
                'success' => false,
                'message' => 'Galérie photo',
                'data' => $pathDoc,
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
