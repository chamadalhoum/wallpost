<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helper\Helper;
use App\Models\Statistique;
use App\Models\Postfiche;
use App\Models\Fiche;
use App\Models\Post;
class CronPostMybusines implements ShouldQueue {

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct() {
        $mybusinessService = Helper::GMB();
        $placeID = Helper::GMBLOCATIONPOST();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
       
      
       
        $localpost = $mybusinessService->accounts_locations_localPosts;
     
                   $fiche = Fiche::all();
      
        foreach ($fiche as $listfiche) {
             //  $nextToken = null;
            // $locationsList = ($nextToken != null) ? $localpost->listAccountsLocationsLocalPosts("accounts/108337422416691105497/locations/14370789834415068299", array('pageSize' => 100, 'pageToken' => $localpost->nextPageToken)) : $localpost->listAccountsLocationsLocalPosts("accounts/108337422416691105497/locations/14370789834415068299", array('pageSize' => 100));
         ///  $nextToken = $localpost->nextPageToken ? $localpost->nextPageToken : null;
            
             $locationsList = $localpost->listAccountsLocationsLocalPosts("accounts/108337422416691105497/locations/14370789834415068299");     ///  $nextToken = $localpost->nextPageToken ? $localpost->nextPageToken : null;
           PRINT_R($locationsList);EXIT;
             foreach ($locationsList["localPosts"] as $local) {
                  
            if ($local['callToAction']) {

                $data['action_type'] = $local['callToAction']['actionType'];
                switch ($local['callToAction']['actionType']) {

                    case "LEARN_MORE":
                        $calltoactions = "En savoir plus";
                        break;
                    case "ACTION_TYPE_UNSPECIFIED":
                        $calltoactions = "Aucun";
                        break;
                    case "BOOK":
                        $calltoactions = "Réserver";
                        break;
                    case "ORDER":
                        $calltoactions = "Commande en ligne";

                        break;
                    case "SHOP":
                        $calltoactions = "Acheter";

                        break;
                    case "SIGN_UP":
                        $calltoactions = "S'inscrire";

                        break;
                    case "CALL":
                        $calltoactions = "Appeler";
                        break;
                }

                $data['calltoaction'] = $calltoactions;
  
   
                $data['action_url'] = $local['callToAction']['url'];
   
                
            }
     
            $data['summary'] = $local['summary'];
            $data['search_url'] = $local['searchUrl'];
            $data['topic_type'] = $local['topicType'];
            $data['created_at'] = $local['createTime'];
            $data['updated_at'] = $local['updateTime'];
                 
            if ($local['event']) {
                $data['name'] = $local['event']['title'];
                $data['event_start_date'] = $local["event"]["schedule"]["startDate"]['year'] . '-' .
                        $local["event"]["schedule"]["startDate"]['month'] . '-' .
                        $local["event"]["schedule"]["startDate"]['day'];
                $data['event_start_time'] = $local["event"]["schedule"]["startTime"]['hours'] . ':' .
                        $local["event"]["schedule"]["startTime"]['minutes'] . ':' .
                        $local["event"]["schedule"]["startTime"]['seconds'];
                $data['event_end_time'] = $local["event"]["schedule"]["startTime"]['hours'] . ':' .
                        $local["event"]["schedule"]["startTime"]['minutes'] . ':' .
                        $local["event"]["schedule"]["startTime"]['seconds'];
                $data['event_end_date'] = $local["event"]["schedule"]["startDate"]['year'] . '-' .
                        $local["event"]["schedule"]["startDate"]['month'] . '-' .
                        $local["event"]["schedule"]["startDate"]['day'];
            }
            if ($local['offer']) {

                $data['coupon_code'] = $local['callToAction']['couponCode'];
                $data['redeem_online_url'] = $local['callToAction']['redeemOnlineUrl'];
                $data['terms_conditions'] = $local['callToAction']['termsConditions'];
            }

            /* $data['prix_max'] = $request->Prix_maximal;
              $data['prix_min'] = $request->Prix_minimal;
              $data['prix_produit'] = $request->Prix_produit; */

            //      $data['type_envoi'] = $request->type_envoi;


        //  $data['user_id'] = 29;
          //  $data['user_id'] = auth()->user()->id;
      


        if ($local['media']) {

            foreach ($local['media'] as $media) {
                $time = time();
                $info = get_headers($media['googleUrl']);
               /* $new_data = explode('"', $info[4]);
                $type = $new_data[1];
                $extension = explode('.', $type);
                $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];
                Storage::disk('public')->put($imageName, file_get_contents($media['googleUrl']));
                $media_url[] = $imageName;*/
            }
       //   $data['media_url'] = json_encode($media_url, 1);
        }
            $data['state'] = "Envoyer";

          
        
            $postfiche=Postfiche::where('genre',$local['name'])->where('fiche_id',$listfiche->id);
               
            if ($postfiche->exists()) {
           
                $post=$postfiche->first(); 
       
                $post->update($data);
            }
            else{  
                 $post = Post::create($data);
               
                 $datap['name'] = $listfiche->name;
                 $datap['genre'] = $local['name'];
                 $datap['post_id']=$post->id;
                 $datap['fiche_id']=$listfiche->id;
                  Postfiche::create($datap);
            }
        }

        }

    }

 
    }
    