<?php

namespace App\Http\Controllers;

use App\Models\Statistique;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use phpDocumentor\Reflection\Types\Float_;
use Ramsey\Uuid\Type\Decimal;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;

class StatistiqueController extends Controller
{
   
    public function performance(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
       $fiche_id= $request->fiche_id;
 
        try {
            $totalActivite=0;
           if($request->datedebut =="" && $request->datefin ==""){
                $start = Carbon::now()->subDays(28)->toDateString();
                $end= carbon::now()->toDateString();

            }else{
                $start = Carbon::createFromFormat('Y-m-d',$request->datedebut);
                $end =Carbon::createFromFormat('Y-m-d',$request->datefin);
            }
         $ends= carbon::now();
           
           $Pend = $ends->subDay(28)->translatedFormat('Y-m-d');
           $Pstart=  Carbon::parse($Pend)->subDays(28)->toDateString();
           
              if ($start || $end) {
              
                $statistique  = Statistique::query()->Join('fiches', 'fiches.id', '=', 'statistiques.fiche_id')
                ->where('fiches.franchises_id',$request->header('franchise'))
                ->join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
                ->where('ficheusers.user_id', auth()->user()->id)
                ->when($fiche_id,function ($query) use($fiche_id){
                    $query->where('statistiques.fiche_id', $fiche_id);
                    })
                  ->whereBetween('statistiques.date', [$start, $end]);
                    $viewsSearch =$statistique->sum('statistiques.viewsSearch');
                    $viewsMaps =$statistique->sum('statistiques.viewsMaps');
                    $query_direct=$statistique->sum('statistiques.queriesDirect');
                    $query_indirect=$statistique->sum('statistiques.queriesIndirect');
                    $search=$query_direct+$query_indirect;
                    $actionsWebsite=$statistique->sum('statistiques.actionsWebsite');
                    $actionsPhone=$statistique->sum('statistiques.actionsPhone');
                    $actionsDrivingDirections=$statistique->sum('statistiques.actionsDrivingDirections');
                    $photosViewsMerchant=$statistique->sum('statistiques.photosViewsMerchant');
                    $photosViewsCustomers=$statistique->sum('statistiques.photosViewsCustomers');
               $totalActivite=$actionsWebsite+$actionsPhone+$actionsDrivingDirections+$photosViewsMerchant+$photosViewsCustomers;
                 // $totalActivite=1+0+0+0+0;
                    $total=$viewsSearch+$viewsMaps;
                 
                }
                if ($Pstart || $Pend) {
                    $statistiquep  = Statistique::query()->Join('fiches', 'fiches.id', '=', 'statistiques.fiche_id')
                    ->where('fiches.franchises_id',$request->header('franchise'))
                    ->join('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')
                   ->where('ficheusers.user_id', auth()->user()->id)
                    ->when($fiche_id,function ($query) use($fiche_id){
                        $query->where('statistiques.fiche_id', $fiche_id);
                        })
                    ->whereBetween('statistiques.date', [$Pstart, $Pend]);
                    $Psearch =$statistiquep->sum('statistiques.viewsSearch');
                    $Pmaps =$statistiquep->sum('statistiques.viewsMaps');
                    $pdirect=$statistiquep->sum('statistiques.queriesDirect');
                    $pindirect=$statistiquep->sum('statistiques.queriesIndirect');
                    $search=$query_direct+$query_indirect;
                    $purl=$statistiquep->sum('statistiques.actionsWebsite');
                    $pappel=$statistiquep->sum('statistiques.actionsPhone');
                    $pitineraire=$statistiquep->sum('statistiques.actionsDrivingDirections');
                    $pphotosViewsMerchant=$statistiquep->sum('statistiques.photosViewsMerchant');
                    $pphotosViewsCustomers=$statistiquep->sum('statistiques.photosViewsCustomers');
                    $ptotalActivite=$actionsWebsite+$actionsPhone+$pitineraire+$pphotosViewsMerchant+$pphotosViewsCustomers;
                   // $totalActivite=$actionsWebsite+$actionsPhone+$pitineraire;
                    $countp= $statistiquep->count();
                 

                }
                $datelast= Carbon::parse($end);
                $datefirst=Carbon::parse($start);
                $intar = $datelast->diffInDays($datefirst);
                $data=[
                'detailsvues'=>[
                'Vuetotal'=>$this->number_format_short($total),
                'vuesearch' => $this->number_format_short($viewsSearch),
                'Vuemaps' =>$this->number_format_short($viewsMaps), 
                'Psearch' =>[$this->calculpourcentage($viewsSearch,$Psearch)],
                'Pmaps' => [$this->calculpourcentage($viewsMaps,$Pmaps)],
                 ],
                'detailssearch'=>[
                'searchtotal'=>$this->number_format_short($search), 
                'direct'=>$this->number_format_short($query_direct),
                'pdirect'=>[$this->calculpourcentage($query_direct,$pdirect)],
                'indirect'=>$this->number_format_short($query_indirect),
                'pindirect'=>[$this->calculpourcentage($query_indirect,$pindirect)],
                ],
                'detailsactivite'=>[
                'activitetotal'=>$this->number_format_short($totalActivite), 
                'url'=>$this->number_format_short($actionsWebsite),
                'purl'=>[$this->calculpourcentage($actionsWebsite,$purl)],
                'appel'=>$this->number_format_short($actionsPhone),
                'pappel'=>[$this->calculpourcentage($actionsPhone,$pappel)],
                'vuephoto'=>$this->number_format_short($photosViewsCustomers + $photosViewsMerchant),
                'pvuephoto'=>[$this->calculpourcentage(($photosViewsCustomers + $photosViewsMerchant),($pphotosViewsCustomers + $pphotosViewsMerchant))], 
                'itineraire'=>$this->number_format_short($actionsDrivingDirections),
                'pitineraire'=>[$this->calculpourcentage($actionsDrivingDirections,$pitineraire)], 

                ],
                "datedebut"=>Carbon::parse($start)->format('d-m-Y')
                ,
                "datefin"=>Carbon::parse($end)->format('d-m-Y')
            ,
                "nbjour"=>$intar,
               
        ];
                return response()->json([
                    'success' => true,
                    'message' => 'Performance ',
                    'data' => $data,

                    'status' => 200
                ], 200);


        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Statistique not found.',

                'status' => 400
            ], 400);
        }
    }
    
   public static function  number_format_short( $n, $precision = 1 ) {
        if ($n < 900) {
            // 0 - 900
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = ' K';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = ' M';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = ' B';
        } else {
            // 0.9t+
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = ' T';
        }
    
      // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
      // Intentionally does not affect partials, eg "1.50" -> "1.50"
       if ( $precision > 0 ) {
            $dotzero = ',' . str_repeat( '0', $precision );
            
            $n_format = str_replace( $dotzero, '', $n_format);
          
        }
       
       $cleanedNumber = (strpos($n_format, '.') === false)
       ? $n_format
       : rtrim(rtrim($n_format, '0'), '.');

      return str_replace( '.', ',', $cleanedNumber) . $suffix;  
    
        
    }
    public static function calculpourcentage($a,$b){
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