<?php

namespace App\Jobs;

use App\Helper\Helper;
use App\Models\Fiche;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CronPostMybusines implements ShouldQueue
{
    use Dispatchable;

    use InteractsWithQueue;

    use Queueable;

    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        echo '-------->';
        print_r($fiche);
        exit;
        $mybusinessService = Helper::GMB();
        $placeID = Helper::GMBLOCATIONPOST();

        $localpost = $mybusinessService->accounts_locations_localPosts;
        $locations = $mybusinessService->accounts_locations;
        $fiche = Fiche::all();

        foreach ($fiche as $listfiche) {
            $locationsList = $localpost->listAccountsLocationsLocalPosts($listfiche->name);
            foreach ($locationsList['localPosts'] as $local) {
                //   Log::info(json_encode($local));

                if ($local['callToAction']) {
                    $data['action_type'] = $local['callToAction']['actionType'];
                    switch ($local['callToAction']['actionType']) {
                    case 'LEARN_MORE':
                        $calltoactions = 'En savoir plus';
                        break;
                    case 'ACTION_TYPE_UNSPECIFIED':
                        $calltoactions = 'Aucun';
                        break;
                    case 'BOOK':
                        $calltoactions = 'Réserver';
                        break;
                    case 'ORDER':
                        $calltoactions = 'Commande en ligne';

                        break;
                    case 'SHOP':
                        $calltoactions = 'Acheter';

                        break;
                    case 'SIGN_UP':
                        $calltoactions = "S'inscrire";

                        break;
                    case 'CALL':
                        $calltoactions = 'Appeler';
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
                    $data['event_start_date'] = $local['event']['schedule']['startDate']['year'].'-'.
                        $local['event']['schedule']['startDate']['month'].'-'.
                        $local['event']['schedule']['startDate']['day'];
                    $data['event_start_time'] = $local['event']['schedule']['startTime']['hours'].':'.
                        $local['event']['schedule']['startTime']['minutes'].':'.
                        $local['event']['schedule']['startTime']['seconds'];
                    $data['event_end_time'] = $local['event']['schedule']['startTime']['hours'].':'.
                        $local['event']['schedule']['startTime']['minutes'].':'.
                        $local['event']['schedule']['startTime']['seconds'];
                    $data['event_end_date'] = $local['event']['schedule']['startDate']['year'].'-'.
                        $local['event']['schedule']['startDate']['month'].'-'.
                        $local['event']['schedule']['startDate']['day'];
                }
                if ($local['offer']) {
                    $data['coupon_code'] = $local['offer']['couponCode'];
                    $data['redeem_online_url'] = $local['offer']['redeemOnlineUrl'];
                    $data['terms_conditions'] = $local['offer']['termsConditions'];
                }

                if ($local['media']) {
                    foreach ($local['media'] as $media) {
                        $time = time();
                        $info = get_headers($media['googleUrl']);
                    }
                }
                $data['state'] = 'Envoyer';
                try {
                    $post = Post::updateOrCreate(
                        [
                          'name' => $data['name'],
                          'summary' => $data['summary'],
                          'search_url' => $data['search_url'],
                          'created_at' => $data['created_at'],
                          'updated_at' => $data['updated_at'],
                          'topic_type' => $data['topic_type'],
                         ],
                         $data
                    );
                } catch (\Throwable $th) {
                    print_r($th->getMessage());
                }
                try {
                    $datap['name'] = $listfiche->name;
                    $datap['genre'] = $local['name'];
                    $datap['post_id'] = $post->id;
                    $datap['fiche_id'] = $listfiche->id;
                    $post = Postfiche::updateOrCreate(
                        [
                            'genre' => $datap['genre'],
                            'fiche_id' => $datap['fiche_id'],
                         ],
                         $datap
                    );
                } catch (\Throwable $th) {
                    print_r($th->getMessage());
                }
            }
        }
    }
}
