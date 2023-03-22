<?php

namespace App\Jobs;

use App\Helper\Helper;
use App\Models\Fiche;
use App\Models\Statistique;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CronStatistiqueMybusines implements ShouldQueue
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
        $mybusinessService = Helper::GMB();
        $locations = $mybusinessService->accounts_locations;
        $start = Carbon::now()->subMonths(4)->format('Y-m-d\TH:i:s\Z');
        $end = Carbon::now()->format('Y-m-d\TH:i:s\Z');
        $reportLocationInsightsRequest = Helper::ReportLocationInsightsRequestAction();
        $basicRequest = Helper::BasicMetricsRequestAction();
        $metricRequests = Helper::MetricRequestAction();
        $metricRequests->setMetric('ALL');
        $metricRequests->setOptions('AGGREGATED_DAILY');
        $time = Helper::TimeRangeAction();

        $time->setEndTime($end);
        $time->setStartTime($start);
        $basicRequest->setTimeRange($time);
        $basicRequest->setMetricRequests($metricRequests);
        $reportLocationInsightsRequest->setBasicRequest($basicRequest);
        $fiches = Fiche::leftjoin('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
        ->leftjoin('accountagences', 'accountagences.franchise_id', '=', 'ficheusers.franchise_id')
        ->select('accountagences.account', 'fiches.name', 'fiches.id')->Where('fiches.state', 'COMPLETED')->get();
        foreach ($fiches as $fiche) {
            try {
                $reportLocationInsightsRequest->setLocationNames($fiche->name);
                $metric = $locations->reportInsights($fiche->account, $reportLocationInsightsRequest)->getLocationMetrics();

                if (!empty($metric)) {
                    $tab = $metric[0]->getMetricValues();
                    foreach ($tab as $k => $value) {
                        $metricValuesItem = [];
                        foreach ($value as $j => $val) {
                            $datee = Carbon::parse($val['timeDimension']['timeRange']['startTime'])->toDateTimeString();
                            $date = Carbon::create($tab[1]['dimensionalValues'][$j]['timeDimension']['timeRange']['startTime'])->toDateTimeString();

                            $queryDirect = $tab[0]['dimensionalValues'][$j]['value'] ?: 0;
                            $queryInDirect = $tab[1]['dimensionalValues'][$j]['value'] ?: 0;
                            $chaine = $tab[2]['dimensionalValues'][$j]['value'] ?: 0;
                            $Map = $tab[3]['dimensionalValues'][$j]['value'] ?: 0;
                            $Search = $tab[4]['dimensionalValues'][$j]['value'] ?: 0;
                            $webSite = $tab[5]['dimensionalValues'][$j]['value'] ?: 0;
                            $appel = $tab[6]['dimensionalValues'][$j]['value'] ?: 0;
                            $drivingDirection = $tab[7]['dimensionalValues'][$j]['value'] ?: 0;
                            $a1 = $tab[8]['dimensionalValues'][$j]['value'] ?: 0;
                            $a2 = $tab[9]['dimensionalValues'][$j]['value'] ?: 0;
                            $a3 = $tab[10]['dimensionalValues'][$j]['value'] ?: 0;
                            $a4 = $tab[11]['dimensionalValues'][$j]['value'] ?: 0;
                            $a5 = $tab[12]['dimensionalValues'][$j]['value'] ?: 0;
                            $a6 = $tab[13]['dimensionalValues'][$j]['value'] ?: 0;

                            $data = [
                         'fiche_id' => $fiche->id,
                            'queriesDirect' => $queryDirect,
                            'queriesIndirect' => $queryInDirect,
                            'queriesChain' => $chaine,
                            'viewsMaps' => $Map,
                            'viewsSearch' => $Search,
                            'actionsWebsite' => $webSite,
                            'actionsPhone' => $appel,
                            'actionsDrivingDirections' => $drivingDirection,
                            'photosViewsMerchant' => $a1,
                            'photosViewsCustomers' => $a2,
                            'photosCountMerchant' => $a3,
                            'photosCountCustomers' => $a4,
                            'localPostViewsSearch' => $a5,
                            'localPostActions' => $a6,
                            'date' => $date,
                        ];

                            Statistique::updateOrCreate(['date' => $date, 'fiche_id' => $fiche->id], $data);
                        }
                    }
                }
            } catch (\Throwable $th) {
                print_r(($th->getMessage()));
            }
        }
    }
}
