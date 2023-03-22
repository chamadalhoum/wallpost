<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helper\Helper;
use App\Models\Statistique;
use App\Models\Fiche;
use Carbon\Carbon;   
use App\Models\Postfiche;
use App\Models\Poststat;
use App\Models\Poststats;

class PostStatistiqueCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poststate:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        {
            $mybusinessService =Helper::GMB();
            $localpost = $mybusinessService->accounts_locations_localPosts; 
            $postrest= Helper::GMBPOSTREPORTAction(); 
        $BasicRequest= Helper::BasicMetricsRequestAction();
        $time= Helper::TimeRangeAction();
        $metricRequests = Helper::MetricRequestAction();
        $metricRequests->setMetric('ALL');
        $metricRequests->setOptions('AGGREGATED_DAILY');
        $time = Helper::TimeRangeAction();
     $start = Carbon::now()->subMonth(2)->format('Y-m-d\TH:i:s\Z');
     $end= Carbon::now()->subHour(1)->format('Y-m-d\TH:i:s\Z'); 
     $time->setEndTime($end);
      $time->setStartTime($start);
        $BasicRequest->setTimeRange($time);
        $BasicRequest->setMetricRequests($metricRequests);
        $postrest->setBasicRequest($BasicRequest);
            
        $listefiche= Postfiche::all();
   
        foreach ($listefiche as $postfiche) {
            try {
             $fichepost= Postfiche::find($postfiche['id']);
                $postrest->setLocalPostNames($fichepost->genre);
             
                $post= $localpost->reportInsights($fichepost->name,$postrest,array())->getLocalPostMetrics();
             
                if (!empty($post)) {
                    $tab = $post[0]->getMetricValues();
                    foreach ($tab as $k => $value) {
                        $metricValuesItem = [];
                        foreach ($value as $j => $val) {
                          $date = Carbon::create($tab[1]['dimensionalValues'][$j]['timeDimension']['timeRange']['startTime'])->toDateTimeString();

                           $localPostViewsSearch = $tab[0]['dimensionalValues'][$j]['value'] ?: 0;
                           $localPostActions = $tab[1]['dimensionalValues'][$j]['value'] ?: 0;
                            $data = [
                           'post_fiche_id' => $postfiche['id'],
                            'localPostViewsSearch' => $localPostViewsSearch,
                            'localPostActions' => $localPostActions,
                            'date' => $date,
                        ];
                          Poststats::updateOrCreate($data);
                        }
                    }
                }
              
            } catch (\Throwable $th) {
                print_r(($th->getMessage()));
                continue;
            }
        }
  
    }
}
}
