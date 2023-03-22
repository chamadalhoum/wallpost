<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Avi;
use App\Models\Avisreponse;
use App\Models\Etiquette;
use App\Models\Fiche;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AviController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $avi = Avi::with('fiche:id,locationName,name');
            $s = request('search');
            if ($s) {
                $aviesarch = $avi->where('code', 'LIKE', '%'.$s.'%')->
                orWhere('title', 'LIKE', '%'.$s.'%')->

                orWhere('contents', 'LIKE', '%'.$s.'%')->

                orWhere('rating', 'LIKE', '%'.$s.'%')->
                orWhere('fiche_id', 'LIKE', '%'.$s.'%')->
                orWhere('date', 'LIKE', '%'.$s.'%')
                    ->get();

                if ($avi->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => "Liste d'avis",
                        'data' => $aviesarch,

                        'status' => 200,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Désole, Avis not found.',

                        'status' => 200,
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => "Liste d'avis",
                    'data' => $avi->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200,
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Avis not found.',

                'status' => 400,
            ], 400);
        }
    }

    public function notation()
    {
        try {
            $avis = DB::table('avis')
                ->select(DB::raw('count(*) as rating_count, rating'))
                ->orderBy('rating', 'desc')
                ->groupBy('rating')
                ->get();

            return response()->json([
                'success' => false,
                'message' => 'COUNT avis negatif.',
                'data' => $avis,

                'status' => 400,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole , Avis Negatif not found.',

                'status' => 400,
            ]);
        }
    }

    public function notationbyid($id)
    {
        try {
            $avis = DB::table('avis')
                ->select(DB::raw('count(*) as rating_count, rating'))
                ->where('rating', $id)
                ->orderBy('rating', 'desc')
                ->groupBy('rating')
                ->get();
            if (count($avis) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'COUNT NOTATION.',
                    'data' => $avis,

                    'status' => 400,
                ]);
            } else {
                $avis[] = ['rating_count' => 0,
                    'rating' => $id, ];

                return response()->json([
                    'success' => false,
                    'message' => 'COUNT avis NOTATION.',
                    'data' => $avis,

                    'status' => 400,
                ]);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole , Avis Negatif not found.',

                'status' => 400,
            ]);
        }
    }

    public function global(Request $request)
    {
        $messages = [
            
            'Fiche.exists' => 'Fiche est invalide',

        ];
        $fiches = null;
        $listfihes = null;
        $input=null;
        $etiquetes = null;
        if (isset($request->Etiquette) && !empty($request->Etiquette)) {


            foreach ($request->Etiquette as $key => $et) {

                // code...

                if ($et['type'] == 'Fiche') {
                    $listfihes[] = $et['id'];
                } else {
                    $ettiquetslist =  Fiche::join('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'fiches.id')->join('etiquettes', 'etiquette_id', '=', 'etiquettes.id')->where("etiquettes.id", '=', $et['id'])->where('fiches.franchises_id', '=', $request->header('franchise'))->get("fiches.id");
 
                    foreach ($ettiquetslist as $key => $value) {
                        $listfihes[]=$value["id"];
                    }
                }
            }
            $input = [
          
                'Fiche' => array_unique($listfihes),
            ];
        }else{
            $input = [
          
                'Fiche' => null,
            ];
        }

        
    
   
        $validator = Validator::make(
            $input,
            [
                            'Fiche.*.id' => 'exists:fiches,id',
                          
                        ],
            $messages
        );

        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json(
                [
                'success' => false,
                   'message' => $message,
                'status' => 422, ],
                422
            );
        }
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }

  

        try {


            $avisQuery = DB::table('avis')->select(DB::raw('count(*) as rating_count, rating'))
            ->Join('fiches', 'fiches.id', '=', 'avis.fiche_id')
            ->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')
            ->where('franchises.id', '=', $request->header('franchise'))
                ->orderBy('rating', 'desc')
                ->groupBy('rating');
                if(isset($input->Fiche) && !empty($input->Fiche)){
                   
                    $avisQuery->whereIn('avis.fiche_id',$input->Fiche) ;
                }
                

                
                
                $avis=$avisQuery->get();
             

            $total = array_sum(array_column($avis->toArray(), 'rating_count'));
            $data['ListAvis'] = null;

            $data['ListstarALL'] = ['TotalAvis' => 0, 'TotalRate' => 0];
            if ($total > 0) {
                $totalRating = 0;
                $ListAvis = [];
                for ($i = 1; $i <= 5; ++$i) {
                    $ListAvis[$i] = ['Name' => $i, 'progressValue' => 0];
                }
                foreach ($avis as $key => $value) {
                    $ListAvis[$value->rating] = ['Name' => $value->rating, 'progressValue' => ($value->rating_count * 100) / $total];
                    $totalRating += ((int) $value->rating_count * (int) $value->rating);
                }

                $data['ListAvis'] = array_reverse(array_values($ListAvis));

                $data['ListstarALL'] = ['TotalAvis' => $this->shortNumber($total), 'TotalRate' => number_format((float) $totalRating / $total, 1, '.', '')];
            }
          
            return response()->json([
    'success' => true,
    'message' => 'Operation success.',
    'data' => $data,
    'status' => 200,
]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage,
                'status' => 400,
            ]);
        }
    }

    public function globalbyid(Request $request)
    {
        try {
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
            $fiche_id = $request->fiche_id;
            $avis = DB::table('avis')->select(DB::raw('count(*) as rating_count, rating'))
            ->Join('fiches', 'fiches.id', '=', 'avis.fiche_id')
            ->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')
            ->where('franchises.id', '=', $request->header('franchise'))
            ->when($fiche_id, function ($query) use ($fiche_id) {
                $query->where('avis.fiche_id', '=', $fiche_id);
            })

                ->orderBy('rating', 'desc')
                ->groupBy('rating')
                ->get();

            $total = array_sum(array_column($avis->toArray(), 'rating_count'));
            $data['ListAvis'] = null;

            $data['ListstarALL'] = ['TotalAvis' => 0, 'TotalRate' => 0];
            if ($total > 0) {
                $totalRating = 0;
                $ListAvis = [];
                for ($i = 1; $i <= 5; ++$i) {
                    $ListAvis[$i] = ['Name' => $i, 'progressValue' => 0];
                }
                foreach ($avis as $key => $value) {
                    $ListAvis[$value->rating] = ['Name' => $value->rating, 'progressValue' => ($value->rating_count * 100) / $total];
                    $totalRating += ((int) $value->rating_count * (int) $value->rating);
                }

                $data['ListAvis'] = array_reverse(array_values($ListAvis));

                $data['ListstarALL'] = ['TotalAvis' => $this->shortNumber($total), 'TotalRate' => number_format((float) $totalRating / $total, 1, '.', '')];
            }

            return response()->json([
    'success' => true,
    'message' => 'Operation success.',
    'data' => $data,
    'status' => 200,
]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage,
                'status' => 400,
            ]);
        }
    }

    public function gloabalrep(Request $request)
    {
        try {
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
            $fiche_id = $request->fiche_id;
            $dernier_avis = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')
            ->leftJoin('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')
            ->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')
            ->where('fiches.state', 'LIKE', 'COMPLETED')
            
            ->where('franchises.id', '=', $request->header('franchise'))
            ->when($fiche_id, function ($query) use ($fiche_id) {
                $query->where('avis.fiche_id', $fiche_id);
            })
            ->orderBy('date', 'DESC')
            ->first(['avis.*', 'fiches.locationName', 'fiches.name as FicheName', 'avisreponses.reponse', 'franchises.logo']);
            $now = Carbon::now();
            $end = Carbon::now();

            $data['review'] = [];
            if ($dernier_avis) {
                $end = Carbon::parse($dernier_avis->date);

                if ($years = $end->diffInYears($now)) {
                    $dernier_avis->date = 'Il ya '.$years.' années';
                } elseif ($months = $end->diffInMonths($now)) {
                    $dernier_avis->date = 'Il ya '.$months.' mois';
                } elseif ($weeks = $end->diffInWeeks($now)) {
                    $dernier_avis->date = 'Il ya '.$weeks.' semaines';
                } else {
                    $days = $end->diffInDays($now);
                    $dernier_avis->date = 'Il ya '.$days.' jour';
                }
                $name = explode(' ', $dernier_avis->title);
                $lastname=null;
                $firstname=null;
                if (isset($name[0])) {
                    $firstname = $name[0];
                }
                if (isset($name[1])) {
                    $lastname = $name[1];
                }
                $dernier_avis->title = $firstname.' <b>'.$lastname.'</b>';
                $dernier_avis->logo = \Illuminate\Support\Facades\URL::to($dernier_avis->logo);
                $data['review'] = [$dernier_avis];
            }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => $data,
                'status' => 200,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'status' => 400,
            ]);
        }
    }

    public function gloabalnonrep(Request $request)
    {
        try {
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
            $avis = Avi::count();

            $repavis = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('fiches.state', 'LIKE', 'COMPLETED')->where('franchises.id', '=', $request->header('franchise'))->join('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')
                ->where('avisreponses.user_id', auth()->user()->id)
                ->count();

            $notrepondu = $avis - $repavis;

            return response()->json([
                'success' => false,
                'message' => 'COUNT avis.',
                'data' => $notrepondu,

                'status' => 400,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole , Avis postif not found.',

                'status' => 400,
            ]);
        }
    }

    public function postif(Request $request)
    {
        $messages = [
            
            'Fiche.exists' => 'Fiche est invalide',

        ];
        $fiches = null;
        $listfihes = null;
        $input=null;
        $etiquetes = null;
        if (isset($request->Etiquette) && !empty($request->Etiquette)) {


            foreach ($request->Etiquette as $key => $et) {

                // code...

                if ($et['type'] == 'Fiche') {
                    $listfihes[] = $et['id'];
                } else {
                    $ettiquetslist =  Fiche::join('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'fiches.id')->join('etiquettes', 'etiquette_id', '=', 'etiquettes.id')->where("etiquettes.id", '=', $et['id'])->where('fiches.franchises_id', '=', $request->header('franchise'))->get("fiches.id");
 
                    foreach ($ettiquetslist as $key => $value) {
                        $listfihes[]=$value["id"];
                    }
                }
            }
            $input = [
          
                'Fiche' => array_unique($listfihes),
            ];
        }else{
            $input = [
          
                'Fiche' => null,
            ];
        }

        
    
   
        $validator = Validator::make(
            $input,
            [
                            'Fiche.*.id' => 'exists:fiches,id',
                          
                        ],
            $messages
        );

        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json(
                [
                'success' => false,
                   'message' => $message,
                'status' => 422, ],
                422
            );
        }
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            

            if(isset($input->Fiche) && !empty($input->Fiche)){

                $avis_total = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('rating', '>', '3')->where('fiches.state', 'LIKE', 'COMPLETED')->whereIn('avis.fiche_id',$input->Fiche)->count();
                $avis_repondu = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->join('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->where('rating', '>', '3')->whereIn('avis.fiche_id',$input->Fiche)->where('fiches.state', 'LIKE', 'COMPLETED')->count();
                $avis_non_repondu = $avis_total - $avis_repondu;
                       
                
                }else{
                    $avis_total = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('rating', '>', '3')->where('fiches.state', 'LIKE', 'COMPLETED')->count();
                    $avis_repondu = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->join('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->where('rating', '>', '3')->where('fiches.state', 'LIKE', 'COMPLETED')->count();
                    $avis_non_repondu = $avis_total - $avis_repondu;

                }





            $data['stat'] = ['Total' => $this->shortNumber($avis_total) , 'Repondu' => $this->shortNumber($avis_repondu), 'NonRepondu' => $this->shortNumber($avis_non_repondu)];
            if(isset($input->Fiche) && !empty($input->Fiche)){

                $dernier_positif_avis = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->leftJoin('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')->whereIn('avis.fiche_id',$input->Fiche)->where('rating', '>', '3')->orderBy('date', 'DESC')->first(['avis.*', 'fiches.locationName', 'fiches.name as FicheName', 'avisreponses.reponse', 'avisreponses.CreateOrUpadate', 'avisreponses.updated_at as reponse_date',  'franchises.logo']);

                       
                
                }else{
                    $dernier_positif_avis = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->leftJoin('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')->where('rating', '>', '3')->orderBy('date', 'DESC')->first(['avis.*', 'fiches.locationName', 'fiches.name as FicheName', 'avisreponses.reponse', 'avisreponses.CreateOrUpadate', 'avisreponses.updated_at as reponse_date',  'franchises.logo']);


                }
                if (isset($dernier_positif_avis) && !empty($dernier_positif_avis)) {
            if (isset($dernier_positif_avis->reponse_date)) {
                $dernier_positif_avis->reponse_date = Carbon::parse($dernier_positif_avis->reponse_date)->translatedFormat('d/m/Y');
            }
            $now = Carbon::now();
            $end = Carbon::parse($dernier_positif_avis->date);

            if ($years = $end->diffInYears($now)) {
                $dernier_positif_avis->date = 'Il ya '.$years.' années';
            } elseif ($months = $end->diffInMonths($now)) {
                $dernier_positif_avis->date = 'Il ya '.$months.' mois';
            } elseif ($weeks = $end->diffInWeeks($now)) {
                $dernier_positif_avis->date = 'Il ya '.$weeks.' semaines';
            } else {
                $days = $end->diffInDays($now);
                $dernier_positif_avis->date = 'Il ya '.$days.' jours';
            }
            $name = explode(' ', $dernier_positif_avis->title);
            $firstname=null;
            $lastname=null;
            if (isset($name[0])) {
                $firstname = $name[0];
            }
            if (isset($name[1])) {
                $lastname = $name[1];
            }
            $dernier_positif_avis->title = $firstname.' <b>'.$lastname.'</b>';
            $dernier_positif_avis->logo = \Illuminate\Support\Facades\URL::to($dernier_positif_avis->logo) ;
            $data['review'] = $dernier_positif_avis;
        }else{
            $data["review"]=null;
        }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => $data,
                'status' => 200,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'status' => 400,
            ]);
        }
    }

    public function listepostif()
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            $avis = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->whereIn('rating', [4, 5])->where('fiches.state', 'LIKE', 'COMPLETED')->get();

            return response()->json([
                'success' => false,
                'message' => 'LIst avis postif.',
                'data' => $avis,

                'status' => 400,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole , Avis postif not found.',

                'status' => 400,
            ]);
        }
    }

    public function norepondup()
    {
        try {
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
            $token = JWTAuth::parseToken()->refresh();
            User::where('id', auth()->user()->id)->update(['remember_token' => $token]);
            $repavis = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->join('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->where('fiches.state', 'LIKE', 'COMPLETED')
                ->where('avisreponses.user_id', auth()->user()->id)->whereIn('avis.rating', [4, 5])
                ->count();
            $avis = Avi::whereIn('rating', [4, 5])->count();
            $notrepondu = $avis - $repavis;

            return response()->json([
                'success' => false,
                'message' => 'COUNT avis negatif.',
                'data' => $notrepondu,

                'status' => 400,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole , Avis Negatif not found.',

                'status' => 400,
            ]);
        }
    }

    public function reply_update(Request $request)
    {
        $messages = [
            'Code.required' => ':Le code est obligatoire',
            'FicheName.required' => 'L\'id fiche est obligatoire',
            'Fiche_id.required' => 'L\'id fiche est obligatoire',
            'Avis_id.required' => 'L\'id avis est obligatoire',
            'User_id.required' => 'L\'id user est obligatoire',
            'Reply.required' => 'La reponse est obligatoire',
        ];
        $input = [
            'Code' => $request->Code,
            'FicheName' => $request->FicheName,
            'Fiche_id' => $request->Fiche_id,
            'Avis_id' => $request->Avis_id,
            'User_id' => $request->User_id,
            'Reply' => $request->Reply,
        ];
        $validator = Validator::make(
            $input,
            [
                            'Code' => 'required|exists:avis,code',
                            'FicheName' => 'required|exists:fiches,name',
                            'Fiche_id' => 'required|exists:fiches,id',
                            'Avis_id' => 'required|exists:avis,id',
                            'User_id' => 'required|exists:users,id',
                            'Reply' => 'required',
                        ],
            $messages
        );
        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json(
                [
                        'success' => false,
                        'message' => $message,
                        'status' => 422, ],
                422
            );
        }
        if ($validator->passes()) {
            $mybusinessService = Helper::GMB();

            try {
                $reply = Helper::Reviewreply();
                $reply->setComment($input->Reply);
                $d = date('Y-m-d H:i:s');
                $date = str_replace('+00:00', 'Z', gmdate('c', strtotime($d)));
                $reply->setUpdateTime($date);
                $reviewName = $input->FicheName.'/reviews/'.$input->Code;
                $r = $mybusinessService->accounts_locations_reviews->updateReply($reviewName, $reply);

                $avi = Avisreponse::updateOrCreate(['avis_id' => $input->Avis_id], [
            'reponse' => $input->Reply,
            'user_id' => $input->User_id,
            'fiche_id' => $input->Fiche_id,
            'created_at' => date('Y-m-d h:m:i'),
          ]);
                if (!$avi->wasRecentlyCreated && $avi->wasChanged()) {
                    $avi->CreateOrUpadate = 1;
                    $avi->save();
                }

                return response()->json([
            'success' => true,
            'message' => 'La reponse est ajouté',
            'status' => 200,
        ]);
            } catch (\Exception $th) {
                return response()->json([
            'success' => true,
            'message' => $th->getMessage(),
            'status' => 200,
        ]);
            }
        }
    }

    public function avisnegatif(Request $request)
    {
        $messages = [
            
            'Fiche.exists' => 'Fiche est invalide',

        ];
        $fiches = null;
        $listfihes = null;
        $input=null;
        $etiquetes = null;
        if (isset($request->Etiquette) && !empty($request->Etiquette)) {


            foreach ($request->Etiquette as $key => $et) {

                // code...

                if ($et['type'] == 'Fiche') {
                    $listfihes[] = $et['id'];
                } else {
                    $ettiquetslist =  Fiche::join('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'fiches.id')->join('etiquettes', 'etiquette_id', '=', 'etiquettes.id')->where("etiquettes.id", '=', $et['id'])->where('fiches.franchises_id', '=', $request->header('franchise'))->get("fiches.id");
 
                    foreach ($ettiquetslist as $key => $value) {
                        $listfihes[]=$value["id"];
                    }
                }
            }
            $input = [
          
                'Fiche' => array_unique($listfihes),
            ];
        }else{
            $input = [
          
                'Fiche' => null,
            ];
        }

        
    
   
        $validator = Validator::make(
            $input,
            [
                            'Fiche.*.id' => 'exists:fiches,id',
                          
                        ],
            $messages
        );

        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json(
                [
                'success' => false,
                   'message' => $message,
                'status' => 422, ],
                422
            );
        }
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            if(isset($input->Fiche) && !empty($input->Fiche)){
            $avis_total = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->leftJoin('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('rating', '<=', '3')->where('fiches.state', 'LIKE', 'COMPLETED')->whereIn('avis.fiche_id',$input->Fiche)->count();
            $avis_repondu = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->join('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->where('rating', '<=', '3')->where('fiches.state', 'LIKE', 'COMPLETED')->whereIn('avis.fiche_id',$input->Fiche)->count();
            $avis_non_repondu = $avis_total - $avis_repondu;
                   
            
            }else{
                $avis_total = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->leftJoin('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('rating', '<=', '3')->where('fiches.state', 'LIKE', 'COMPLETED')->count();
            $avis_repondu = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->join('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->where('rating', '<=', '3')->where('fiches.state', 'LIKE', 'COMPLETED')->count();
            $avis_non_repondu = $avis_total - $avis_repondu;
            }


            $data['stat'] = ['Total' =>$this->shortNumber($avis_total) , 'Repondu' => $this->shortNumber($avis_repondu) , 'NonRepondu' => $this->shortNumber($avis_non_repondu) ];

            if(isset($input->Fiche) && !empty($input->Fiche)){
                $dernier_negatif_avis = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->leftJoin('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')->whereIn('avis.fiche_id',$input->Fiche)->where('rating', '<=', '3')->orderBy('date', 'DESC')->first(['avis.*', 'fiches.locationName', 'fiches.name as FicheName', 'avisreponses.reponse', 'avisreponses.CreateOrUpadate', 'avisreponses.updated_at as reponse_date', 'franchises.logo']);

                
                }else{
                    $dernier_negatif_avis = Avi::Join('fiches', 'fiches.id', '=', 'avis.fiche_id')->leftJoin('avisreponses', 'avisreponses.avis_id', '=', 'avis.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')->where('rating', '<=', '3')->orderBy('date', 'DESC')->first(['avis.*', 'fiches.locationName', 'fiches.name as FicheName', 'avisreponses.reponse', 'avisreponses.CreateOrUpadate', 'avisreponses.updated_at as reponse_date', 'franchises.logo']);

                }
    
                if (isset($dernier_negatif_avis) && !empty($dernier_negatif_avis)) {
            if (isset($dernier_negatif_avis->reponse_date) && !empty($dernier_negatif_avis->reponse_date)) {
                $dernier_negatif_avis->reponse_date = Carbon::parse($dernier_negatif_avis->reponse_date)->translatedFormat('d/m/Y');
            }

          
            $now = Carbon::now();
            $end = Carbon::parse($dernier_negatif_avis->date);

            if ($years = $end->diffInYears($now)) {
                $dernier_negatif_avis->date = 'Il ya '.$years.' années';
            } elseif ($months = $end->diffInMonths($now)) {
                $dernier_negatif_avis->date = 'Il ya '.$months.' mois';
            } elseif ($weeks = $end->diffInWeeks($now)) {
                $dernier_negatif_avis->date = 'Il ya '.$weeks.' semaines';
            } else {
                $days = $end->diffInDays($now);
                $dernier_negatif_avis->date = 'Il ya '.$days.' jours';
            }

            $name = explode(' ', $dernier_negatif_avis->title);
            if (isset($name[0])) {
                $firstname = $name[0];
                $dernier_negatif_avis->title = $firstname;
            }
            if (isset($name[1])) {
                $lastname = $name[1];
                $dernier_negatif_avis->title .= ' <b>'.$lastname.'</b>';
            }
            $dernier_negatif_avis->logo = \Illuminate\Support\Facades\URL::to($dernier_negatif_avis->logo) ;


            $data['review'] = $dernier_negatif_avis;
        }else{
            $data["review"]=null;
        }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => $data,
                'status' => 200,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'status' => 400,
            ]);
        }
    }
    public function shortNumber($num)
    {
        $units = ['', 'K', 'M', 'B', 'T'];
        for ($i = 0; $num >= 1000; ++$i) {
            $num /= 1000;
        }

        return round($num, 1) . $units[$i];
    }
    public function Review_autocompele(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        $messages = [
            'Filtre_search' => 'la recherche est obligatoire',
        ];

        $input = [
            'Filtre_search' => $request->Filtre_search,
            'Franchise_id' => (int) $request->header('franchise'),
        ];

        $validator = Validator::make(
            $input,
            [
                            'Filtre_search' => 'required|string',
                        ],
            $messages
        );
        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json(
                [
                        'success' => false,
                           'message' => $message,
                        'status' => 422, ],
                422
            );
        }
        if ($validator->passes()) {
            try {
                $etiquettes = Etiquette::join('etiquetgroupes', 'etiquetgroupes.etiquette_id', '=', 'etiquettes.id')
            ->join('groupes', 'groupes.id', '=', 'etiquetgroupes.groupe_id')
            ->where('etiquettes.name', 'LIKE', '%'.$input->Filtre_search.'%')
            ->whereNotNull('etiquetgroupes.fiche_id')
            ->distinct()->get(['etiquettes.id', 'etiquettes.name', 'groupes.color as color']);

                $fiches = Fiche::where('fiches.locationName', 'LIKE', '%'.$input->Filtre_search.'%')->where('fiches.franchises_id', '=', $input->Franchise_id)->where('state', 'LIKE', 'COMPLETED')
            ->get(['fiches.id', 'fiches.locationName']);
                $datacomplete = [];
                foreach ($etiquettes as $key => $value) {
                    $datacomplete[] = ['id' => $value->id, 'name' => $value->name, 'color' => $value->color, 'type' => 'Etiquette'];
                }
                foreach ($fiches as $key => $value) {
                    $datacomplete[] = ['id' => $value->id, 'name' => $value->locationName, 'color' => '#0081C7', 'type' => 'Fiche'];
                }
                $data = ['autocomplete' => $datacomplete];

                return response()->json([
                'success' => true,
                'message' => 'Opération success',
                'data' => $data,
                'status' => 200,
            ]);
            } catch (\Throwable $th) {
                return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'status' => 400,
            ]);
            }
        }
    }

    public function All_reviews(Request $request)
    {
        $messages = [
            
            'Filtre.required' => 'Type d\'avis est obligatoire (Tous,Répondu,Non répondu)',
            'Count.required' => 'Nombre de avis à afficher est obligatoire',
            'Filtre.in' => 'Type d\'avis est obligatoire (Tous,Répondu,Non répondu)',
            'Rating.*.in' => 'Avis doit être compris entre 1..5',
        ];
        $fiches = null;
        $listfihes = [];
        $columns = [];
        $etiquetes = null;

        if (isset($request->Etiquette) && !empty($request->Etiquette)) {

            foreach ($request->Etiquette as $key => $et) {

                // code...

                if ($et['type'] == 'Fiche') {
                    $listfihes[] = $et['id'];
                } else {
                    $ettiquetslist =  Fiche::join('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'fiches.id')->join('etiquettes', 'etiquette_id', '=', 'etiquettes.id')->where("etiquettes.id", '=', $et['id'])->where('fiches.franchises_id', '=', $request->header('franchise'))->get("fiches.id");
 
                    foreach ($ettiquetslist as $key => $value) {
                        $listfihes[]=$value["id"];
                    }
                }
            }
        }



        $input = [
          
            'Fiche' => array_unique($listfihes),
            'Filtre' => $request->Filtre,
            'Rating' => $request->Rating,
            'Order' => $request->Order,
            'Count' => $request->Count,
        ];

        $validator = Validator::make(
            $input,
            [
                            'Fiche.*.id' => 'exists:fiches,id',
                          
                            'Filtre' => 'required|string|in:All,Reply,NoReply',
                           'Rating.*' => 'int|in:1,2,3,4,5',
                           'Order' => 'string|in:ASC,DESC',
                           'Count' => 'required|int',
                        ],
            $messages
        );

        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json(
                [
                'success' => false,
                   'message' => $message,
                'status' => 422, ],
                422
            );
        }
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            $avis = Avi::query();
            if (isset($input->Filtre)) {
                switch ($input->Filtre) {
                case 'Reply':
                    $avis->Join('avisreponses', 'avisreponses.avis_id', '=', 'avis.id');
                    $avis->join('fiches', 'avis.fiche_id', '=', 'fiches.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
                    $columns = ['avis.*', 'fiches.locationName', 'fiches.name as FicheName', 'avisreponses.reponse', 'avisreponses.CreateOrUpadate', 'avisreponses.updated_at as reponse_date', 'franchises.logo'];
                    $avis->whereNotNull('reponse');
                    break;
                    case 'NoReply':
                        $columns = ['avis.*', 'fiches.locationName', 'fiches.name as FicheName',  'franchises.logo'];
                        $reponse = Avisreponse::All('avis_id')->toArray();
                        $avis->whereNotIn('avis.id', $reponse);
                        $avis->join('fiches', 'avis.fiche_id', '=', 'fiches.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
                        break;
                default:
                $columns = ['avis.*', 'fiches.locationName', 'fiches.name as FicheName', 'avisreponses.reponse', 'avisreponses.CreateOrUpadate', 'avisreponses.updated_at as reponse_date', 'franchises.logo'];
                $avis->leftJoin('avisreponses', 'avisreponses.avis_id', '=', 'avis.id');
                $avis->join('fiches', 'avis.fiche_id', '=', 'fiches.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
                    break;
            }
            }

            /*             if (isset($input->Etiquette) && !empty($input->Etiquette)) {
                            $avis->Leftjoin('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'fiches.id')
                        ->Leftjoin('etiquettes', 'etiquettes.id', '=', 'etiquetgroupes.etiquette_id')
                        ->Where(function ($avis) use ($input) {
                            $avis->whereIn('etiquettes.id', array_column($input->Etiquette, 'id'));
                            if (isset($input->Fiche) && !empty($input->Fiche)) {
                                $avis->orwhereIn('fiches.id', array_column($input->Fiche, 'id'));
                            }
                        });
                        } */

            if ((!empty($input->Fiche) && (isset($input->Fiche)))) {
                $avis->whereIn('fiches.id', $input->Fiche);
            }
            if (isset($input->Rating) && !empty($input->Rating)) {
                $avis->whereIn('rating', $input->Rating);
            }
            if (isset($input->Order)) {
                $avis->orderBy('avis.date', $input->Order);
            }

            $count = $avis->distinct('avis.id')->count();

            $all_reviews = $avis->limit($input->Count)->distinct()->get($columns);
            foreach ($all_reviews as $value) {
                if (isset($value->reponse_date)) {
                    $value->reponse_date = Carbon::parse($value->reponse_date)->translatedFormat('d/m/Y');
                }
                if (isset($value->date)) {
                    $value->date = Carbon::parse($value->date)->translatedFormat('d/m/Y');
                }
                $value->logo = \Illuminate\Support\Facades\URL::to($value->logo) ;
            }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => ['reviews' => $all_reviews, 'Total' => $count],
                'status' => 200,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'status' => 400,
            ]);
        }
    }

    public function avis_classement(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            $dt = Carbon::now();
            $End_day = $dt->translatedFormat('Y-m-d');
            $Start_day = $dt->subMonth(2)->translatedFormat('Y-m-d');
            $Old_Start_date = $dt->subMonth(1)->translatedFormat('Y-m-d');
            $fiches = Fiche::where('franchises_id', '=', $request->header('franchise'))->get();
            $data = [];
            foreach ($fiches as $fiche) {
                $reviews = 0;
                $total = 0;
                $score = 0;

                $avis = Avi::where('fiche_id', '=', $fiche->id)->whereBetween('date', [$Start_day, $End_day]);

                $count = $avis->count();
                if ($count > 0) {
                    $avis_fiche = $avis->get();
                    foreach ($avis_fiche as $review) {
                        $reviews += $review->rating;
                        $total = $total + 1;

                        switch ($review->rating) {
                            case 1:
                                $score += $review->rating * 1;
                                break;
                            case 2:
                                $score += $review->rating * 2;
                                break;
                            case 3:
                                $score += $review->rating * 3;
                                break;
                            case 4:
                                $score += $review->rating * 6;
                                break;
                            case 5:
                                $score += $review->rating * 10;
                                break;
                            default:
                            $score += 0;
                                break;
                        }
                    }

                    $data[$fiche->id] = ['Id' => $fiche->id, 'Name' => $fiche->locationName, 'City' => $fiche->city, 'Rating' => number_format((float) $reviews / $total, 1, '.', ''), 'Count' => $count, 'Score' => $score];
                }
            }
            if (isset($data) && !empty($data)) {
                usort($data, function ($a, $b) {
                    return $a['Score'] <=> $b['Score'];
                });

                $data = array_reverse($data, false);

                foreach ($data as $key => $value) {
                    $reviews = 0;
                    $total = 0;
                    $score = 0;
                    $avis = Avi::where('fiche_id', '=', $value['Id'])->whereBetween('date', ["$Old_Start_date", "$Start_day"]);
                    $count = $avis->count();
                    if ($count > 0) {
                        $avis_fiche = $avis->get();
                        foreach ($avis_fiche as $review) {
                            $reviews += $review->rating;
                            $total = $total + 1;

                            switch ($review->rating) {
                            case 1:
                                $score += $review->rating * 1;
                                break;
                            case 2:
                                $score += $review->rating * 2;
                                break;
                            case 3:
                                $score += $review->rating * 3;
                                break;
                            case 4:
                                $score += $review->rating * 6;
                                break;
                            case 5:
                                $score += $review->rating * 10;
                                break;
                            default:
                            $score += 0;
                                break;
                        }
                        }
                    } else {
                        $score = 0;
                    }
                    $balance = $data[$key]['Score'] - $score;
                    $data[$key]['Balance'] = $balance;
                    switch ($balance) {
                    case $balance > 0:
                        $data[$key]['Classify'] = 0;
                        break;
                        case $balance < 0:
                            $data[$key]['Classify'] = 2;
                            break;
                    default:
                    $data[$key]['Classify'] = 1;
                    break;
                }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => $data,

                'status' => 200,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'status' => 400,
            ]);
        }
    }

    public function avis_wording(Request $request)
    {
        $messages = [
            'User_id.exists' => 'Ce utilisateur n\'existe pas',
            'User_id.required' => 'Utilisateur est Obligatoire)',
        ];

        $input = [
            'User_id' => $request->User_id,
        ];

        $validator = Validator::make(
            $input,
            [
                            'User_id' => 'required|exists:users,id',
                        ],
            $messages
        );

        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json(
                [
                'success' => false,
                   'message' => $message,
                'status' => 422, ],
                422
            );
        }
        try {
            $wording = [];
            if (!is_numeric($request->header('franchise'))) {
                return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
            }
            $avis = Avi::join('fiches', 'avis.fiche_id', '=', 'fiches.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->whereNotNull('content')->pluck('content')->toArray();
            foreach ($avis as $key => $words) {
                $lisword = str_word_count($words, 1);

                foreach ($lisword as $key => $word) {
                    if (strlen($word) > 4) {
                        if (!array_key_exists(trim($word), $wording)) {
                            $wording[trim($word)]['Count'] = 1;
                            $wording[trim($word)]['Word'] = $word;
                        } else {
                            ++$wording[trim($word)]['Count'];
                        }
                    }
                }
            }

            usort($wording, function ($a, $b) {
                return $a['Count'] <=> $b['Count'];
            });

            foreach (array_reverse($wording) as $key => $value) {
                if ($value['Count'] > 20) {
                    $wordingList[] = $value;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => ['WordList' => $wordingList, 'Total' => count($wordingList)],
                'status' => 200,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'status' => 400,
            ]);
        }
    }

    public function avis_wording_negatif(Request $request)
    {
        $messages = [
            'User_id.exists' => 'Ce utilisateur n\'existe pas',
            'User_id.required' => 'Utilisateur est Obligatoire)',
        ];

        $input = [
            'User_id' => $request->User_id,
        ];

        $validator = Validator::make(
            $input,
            [
                            'User_id' => 'required|exists:users,id',
                        ],
            $messages
        );

        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json(
                [
                'success' => false,
                   'message' => $message,
                'status' => 422, ],
                422
            );
        }
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            $wording = [];
            $wordingList = [];

            $avis = Avi::join('fiches', 'avis.fiche_id', '=', 'fiches.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->whereNotNull('content')->where('rating', '<=', 2)->pluck('content')->toArray();
            foreach ($avis as $key => $words) {
                $lisword = str_word_count($words, 1);

                foreach ($lisword as $key => $word) {
                    if (strlen($word) > 4) {
                        if (!array_key_exists(trim($word), $wording)) {
                            $wording[trim($word)]['Count'] = 1;
                            $wording[trim($word)]['Word'] = $word;
                        } else {
                            ++$wording[trim($word)]['Count'];
                        }
                    }
                }
            }

            usort($wording, function ($a, $b) {
                return $a['Count'] <=> $b['Count'];
            });

            foreach (array_reverse($wording) as $key => $value) {
                if ($value['Count'] > 6) {
                    $wordingList[] = $value;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => ['WordList' => $wordingList, 'Total' => count($wordingList)],
                'status' => 200,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'status' => 400,
            ]);
        }
    }

    public function avis_wording_positif(Request $request)
    {
        $messages = [
            'User_id.exists' => 'Ce utilisateur n\'existe pas',
            'User_id.required' => 'Utilisateur est Obligatoire)',
        ];

        $input = [
            'User_id' => $request->User_id,
        ];

        $validator = Validator::make(
            $input,
            [
                            'User_id' => 'required|exists:users,id',
                        ],
            $messages
        );

        $input = (object) $input;

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json(
                [
                'success' => false,
                   'message' => $message,
                'status' => 422, ],
                422
            );
        }
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            $wording = [];
            $wordingList = [];

            $avis = Avi::join('fiches', 'avis.fiche_id', '=', 'fiches.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->whereNotNull('content')->where('rating', '>=', 5)->pluck('content')->toArray();
            foreach ($avis as $key => $words) {
                $lisword = str_word_count($words, 1);

                foreach ($lisword as $key => $word) {
                    if (strlen($word) > 4) {
                        if (!array_key_exists(trim($word), $wording)) {
                            $wording[trim($word)]['Count'] = 1;
                            $wording[trim($word)]['Word'] = $word;
                        } else {
                            ++$wording[trim($word)]['Count'];
                        }
                    }
                }
            }

            usort($wording, function ($a, $b) {
                return $a['Count'] <=> $b['Count'];
            });

            foreach (array_reverse($wording) as $key => $value) {
                if ($value['Count'] > 20) {
                    $wordingList[] = $value;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => ['WordList' => $wordingList, 'Total' => count($wordingList)],
                'status' => 200,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'status' => 400,
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $messages = [
            'code.required' => 'Vérifier Votre Code!',
            'title.required' => 'Vérifier Votre title!',
            'contents.required' => 'Vérifier Votre content!',
            'rating.required' => 'Vérifier Votre rating!',
            'fiche_id.required' => 'Vérifier Votre fiche !',
            'date.required' => 'Vérifier Votre date!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];

        $validator = Validator::make(
            $request->all(),
            [
                'code' => 'required',
                'title' => 'required|max:100',
                'date' => 'date',
                'rating' => 'numeric|min:0|max:4',
                'fiche_id' => 'exists:fiches,id',
            ],
            $messages
        );
        if ($validator->fails()) {
            return response()->json(
                [
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422,
                'token' => 'Bearer '.$token, ],
                422
            );
        }
        if ($validator->passes()) {
            try {
                $data = $request->all();

                $avi = Avi::create($data);

                return response()->json([
                    'success' => true,
                    'message' => 'Avis ajouté avec succès',
                    'data' => $avi,
                    'status' => Response::HTTP_OK,
                    'token' => 'Bearer '.$token,
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400,
                        'token' => 'Bearer '.$token,
                    ],
                    400
                );
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Avi $avi)
    {
        $avis = Avi::with('fiche:id,locationName,name')->find($avi->id);
        if (!$avis) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Avis not found.',

                'status' => 400,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avis id '.$avi->id,
            'data' => $avis,

            'status' => 200,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Avi $avi)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Avi $avi)
    {
        $messages = [
            'code.required' => 'Vérifier Votre Code!',
            'title.required' => 'Vérifier Votre title!',
            'contents.required' => 'Vérifier Votre content!',
            'rating.required' => 'Vérifier Votre rating!',
            'fiche_id.required' => 'Vérifier Votre fiche !',
            'date.required' => 'Vérifier Votre date!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];

        $validator = Validator::make(
            $request->all(),
            [
                'code' => 'required',
                'title' => 'required|max:100',
                'date' => 'date',
                'rating' => 'numeric|min:0|max:4',
                'fiche_id' => 'exists:fiches,id',
            ],
            $messages
        );

        if ($validator->fails()) {
            return response()->json(
                [
                'succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422,
                'token' => 'Bearer '.$token, ],
                422
            );
        }
        if ($validator->passes()) {
            try {
                $avi->code = $request->code;
                $avi->title = $request->title;
                $avi->date = $request->date;
                $avi->rating = $request->rating;
                $avi->fiche_id = $request->fiche_id;
                $avi->update();

                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $avi,

                    'status' => Response::HTTP_OK,
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => $ex->getMessage(),
                        'status' => 400,
                    ]
                );
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Avi $avi)
    {
        try {
            $avi->delete();

            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Avi could not be deleted',
                'status' => 500,
            ], 500);
        }
    }
}
