<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Paramater;
use App\Models\Raccourcifiche;
use Illuminate\Http\Request;

class RaccourcificheController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function store1(Request $request)
    {

       
       $fiche_id=$request->fiche_id;
       // $raccourci_id=$request->raccourci_id;
      // $raccourci_id=3;
       $raccourcis=$request->raccourcis;
       $collection=collect($raccourcis);
      $listsup= $collection->where('state', false)->toArray();
      foreach($listsup as $lists){
        $ficheraccourci=Raccourcifiche::where('fiche_id',$lists['fiche_id'])->where('raccourci_id',$lists['raccourci_id'])->delete();
     
      }
  /*  $listfiche=Fiche::all();
    foreach($listfiche as $fiche){
      */
       foreach($raccourcis as $raccourci)
       {
        //$fiche_id=$fiche->id;
        $raccourci_id=$raccourci['raccourci_id'];
      //  $ficheraccourci=Raccourcifiche::where('fiche_id',$fiche_id)->where('raccourci_id',$raccourci_id);
     if($raccourci['state']){
    

        $ficheraccourci=Raccourcifiche::where('fiche_id',$fiche_id)->count();
        $param=Paramater::where('name','raccourci')->first();
                      if($ficheraccourci > $param->value){
                       //   $list[]=['locationName'=>$fiche->locationName,'message' =>"Impossible d'ajouter un autre raccourci, il faut supprimer au moins un raccourci"]
                          return response()->json([
                              'success' => false,
                              'message' =>"Impossible d'ajouter un autre raccourci, il faut supprimer au moins un raccourci",
                              'status' => 400
                                  ], 400);   
                      }else {
                       
                           Raccourcifiche::updateOrCreate(
                          ['fiche_id' => $fiche_id,'raccourci_id' => $raccourci_id],
                          [ 
                              'created_at' => date('Y-m-d h:m:i'),
                            ]);
  
  
  
                          }
                     

       }
       }
  //  }
      
                        return response()->json([
                            'success' => true,
                            'message' =>'Ajouter avec success',
                            
                            'status' => 200
                                ], 200);
                    
    }
    public function store(Request $request)
    {

       
       $fiche_id=$request->fiche_id;
       // $raccourci_id=$request->raccourci_id;
      // $raccourci_id=3;
       $raccourcis=$request->raccourcis;
       $collection=collect($raccourcis);
      $listsup= $collection->where('state', false)->toArray();
     /* foreach($listsup as $lists){
        $ficheraccourci=Raccourcifiche::where('fiche_id',$lists['fiche_id'])->where('raccourci_id',$lists['raccourci_id'])->delete();
     
      }*/
   // $listfiche=Fiche::all();
    //foreach($listfiche as $fiche){
        //$fiche_id=$fiche->id;
        foreach($listsup as $lists){
            $ficheraccourci=Raccourcifiche::where('raccourci_id',$lists['raccourci_id'])->delete();
         
          }
       foreach($raccourcis as $raccourci)
       {
      //  $fiche_id=$fiche->id;
        $raccourci_id=$raccourci['raccourci_id'];
      //  $ficheraccourci=Raccourcifiche::where('fiche_id',$fiche_id)->where('raccourci_id',$raccourci_id);
     if($raccourci['state']){
    

        $ficheraccourci=Raccourcifiche::count();
        $param=Paramater::where('name','raccourci')->first();
                      if($ficheraccourci > $param->value){
                       //   $list[]=['locationName'=>$fiche->locationName,'message' =>"Impossible d'ajouter un autre raccourci, il faut supprimer au moins un raccourci"]
                          return response()->json([
                              'success' => false,
                              'message' =>"Impossible d'ajouter un autre raccourci, il faut supprimer au moins un raccourci",
                              'status' => 400
                                  ], 400);   
                      }else {
                       
                           Raccourcifiche::updateOrCreate(
                          ['raccourci_id' => $raccourci_id],
                          [ 
                              'created_at' => date('Y-m-d h:m:i'),
                            ]);
  
  
  
                          }
                     

       }
       }
  //}
      
                        return response()->json([
                            'success' => true,
                            'message' =>'Ajouter avec success',
                            
                            'status' => 200
                                ], 200);
                    
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Raccourcifiche  $raccourcifiche
     * @return \Illuminate\Http\Response
     */
    public function show(Raccourcifiche $raccourcifiche)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Raccourcifiche  $raccourcifiche
     * @return \Illuminate\Http\Response
     */
    public function edit(Raccourcifiche $raccourcifiche)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Raccourcifiche  $raccourcifiche
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Raccourcifiche $raccourcifiche)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Raccourcifiche  $raccourcifiche
     * @return \Illuminate\Http\Response
     */
    public function destroy(Raccourcifiche $raccourcifiche)
    {
        //
    }
}
