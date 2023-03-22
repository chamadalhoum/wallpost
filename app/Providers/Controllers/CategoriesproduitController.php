<?php

namespace App\Http\Controllers;

use App\Models\Categoriesproduit;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Post;
class CategoriesproduitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     public function listcategory(Request $request) {
        try {
             if ($request->fiche_id) {
           $post = Categoriesproduit::where('fiche_id', $request->fiche_id)->get();
             }else{
            $post = Categoriesproduit::get();
             }
            $postab = array();

            foreach ($post as $pos) {

                $postab[] = array("id_Categorie" => $pos->id, "Categorie_produit" => $pos->displayName);
            }
            return response()->json([
                        'success' => true,
                        'message' => 'Liste categorie produit',
                        'data' => $postab,
                        'status' => 200
                            ], 200);
        } catch (QueryException $ex) {
            return response()->json(
                            [
                                'success' => false,
                                'message' => $ex->getMessage(),
                                'status' => 400,
                            ],
                            400
            );
        }
    }
    
    
    
   

    public function listbycategory(Request $request) {
        try {
            $post = Post::leftJoin('categoriesproduits', "categoriesproduits.id", "=", "posts.catprod_id")
                    ->leftJoin('metadatas', "metadatas.fiche_id", "=", "posts.fiche_id")
                    ->leftJoin('postfiches', 'postfiches.post_id', '=', 'posts.id')
                    ->select("posts.name as Nom_produit",
                    "posts.summary as Description_produit",
                    "categoriesproduits.displayName as Categorie_produit"
                    , "posts.fiche_id as id_fiche", "posts.action_url as lien_produit",
                    "posts.media_type", "posts.user_id", "posts.prix_max as Prix_maximal"
                    , "posts.prix_min as Prix_minimal", "posts.prix_produit as Prix_produit",
                    "posts.media_url as file",
                    "categoriesproduits.id as id_Categorie",
                    'posts.id as produit_id',
                    'posts.calltoaction',
                    'metadatas.newReviewUrl as Googleurl'
            );
            if ($request->id_Categorie) {
                $post = $post->where('posts.catprod_id', $request->id_Categorie);
            }
            if ($request->fiche_id) {
                $post = $post->where('postfiches.fiche_id', $request->fiche_id);
            }
            $post = $post->get();

            return response()->json([
                        'success' => true,
                        'message' => 'Liste categorie produit',
                        'data' => $post,
                        'status' => 200
                            ], 200);
        } catch (QueryException $exception) {
            return response()->json([
                        'success' => false,
                        'message' => 'Post could not be deleted',
                        'status' => 500,
                            ], 500);
        }
    }
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\categoriesproduit  $categoriesproduit
     * @return \Illuminate\Http\Response
     */
    public function show(categoriesproduit $categoriesproduit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\categoriesproduit  $categoriesproduit
     * @return \Illuminate\Http\Response
     */
    public function edit(categoriesproduit $categoriesproduit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\categoriesproduit  $categoriesproduit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, categoriesproduit $categoriesproduit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\categoriesproduit  $categoriesproduit
     * @return \Illuminate\Http\Response
     */
    public function destroy(categoriesproduit $categoriesproduit)
    {
        //
    }
}
