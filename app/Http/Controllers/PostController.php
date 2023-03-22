<?php

namespace App\Http\Controllers;

use App\Models\profilincomplete;
use App\Helper\Helper;
use App\Models\Categoriesproduit;
use App\Models\Etiquetgroupe;
use App\Models\Fiche;
use App\Models\Franchise;
use App\Models\Post;
use App\Models\Postfiche;
use App\Models\Postfichestag;
use App\Models\Posthistorie;
use App\Models\Typepost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\Photo;

class PostController extends Controller
{
    public $mybusinessService;
    public $placeID;
    public $localpost;
    public $calltoaction;
    public $media;

    public function __construct()
    {
        $this->mybusinessService = Helper::GMB();
        $this->placeID = Helper::GMBLOCATIONPOST();
        $this->localpost = $this->mybusinessService->accounts_locations_localPosts;
        $this->calltoaction = Helper::CallToAction();
        $this->media = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_MediaItem();
    }

    public function last_posts(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            $ettiquets = null;
            $Progettiquets = null;
            $etiquette = null;
            $dt = Carbon::now();
            $End_day = $dt->translatedFormat('Y-m-d');
            $Start_day = $dt->subDay(30)->translatedFormat('Y-m-d');
            $data['LastPost'] = ['statusLastPost'=>false, 'FicheCount' => 0];
    

            
            $postsEnvoye = Post::whereIn('id', Postfiche::Distinct()->pluck('post_id')->toArray())->where('state', 'Envoyer')->groupBy('created_at')->first();
            if(isset($postsEnvoye)){
                $fiches = Postfiche::join('fiches', 'fiches.id', '=', 'postfiches.fiche_id')->where('postfiches.post_id', '=', $postsEnvoye->id)->get('fiches.*');
                $fichesCount = Postfiche::join('fiches', 'fiches.id', '=', 'postfiches.fiche_id')->where('postfiches.post_id', '=', $postsEnvoye->id)->count();
                $stats = Postfiche::join('post_stats', 'post_stats.post_fiche_id', '=', 'postfiches.id')->where('postfiches.post_id', '=', $postsEnvoye->id)->whereBetween('post_stats.date', [$Start_day, $End_day]);
                $views = $stats->sum('post_stats.localPostViewsSearch');
                $clics = $stats->sum('post_stats.localPostActions');
               $datacreate= Carbon::parse($postsEnvoye->created_at)->translatedFormat('d/m/Y');
                $postsEnvoye['created_ats'] = $datacreate;
                if ($postsEnvoye->type_envoi == 'Envoi groupé') {
                    $etiquette = Postfichestag::join('groupes', 'groupes.id', '=', 'postfichestags.groupe_id')->join('etiquettes', 'postfichestags.etiquettes_id', '=', 'etiquettes.id')->where('postfichestags.post_id', '=', $postsEnvoye->id)->get(['groupes.color', 'etiquettes.name', 'etiquettes.id as et_id'])->toArray();
                    foreach ($etiquette as $key => $value) {
                        $ettiquets[$value['et_id']] = $value;
                    }
                } else {
                    $ettiquets = null;
                }
                $data['LastPost'] = ['Post' => $postsEnvoye, 'FicheCount' => $fichesCount, 'Views' => $views,
                 'Clics' => $clics, 'statusLastPost'=>true,'Etiquettes' => (!empty($ettiquets)) ? array_values(array_filter($ettiquets)) : null];
    
            }

           $postsPrograme = Post::whereIn('id', Postfiche::Distinct()->pluck('post_id')->toArray())->where('state', 'Programme')->orderBy('created_at', 'ASC')->limit(2)->get();

            if (isset($postsPrograme)) {
                foreach ($postsPrograme as $key => $post) {
                    $post['programmed_date'] = Carbon::parse($post->programmed_date)->translatedFormat('d/m/Y');
                    $fiches = Postfiche::join('fiches', 'fiches.id', '=', 'postfiches.fiche_id')->where('postfiches.post_id', '=', $post->id)->get('fiches.*');
                    $fichesProgCount = Postfiche::join('fiches', 'fiches.id', '=', 'postfiches.fiche_id')->where('postfiches.post_id', '=', $post->id)->count();

                    if ($post['type_envoi'] == 'Envoi groupé') {
                        $etiquette = Postfichestag::join('groupes', 'groupes.id', '=', 'postfichestags.groupe_id')->join('etiquettes', 'postfichestags.etiquettes_id', '=', 'etiquettes.id')->where('postfichestags.post_id', '=', $post->id)->get(['groupes.color', 'etiquettes.name', 'etiquettes.id as et_id'])->toArray();

                        foreach ($etiquette as $key => $value) {
                            $Progettiquets[$value['et_id']] = $value;
                        }
                    } else {
                        $Progettiquets = null;
                    }

                    $data['ProgPost'][] = ['Post' => $post->toArray(), 'FicheCount' => $fichesProgCount, 'Etiquettes' => (!empty($Progettiquets)) ? array_values(array_filter($Progettiquets)) : null];
                }
            }

            return response()->json([
            'success' => true,
            'message' => 'Operation success.',
            'data' => $data,
            'status' => 200,
        ]);
        } catch (\Throwable $th) {
            return response()->json([
        'success' => false,
        'message' => $th->getMessage(),
        'line' => $th->getLine(),
        'status' => 400,
    ]);
        }
    }

    public function post_classify(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            $data=[];
            $dt = Carbon::now();
            $End_day = $dt->translatedFormat('Y-m-d');
            $Start_day = $dt->subDay(30)->translatedFormat('Y-m-d');
            $Old_Start_date = $dt->subDay(30)->translatedFormat('Y-m-d');

            $posts = Post::join('postfiches', 'postfiches.post_id', '=', 'posts.id')->join('fiches', 'fiches.id', '=', 'postfiches.fiche_id')->where('fiches.franchises_id', '=', $request->header('franchise'))
            ->where('fiches.state', 'LIKE', 'COMPLETED')->distinct()->get(['posts.*', 'fiches.city', 'fiches.locationName']);
            // print_r($posts->toArray());
            // exit;
            foreach ($posts as $key => $post) {
                $stats = Postfiche::join('post_stats', 'post_stats.post_fiche_id', '=', 'postfiches.id')->where('postfiches.post_id', '=', $post->id)->whereBetween('post_stats.date', [$Start_day, $End_day]);

                $data[] = ['Id' => $post->id, 'Date' => Carbon::parse($post->updated_at)->translatedFormat('d/m/Y'), 'FicheName' => $post->locationName, 'City' => $post->city, 'Name' => $post->name, 'Desc' => $post->summary, 'Views' => $stats->sum('post_stats.localPostViewsSearch'), 'Clics' => $stats->sum('post_stats.localPostActions')];
            }

            usort($data, function ($a, $b) {
                return $a['Views'] <=> $b['Views'];
            });
            $data = array_reverse($data, false);

            foreach ($data as $key => $value) {
                $stats2 = Postfiche::join('post_stats', 'post_stats.post_fiche_id', '=', 'postfiches.id')->where('postfiches.post_id', '=', $value['Id'])->whereBetween('post_stats.date', [$Old_Start_date, $Start_day]);

                $view = $stats2->sum('post_stats.localPostViewsSearch');

                $diff = $value['Views'] - $view;

                $data[$key]['diff'] = $value['Views'] - $view;
                $data[$key]['lastViews'] = $view;
                if ($diff > 0) {
                    $data[$key]['Classify'] = 0;
                } elseif ($diff == 0) {
                    $data[$key]['Classify'] = 1;
                } else {
                    $data[$key]['Classify'] = 2;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => ['TOP' => array_slice($data, 0, 3), 'FLOP' => array_reverse(array_slice($data, -3, 3)), 'ALL' => $data],
                'status' => 200,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
            'success' => false,
            'message' => $th->getMessage(),
            'line' => $th->getLine(),
            'status' => 400,
        ]);
        }
    }

    public function store(Request $request)
    {
      
        $messages = [
            'user_id.required' => 'Vérifier Votre user!',
            'fiche_id.required' => 'Vérifier Votre fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];

        $validator = Validator::make($request->all(),
                        [
                            'user_id' => 'exists:users,id',
                            'fiche_id' => 'exists:fiches,id',
                        ], $messages
        );
        if ($validator->fails()) {
            return response()->json([
                        'succes' => false,
                        'message' => $validator->errors()->toArray(),
                        'status' => 422, ],
                            422);
        }
        if ($validator->passes()) {
            try {
                $produit=array();
                if(isset($request->produit)){
                    $produit=$request->produit;
                }else if(isset($request->prod)){
                    $produit=$request->prod;
                }
                $Appelaction='';
                $request->descriptionproduit? $descriptionproduit = $request->Prix_produit:$descriptionproduit='';
                if(count($produit)>0){
                    $produit ? $descriptionproduit = $produit['Prix_produit']:$descriptionproduit='';
                    $produit? $calltoActiontype= $produit['calltoaction']:$calltoActiontype;
                    $produit['lien_produit']? $lien_produit = $produit['lien_produit']:$lien_produit;
                    $produit? $Nom_produit = $produit['Nom_produit']: $Nom_produit;
                    $produit? $Prix_maximal = $produit['Prix_maximal']:$Prix_maximal;
                    $produit? $image_64 = $produit['photo_produit']:$image_64;
                    $produit?$Prix_minimal = $produit['Prix_minimal']:$Prix_minimal;
                    $produit? $Prix_produit = $produit['Prix_produit']: $Prix_produit ;
                    $produit? $Nom_produit = $produit['Nom_produit']:$Nom_produit;
                    $produit? $Categorie_produit = $produit['Categorie_produit']:$Categorie_produit;
                    $produit['media_type']? $media_type = $produit['media_type']:$media_type;
                    $produit['Description_produit']?$summary = $produit['Description_produit']:$summary;
                    $request->Appelaction? $Appelaction = $request->Appelaction:$Appelaction='';
                    if(array_key_exists('Appelaction',$produit)){
                       $Appelaction = $produit['Appelaction'];
                    }
                    if(array_key_exists('produit_id',$produit)){
                        $produit_id = $produit['produit_id'];
                     }
                }
               
                $request->Description_produit? $descriptionproduit = $request->Prix_produit:$descriptionproduit='';
                $request->calltoaction? $calltoActiontype =$request->calltoaction:$calltoActiontype = '';
                
                $request->lien_produit? $lien_produit = $request->lien_produit: $lien_produit="";
               
                $request->Nom_produit? $Nom_produit= $request->Nom_produit:$Nom_produit ='';
               
                $request->Prix_maximal? $Prix_maximal = $request->Prix_maximal:$Prix_maximal='';
               
                $request->photo_produit? $image_64 = $request->photo_produit:$image_64='';
               
                $request->Prix_minimal? $Prix_minimal = $request->Prix_minimal:$Prix_minimal='';
               
                $request->Prix_produit? $Prix_produit = $request->Prix_produit: $Prix_produit ='';
               
                $request->Nom_produit? $Nom_produit = $request->Nom_produit:$Nom_produit='';
               
                $request->Categorie_produit? $Categorie_produit = $request->Categorie_produit:$Categorie_produit='';
               
                $request->produit_id? $produit_id= $request->produit_id:$produit_id='';
              
             //  $request->produit['produit_id']?$produit_id = $request->produit['produit_id']:$produit_id;
               
               // 
                $request->media_type? $media_type= $request->media_type:$media_type='';
          
                $request->id_fiche? $fiche_id=$request->id_fiche:$fiche_id='';
                $request->fiche_id? $fiche_id = $request->fiche_id:$fiche_id;
                $request->Description_produit? $summary= $request->Description_produit: $summary='';
                
                $actioncall = null;
                $this->placeID->setLanguageCode('fr');
                $this->placeID->setTopicType('STANDARD');
               
                if ($descriptionproduit) {
                    $descriptionproduit.' €';
                }
                if ($Prix_produit) {
                    $descriptionproduit = "\n\n".' Prix :'.$Prix_produit.' €';
                }
                if ($Prix_minimal) {
                    $descriptionproduit .= "\n\n".'Prix Minimal:'.$Prix_minimal.' € ';
                }
                if ($Prix_maximal) {
                    $descriptionproduit .= "\n\n".' Prix Maximal :'.$Prix_maximal.' €';
                }
                $titledescription = $Nom_produit."\n\n";
                if($calltoActiontype != 'Aucun') {
                    $typepost = Typepost::where('title', $calltoActiontype)->first();
                    $calltoActiontype = $typepost->nametype;
                    $this->calltoaction->setActionType($calltoActiontype);
                    if ($lien_produit) {
                        $this->calltoaction->setUrl($lien_produit);
                        $data['action_url'] = $lien_produit;
                    }
                    if ($Appelaction) {
                        $this->calltoaction->setUrl($Appelaction);
                        $data['action_url'] = $Appelaction;
                    }
                    $this->placeID->setCallToAction($this->calltoaction);
                    $data['action_type'] = $typepost->nametype;
                    $data['calltoaction'] = $calltoActiontype;
                    $data['action_url'] = $lien_produit;
                    $actioncall = ',callToAction.actionType,callToAction.url';
                }

                $this->placeID->setSummary($titledescription.$summary.$descriptionproduit);
                $data['summary'] = $summary;
                $data['name'] = $Nom_produit;
                $data['prix_max'] = $Prix_maximal;
                $data['prix_min'] = $Prix_minimal;
                $data['prix_produit'] = $Prix_produit;
                $data['fiche_id'] = $fiche_id;
                $data['media_type'] = $media_type;
                $data['topic_type'] = 'PRODUCT';
                $data['type'] = 'Produits';
                $data['state'] = 'Envoyer';
                if ($image_64) {
                    $imageName = PhotoController::photo($image_64);

                    $this->media->setMediaFormat('PHOTO');
                    $this->media->setSourceUrl($imageName);
                    $this->placeID->setMedia($this->media);
                    $data['media_url'] = $imageName;
                }
           
                if ($produit_id) {
                    $postfiche = Postfiche::where('post_id', $produit_id)
                    ->where('fiche_id', $fiche_id)->first();
                    $rest = $this->localpost->patch($postfiche->genre, $this->placeID,
                     ['updateMask' => ['media.sourceUrl,media.mediaFormat,event.title_post,summary,offer.couponCode,offer.redeemOnlineUrl,offer.termsConditions,topicType,alertType'.$actioncall]]);

                    $post = Post::find($produit_id);
                    Storage::disk('public')->delete($post->media_url);
                    $post->update($data);
                    $msg = 'Post Mise a jour avec succès';
                } else {
                    $fiche = Fiche::find($fiche_id);
                    $rest = $this->localpost->create($fiche->name, $this->placeID);
                    $msg = 'Post ajouté avec succès';
                    $post = Post::create($data);
                  
               $fichename=  Fiche::find($fiche_id);
                       $datas = ['post_id' => $post->id,
                            'fiche_id' => $fiche_id,
                            'genre' => $rest['name'],
                            'name' => $fichename->name ];
                            /*$post = Post::find($post->id);
                            $post->search_url=$rest['searchUrl'];
                    $post->update();*/
                   
                    Postfiche::insert($datas);
                }
                if ($Categorie_produit) {
                    $datas['displayName'] = $Categorie_produit;
                    $datas['fiche_id'] = $fiche_id;
                    $datas['post_id'] = $post->id;
                    $catprod = Categoriesproduit::where('displayName', $Categorie_produit)
                            ->where('fiche_id',$fiche_id);
                    if (($catprod->count() === 0)) {
                        $catprodts = Categoriesproduit::create($datas);

                        $data['catprod_id'] = $catprodts->id;
                    } else {
                        $catprod = $catprod->get()->toarray();

                        $data['catprod_id'] = $catprod[0]['id'];
                    }
                    $post->update($data);
                }

                return response()->json([
                            'success' => true,
                            'message' => $msg,
                            'data' => $post,
                            'vvv'=>$rest,
                            'status' => Response::HTTP_OK,
                                ], Response::HTTP_OK);
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
    }

    public function postgmb(Request $request)
    {
        $messages = [
            'user_id.required' => 'Vérifier Votre user!',
            'fiche_id.required' => 'Vérifier Votre fiche!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
            'Lienaction.regex' => 'Saisissez une URL de destination valide pour cette action',
            'liens_post.regex' => 'Saisissez une URL de destination valide pour cette action',
         //   'title_post.required' => 'Vous devez indiquer un Titre de post',
            'date_debut.required_if' => 'Sélectionnez une date debut valide ',
            'date_fin.required_if' => 'Sélectionnez une date Fin valide',
            'Lienaction.required_if' => 'Saisissez une URL valide (ex. : www.example.com)',
        ];
        $validator = Validator::make($request->post,
                        [
                            'image' => 'dimensions:min_width=100,min_height=100,max_width=1000,max_height=1000',
                            'picture' => 'data:image/png;base64,this-is-the-base64-encode-string',
                            'user_id' => 'exists:users,id',
                            'fiche_id' => 'exists:fiches,id',
                            'Lienaction' => 'nullable|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
                            'liens_post' => 'nullable|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
                            //'title_post' => 'nullable|required|max:100',
                            'date_debut' => 'required_if:topictype,==,OFFER,topictype,==,EVENT',
                            'date_fin' => 'required_if:topictype,==,OFFER,topictype,==,EVENT',
                            'date_fin' => 'required_if:topictype,==,OFFER,topictype,==,EVENT',
                            'Lienaction' => 'nullable|required_if:calltoaction.actiontype,==,LEARN_MORE,'
                            .'calltoaction.actiontype,==,BOOK,'
                            .'calltoaction.actiontype,==,ORDER,'
                            .'calltoaction.actiontype,==,SHOP,'
                            .'calltoaction.actiontype,==,SIGN_UP|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
                        ], $messages
        );

        if ($validator->fails()) {
            return response()->json([
                        'succes' => false,
                        'message' => $validator->errors()->toArray(),
                        'status' => 422, ],
                            422);
        }
        if ($validator->passes()) {
            try {
                $descriptionproduit = null;
                $titledescription = null;
                $calltoActiontype = null;
                $datapfc = [];
                $datas = [];
                $requestpost=$request->post; 
               if(array_key_exists('calltoaction',$requestpost)){
                $calltoAction = $requestpost['calltoaction'];
               }else{
                $calltoAction = '';
               }

                $topictype = $requestpost['topictype'];
                $actionUrl = $requestpost['Lienaction'];
                $Appelaction = $requestpost['Appelaction'];
                $actionEvent = $requestpost['title_post'];
                $offeraction = $requestpost['code_post'];
               $fiche_id= $request->fiche_id;
                $image = $requestpost['post_listImages'];
                $imageName = null;
                $actioncall = null;
                $data['topic_type'] = $topictype;
                if ($topictype == 'PRODUCT') {
                    $data['topic_type'] = $topictype;
                    $topictype = 'STANDARD';
                    if(array_key_exists('Prix_produit',$requestpost)){
                        $descriptionproduit = "\n\n".' Prix :'.$requestpost['Prix_produit'].' €';
                    }
                    if(array_key_exists('Prix_minimal',$requestpost)){
                        $descriptionproduit .= "\n\n".'Prix Minimal:'.$requestpost['Prix_minimal'].' € ';
                    }
                    if(array_key_exists('Prix_maximal',$requestpost)){
                
                        $descriptionproduit .= "\n\n".' Prix Maximal :'.$requestpost['Prix_maximal'].' €';
                    }
                }
             
             
                if ($topictype != 'OFFER' || $topictype != 'EVENT') {
                    $titledescription = $requestpost['title_post']."\n\n";
                }
                try {
                    $summary = $titledescription.$requestpost['description_post'].$descriptionproduit;
                    $this->placeID->setSummary($summary);
                    $this->placeID->setLanguageCode('fr');
                    $typepost = Typepost::where('nametype', $requestpost['topictype'])->first();
                    $data['type'] = $typepost->title;
                    $this->placeID->setTopicType($topictype);
                    if ($calltoAction) {
                        $calltoActiontype = $requestpost['calltoaction']['actiontype'];
                        $this->calltoaction->setActionType($calltoActiontype);
                        if ($actionUrl) {
                            $this->calltoaction->setUrl($actionUrl);
                            $data['action_url'] = $actionUrl;
                        }
                        if ($Appelaction) {
                            $data['action_url'] = $Appelaction;
                        }
                        $this->placeID->setCallToAction($this->calltoaction);
                        $data['action_type'] = $calltoActiontype;
                        $data['calltoaction'] = $requestpost['calltoaction']['calltoaction'];
                        $actioncall = ',callToAction.actionType,callToAction.url';
                    }
                    if ($actionEvent) {
                        $event = Helper::EventAction();
                        $event->setTitle($requestpost['title_post']);
                        if ($requestpost['date_debut'] || $requestpost['date_fin']) {
                            $schedule = Helper::TimeIntervalAction();
                            $schedule->setStartDate($this->datepost($requestpost['date_debut'], Helper::DateAction()));
                            $schedule->setEndTime($this->detailstimeofday($requestpost['time_fin']));
                            $schedule->setStartTime($this->detailstimeofday($requestpost['time_debut']));
                            $schedule->setEndDate($this->datepost($requestpost['date_fin'], Helper::DateAction()));
                            $event->setSchedule($schedule);
                        }
                        $this->placeID->setEvent($event);
                    }
                    if ($image) {
                        foreach ($image as $listimage) {
                            foreach ($listimage['objet'] as $path) {
                                $image_64 = $path['attachement_nom'];
                                $imageName = PhotoController::photo($image_64);
                                //$this->media->setMediaFormat("VIDEO");
                                $this->media->setMediaFormat('PHOTO');
                                $this->media->setSourceUrl($imageName);
                                $this->placeID->setMedia($this->media);
                            }
                        }
                    }
                    if ($offeraction) {
                        $offer = Helper::OfferAction();
                        $offer->setCouponCode($requestpost['code_post']);
                        $offer->setRedeemOnlineUrl($requestpost['liens_post']);
                        $offer->setTermsConditions($requestpost['condition_post']);
                        $this->placeID->setOffer($offer);
                        $data['redeem_online_url'] = $requestpost['liens_post'];
                    }
                    if ($requestpost['liens_post']) {
                        $this->placeID->setSearchUrl($requestpost['liens_post']);
                        $data['action_url'] = $requestpost['liens_post'];
                    }
                    $data['state'] = 'Envoyer';
                    $this->placeID->setState('LIVE');
                    if ($requestpost['post_programme']) {
                        $this->placeID->setState('PROCESSING');
                        $data['state'] = 'Programme';
                    }
                    if(array_key_exists('date_programme',$requestpost)){
                        $data['programmed_date'] = $requestpost['date_programme'];
                       }
                    if ($requestpost['date_debut']) {
                        $data['event_start_date'] = $requestpost['date_debut'];
                    }
                    if ($requestpost['date_fin']) {
                        $data['event_end_date'] = $requestpost['date_fin'];
                    }
                    if ($requestpost['time_debut']) {
                        $data['event_start_time'] = $requestpost['time_debut'];
                    }
                    if ($requestpost['time_fin']) {
                        $data['event_end_time'] = $requestpost['time_fin'];
                    }
                    if(array_key_exists('Prix_maximal',$requestpost)){
                  
                        $data['prix_max'] = $requestpost['Prix_maximal'];
                    }
                    if(array_key_exists('Prix_minimal',$requestpost)){
                  
                        $data['prix_min'] = $requestpost['Prix_minimal'];
                    }
                    if(array_key_exists('Prix_produit',$requestpost)){
                    
                        $data['prix_produit'] = $requestpost['Prix_produit'];
                    }
                    $data['summary'] = $requestpost['description_post'];
                    $data['coupon_code'] = $requestpost['code_post'];

                    $data['terms_conditions'] = $requestpost['condition_post'];

                    $data['type_envoi'] = $requestpost['type_envoi'];
                    $data['name'] = $requestpost['title_post'];
                    $data['user_id'] = auth()->user()->id;
                    if(!is_array($fiche_id)  && $fiche_id){
                     
                            $fiche = Fiche::find($fiche_id);
                            $datapf['fiche_id'] =$fiche_id;
                            $datapf['name'] = $fiche->name;
                            if ($requestpost['post_id']) {
                                $post = Post::find($requestpost['post_id']);
                                if (!$image && $post->media_url != null) {
                                    $data['media_url'] = null;
                                }
                                $postfiche = Postfiche::where('post_id', $requestpost['post_id'])->where('fiche_id', $fiche_id)->first();
                                if ($postfiche->genre) {
                                    $msg = 'Post Modifier avec succès';
                                    $rest = $this->localpost->patch($postfiche->genre, $this->placeID,
                                            ['updateMask' => ['media.sourceUrl,media.mediaFormat,event.title_post,summary,offer.couponCode,offer.redeemOnlineUrl,offer.termsConditions,topicType,alertType'.$actioncall]]);
                                    if ($rest->state == 'REJECTED') {
                                        $data['state'] = $rest->state;
                                    }
                                    if(array_key_exists('Categorie_produit',$requestpost)){
                                    
                                        $idreq = $requestpost['Categorie_produit'];
                                        $post->catprod_id = $this->categoriepost($post, $idreq, $datapf['fiche_id']);
                                    }
                                    $post->update($data);
                                } else {
                                    $msg = 'Verifier Votre donne';
                                }
                            } else {
                                $rest = $this->localpost->create($fiche->name, $this->placeID);
                                $data['search_url'] = $rest->searchUrl;
                                $datapf['genre'] = $rest->name;
                                if ($rest->state == 'REJECTED') {
                                    $data['state'] = $rest->state;
                                }
                            }
                            $datapfc[] = $datapf;
                    
                }
                    if ($requestpost['type_envoi'] == 'Envoi ciblé' || $requestpost['type_envoi'] == 'Tous') {
                        
                        foreach ($requestpost['listFiches'] as $list) {
                            if ($list['status']) {
                                $fiche = Fiche::find($list['fiche_id']);
                                $datapf['fiche_id'] = $list['fiche_id'];
                                $datapf['name'] = $fiche->name;
                                if ($requestpost['post_id']) {
                                    $post = Post::find($requestpost['post_id']);
                                    if (!$image && $post->media_url != null) {
                                        $data['media_url'] = null;
                                    }
                                    $postfiche = Postfiche::where('post_id', $requestpost['post_id'])->where('fiche_id', $list['fiche_id'])->first();
                                    if ($postfiche->genre) {
                                        $msg = 'Post Modifier avec succès';
                                        $rest = $this->localpost->patch($postfiche->genre, $this->placeID,
                                                ['updateMask' => ['media.sourceUrl,media.mediaFormat,event.title_post,summary,offer.couponCode,offer.redeemOnlineUrl,offer.termsConditions,topicType,alertType'.$actioncall]]);
                                        if ($rest->state == 'REJECTED') {
                                            $data['state'] = $rest->state;
                                        }
                                        if ($requestpost['Categorie_produit']) {
                                            //$idreq = $requestpost['Categorie_produit']; 
                                            if(is_array($requestpost['Categorie_produit'])){
                                                $idreq = $requestpost['Categorie_produit']['Categorie_produit'];
                                            }else{
                                                $idreq = $requestpost['Categorie_produit']; 
                                            }
                                  
                                            $post->catprod_id = $this->categoriepost($post, $idreq, $datapf['fiche_id']);
                                        }
                                        $post->update($data);
                                    } else {
                                        $msg = 'Verifier Votre donne';
                                    }
                                } else {
                                    $rest = $this->localpost->create($fiche->name, $this->placeID);
                                    $data['search_url'] = $rest->searchUrl;
                                    $datapf['genre'] = $rest->name;
                                    if ($rest->state == 'REJECTED') {
                                        $data['state'] = $rest->state;
                                    }
                                }
                                $datapfc[] = $datapf;

                                $dataprofil['Post']=true;
                                $dataprofil['nombrejour']=carbon::now()->toDateString();
                                profilincomplete::updateOrCreate(['fiche_id'=>$list['fiche_id']],$dataprofil);
                                UserController::totalprofilincomplet($list['fiche_id']);
                            }
                        }
                    }
                    $tab = [];
                    $datatag = [];
                    if ($requestpost['type_envoi'] == 'Envoi groupé') {
                        foreach ($requestpost['listGroupe'] as $groupe) {
                            $tabs = ['groupe_id' => $groupe['id_groupe']];
                            $i = 0;
                            foreach ($groupe['ettiquettes'] as $etiquette) {
                               
                                    if ($etiquette['status'] === true) {
                                        $etiq = Etiquetgroupe::find($etiquette['etiquettegroupe']);
                                        // $tab['etiquette_id'][] = $etiq->etiquette_id;
                                        $tab[] = $etiq->etiquette_id;
                                        $datatag[] = ['etiquette_id'=>$etiq->etiquette_id,'id_groupe'=>$groupe['id_groupe']];
                                    }
                               
                                ++$i;
                            }
                        }
                        $fiches = Etiquetgroupe::leftjoin('fiches', 'etiquetgroupes.fiche_id', '=', 'fiches.id')
                                        ->whereNotNull('etiquetgroupes.fiche_id')
                                        // ->where('etiquetgroupes.groupe_id',$tag['groupe_id'])
                                        ->where('etiquetgroupes.state', 1)
                                        ->whereIN('etiquetgroupes.etiquette_id', $tab)
                                        //->whereIN('etiquetgroupes.etiquette_id', $tab['etiquette_id'])
                                        ->select(DB::raw('count(*) as fiche_count, fiches.id, fiches.name'))
                                        ->groupBy('fiches.id', 'fiches.name')->get()->toarray();

                        foreach ($fiches as $fichet) {
                            $datapf['fiche_id'] = $fichet['id'];
                            $datapf['name'] = $fichet['name'];
                            if ($requestpost['post_id']) {
                                if ($requestpost['Categorie_produit']) {
                                    $idreq = $requestpost['Categorie_produit'];
                                    $post->catprod_id = $this->categoriepost($post, $idreq, $datapf['fiche_id']);
                                }
                                $postfiche = Postfiche::where('post_id', $requestpost['post_id'])->where('fiche_id', $fichet['id']);
                                $post = Post::find($requestpost['post_id']);
                                if (!$image && $post->media_url != null) {
                                    $data['media_url'] = null;
                                }
                                if ($postfiche->exists()) {
                                    $postfiche= $postfiche->first();
                                    $rest = $this->localpost->patch($postfiche->genre, $this->placeID,
                                            ['updateMask' => ['media.sourceUrl,media.mediaFormat,event.title_post,summary,'
                                                    .'offer.couponCode,offer.redeemOnlineUrl,offer.termsConditions,topicType,alertType'.$actioncall, ]]);
                                    if ($rest->state == 'REJECTED') {
                                        $data['state'] = $rest->state;
                                    }
                                    $msg = 'Post Modifier avec succès';
                                    $post->update($data);
                                } else {
                                    $rest = $this->localpost->create($fichet['name'], $this->placeID);
                                $data['search_url'] = $rest->searchUrl;
                                $datapf['genre'] = $rest->name;
                                   // $msg = 'Verifier Votre donne';
                                }
                            } else {
                                $rest = $this->localpost->create($fichet['name'], $this->placeID);
                                $data['search_url'] = $rest->searchUrl;
                                $datapf['genre'] = $rest->name;

                                if ($rest->state == 'REJECTED') {
                                    $data['state'] = $rest->state;
                                }
                            }
                            $datapfc[] = $datapf;
                            $dataprofil['Post']=true;
                            $dataprofil['nombrejour']=carbon::now()->toDateString();
                            profilincomplete::updateOrCreate(['fiche_id'=>$fichet['id']],$dataprofil);
                            UserController::totalprofilincomplet($fichet['id']);
                        }
                    }
                    if ($imageName) {
                        Storage::disk('public')->delete($imageName);
                        $data['media_url'] = $rest->media[0]['googleUrl'];
                    }
                    if (!$requestpost['post_id']) {
                        $msg = 'Post ajouté avec succès';
                        $post = Post::create($data);
                        foreach ($datapfc as $datp) {
                            $datas[] = ['post_id' => $post->id,
                                'fiche_id' => $datp['fiche_id'],
                                'name' => $datp['name'],
                                'genre' => $datp['genre'], ];
                                if(array_key_exists('Categorie_produit',$requestpost)){
                            
                                $idreq = $requestpost['Categorie_produit'];
                                $post->catprod_id = $this->categoriepost($post, $idreq, $datp['fiche_id']);
                                $post->update();
                            }
                        }
                        Postfiche::insert($datas);
                    } else {
                        $post->update($data);
                    }
                    $dataF['post_id'] = $post->id;
                    $dataF['modif_type'] = 'You have created Post';
                    $dataF['new_content'] = $post;
                    $dataF['user_id'] = $post->user_id;
                    Posthistorie::create($dataF);
                    if ($requestpost['columnsToIgnore']) {
                        foreach ($requestpost['columnsToIgnore'] as $tags) {
                            $datstags['name'] = $tags;
                            if (!\App\Models\Tag::where('name', $tags)->exists()) {
                                $tags = \App\Models\Tag::create($datstags);
                            } else {
                                $tags = \App\Models\Tag::where('name', $tags)->first();
                            }
                            $datapost['post_id'] = $post->id;
                            $datapost['tag_id'] = $tags->id;
                            if (!\App\Models\Posttag::where('post_id', $post->id)->where('tag_id', $tags->id)->exists()) {
                                \App\Models\Posttag::create($datapost);
                            }
                        }
                    }
                    if ($datatag) {
                        foreach ($datatag as $datp) {
                            if (!Postfichestag::where('post_id', $post->id)
                                ->where('etiquettes_id', $datp)->exists()) {
                                    $datastag[] = ['post_id' => $post->id,
                                    'etiquettes_id' => $datp['etiquette_id'],
                                    'groupe_id' => $datp['id_groupe'],
                                    ];
                                 //   Postfichestag::updateOrCreate(['post_id'=>  $post->id,'etiquettes_id'=> $datp],['groupe_id' => $datp['id_groupe']]);
                            }
                        }
                        if(!empty($datastag)){
                            Postfichestag::insert($datastag);
                        }
                        
                    }
                } catch (\Google_Service_Exception $e) {
                    return response()->json(
                                    [
                                        'success' => false,
                                        'message' => $e->getMessage(),
                                        'status' => $e->getCode(),
                                    ],
                                    $e->getCode()
                    );
                }

                return response()->json([
                            'success' => true,
                            'message' => $msg,
                            'data' => $post,
                            
                            'status' => Response::HTTP_OK,
                                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json(
                                [
                                    'success' => false,
                                    'message' => $ex->getMessage(),

                                    'status' => 421,
                                ],
                                421
                );
            }
        }
    }

    public function update(Request $request, Post $post)
    {
        try {
            $dataF['old_content'] = $post->state;
            $dataF['new_content'] = $request->typeaction;
            $dataF['user_id'] = $post->user_id;
            $dataF['post_id'] = $post->id;

            if ($request->typeaction === 'Corbeille') {
                $dataF['modif_type'] = 'Supprimer post';
                $post->state = $request->typeaction;
            }
            if ($request->typeaction === 'Restaurer') {
                $posthist = Posthistorie::where('post_id', $post->id)
                        ->where('modif_type', 'Supprimer post')
                        ->first();
                $dataF['modif_type'] = 'Restaurer post';
                $post->state = $posthist->old_content;
            }
            Posthistorie::create($dataF);
            $post->update();

            return response()->json([
                        'success' => true,
                        'message' => 'Mise a jour traité avec succes',
                        'data' => $post,
                        'status' => Response::HTTP_OK,
                            ], Response::HTTP_OK);
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
    /////
    public function deletepost(Request $request)
  //public function destroy($id)
    {
       
        try {
          if(isset($request->post_id)){
            $post_id=$request->post_id;
          } 
          if(isset($request->id_produit)){
            $post_id=$request->id_produit;
          }
            $postfiche = Postfiche::where('post_id',  $post_id)->get();
           
            foreach ($postfiche as $fiche) {
                try {
                    $this->localpost->delete($fiche->genre);
                    Postfiche::where('id', $fiche->id)->delete();
                    $dataprofil['Post']=false;
                    $dataprofil['nombrejour']=carbon::now()->toDateString();
                    profilincomplete::updateOrCreate(['fiche_id'=>$fiche->id],$dataprofil);
                    UserController::totalprofilincomplet($fiche->id);

                } catch (\Google_Service_Exception $e) {
                    return response()->json([
                            'success' => false,
                            'message' => $e->getMessage(),
                            'data' => '',
                        ], $e->getCode());
                }
            }
           Post::where('id', $post_id)->delete();
           Categoriesproduit::where('post_id', $post_id)->delete();
            return response()->json([
                        'success' => true,
                        'message' => 'Supprimer avec succées',
                        'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                        'success' => false,
                        'message' => $exception->getMessage(),
                        'status' => 500,
                            ], 500);
        }
    }

    public function destroy(Post $post)
  //public function destroy($id)
    {
        
        try {
          // $post=Post::find($id);
            $postfiche = Postfiche::where('post_id', $post->id)->get();
           
            foreach ($postfiche as $fiche) {
                try {
                    $this->localpost->delete($fiche->genre);
                    Postfiche::where('id', $fiche->id)->delete();
                } catch (\Google_Service_Exception $e) {
                    return response()->json([
                            'success' => false,
                            'message' => $e->getMessage(),
                            'data' => '',
                        ], $e->getCode());
                }
            }
            $post->delete();

            return response()->json([
                        'success' => true,
                        'message' => 'Supprimer avec succées',
                        'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                        'success' => false,
                        'message' => $exception->getMessage(),
                        'status' => 500,
                            ], 500);
        }
    }

    public function notificationpost()
    {
        try {
            $post = Posthistorie::with('post:id,name,type,genre', 'user:id,lastname,firstname')->
                        where('state', 'inactif')->get();
            $totalfiche = Fiche::leftJoin('posts', 'posts.fiche_id', '=', 'fiches.id')->
                leftJoin('posthistories', 'posthistories.post_id', '=', 'posts.id')
                ->select(DB::raw('count(*) as fiche_count,posts.fiche_id'))
                ->where('posthistories.state', 'inactif')
                ->orwhere('posthistories.user_id', auth()->user()->id)
                ->groupBy('posts.fiche_id')
                ->get();
            $totalcategorie = Fiche::leftJoin('posts', 'posts.fiche_id', '=', 'fiches.id')->
                leftJoin('posthistories', 'posthistories.post_id', '=', 'posts.id')
                ->select(DB::raw('count(*) as fiche_count,posts.fiche_id,fiches.locationName'))
                ->where('posthistories.state', 'inactif')
                ->orwhere('posthistories.user_id', auth()->user()->id)
                ->groupBy('posts.fiche_id', 'fiches.locationName')
                ->get();
            if ($post->count() > 0) {
                return response()->json([
                        'success' => true,
                        'message' => 'Liste photos',
                        'totalnotif' => $totalfiche->count(),
                        'data' => $totalcategorie,
                        'status' => 200,
                            ], 200);
            }
        } catch (QueryException $ex) {
            return response()->json([
                    'success' => false,
                    'message' => 'Désole, Post not found.',
                    'status' => 400,
                        ], 400);
        }
    }

    public function listdetails(Request $request)
    {
        try {
            $fiche_id=  $request->fiche_id;
            $listfiches = [];
            $appelaction = null;
            

            if(!is_array($fiche_id) && $fiche_id){
               
                $data= $this->listfichepost([$fiche_id]);
            }else if(is_array($fiche_id)){
                foreach($fiche_id as $fiche){
                    if($fiche['status']== true){
                        $lisid[]=$fiche['id'];
                       
                    }
                }
                
                $data= $this->listfichepost($lisid);
            }else{
                $data= $this->listfichepost($id = null);
            }
        
            

            return response()->json([
                    'success' => true,
                    'message' => 'Details post',
                    'data' => $data,
                    'status' => 200,
                        ], 200);
        } catch (QueryException $ex) {
            return response()->json([
                    'success' => false,
                    'message' => 'Désole, Post not found.',
                    'status' => 200,
                        ], 200);
        }
    }
    public static function listfichepost($fiche_id){
        $listfiches=array();
        $appelaction=null;
        $ficheslist= Fiche::query()
           ->join('franchises','franchises.id' , '=', 'fiches.franchises_id')
         ->leftJoin('ficheusers','fiches.id', '=', 'ficheusers.fiche_id' )
         
             ->join('states','states.fiche_id','=','fiches.id')
             ->where('states.isPublished','=',1)
             ->where('fiches.franchises_id', 1)
             
             //->where('fiches.state','LIKE', 'COMPLETED')
            ->where('states.isVerified','=',1)
            ->when($fiche_id,function ($query) use($fiche_id){
                $query->whereIN('fiches.id', $fiche_id);
                })
                ->select('fiches.locationName', 'fiches.name', 'ficheusers.*',  'franchises.phone as Appelaction', DB::raw('count(*) as total'))
               ->groupBy('fiches.locationName')
               ->where('ficheusers.user_id', '=', Auth()->user()->id)
             ->get();

       /* $ficheslist = Franchise::leftJoin('ficheusers', 'ficheusers.franchise_id', '=', 'franchises.id')->
        leftJoin('fiches', 'fiches.id', '=', 'ficheusers.fiche_id')
        ->select('fiches.locationName', 'fiches.name', 'ficheusers.*', 'franchises.phone as Appelaction')
        
        ->leftJoin('states', 'fiches.id', '=', 'states.fiche_id')->
        where('states.isVerified', 1)
       ->where('states.isPublished',1)
        ->when($fiche_id,function ($query) use($fiche_id){
            $query->whereIN('fiches.id', $fiche_id);
            })
        ->where('ficheusers.user_id', '=', Auth()->user()->id)->get();*/
  
    

    $fiche = $ficheslist->toarray();
    if(!$fiche_id){
    if (count($ficheslist) > 0) {
        foreach ($fiche as $list) {
            $res = ['locationName' => $list['locationName'],
        'id' => $list['id'],
        'fiche_id' => $list['fiche_id'],
        'user_id' =>  Auth()->user()->id,
        //'user_id' => $list['user_id'],
        'franchise_id' => $list['franchise_id'], 'status' => false, ];
            $appelaction = $list['Appelaction'];
            $listfiches[] = $res;
        }
    }
    

                $data = ['etiquette' => GroupeController::group_by('Name_groupe', null),
            'fiche' => $listfiches,
            'countfiche' => $ficheslist->count(),
                'Appelaction' => $appelaction];

}
else{
foreach ($fiche as $list) {

$appelaction = $list['Appelaction'];
$listfiches[] = ['locationName' => $list['locationName'],
'id' => $list['id'],
'fiche_id' => $list['fiche_id'],
'user_id' =>  Auth()->user()->id,
//'user_id' => $list['user_id'],
'franchise_id' => $list['franchise_id'], 'status' => true, ];
}
$data = [
'fiche' => $listfiches,
'countfiche' => $ficheslist->count(),
'Appelaction' => $appelaction,
];
}
return $data;
    }

    public function detailspostgmb(Request $request)
    {
        try {
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
            $franchise_id = $request->header('franchise');

            $listpost = Post::leftJoin('categoriesproduits', 'categoriesproduits.id', '=', 'posts.catprod_id')
                ->select('categoriesproduits.displayName as Categorie_produit', 'posts.*');
            if ($request->type === 'Tous') {
                $listpost = $listpost->whereNotIn('state', ['Corbeille', ''])->limit(50);
            } elseif ($request->type === 'Corbeille') {
                $listpost = $listpost->where('state', $request->type)->limit(50);
            } else {
                $listpost = $listpost->where('type', $request->type)->whereNotIn('state', ['Corbeille'])->limit(50);
            }
            $listpost = $listpost->orderByDesc('id')->get();

            return response()->json([
                    'success' => true,
                    'message' => 'List des posts',
                    'data' => ['TousPost' => $this->listpost($listpost, null, $franchise_id)],
                    'status' => Response::HTTP_OK,
                        ], Response::HTTP_OK);
        } catch (QueryException $ex) {
            return response()->json([
                    'success' => false,
                    'message' => 'Auccun List.',
                    'status' => 400,
                        ], 400);
        }
    }



    public function galerie(Request $request)
    {
        $photos=[];
        try {
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
            $franchise_id = $request->header('franchise');

            $listpost = Post::whereNotIn('state', ['Corbeille', ''])
            ->whereNotNull('media_url')
            ->orderByDesc('id')->get();


            foreach($listpost as $post){
                $photos[]=$post['media_url'];
            }

            return response()->json([
                    'success' => true,
                    'message' => 'Galérie photo',
                    'data' => $photos,
                    'status' => Response::HTTP_OK,
                        ], Response::HTTP_OK);
        } catch (QueryException $ex) {
            return response()->json([
                    'success' => false,
                    'message' => 'Auccun List.',
                    'status' => 400,
                        ], 400);
        }
    }
    public function bycategory(Request $request) {

        try{
            $search=array();
            $photos=array();
            $photos=  Photo::select('category','file','id',DB::raw('count(*) as fiche_count'))
               // ->where('user_id', auth()->user()->id)
                ->groupBy('category')
                ->get();
            $s = $request->search;
            if ($s) {
                $search = Photo::select('category','file','id')
                        ->where('category', 'LIKE', '%' . $s . '%')
                        ->orwhere('file', 'LIKE', '%' . $s . '%')
                      ->orderBy('id', 'desc')
                        ->get();
                foreach ($search as $sear){
                  $category =$sear["category"];
                  $file=$sear["file"];
                  $categorytab[]= ['file'=>$sear["file"],"id"=>$sear["id"]];
                }
                $tab[]=['category'=>$category,"file"=>$file,"list"=>$categorytab];
                    return response()->json([
                                'success' => true,
                                'message' => $tab,
                                'status' => 200
                                    ], 200);
                
            }
             return response()->json([
                                'success' => true,
                                'message' => $photos,
                                'status' => 200
                                    ], 200);
            
        } catch (Exception $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, photo not found.',
                        'status' => 400
                            ], 400);
        }
    }
    public function Listpostgmb()
    {
        try {
            $post = Post::whereNotIn('state', ['Corbeille', ''])->orderByDesc('id')->skip(1)->take(1)->get();
            $listpostprograme = Post::where('programmed_date', '>', Carbon::now())->orderByDesc('id')->get()->toarray();
            $franchise_id = 1;

            return response()->json([
                    'success' => true,
                    'message' => 'List des posts',
                    'datadernierpost' => ['TousPost' => $this->listpost($post, 'POST', $franchise_id)],
                    'datapostprogramme' => ['TousPost' => $this->listpost($listpostprograme, 'POST', $franchise_id)],
                    'status' => Response::HTTP_OK,
                        ], Response::HTTP_OK);
        } catch (QueryException $ex) {
            return response()->json([
                    'success' => false,
                    'message' => 'Désole, Post not found.',
                    'status' => 400,
                        ], 400);
        }
    }

    public function categoriepost($post, $idreq, $idfiche)
    {
        try {
            $catprod = Categoriesproduit::where('displayName', $idreq)
                ->where('fiche_id', $idfiche);
            if (($catprod->count() === 0)) {
                $datas['displayName'] = $idreq;
                $datas['fiche_id'] = $idfiche;
                $datas['post_id'] = $post->id;
                $catprodts = Categoriesproduit::create($datas);

                $catprod_id = $catprodts->id;
            } else {
                $catprod = $catprod->get()->toarray();

                $catprod_id = $catprod[0]['id'];
            }

            return $catprod_id;
        } catch (QueryException $ex) {
            return $catprod_id;
        }
    }

    public function listpost($listpost, $type, $franchise_id)
    {
        $data = [];
        $state = [];
        $listefiche = [];
        $fiche_id = [];
        $listgroupe = [];
        $datapath = [];
        $dataobjet = [];
        $datepostprogramme = null;
        $Dateprogramme = null;
        $Lienaction = null;
        $nbcountetiquette=0;
        try {
            foreach ($listpost as $post) {
                $listfichesexist = Postfiche::Join('fiches', 'fiches.id', '=', 'postfiches.fiche_id')
                ->where('fiches.state','=','COMPLETED')
                 ->leftJoin('states', 'fiches.id', '=', 'states.fiche_id')
                 ->where('states.isPublished',1)
                ->where('postfiches.post_id', $post['id'])->exists();
                if($listfichesexist){
                $date = $post['updated_at'];
                $eventstartdate = $post['event_start_date'];
                $eventenddate = $post['event_end_date'];
                if ($post['state'] === 'Programme') {
                    $Dateprogramme = Carbon::parse($post['programmed_date'])->translatedFormat('j F Y').' à '.Carbon::parse($post['programmed_date'])->translatedFormat('h').'h'.Carbon::parse($post['programmed_date'])->translatedFormat('i');
                    $datepostprogramme = Carbon::parse($post['programmed_date'])->translatedFormat('Y-m-d');
                }
                if ($post['topic_type'] === 'EVENT') {
                    $event_start_date = Carbon::parse($eventstartdate)->translatedFormat('j F Y').' à '.Carbon::parse($eventstartdate)->translatedFormat('h').'h'.Carbon::parse($eventstartdate)->translatedFormat('i');

                    $event_end_date = Carbon::parse($eventenddate)->translatedFormat('j F Y').' à '.Carbon::parse($eventenddate)->translatedFormat('h').'h'.Carbon::parse($eventenddate)->translatedFormat('i');
                }

                $listTags = Post::rightJoin('posttags', 'posttags.post_id', '=', 'posts.id')
                    ->rightJoin('tags', 'tags.id', '=', 'posttags.tag_id')
                    ->select('tags.name as title_post')
                    ->where('posts.id', $post['id'])
                    ->groupBy('tags.name')
                    ->where('posts.user_id', auth()->user()->id)
                    ->get();
                $listfiches = Postfiche::where('postfiches.post_id', $post['id'])
                            ->Join('fiches', 'fiches.id', '=', 'postfiches.fiche_id')
                            ->select('fiches.locationName', 'fiches.id')
                            ->where('fiches.franchises_id', $franchise_id)
                            ->where('fiches.state', '=', 'COMPLETED')
                            ->groupBy('fiches.locationName', 'fiches.id')
                            ->get()->toarray();

                foreach ($listfiches as $fiche) {
                    $fiche_id[] = $fiche['id'];
                    $listefiche[] = ['locationName' => $fiche['locationName'],
                    'fiche_id' => $fiche['id'],
                    'etat' => true, ];
                }
                $statistiques = Postfiche::select(Db::raw('SUM(localPostViewsSearch) AS localPostViewsSearch,SUM(localPostActions) AS localPostActions'))->where('post_id', $post['id'])->get()->toarray();
                if ($statistiques) {
                    $state = ['vues' => $statistiques[0]['localPostViewsSearch'], 'clics' => $statistiques[0]['localPostActions']];
                }
             

                if ($post['media_url']) {
                    $i = 0;
                    $datapath['post_listImages'] = [['action' => '', 'objet' => [['attachement_id' => $i, 'attachement_nom' => $post['media_url']]]]];
                }
                if ($post['action_type'] != 'CALL') {
                    $Lienaction = $post['action_url'];
                }
                if ($type) {
                    $listgroupe = GroupeController::group_etiq('Name_groupe', $fiche_id, $post['id']);
                    $data[] = ['etat' => $post['state'],
                    'post_id' => $post['id'],
                    'title_post' => $post['name'],
                    'description_post' => $post['summary'],
                    'totalfiche' => count($listfiches),
                    'dateprogramme' => $Dateprogramme,
                    'date' => Carbon::parse($date)->translatedFormat('j F Y'),
                    'listTags' => $listTags,
                    'listgroupe' => $listgroupe,
                    'fiche' => $this->fichepost($post['id'], 'POST'),
                    'statistique' => $state,
                ];
                } else {
                    $listgroupe = GroupeController::group_post('Name_groupe',  $fiche_id, $post['id']);

                    $listgroupes=array();
                    
                    if ($post['type_envoi'] === 'Envoi groupé') {
                        $nbcountetiquette=1;
                        $etiquette = Postfichestag::join('groupes', 'groupes.id', '=', 'postfichestags.groupe_id')
                        ->join('etiquettes', 'postfichestags.etiquettes_id', '=', 'etiquettes.id')
                        ->where('postfichestags.post_id', '=', $post['id'])
                        ->get(['groupes.color','groupes.name as Name_groupe', 'groupes.id as id_groupe','etiquettes.name as Nom_etiquette', 'etiquettes.id as et_id'])->toArray();
                        foreach ($etiquette as $key => $value) {
                      
                            $listgroupes[] = array("id_groupe" => $value['id_groupe'],
                            "Name_groupe" => $value['Name_groupe'],'Nom_etiquette' => $value['Nom_etiquette'],
                             'status' => true,'etiquettegroupe'=> $value['et_id'],
                            "couleur_groupe" => $value['color'], 
                            'etatActivat'=>true);
                        }
                    } else{
                        $nbcountetiquette=0;
                    }
                    $data[] = ['Etat' => $post['state'],
                   
                    'Type' => $post['type'],
                    'post_id' => $post['id'],
                    'title_post' => $post['name'],
                    'description_post' => $post['summary'],
                    'totalfiche' => count($listfiches),
                    'datepost' => Carbon::parse($date)->translatedFormat('Y-m-d'),
                    'date_programme' => $datepostprogramme,
                    'Dateprogramme' => $Dateprogramme,
                    'nbcountetiquette'=>$nbcountetiquette,
                    'Date' => Carbon::parse($date)->translatedFormat('j F Y').' à '.Carbon::parse($date)->translatedFormat('h').'h'.Carbon::parse($date)->translatedFormat('i'),
                    'Categorie_produit' => $post['Categorie_produit'],
                    'id_Categorie' => $post['catprod_id'],
                    'Prix_minimal' => $post['prix_min'],
                    'Prix_produit' => $post['prix_produit'],
                    'Prix_maximal' => $post['prix_max'],
                    'type_envoi' => $post['type_envoi'],
                    'media_type' => $post['media_type'],
                    'media_url' => $datapath,
                  // 'media_url' =>['post_listImages'=>[ ['action' => '', 'objet' => ["attachement_id" => 0, "attachement_nom" => $post['media_url']]]]],
                    'photo_post' => $post['media_url'],
                    'action_type' => $post['action_type'],
                    'Lienaction' => $Lienaction,
                    'code_post' => $post['coupon_code'],
                    'liens_post' => $post['redeem_online_url'],
                    'action_type' => $post['action_type'],
                    'condition_post' => $post['terms_conditions'],
                    'calltoaction' => ['calltoaction' => $post['calltoaction'], 'actiontype' => $post['action_type']],
                    'date_debut' => $eventstartdate,
                    'time_debut' => $post['event_start_time'],
                    'date_fin' => $eventenddate,
                    'time_fin' => $post['event_end_time'],
                    'listTags' => $listTags,
                    'listgroupe' => $listgroupe,
                    'listgroupes' => $listgroupes,
                    'fiche' => $this->fichepost($post['id'], null),
                    'statistique' => $state,
                    'topicType' => $post['topic_type'],
                ];
                }
            }
       }

            return $data;
        } catch (QueryException $ex) {
            return $data;
        }
    }

    public function datepost($date, $datedebut)
    {
        $datedebut->setDay(Carbon::parse($date)->translatedFormat('j'));
        $datedebut->setYear(Carbon::parse($date)->translatedFormat('Y'));
        $datedebut->setMonth(Carbon::parse($date)->Format('m'));

        return $datedebut;
    }

    public function detailstimeofday($date)
    {
        $timeofday = Helper::TimeOfDayAction();
        $timeofday->setHours(Carbon::parse($date)->Format('H'));
        $timeofday->setMinutes(Carbon::parse($date)->Format('i'));
        $timeofday->setSeconds(Carbon::parse($date)->Format('s'));

        return $timeofday;
    }

    public function fichepost($id, $type)
    {
        $status = false;
        $res = null;
        $listfiche = [];
        $i = 0;
        try {
            if ($type) {
                $listfichest = Franchise::leftJoin('ficheusers', 'ficheusers.franchise_id', '=', 'franchises.id')->
                leftJoin('fiches', 'fiches.id', '=', 'ficheusers.fiche_id')
               ->where('ficheusers.user_id', auth()->user()->id)
                ->leftJoin('postfiches', 'fiches.id', '=', 'postfiches.fiche_id')
        ->leftJoin('states', 'fiches.id', '=', 'states.fiche_id')->
       where('states.isVerified', 1)
        ->select('fiches.locationName', 'fiches.name', 'ficheusers.*', DB::raw('count(*) as total'))
        
              
                ->groupBy('fiches.locationName')
               ->where('postfiches.post_id', $id)
                ->get()->toarray();
                foreach ($listfichest as $list) {
                    $res = ['locationName' => $list['locationName'],
                    'id' => $list['id'],
                    'fiche_id' => $list['fiche_id'],
                    'user_id' => $list['user_id'],
                    'franchise_id' => $list['franchise_id'], 'status' => true, ];
                    $listfiche[] = $res;
                }

                return $listfiche;
            } else {
                $fiche =  Franchise::leftJoin('ficheusers', 'ficheusers.franchise_id', '=', 'franchises.id')->
                leftJoin('fiches', 'fiches.id', '=', 'ficheusers.fiche_id')
               
                ->where('ficheusers.user_id', auth()->user()->id)
                ->leftJoin('postfiches', 'fiches.id', '=', 'postfiches.fiche_id')
        ->leftJoin('states', 'fiches.id', '=', 'states.fiche_id')->
       where('states.isVerified', 1)
       ->select('fiches.locationName', 'fiches.name', 'ficheusers.*', DB::raw('count(*) as total'))
       
         
       ->groupBy('fiches.locationName')
                ///->orwhere('states.isPublished',1)
              ->get()->toarray();
                foreach ($fiche as $list) {
                    $listfiches = Postfiche::where('postfiches.post_id', $id)
                        ->where('postfiches.fiche_id', '=', $list['fiche_id']);

                    if ($listfiches->count() > 0) {
                        $status = true;
                    } else {
                        $status = false;
                    }
                    $res = ['locationName' => $list['locationName'],
                    'id' => $list['id'],
                    'fiche_id' => $list['fiche_id'],
                    'user_id' => $list['user_id'],
                    'franchise_id' => $list['franchise_id'], 'status' => $status, ];
                    $listfiche[] = $res;
                }

                //return $listfiche;
                $collection = collect($listfiche);
                $sorted = $collection->SortByDesc('status');
    
                return $listfiche = $sorted->values()->all();
            }
        } catch (QueryException $ex) {
            return $listfiche;
        }
    }
        public static function numbreproduict($idfiche){
            $postsPrograme = Postfiche::where('postfiches.fiche_id', '=',$idfiche)
            ->Join('posts', 'posts.id', '=', 'postfiches.post_id')->where('topic_type', 'PRODUCT')->count();
            return $postsPrograme;
        }
   
}
