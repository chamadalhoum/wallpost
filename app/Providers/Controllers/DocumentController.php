<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use GPBMetadata\Google\Api\Auth;
use Illuminate\database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\DB;
class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function download($id){
        $doc=Document::where('id',$id)->firstOrFail();


        $pathDoc=storage_path('app/public/document/'.$doc->file);

        $details =['title' => $doc->file];
        $pdf = PDF::loadView('app/public/document/'.$doc->file, $details);
        return $pdf->download($doc->file);
        /*
$pdf=PDF::loadFile($pathDoc);
return $pdf;
        return response()->download($pathDoc);*/
    }
    public function indexpost(Request $request)
    {

        try {
            
            $Document = Document::leftjoin('users','documents.user_id','=','users.id')->
            select(DB::raw('CONCAT(users.firstname," ",users.lastname) AS expediteur,documents.type,documents.file as fichier,
            CONCAT(DATE_FORMAT(documents.created_at, "%Y/%m/%d")," à ",DATE_FORMAT(documents.created_at, "%h:%i:%s")) AS date,
           CONCAT ("'.\Illuminate\Support\Facades\URL::to('/document/').'/",documents.file) AS path'));
          
          //  ->where('documents.user_id', '=', Auth()->user()->id);
           
            $start = $request->date_debut;
            $end = $request->date_fin;
            
            if($request->Page){
                $cp=$request->Page;
            }else{
                $cp=10;
            }
            if ($start && $end) {

                $Documentearch=  $Document->
                whereBetween('documents.created_at', ["$start","$end"])->orderBy('documents.id', 'DESC')->get()
                    ;

                if ($Documentearch->count() > 0) {
                    foreach($Documentearch as $data){
                        $tabsearch[]=['expediteur'=>$data->expediteur,
                        'date'=>$data->date,
                        'fichier'=>$data->fichier,
                        'path'=>$data->path,
                        'type'=>$data->type,
                        'msg'=>true
                    ];
                    }
                    return response()->json([
                        'success' => true,
                        'message' => 'Liste Document',
                        'data' => $tabsearch,
                        'date_debut'=>$start,
                        'date_fin'=>$end,
                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Désole, Document not found.',
                        'data' => [['expediteur'=>'',
                        'date'=>'',
                        'fichier'=>'',
                        'path'=>'',
                        'type'=>'',
                        'msg'=>false
                    ]],
                        'date_debut'=>$start,
                        'date_fin'=>$end,
                        'status' => 200
                    ], 200);
                }
            } else {
               $start = Carbon::now()->subMonth()->toDateString();
               $end= carbon::now()->toDateString();
               $datas= $Document->whereBetween('documents.created_at', ["$start","$end"])->orderBy('documents.id', 'DESC')->get();
               foreach($datas as $data){
                   $tab[]=['expediteur'=>$data->expediteur,
                   'date'=>$data->date,
                   'fichier'=>$data->fichier,
                   'path'=>$data->path,
                   'type'=>$data->type,
                   'msg'=>true
               ];
               }
               if(!$datas->count()){
                $tab[]=['expediteur'=>'',
                'date'=>'',
                'fichier'=>'',
                'path'=>'',
                'type'=>'',
                'msg'=>false
            ];
                
              }
                 return response()->json([
                    'success' => true,
                    'message' => 'Liste Document',
                    'data' =>$tab, 
                    'date_debut'=>$start,
                    'date_fin'=>$end,
                    'status' => 200
                ], 200);
            }
        } catch (QueryException $ex) {
            $start = Carbon::now()->subMonth()->toDateString();
            $end= carbon::now()->toDateString();
            return response()->json([
                'success' => false,
                'message' => 'Désole, Document not found.',
                'data'=>[],
                'date_debut'=>$start,
                'date_fin'=>$end,
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
            'type.required' => 'Vérifier Votre Type!',
            'file.required' => 'Vérifier Votre File!',
            'Fiche.required' => 'Vérifier Votre Fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',

        ];

        $validator = Validator::make($request->all(),
            [
                "type" => 'required|max:45',
                "file" =>  'file|mimes:doc,pdf',
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
                $data=$request->all();
                if ($request->file) {
                   $request->file->store('/document', 'public');

                    $imageName = 'document' . '_' . time() . '.' . $request->file->extension();

                    $request->file->move(public_path('/document'), $imageName);

                    $data['file'] =  $imageName;
                }

                $document= Document::create($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Document ajouté avec succès',
                    'data' => $document,
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
     * @param  \App\Models\Document  $Document
     * @return \Illuminate\Http\Response
     */
    public function show(Document $Document)
    {


        $Documents=Document::with('fiche:id,locationName,name')->find($Document->id);
        if (!$Documents) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Document not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document id ' . $Document->id,
            'data' => $Documents,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Document  $Document
     * @return \Illuminate\Http\Response
     */
    public function edit(Document $Document)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Document  $Document
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Document $Document)
    {


        $messages = [
        'type.required' => 'Vérifier Votre Type!',
        'file.required' => 'Vérifier Votre File!',
        'Fiche.required' => 'Vérifier Votre Fiche!',
        'size' => 'The :attribute must be exactly :size.',
        'between' => 'The :attribute must be between :min - :max.',
        'in' => 'The :attribute must be one of the following types: :values',

    ];

        $validator = Validator::make($request->all(),
            [
                "type" => 'required|max:45',
             //   "file" =>  'file|mimes:doc,pdf',
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

             if ($request->file) {
                    $request->file->store('/document', 'public');

                    $imageName = 'document' . '_' . time() . '.' . $request->file->extension();

                    $request->file->move(public_path('/document'), $imageName);
                    $Document->file =$imageName;
                }
                $Document->type = $request->type;
                $Document->fiche_id = $request->fiche_id;
                $Document->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $Document,

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
     * @param  \App\Models\Document  $Document
     * @return \Illuminate\Http\Response
     */
    public function destroy(Document $Document)
    {


        try {
            $Document->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Document could not be deleted',
                'status' => 500,

            ], 500);
        }

    }
}
