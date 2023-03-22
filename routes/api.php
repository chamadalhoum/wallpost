<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
  |--------------------------------------------------------------------------
  | API Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register API routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | is assigned the "api" middleware group. Enjoy building your API!
  |
 */

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::delete('post/{id}', [App\Http\Controllers\PostController::class, 'destroy']);
Route::get('ensec', [App\Http\Controllers\SecurityController::class, 'encryption']);
Route::post('secure', [App\Http\Controllers\SecurityController::class, 'decryption']);
Route::post('decrypt', [App\Http\Controllers\SecurityController::class, 'decrypt']);
Route::middleware('request_encode')->post('login', [App\Http\Controllers\ApiController::class, 'authenticate']);
Route::middleware('request_encode')->post('resetpassword', [App\Http\Controllers\ResetPwdReqController::class, 'reqForgotPassword']);
Route::middleware('request_encode')->post('updatepassword', [App\Http\Controllers\UpdatePwdController::class, 'updatePassword']);
Route::group(['middleware' => ['request_encode', 'jwt.verify']], function () {
    // Route::get('document/{id}/download', 'App\Http\Controllers\DocumentController@download')->name('documents.download');
    Route::post('deletepost', [App\Http\Controllers\PostController::class, 'deletepost']);
    Route::post('refresh', [App\Http\Controllers\ApiController::class, 'refresh']);
    Route::apiResource('franchises', 'App\Http\Controllers\FranchiseController');
    Route::post('franchise', 'App\Http\Controllers\FranchiseController@franchise')->name('franchise.franchise');
    Route::get('franchise_classify', 'App\Http\Controllers\FranchiseController@franchise_classify')->name('franchise.franchise_classify');
    //  Route::get('franchise', 'App\Http\Controllers\FranchiseController@franchise01')->name('franchise.franchise01');
    // Route::get('fichebyid', 'App\Http\Controllers\FranchiseController@fichebyid')->name('franchise.fichebyid');
    // profil
    Route::apiResource('user', 'App\Http\Controllers\UserController');
    Route::post('indexadmin', 'App\Http\Controllers\UserController@indexadmin');

    Route::post('listprofil', [App\Http\Controllers\UserController::class, 'listprofil']);
    Route::post('userdelete/{id}', [App\Http\Controllers\UserController::class, 'destroy']);

    Route::post('register', [App\Http\Controllers\UserController::class, 'store']);

    // end profil
    // start gere utilisateur
    Route::post('updaterole', 'App\Http\Controllers\FicheuserController@updaterole');
    Route::post('userfiche', 'App\Http\Controllers\FicheuserController@userfiche');
    Route::post('dissocierfiche', 'App\Http\Controllers\FicheuserController@dissocierfiche');
    Route::post('deletefiche', 'App\Http\Controllers\FicheuserController@deletefiche');
    Route::get('topictype', 'App\Http\Controllers\TypepostController@topictype');
    //end gere utilisateur

    // startuser
    ////start tableau de bord
    Route::post('profilincomplet', 'App\Http\Controllers\UserController@profilincomplet')->name('user.profilincomplet');
    Route::post('profilstat', 'App\Http\Controllers\UserController@profilstat')->name('user.profilstat');
    
    Route::post('suggestion', 'App\Http\Controllers\UserController@suggestion')->name('user.suggestion');
    Route::post('suggestionbyid', 'App\Http\Controllers\UserController@suggestionbyid')->name('user.suggestionbyid');
    Route::post('detailsuggestion', 'App\Http\Controllers\UserController@detailsuggestion')->name('user.detailsuggestion');
    Route::post('performance', [App\Http\Controllers\StatistiqueController::class, 'performance']);
    Route::post('updateficherendezvous', [App\Http\Controllers\AttributeController::class, 'updateficherendezvous']);
    Route::post('updatefichephone', [App\Http\Controllers\FicheController::class, 'updatefichephone']);
    Route::post('updateficheservice', [App\Http\Controllers\CategoriesController::class, 'updateficheservice']);
    Route::post('updatefichehoraire', [App\Http\Controllers\FichehourController::class, 'updatefichehoraire']);
    Route::post('updatefichehorairexecep', [App\Http\Controllers\FichehourController::class, 'updatefichehorairexecep']);
    Route::post('updateficheurlsite', [App\Http\Controllers\FicheController::class, 'updateficheurlsite']);
    Route::post('listeJourferies', [App\Http\Controllers\ParamaterController::class, 'listeJourferies']);
    Route::post('storelocatore', 'App\Http\Controllers\StateController@storelocatore')->name('state.storelocatore');
    Route::post('horairebyfiche', [App\Http\Controllers\FichehourController::class, 'horairebyfiche']);
    Route::post('validenotifs', [App\Http\Controllers\ParamaterController::class, 'validenotifs']);

    Route::post('filtre', [App\Http\Controllers\ParamaterController::class, 'filtre']);
    Route::post('ficheadministre', [App\Http\Controllers\ParamaterController::class, 'ficheadministre']);
    Route::get('hourgeneral', [App\Http\Controllers\FichehourController::class, 'hourgeneral']);
    Route::get('hourgeneralexp', [App\Http\Controllers\FichehourController::class, 'hourgeneralexp']);

    ////end tableau de bord
    //start raccourci
    Route::post('raccourci', [App\Http\Controllers\RaccourciController::class, 'index']);
    Route::post('raccourcifiche', [App\Http\Controllers\RaccourcificheController::class, 'store']);
    //end raccourci
    // start photo
    Route::post('updatefichephoto', [App\Http\Controllers\PhotoController::class, 'updatefichephoto']);
    Route::apiResource('photo', 'App\Http\Controllers\PhotoController');
    Route::post('photo_stats', 'App\Http\Controllers\PhotoController@photo_stats')->name('photo.photo_stats');
    Route::get('photo_missing', 'App\Http\Controllers\PhotoController@photo_missing')->name('photo.photo_missing');
    Route::post('upload_photo', 'App\Http\Controllers\PhotoController@upload_photo')->name('photo.upload_photo');
    Route::post('get_Gallery', 'App\Http\Controllers\PhotoController@get_Gallery')->name('photo.get_Gallery');
    Route::post('add_category', 'App\Http\Controllers\PhotoController@add_category')->name('photo.add_category');
    Route::post('delete_photo', 'App\Http\Controllers\PhotoController@delete_photo')->name('photo.delete_photo');
    Route::post('avertir_photo', 'App\Http\Controllers\PhotoController@avertir_photo')->name('photo.avertir_photo');
    Route::get('classify_photo', 'App\Http\Controllers\PhotoController@classify_photo')->name('photo.classify_photo');
    Route::post('photo_autocompele', 'App\Http\Controllers\PhotoController@photo_autocompele')->name('photo.photo_autocompele');
    Route::post('add_photo_autocompele', 'App\Http\Controllers\PhotoController@add_photo_autocompele')->name('photo.add_photo_autocompele');
    Route::post('dernier_photo', 'App\Http\Controllers\PhotoController@dernier_photo')->name('photo.dernier_photo');
    Route::get('get_localisation_service', 'App\Http\Controllers\PhotoController@get_localisation_service')->name('photo.get_localisation_service');
    Route::post('add_photo', 'App\Http\Controllers\PhotoController@add_photo')->name('photo.add_photo');
    Route::post('signalier_photo', 'App\Http\Controllers\PhotoController@signalier_photo')->name('photo.signalier_photo');
    Route::post('get_count_photo', [App\Http\Controllers\PhotoController::class, 'get_count_photo']);
    
    Route::post('sidbar_ficheadmin', [App\Http\Controllers\PhotoController::class, 'sidbar_ficheadmin']);
    
    //end

    // START NOTIFICATION
    Route::get('notificationphoto', [App\Http\Controllers\ParamaterController::class, 'notificationphoto']);
    Route::get('notificationfiche', [App\Http\Controllers\ParamaterController::class, 'notificationfiche']);
    Route::post('notificationbyfiche', [App\Http\Controllers\ParamaterController::class, 'notificationbyfiche']);
  
    // END NOTIFICATION
    Route::apiResource('role', 'App\Http\Controllers\RoleController');
    Route::apiResource('etiquette', 'App\Http\Controllers\EtiquetteController');
    Route::apiResource('groupe', 'App\Http\Controllers\GroupeController');
    Route::apiResource('etiquettegroupe', 'App\Http\Controllers\EtiquetgroupeController');
    Route::get('groupetiquette', 'App\Http\Controllers\GroupeController@groupetiquette')->name('groupe.groupetiquette');
    Route::post('deplacement', 'App\Http\Controllers\EtiquetgroupeController@deplacement')->name('etiquettegroupe.deplacement');
    Route::post('deletetiquette', 'App\Http\Controllers\EtiquetgroupeController@delete')->name('etiquettegroupe.delete');
    Route::post('deletegroupe', 'App\Http\Controllers\GroupeController@deletegroupe')->name('groupe.deletegroupe');
    Route::post('restaurer', 'App\Http\Controllers\EtiquetgroupeController@restaurer')->name('etiquettegroupe.restaurer');
    Route::post('deletetdefinitivement', 'App\Http\Controllers\EtiquetgroupeController@deletetdefinitivement')->name('etiquettegroupe.deletetdefinitivement');
    Route::get('etiquettegroupe/{idfiche}/byfiche', 'App\Http\Controllers\EtiquetgroupeController@byfiche')->name('etiquettegroupe.byfiche');
    Route::get('fiches', 'App\Http\Controllers\FicheController@fiche')->name('fiche.fiche');
    Route::get('nombrefiche/{id}', 'App\Http\Controllers\GroupeController@nombrefiche')->name('groupe.nombrefiche');
    Route::apiResource('fichehours', 'App\Http\Controllers\FichehourController');
    Route::apiResource('horaires', 'App\Http\Controllers\MorehoursController');
    Route::post('deletehoraires', 'App\Http\Controllers\MorehoursController@deletehoraire')->name('horaires.deletehoraire');
    Route::apiResource('historiquehours', 'App\Http\Controllers\FicheHourhistoriqueController');

    Route::get('historiquehours/{id}/fiche', 'App\Http\Controllers\FicheHourhistoriqueController@fiche')->name('historiquehours.fiche');
   
    //start post
    Route::apiResource('post', 'App\Http\Controllers\PostController');
    Route::get('post_classify', 'App\Http\Controllers\PostController@post_classify')->name('post.post_classify');
    Route::get('last_posts', 'App\Http\Controllers\PostController@last_posts')->name('post.last_posts');
    Route::post('produit', 'App\Http\Controllers\PostController@produit')->name('post.produit');
    Route::post('postgmb', 'App\Http\Controllers\PostController@postgmb')->name('post.postgmb');

    Route::post('detailspostgmb', 'App\Http\Controllers\PostController@detailspostgmb')->name('post.detailspostgmb');
    Route::post('listdetails', 'App\Http\Controllers\PostController@listdetails')->name('post.listdetails');
    Route::post('listcategory', [App\Http\Controllers\CategoriesproduitController::class, 'listcategory']);
    Route::post('listbycategory', [App\Http\Controllers\CategoriesproduitController::class, 'listbycategory']);
    Route::get('Listpostgmb', 'App\Http\Controllers\PostController@Listpostgmb')->name('post.Listpostgmb');
    Route::get('dernierpost', 'App\Http\Controllers\PostController@dernierpost')->name('post.dernierpost');
    Route::apiResource('posthistorique', 'App\Http\Controllers\PosthistorieController');
    Route::post('galerie', 'App\Http\Controllers\PostController@bycategory')->name('post.bycategory');
    // end post
    Route::get('notificationpost', 'App\Http\Controllers\PostController@notificationpost')->name('post.notificationpost');
    Route::apiResource('useraction', 'App\Http\Controllers\UseractionController');
    Route::apiResource('avis', 'App\Http\Controllers\AviController');
    Route::get('notreponduneg', 'App\Http\Controllers\AviController@notreponduneg')->name('avis.notreponduneg');
    Route::get('reponduneg', 'App\Http\Controllers\AviController@reponduneg')->name('avis.reponduneg');
    //aviiis
    Route::post('global', 'App\Http\Controllers\AviController@global')->name('avis.global');
    Route::post('globalbyid', 'App\Http\Controllers\AviController@globalbyid')->name('avis.globalbyid');
    Route::post('avis_negatif', 'App\Http\Controllers\AviController@avisnegatif')->name('avis.negatif');
    Route::post('avis_postif', 'App\Http\Controllers\AviController@postif')->name('avis.postif');
    Route::post('reply_update', 'App\Http\Controllers\AviController@reply_update')->name('avis.reply_update');
    Route::post('All_reviews', 'App\Http\Controllers\AviController@All_reviews')->name('avis.All_reviews');
    Route::post('Review_autocompele', 'App\Http\Controllers\AviController@Review_autocompele')->name('avis.Review_autocompele');
    Route::post('gloabalrep', 'App\Http\Controllers\AviController@gloabalrep')->name('avis.gloabalrep');

    Route::post('avis_wording', 'App\Http\Controllers\AviController@avis_wording')->name('avis.avis_wording');
    Route::post('avis_wording_negatif', 'App\Http\Controllers\AviController@avis_wording_negatif')->name('avis.avis_wording_negatif');
    Route::post('avis_wording_positif', 'App\Http\Controllers\AviController@avis_wording_positif')->name('avis.avis_wording_positif');
    Route::get('avis_classement', 'App\Http\Controllers\AviController@avis_classement')->name('avis.avis_classement');

    //endavis

    Route::apiResource('state', 'App\Http\Controllers\StateController');
    Route::get('verified', 'App\Http\Controllers\StateController@verified')->name('state.verified');
    Route::get('demandeacces', 'App\Http\Controllers\StateController@demandeacces')->name('state.demandeacces');
    Route::apiResource('avisreponse', 'App\Http\Controllers\AvisreponseController');
    Route::apiResource('documents', 'App\Http\Controllers\DocumentController');
    Route::post('document', 'App\Http\Controllers\DocumentController@indexpost')->name('documents.indexpost');
    Route::apiResource('acce', 'App\Http\Controllers\AcceController');
    Route::apiResource('attribute', 'App\Http\Controllers\AttributeController');
    Route::apiResource('categorie', 'App\Http\Controllers\CategoriesController');
    Route::post('categorieup', [App\Http\Controllers\CategoriesController::class, 'categorieup']);
    Route::post('delete', 'App\Http\Controllers\CategoriesController@delete')->name('categorie.delete');
    Route::apiResource('posttags', 'App\Http\Controllers\PosttagController');
    Route::apiResource('tags', 'App\Http\Controllers\TagController');
    Route::apiResource('statistique', 'App\Http\Controllers\StatistiqueController');
    Route::apiResource('fiche', 'App\Http\Controllers\FicheController');
    Route::get('fiche/codegmb', 'App\Http\Controllers\FicheController@download')->name('fiche.codegmb');
    Route::get('infomaps', 'App\Http\Controllers\FicheController@infomaps')->name('fiche.infomaps');
    Route::post('relancemailgmb', [App\Http\Controllers\FicheController::class, 'relancecodegmb']);
    Route::post('listficheencours', [App\Http\Controllers\StateController::class, 'listficheencours']);
    Route::get('etatfiche', 'App\Http\Controllers\StateController@etatfiche')->name('state.etatfiche');
    // Route::get('ficheadministre', [App\Http\Controllers\FicheController::class, 'ficheadministre']);
    //*  start Service
    Route::apiResource('service', 'App\Http\Controllers\ServiceController');
    Route::get('servicecategorie', 'App\Http\Controllers\ServiceController@listeserivcebycat')->name('service.listeserivcebycat');
    Route::post('listeserivce', 'App\Http\Controllers\ServiceController@listeserivce')->name('service.listeserivce');
    //*  end Service
    //  start attribute
    Route::post('gmb-attribute', [App\Http\Controllers\AttributeController::class, 'attribute']);
    Route::post('listattributs', [App\Http\Controllers\AttributeController::class, 'listattributs']);
    //*  end //*  start Service

    // Gmb
    Route::get('gmb-categories', [App\Http\Controllers\GoogleController::class, 'categories']);
    Route::get('categoriesmigration', [App\Http\Controllers\GoogleController::class, 'categories']);
    Route::get('gmb-accounts', [App\Http\Controllers\GoogleController::class, 'accounts']);
    Route::post('gmb-associateadresse', [App\Http\Controllers\GoogleController::class, 'associateadresse']);
    Route::post('gmb-horaire', [App\Http\Controllers\GoogleController::class, 'listsupplement']);
    Route::post('gmb-createfiche', [App\Http\Controllers\FicheController::class, 'createfiche']);
    Route::post('gmb-adressememe', [App\Http\Controllers\GoogleController::class, 'adressememe']);
    Route::get('gmb-etablissement', [App\Http\Controllers\GoogleController::class, 'gerefiche']);
    Route::post('gmb-googlelocation', [App\Http\Controllers\GoogleController::class, 'googlelocation']);
    Route::post('gmb-address', [App\Http\Controllers\GoogleController::class, 'address']);
    Route::post('gmb-locality', [App\Http\Controllers\GoogleController::class, 'locality']);
    Route::post('codegoogle', [App\Http\Controllers\StateController::class, 'verifipin']);
    Route::post('gmb-notification', [App\Http\Controllers\StateController::class, 'verifipin']);
    Route::post('gmb-updatefiche', [App\Http\Controllers\GoogleController::class, 'UpdateGoogle']);
    Route::apiResource('pays', 'App\Http\Controllers\PayController');
    Route::get('listhoraire', [App\Http\Controllers\GoogleController::class, 'horaire']);
    Route::post('zonedesservies', [App\Http\Controllers\ServiceareaController::class, 'zonedesservies']);
    Route::apiResource('servicearea', 'App\Http\Controllers\ServiceareaController');
    Route::get('associate-adresse', [App\Http\Controllers\GoogleController::class, 'associateadresse']);
    //end gmb
});

Route::post('/decrypt', function (Request $request) {
    try {
        $crypto = new Crypto();
        $requestData = json_decode($request->getContent(), 1);

        $requestA = $crypto->decryp($requestData['data']);

        $requestA = json_decode($requestA->getContent(), 1);

        if ($requestA['status'] == 200) {
            $data = json_decode($requestA['message'], 1);

            return response()->json([
    $data,
], 200);
        } else {
            $requestA = json_decode($requestA, 1);

            return response()->json([
            'success' => false,
            'message' => $requestA['message'],
            'status' => 400,
        ], 400);
        }
    } catch (\Throwable $th) {
        print_r($th->getMesaage());
    }
});
