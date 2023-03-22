<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helper\Helper;
use App\Models\Fiche;
use App\Models\Statistique;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\profilincomplete;
use Google; 
class StatistiqueCron extends Command
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistique:cron';

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
        $fiches = Fiche::leftjoin('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
        ->leftjoin('accountagences', 'accountagences.franchise_id', '=', 'fiches.franchises_id')
        ->select('accountagences.account', 'fiches.name', 'fiches.id')
        ->where('fiches.state', 'LIKE', 'COMPLETED')->get();
        foreach ($fiches as $fiche) {
            try {
                if(!defined('CLIENT_SECRET_PATH')){
                    define('CLIENT_SECRET_PATH', storage_path('app/client_secret.json'));
            
                }
                    if(!defined('CREDENTIALS_PATH')){
                    define('CREDENTIALS_PATH', storage_path('app/authorization_token.json'));
            
                }
             
                $credentialsPath = CREDENTIALS_PATH;
                $client = Google::getClient();
                $client->refreshToken($client->getRefreshToken());
                $jsontoken = $client->getAccessToken();
                file_put_contents($credentialsPath, json_encode($jsontoken));
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
                $mybusinessService = Helper::GMB();
                continue;
            }


            $start = Carbon::now()->subMonth()->toDateString();
       $end= carbon::now()->toDateString();
   $Pstart=  Carbon::parse($start)->subMonth(1)->toDateString();
   $Pend=  Carbon::parse($end)->subMonth()->toDateString();
   $viewsSearch=0;
   $Psearch=0;
   $viewsMaps=0;
   $Pmaps=0;
   if ($start || $end) {
              
    $statistique  = Statistique::where('fiche_id',$fiche->id)
    ->whereBetween('date', [$start, $end]);

        $viewsSearch =$statistique->sum('statistiques.viewsSearch');
        $viewsMaps =$statistique->sum('statistiques.viewsMaps');
       }
    if ($Pstart || $Pend) {
        $statistiquep  = Statistique::where('fiche_id',$fiche->id)
      ->whereBetween('statistiques.date', [$Pstart, $Pend]);
        $Psearch =$statistiquep->sum('statistiques.viewsSearch');
        $Pmaps =$statistiquep->sum('statistiques.viewsMaps');
       
    }

    $dataprofil['vuesearch']=[$this->calculpourcentage($viewsSearch,$Psearch)];
    $dataprofil['vuemaps']=[$this->calculpourcentage($viewsMaps,$Pmaps)];
   
  $dataaaa=  profilincomplete::updateOrCreate(['fiche_id'=>$fiche->id],$dataprofil);

        }
    }
    public  function calculpourcentage($a,$b){
        $c=0;
     
        $diff = $a - $b;
        $div = ($b == 0) ? 1 : $b;
        
        $c = (($a - $b) / $div) * 100;
               if ($a > 0 && $b == 0) {
                    $c = 100;
                }
                if ($a == 0 && $b == 0) {
                    $c = 0;
                }
                if ($a== 0 && $b > 0) {
                    $c = -100;
                }
       
    
        if($c >= 0){
            //$c='+ '. $c;
            $status="positif";
            $couleur='#B1CD45';
            
        }else{
           // $c='- '.$c;
            $status="negative";
            $couleur='#C94040';
        }
       
        return ["pourcentage"=>(int)$c,"status"=>$status,'couleur'=>$couleur];

    }

}
