<?php

namespace App\Console\Commands;

use App\Helper\Helper;
use App\Models\Avi;
use App\Models\Avisreponse;
use App\Models\Fiche;
use Carbon\Carbon;
use App\Models\profilincomplete;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AvisCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avis:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Or Create Reviews from GMB to DB';

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
        $ratings = ['STAR_RATING_UNSPECIFIED' => null, 'ONE' => '1', 'TWO' => '2', 'THREE' => '3', 'FOUR' => '4', 'FIVE' => '5'];
        $fiches = Fiche::where('fiches.state', 'LIKE', 'COMPLETED')->get();

        $this->reviews = [];

        foreach ($fiches as $value) {
    
            $aviscount=array();
            $total=0;
            $this->mybusinessService = Helper::GMB();
            $nextPageToken = null;
            do {
                try {
                    $review = $this->mybusinessService->accounts_locations_reviews->listAccountsLocationsReviews($value->name, ['pageSize' => 100, 'pageToken' => $nextPageToken])->getReviews();

                    if (isset($review) && !empty($review)) {
                        foreach ($review as $key => $rv) {
                            $dt = Carbon::parse($rv->updateTime)->translatedFormat('Y-m-d H:i:s');

                            try {
                                if (strpos($rv->comment, '(Translated by Google)')) {
                                    $content = explode('(Translated by Google)', $rv->comment)[0];
                                } else {
                                    $content = $rv->comment;
                                }
                                 $avi = Avi::updateOrCreate(['code' => $rv->reviewId], [
                        'code' => $rv->reviewId,
                        'title' => $rv->reviewer->displayName,
                        'content' => $content,
                        'rating' => $ratings[$rv->starRating],
                        'photo' => (string) $rv->reviewer->profilePhotoUrl,
                        'date' => $dt,
                        'fiche_id' => $value->id,
                      ]);


                                if (!empty($rv->getReviewReply())) {
                                    $reply = $rv->getReviewReply()->getComment();

                                    $replyTime = $rv->getReviewReply()->getUpdateTime();
                                    $replyTimex = $rv->getReviewReply();

                                    $dt = Carbon::parse($replyTime)->translatedFormat('Y-m-d H:i:s');

                                    $avis = Avisreponse::updateOrCreate(['avis_id' => $avi->id], [
                      'reponse' => $reply,
                      'fiche_id' => $value->id,
                      'created_at' => $dt,
                    ]);
                                }
                            } catch (\Throwable $th) {
                                print_r($th->getMessage());
                                exit;
                            }
                        }

                    }
                    $TotalAvis=0;
$TotalRate=0;
$aviscount = DB::table('avis')->select(DB::raw('count(*) as rating_count, rating'))->where('fiche_id',$value->id)->orderBy('rating', 'desc')->groupBy('rating')->get();

$total = array_sum(array_column($aviscount->toArray(), 'rating_count'));


if ($total > 0) {
$totalRating = 0;
$ListAvis = [];

foreach ($aviscount as $key => $values) {
  
    $totalRating += ((int) $values->rating_count * (int) $values->rating);
}
$TotalAvis=$total;
$TotalRate=number_format((float) $totalRating / $total, 1, '.', '');


}

$dataprofil['TotalAvis']=$TotalAvis;
$dataprofil['TotalRate']=$TotalRate;

print_r($dataprofil);
profilincomplete::updateOrCreate(['fiche_id'=>$value->id],$dataprofil);
                } catch (\Throwable $th) {
                    print_r($th->getMessage());
                }

                if (isset($review->nextPageToken)) {
                    $nextPageToken = $review->nextPageToken;
                }
            } while ($nextPageToken != null);
        }
    }
}
