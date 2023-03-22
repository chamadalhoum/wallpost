<?php

namespace App\Console\Commands;
use App\Helper\Helper;
use App\Models\Fiche;
use App\Models\Post;
use App\Models\Postfiche;
use App\Models\Typepost;
use Illuminate\Console\Command;

class PostCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get All Posts From GMB';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mybusinessService = Helper::GMB();
        $placeID = Helper::GMBLOCATIONPOST();

        $localpost = $mybusinessService->accounts_locations_localPosts;
        $locations = $mybusinessService->accounts_locations;
        $fiche = Fiche::all();

        foreach ($fiche as $listfiche) {
            try {
                $locationsList = $localpost->listAccountsLocationsLocalPosts($listfiche->name);
            } catch (\Throwable $th) {
                continue;
            }
            foreach ($locationsList['localPosts'] as $local) {
                //   Log::info(json_encode($local));
                $data['name']=null;
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
                $typepost = Typepost::where('nametype', $local['topicType'])->first();
                $data['type'] = $typepost->title;
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
                            'summary' => $local['summary'],
                          'name' => $data['name'],
                          'topic_type' => $data['topic_type'],
                         ],
                         $data
                    );
                } catch (\Throwable $th) {
                    print_r($th->getMessage());
                    continue;
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
                    continue;
                }
            }
        }
    }
}
