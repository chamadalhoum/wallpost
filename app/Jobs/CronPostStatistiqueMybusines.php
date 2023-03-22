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
use App\Models\Fiche;
use Carbon\Carbon;   
use App\Models\Postfiche;
use App\Models\Poststat;
use App\Models\Poststats;

class CronPostStatistiqueMybusines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
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
      //  $BasicRequest->setMetricRequests(["metric"=>"All"]);
        $BasicRequest->setMetricRequests($metricRequests);
        $postrest->setBasicRequest($BasicRequest);
            
        $listefiche= Postfiche::all();
       
      /*  foreach($listefiche as $postfiche){
          
                $fichepost= Postfiche::find($postfiche['id']);
                $postrest->setLocalPostNames($fichepost->genre);
             
                $post= $localpost->reportInsights($fichepost->name,$postrest,array())->getLocalPostMetrics();
            print_r($post);exit;
              $data['localPostViewsSearch'] = $post[0]['metricValues'][0]['totalValue']['value'];
                $data['localPostActions'] = $post[0]['metricValues'][1]['totalValue']['value'];
                $data['date'] = $post[0]['metricValues'][1]['totalValue']['value'];
               // $data['genre'] =$post[0]['localPostName'];
           //    Poststats::create($data);
               
        }*/
        foreach ($listefiche as $postfiche) {
            try {
             //   $reportLocationInsightsRequest->setLocationNames($fiche->name);
               // $metric = $locations->reportInsights($fiche->account, $reportLocationInsightsRequest)->getLocationMetrics();
                $fichepost= Postfiche::find($postfiche['id']);
                $postrest->setLocalPostNames($fichepost->genre);
             
                $post= $localpost->reportInsights($fichepost->name,$postrest,array())->getLocalPostMetrics();
              //  var_dump($post);exit;
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
            }
        }
  
    }
    }
}
