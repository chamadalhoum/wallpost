<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Raccourci;
use App\Models\Raccourcifiche;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RaccourciController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       /* if (!is_numeric($request->header('franchise'))) {
            return response()->json([
            'success' => false,
            'message' => $request->header('franchise'),
            'status' => 400,
        ]);
        }*/
        $data=array();
        try{
      $fiche_id=$request->fiche_id;
      $total=$request->total;
      
          $racourcifiche=  DB::table('raccourcis')
       
        ->when($fiche_id,function ($query) use($total,$fiche_id){
            if($total == 'true'){
                $query->leftJoin('raccourcifiches', function($join) use ($fiche_id)
                {
                    $join->on('raccourcifiches.raccourci_id', '=', 'raccourcis.id')
                  //  ->where('raccourcifiches.fiche_id', '=', $fiche_id);
                  ->whereNotNull('raccourcifiches.raccourci_id');
                });
            }else{
                $query->leftJoin('raccourcifiches', 'raccourcifiches.raccourci_id', '=', 'raccourcis.id')
                ->whereNotNull('raccourcifiches.raccourci_id');
            }
         //   $query->where('fiches.id', $fiche_id);
            })
        ->select('raccourcis.name','raccourcis.color','raccourcis.icon','raccourcifiches.fiche_id','raccourcifiches.raccourci_id'
        ,'raccourcis.id as raccourci')->get();

    if(!empty($racourcifiche)){
       $nb= 0;
        foreach($racourcifiche as $fiche){
            if(isset($fiche->raccourci_id)){
                $state=true;
              $nb++;
            }else{
                $state=false;
            }
            $data[]=['name'=>$fiche->name,'color'=>$fiche->color,'icon'=>\Illuminate\Support\Facades\URL::to('/') .'/'.$fiche->icon,'state'=>$state,'fiche_id'=>$fiche_id,
            'raccourci_id'=>$fiche->raccourci
        ];
        }
    }
      
    $nbphoto = Photo::where('fiche_id', $fiche_id)->count();
  
        return response()->json([
            'success' => true,
            'message' => 'Operation success.',
            'data' => $data,
            'count'=>$nb,
            'nb_countphoto'=>$nbphoto,
            'status' => 200,
        ],200);
    } catch (\Throwable $th) {
        return response()->json([
            'success' => false,
            'message' => $th->getMessage(),
            'status' => 400,
        ]);
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Raccourci  $raccourci
     * @return \Illuminate\Http\Response
     */
    public function show(Raccourci $raccourci)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Raccourci  $raccourci
     * @return \Illuminate\Http\Response
     */
    public function edit(Raccourci $raccourci)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Raccourci  $raccourci
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Raccourci $raccourci)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Raccourci  $raccourci
     * @return \Illuminate\Http\Response
     */
    public function destroy(Raccourci $raccourci)
    {
        //
    }

}
