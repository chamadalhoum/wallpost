<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Etiquetgroupe;
use App\Models\Fiche;
use App\Models\Ficheuser;
use App\Models\Franchise;
use App\Models\Groupe;
use App\Models\Post;
use App\Models\Role;
use App\Models\Statistique;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\DB;
use App\Helper\Helper;
use App\Models\Fichehour;
use App\Models\Notification;
use App\Models\Photo;
use App\Models\Service;
use App\Models\Attribute;
use App\Models\Iconfiche;
use App\Models\Morehours;
use App\Models\Paramater;
use App\Models\Postfiche;
use App\Models\Servicearea;

class FranchiseController extends Controller
{
    public $mybusinessService;
    public $placeID;
    public $admins;
    public $invitations;

    public function __construct() {
      /*  $this->mybusinessService = Helper::GMB();
        $this->admins = $this->mybusinessService->accounts_locations_admins;
        $this->invitations = $this->mybusinessService->accounts_invitations;
        $this->placeID = Helper::AdminAction();*/
    }


    public function franchise_classifyfff(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            $data=[];
            $dt = Carbon::now();
            $End_day = $dt->translatedFormat('Y-m-d');
            $Start_day = $dt->subDay(30)->translatedFormat('Y-m-d');
            $Old_Start_date = $dt->subDay(30)->translatedFormat('Y-m-d');
            $fiches = Fiche::where('franchises_id', '=', $request->header('franchise'))->where('state', 'LIKE', 'COMPLETED')->leftJoin('states', 'fiches.id', '=', 'states.fiche_id')->where('states.isPublished',1)
            ->where('states.isVerified', 1)->get();
//->where('OpenInfo_status', '<>', 'CLOSED_PERMANENTLY')
            foreach ($fiches as $key => $fiche) {
                $stats = Statistique::where('fiche_id', '=', $fiche->id)->whereBetween('date', ["$Start_day", "$End_day"]);

                $data[] = ['Id' => $fiche->id, 'Name' => $fiche->locationName, 'City' => $fiche->city, 'Views' => round(($stats->sum('viewsSearch') + $stats->sum('viewsMaps')) / 2)];
            }

            usort($data, function ($a, $b) {
                return $a['Views'] <=> $b['Views'];
            });
            $data = array_reverse($data, false);
            foreach ($data as $key => $value) {
                $stats2 = Statistique::where('fiche_id', '=', $value['Id'])->whereBetween('date', ["$Old_Start_date", "$Start_day"]);
                $view = ($stats2->sum('viewsSearch') + $stats2->sum('viewsMaps')) / 2;
                $div = ($view == 0) ? 1 : $view;

                $diff = $value['Views'] - $view;
                $percent = 0;
                $percent = (($value['Views'] - $view) / $div) * 100;
                if ($value['Views'] > 0 && $view == 0) {
                    $percent = 100;
                }
                if ($value['Views'] == 0 && $view == 0) {
                    $percent = 0;
                }
                if ($value['Views'] == 0 && $view > 0) {
                    $percent = -100;
                }
                $data[$key]['diff'] = $value['Views'] - $view;
                $data[$key]['Views'] = $this->shortNumber($value['Views']) ;
                $data[$key]['lastViews'] = (($stats2->sum('viewsSearch') + $stats2->sum('viewsMaps')) / 2);
                if ($diff > 0) {
                    $data[$key]['Classify'] = 0;
                    $data[$key]['Percent'] = '+'.number_format($percent, 1, '.', '').'%';
                } elseif ($diff == 0) {
                    $data[$key]['Classify'] = 1;
                    $data[$key]['Percent'] = number_format($percent, 1, '.', '').'%';
                } else {
                    $data[$key]['Classify'] = 2;
                    $data[$key]['Percent'] = number_format($percent, 1, '.', '').'%';
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => ['TOP' => array_slice($data, 0, 3), 'FLOP' => array_slice($data, -3, 3), 'ALL' => $data],
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
    public function franchise_classify(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        try {
            $data=[];
            $dt = Carbon::now();
            $End_day = $dt->translatedFormat('Y-m-d');
            $Start_day = $dt->subDay(30)->translatedFormat('Y-m-d');
            $Old_Start_date = $dt->subDay(30)->translatedFormat('Y-m-d');
            $table2=Statistique::rightJoin('fiches','statistiques.fiche_id','=','fiches.id');
            $data = Fiche::where('fiches.franchises_id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')
            ->leftjoin('statistiques','fiches.id','=','statistiques.fiche_id')
            //->unionAll($data)
            ->whereBetween('statistiques.date', ["$Start_day", "$End_day"])
            ->select(DB::raw('ROUND((SUM(statistiques.viewsSearch)+SUM(statistiques.viewsMaps))/2) as Views,fiches.id as Id, fiches.locationName as Name, fiches.city as City'))
            ->groupBy(DB::raw("statistiques.fiche_id"))
            ->orderByDESC(DB::raw("ROUND((SUM(statistiques.viewsSearch)+SUM(statistiques.viewsMaps))/2)"))
            ->get()->toarray();
          
//->where('OpenInfo_status', '<>', 'CLOSED_PERMANENTLY')
           /* foreach ($fiches as $key => $fiche) {
                $stats = Statistique::where('fiche_id', '=', $fiche->id)->whereBetween('date', ["$Start_day", "$End_day"]);

                $data[] = ['Id' => $fiche->id, 'Name' => $fiche->locationName, 'City' => $fiche->city,
                 'Views' => round(($stats->sum('viewsSearch') + $stats->sum('viewsMaps')) / 2)];
            }*/

           /* usort($data, function ($a, $b) {
                return $a['Views'] <=> $b['Views'];
            });*/
           // $data = array_reverse($data, false);
            foreach ($data as $key => $value) {
                $stats2 = Statistique::where('fiche_id', '=', $value['Id'])->whereBetween('date', ["$Old_Start_date", "$Start_day"]);
                $view = ($stats2->sum('viewsSearch') + $stats2->sum('viewsMaps')) / 2;
                $div = ($view == 0) ? 1 : $view;

                $diff = $value['Views'] - $view;
                $percent = 0;
                $percent = (($value['Views'] - $view) / $div) * 100;
                if ($value['Views'] > 0 && $view == 0) {
                    $percent = 100;
                }
                if ($value['Views'] == 0 && $view == 0) {
                    $percent = 0;
                }
                if ($value['Views'] == 0 && $view > 0) {
                    $percent = -100;
                }
                $data[$key]['diff'] = $value['Views'] - $view;
                $data[$key]['Views'] = $this->shortNumber($value['Views']) ;
                $data[$key]['lastViews'] = (($stats2->sum('viewsSearch') + $stats2->sum('viewsMaps')) / 2);
                if ($diff > 0) {
                    $data[$key]['Classify'] = 0;
                    $data[$key]['Percent'] = '+'.number_format($percent, 1, '.', '').'%';
                } elseif ($diff == 0) {
                    $data[$key]['Classify'] = 1;
                    $data[$key]['Percent'] = number_format($percent, 1, '.', '').'%';
                } else {
                    $data[$key]['Classify'] = 2;
                    $data[$key]['Percent'] = number_format($percent, 1, '.', '').'%';
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Operation success.',
                'data' => ['TOP' => array_slice($data, 0, 3), 'FLOP' => array_slice($data, -3, 3), 'ALL' => $data],
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
    public function shortNumber($num)
    {
        $units = ['', 'K', 'M', 'B', 'T'];
        for ($i = 0; $num >= 1000; ++$i) {
            $num /= 1000;
        }

        return round($num, 1) . $units[$i];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
            'email.required' => 'Vérifier Votre email!',
            'state.required' => 'Vérifier Votre etat!',
            'socialReason.required' => 'Vérifier votre raison social!',
            'logo.required' => 'Vérifier votre logo!',
            'type.required' => 'Type invalide!',
            'name.required' => 'Vérifier Votre nom Commercial!',
            'taxRegistration.required' => 'Vérifier votre matricule Fiscale!',
            'cinGerant.required' => 'Vérifier votre matricule Fiscale!',
            'statutFiscale.required' => 'Vérifier Votre status fiscale!',
            'tradeRegistry.required' => 'Vérifier registre Commerce!',
            'cinGerant.required' => 'Vérifier Cin!',
            'fax.required' => 'Vérifier votre numéro Fax!',
            'phone.required' => 'Vérifier Votre numéro télephone!',
            'address.required' => 'Vérifier Votre adresse!',
            'city.required' => 'Vérifier Votre Ville!',
            'country.required' => 'Vérifier Votre pays!',
            'postalCode.required' => 'Vérifier Votre code postal!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];

        $validator = Validator::make($request->all(),
                        ['state' => 'required|max:45',
                            'socialReason' => 'required|max:45',
                            'logo' => 'images|mimes:jpg,jpeg,png,gif',
                            'type' => 'required|max:45',
                            'name' => 'required|max:255',
                            'taxRegistration' => 'required|unique:franchises|max:255',
                            'statutFiscale' => 'required|unique:franchises|max:255',
                            'tradeRegistry' => 'required|unique:franchises|max:45',
                            'cinGerant' => 'unique:franchises|max:10',
                            'fax' => 'min:8|max:12',
                            'phone' => 'min:8|max:12',
                            'email' => 'required|unique:franchises|email',
                            'address' => 'max:45',
                            'postalCode' => 'max:6',
                            'city' => 'max:6',
                            'country' => 'max:6',
                        ], $messages
        );
        if ($validator->fails()) {
            return response()->json(['succes' => false,
                        'message' => $validator->errors()->toArray(),
                        'status' => 422, ],
                            422);
        }
        if ($validator->passes()) {
            try {
                $data = $request->all();
                if ($request->logo) {
                    $request->logo->store('/photo/franchise', 'public');

                    $imageName = $request->name.'_'.time().'.'.$request->logo->extension();

                    $request->logo->move(public_path('/photo/franchise'), $imageName);
                    $request->logo = $imageName;

                    $data['logo'] = $imageName;
                }

                $franchise = Franchise::create($data);

                return response()->json([
                            'success' => true,
                            'message' => 'Franchise ajouté avec succès',
                            'data' => $franchise,
                            'status' => Response::HTTP_OK,
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
    public function show(Franchise $franchise)
    {
        if (!$franchise) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, franchise not found.',
                        'status' => 400,
                            ], 400);
        }

        return response()->json([
                    'success' => true,
                    'message' => 'Franchise id '.$franchise->id,
                    'data' => $franchise,
                    'status' => 200,
                        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Franchise $franchise)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Franchise $franchise)
    {
        $messages = [
            'email.required' => 'Vérifier Votre email!',
            'state.required' => 'Vérifier Votre etat!',
            'socialReason.required' => 'Vérifier votre raison social!',
            'logo.required' => 'Vérifier votre logo!',
            'type.required' => 'Type invalide!',
            'name.required' => 'Vérifier Votre nom Commercial!',
            'taxRegistration.required' => 'Vérifier votre matricule Fiscale!',
            'statutFiscale.required' => 'Vérifier Votre status fiscale!',
            'tradeRegistry.required' => 'Vérifier registre Commerce!',
            'cinGerant.required' => 'Vérifier Cin!',
            'fax.required' => 'Vérifier votre numéro Fax!',
            'phone.required' => 'Vérifier Votre numéro télephone!',
            'address.required' => 'Vérifier Votre adresse!',
            'city.required' => 'Vérifier Votre Ville!',
            'country.required' => 'Vérifier Votre pays!',
            'postalCode.required' => 'Vérifier Votre code postal!',
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];

        $validator = Validator::make($request->all(),
                        ['state' => 'required|max:45',
                            'socialReason' => 'required|max:45',
                            //  "logo" => 'required|images|mimes:jpg,jpeg,png,gif',
                            'type' => 'required|max:45',
                            'name' => 'required|max:255',
                            'taxRegistration' => 'required|unique:franchises|max:255',
                            'statutFiscale' => 'required|unique:franchises|max:255',
                            'tradeRegistry' => 'required|unique:franchises|max:45',
                            'cinGerant' => 'unique:franchises|max:10',
                            'fax' => 'min:8|max:12',
                            'phone' => 'min:8|max:12',
                            'email' => 'required|unique:franchises|email',
                            'address' => 'max:45',
                            'postalCode' => 'max:6',
                            'city' => 'max:6',
                            'country' => 'max:6',
                        ], $messages
        );

        if ($validator->fails()) {
            return response()->json(['succes' => false,
                        'message' => $validator->errors()->toArray(),
                        'status' => 422, ],
                            422);
        }
        if ($validator->passes()) {
            try {
                if ($request->logo) {
                    $request->logo->store('/photo/franchise', 'public');

                    $imageName = $request->name.'_'.time().'.'.$request->logo->extension();

                    $request->logo->move(public_path('images'), $imageName);
                    $request->logo = $imageName;

                    $image = $imageName;
                    $franchise->logo = $image;
                }

                $franchise->socialReason = $request->socialReason;
                $franchise->state = $request->state;
                $franchise->type = $request->type;
                $franchise->name = $request->name;
                $franchise->taxRegistration = $request->taxRegistration;
                $franchise->statutFiscale = $request->statutFiscale;
                $franchise->tradeRegistry = $request->tradeRegistry;
                $franchise->cinGerant = $request->cinGerant;
                $franchise->fax = $request->fax;
                $franchise->phone = $request->phone;
                $franchise->email = $request->email;
                $franchise->address = $request->address;
                $franchise->postalCode = $request->postalCode;
                $franchise->city = $request->city;
                $franchise->country = $request->country;
                $franchise->update();

                return response()->json([
                            'success' => true,
                            'message' => 'Mise a jour traitée avec succes',
                            'data' => $franchise,
                            'status' => Response::HTTP_OK,
                                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json([
                            'success' => false,
                            'message' => $ex->getMessage(),
                            'status' => 400,
                                ], $ex->getCode()
                );
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Franchise $franchise)
    {
        try {
            $franchise->delete();

            return response()->json([
                        'success' => true,
                        'message' => 'Supprimer avec succées',
                        'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                        'success' => false,
                        'message' => 'franchise could not be deleted',
                        'status' => 500,
                            ], 500);
        }
    }

    public function franchise(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        $messages = [
            
            'Fiche_id.exists' => 'Fiche  est indisponible',
            'Order.in' => 'doit être ASC ou DESC',
            'Recent.in' => 'doit être TRUE ou FALSE',
        ];
        $input=array();
        if (isset($request['idfiche']) && !empty($request['idfiche'])) {
            $input["Fiche_id"]=$request->idfiche;
        }
        if (isset($request['search']) && !empty($request['search'])) {
            $input["Search"]=$request->search;
        }
        if (isset($request['Ordrefiche']) && !empty($request['Ordrefiche'])) {
            $input["Order"]=$request->Ordrefiche;
            
        }
        if (isset($request['drapeaux']) && !empty($request['drapeaux'])) {
            $input["Drapeaux"]=$request->drapeaux;
            
        }
      
        if (isset($request['plusrecent']) && !empty($request['plusrecent'])) {
            $input["Recent"]=$request->plusrecent;
            
        }
      

        $validator = Validator::make(
            $input,
            [
                            'Fiche_id.exists' => 'exists:fiches,id',
                            'Order' => 'boolean',
                            'Recent' => 'in:ASC,DESC,ALPHA',
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
            $listfiches = [];
            $details = null;

           $filtre= ['etat'=>'All',"namegroupe"=>"Toutes les fiches","status"=>true,'list'=>[
            ['path'=>\Illuminate\Support\Facades\URL::to('/') ."/icon/complet.svg",'etat'=>'complet',"namedr"=> "2/2 groupes d'étiquettes","status"=>true],
            ['path'=>\Illuminate\Support\Facades\URL::to('/') ."/icon/aucune.svg",'etat'=>'aucune',"namedr"=> "1/2 groupes d'étiquettes","status"=>true],
            ['path'=>\Illuminate\Support\Facades\URL::to('/') ."/icon/manque.svg",'etat'=>'manque',"namedr"=> "0/2 groupes d'étiquettes","status"=>true]]];
                      
      $fiches = Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id')
      ->join('profilincompletes','fiches.id','=','profilincompletes.fiche_id')
      ->where("fiches.franchises_id","=",$request->header('franchise'))
      ->where("ficheusers.user_id","=",Auth()->user()->id)
      ->where('fiches.state', 'LIKE', 'COMPLETED')
      ;
     
      $nbfrachise = $fiches->count();
      $fichestab=array();
    
            if (isset($input->Search) && !empty($input->Search)) {
               $fiches->Where('fiches.locationName', 'LIKE',$input->Search.'%');
            }
          
            else {
              //  $fiches->orderBy("fiches.locationName");
            if(isset($input->Order) && !empty($input->Order) && empty($input->Recent)){
                $fiches->orderBy("fiches.locationName");
            }
          //  if($input->Recent==="ALPHA"){
               if(isset($input->Recent) && !empty($input->Recent) && ($input->Recent==="ALPHA")){
                            $fiches->orderBy("fiches.locationName");
                            
                        }
            if(isset($input->Recent) && !empty($input->Recent) && ($input->Recent != "ALPHA")){
               
                            $fiches->orderBy("fiches.locationName",$input->Recent);
            }  
        }
             $fiche=array();
        if(isset($input->Drapeaux) && !empty($input->Drapeaux) && ($input->Drapeaux !="All")) {

            foreach($request->drapeaux as $Drapeaux ){

         
                if($Drapeaux['status']){
                    $drapauxlist[]=$Drapeaux['path'];
                 }
                                   
                                             
                                          
                                  $Drapeaux['status']==true?  $status=$Drapeaux['status']: $status=false;
                          
                                  $listdrapeux[]=['path'=>\Illuminate\Support\Facades\URL::to('/') ."/icon/".$Drapeaux['etat'].".svg",'etat'=>$Drapeaux['etat'],'namedr'=>$Drapeaux['namedr'],"status"=>$status];
                                $filtre=['etat'=>'All',"namegroupe"=>"Toutes les fiches","status"=>false,'list'=>$listdrapeux];
                                                
                                                    
            }

            $fiches->when($drapauxlist,function ($query) use($drapauxlist){
                        $query->whereIN('profilincompletes.etat', $drapauxlist);
                        }) ;
                         } 
                        
           
            $fichefrist=[];
              if(!isset($input->Fiche_id) || empty($input->Fiche_id) || ($input->Fiche_id ==='null'  || $input->Fiche_id === null)){
                $fichefrist=$fiches->get(['profilincompletes.fiche_id','profilincompletes.etat',
                'profilincompletes.fiche_id',
                'fiches.locationName',
              'fiches.franchises_id as franchise_id','ficheusers.user_id']);
           
            }else{
               
                    $fichefrist=Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id') ->leftjoin('profilincompletes','profilincompletes.fiche_id','=','fiches.id')
                    ->where("fiches.franchises_id","=",$request->header('franchise'))->where("ficheusers.user_id","=",Auth()->user()->id)->where('fiches.state', 'LIKE', 'COMPLETED')
                    ->where('profilincompletes.fiche_id',$input->Fiche_id)
                    ->get(['profilincompletes.fiche_id','profilincompletes.etat',
                    'profilincompletes.fiche_id',
                    'fiches.locationName',
                  'fiches.franchises_id as franchise_id','ficheusers.user_id']);

            }
           
            $details=[];
            $listfiches=[];
     if(isset($fichefrist[0])){
        
        $fichest=$fiches->select(DB::raw('COALESCE(false) AS status'),
        'profilincompletes.etat',
        'profilincompletes.fiche_id',
        'fiches.locationName',
      'fiches.franchises_id as franchise_id','ficheusers.user_id')
     ->where('profilincompletes.fiche_id','!=',$fichefrist[0]->fiche_id)
     ->get();
    $collection = collect($fichest);

    $collection->push(['status'=>true,
    'etat'=>$fichefrist[0]->etat,
    'fiche_id'=>$fichefrist[0]->fiche_id,
    'locationName'=>$fichefrist[0]->locationName,
    'franchises_id'=>$fichefrist[0]->franchises_id,
    'user_id'=>$fichefrist[0]->user_id]);

    $collection->all();

    $sorted = $collection->SortByDesc('status');

    $listfiches = $sorted->values()->all();
    $nbfrachise=count($listfiches);
    $details= $this->fichebyid($fichefrist[0]->fiche_id, $request->header('franchise'));
     }
          
        return response()->json([
                        'success' => true,
                        'message' => 'Liste de fiches',
                        'nbfrachise' => $this->shortNumber($nbfrachise),
                        'nbfrachisenb' => $nbfrachise,
                        'fiches' =>$listfiches,
                        'details' => $details,
                        'filtre'=>$filtre,
                   'status' => 200, ]);
        } catch (Exception $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, fiches not found.',
                        'status' => 400,
                            ], 400);
        }
    }
  
    public function fichebyid($id, $idfranchise)
    {
        $pendingInvitation=0;      
        $tabsh = [];
               $listlibelle = null;
                $fiche = Fiche::Where('fiches.id', $id)->first();
              $listutilisateur=  FicheuserController::getUser($fiche->name);
               foreach($listutilisateur as $user){
                        $tabuser[]=$user['name'];
                    }
                    $utilisateurfiche= Ficheuser::leftjoin('users', 'users.id', 'ficheusers.user_id')
                    ->leftjoin('roles', 'roles.id', 'ficheusers.role_id')
                  //  ->whereIN('ficheusers.role_id', [4,5,6,7])
                    ->select( 'ficheusers.role_id','users.lastname','ficheusers.pendingInvitation','ficheusers.namefiche','ficheusers.user_id')
                    ->where('ficheusers.fiche_id', $id)
                    ->where('ficheusers.franchise_id', $idfranchise)->get();
                
                    $i=0;
                    Ficheuser::WhereNOTIN('role_id',[1,2,3])->delete();
                   
                    foreach($listutilisateur as $new){
                        $pendingInvitation=0;
                        $roles=Role::where('nameenglais',$new['role'])->first();
                         if (array_key_exists('pendingInvitation', $new)) {
                            if(isset( $new['pendingInvitation'])){
                                $new['pendingInvitation']==true?$pendingInvitation=1:$pendingInvitation=0;
                            }
                        }
                         $users=User::where('lastname','=',$new['admin'])->first();
                        if($users){
                            if($users->role_id ==1 || $users->role_id ==2 || $users->role_id ==3){
                               $Ficheuser= Ficheuser::Where('fiche_id',$id)->where('user_id',$users->id)->where('franchise_id',$idfranchise)->first();
                               $Ficheuser->namefiche=$new['name'];
                               $Ficheuser->pendingInvitation=$pendingInvitation;
                               $Ficheuser->update();
                            }else{
                                Ficheuser::create(['user_id'=>$users->id,'fiche_id'=>$id,'role_id'=>$roles->id,'namefiche'=>$new['name'],
                                'franchise_id'=>$idfranchise,
                               'pendingInvitation'=>$pendingInvitation]);
                               if(Ficheuser::Where('namefiche',$user['name'])->exists()){
                                $userfiche= Ficheuser::Where('namefiche',$user['name'])->first();
                                 $user_lastname=$user['admin'];
                                 $user=User::find($userfiche->user_id);
                                 $user->lastname=$user_lastname;
                                 $user->update();
              }
                            }
                        }
                    }
                    /*   foreach($utilisateurfiche as $fichesu){
                        $user_id=$fichesu->user_id;
                      
                           if(!in_array($fichesu->namefiche,$tabuser)){
                            $user_id=$fichesu->user_id;
                          foreach($listutilisateur as $new){
                           $roles=Role::where('nameenglais',$new['role'])->first();
                            if (array_key_exists('pendingInvitation', $new)) {
                              $new['pendingInvitation']==true?$pendingInvitation=1:$pendingInvitation=0;
                            }
                            Ficheuser::WhereNOTIN('role_id',[1,2,3])->delete();
                            $user=User::find($userfiche->user_id);
                            $user->lastname=$user_lastname;
                            Ficheuser::create(['user_id'=>$user_id,'fiche_id'=>$id,'role_id'=>$roles->id,'namefiche'=>$new['name'],
                            'franchise_id'=>$idfranchise,
                           'pendingInvitation'=>$pendingInvitation]);
            /* if(!Ficheuser::Where('namefiche',$new['name'])->exists()){
                Ficheuser::where('ficheusers.fiche_id', $id)
                ->where('ficheusers.franchise_id', $idfranchise)
                ->where('user_id',$user_id)
                ->where('namefiche',$fichesu->namefiche)
                ->where('pendingInvitation',1)->delete(); 
                Ficheuser::updateOrCreate(['user_id'=>$user_id,'fiche_id'=>$id,'role_id'=>$fichesu->role_id],['namefiche'=>$new['name'],
               'franchise_id'=>$idfranchise,
              'pendingInvitation'=>$pendingInvitation]);
            
            }     */
    
                  
                       // $i++;
                         //  } 
                          //
                         //  $i++;
                          
                       // }*/
                       
                     
  
        $metadatas = Fiche::leftjoin('metadatas', 'metadatas.fiche_id', 'fiches.id')->where('fiches.id', $id)->
                        select('metadatas.metadatasId', 'metadatas.mapsUrl', 'metadatas.newReviewUrl', 'metadatas.type', 'metadatas.id')->get();
     
                        $utilisateur = Ficheuser::leftjoin('users', 'users.id', 'ficheusers.user_id')
                        ->leftjoin('roles', 'roles.id', 'ficheusers.role_id')
                     //   ->whereIN('ficheusers.role_id', [4,5,6,7])
                  
                        ->select('roles.name as type', 'users.id as userid', 'users.email', 'users.lastname as firstname', 
                         'users.firstname as lastname', 'users.username','ficheusers.pendingInvitation','ficheusers.namefiche',
                         DB::raw('CONCAT ("'.\Illuminate\Support\Facades\URL::to('/app/public/photos/').'/",users.photo) AS photo'))
                        ->where('ficheusers.fiche_id', $id)
                        ->where('ficheusers.franchise_id', $idfranchise)->get();
       
        $i=0;
        $ordernotif= ['["', '"]'];

        $notification = explode('","', str_replace($ordernotif, '', $fiche->notification));
       
      $tel=FranchiseController::notif('primaryPhone',$id);

            $numerotel[] = ['phone' => ['countryCode' => $fiche->country,
            'dialCode' => '+33',
            'e164Number' => '+33 '.$fiche->primaryPhone,
            'internationalNumber' => '+33 '.$fiche->primaryPhone,
            'nationalNumber' => $fiche->primaryPhone,
            'number' => $fiche->primaryPhone,
            'etatvalidation'=>$tel
            
            ]];
        
        $order = ['[', ']', '"'];
        $additionalPhones = null;
      
        $teladditionalPhones=FranchiseController::notif('additionalPhones',$id);
        if($fiche->additionalPhones){

           
        
       $array = explode(',', str_replace($order, '',$fiche->additionalPhones));
        
           
            foreach($array as $arr){
            $numerotel[] = ['phone' => ['countryCode' => $arr,
            'dialCode' => '+33',
            'e164Number' => '+33 '.$arr,
            'internationalNumber' => '+33 '.$arr,
            'nationalNumber' => $arr,
            'number' =>$arr,
            'etatvalidation'=>$teladditionalPhones,
            
            ]];
            }
      
       
    }
    $etatvalidationtel=true;
    if($teladditionalPhones ==false ||$tel ==false ){
    $etatvalidationtel=false;
}
$orders = ['["', '"]'];
        $arraylabel = explode('","', str_replace($orders, '', $fiche->labels));
        if ($fiche->labels) {
            $etatlibelleold= FranchiseController::notif('labels',$id);
            $i=0;
            $etatlibelleold=true;
            foreach ($arraylabel as $arrlab) {
                   
                    $listlibelle[] = ['libelle_value' =>  $arrlab,
                    'etatvalidation'=>$etatlibelleold];
        }   
        } 

        if ($fiche->adwPhone) {
           
            $adwPhone = [
                'countryCode' => $fiche->country,
                'dialCode' => '+33 ',
                'e164Number' => '+33 '.$fiche->adwPhone,
                'internationalNumber' => '+33 '.$fiche->adwPhone,
                'nationalNumber' => $fiche->adwPhone,
                'number' => $fiche->adwPhone,
                'etatvalidation'=>in_array('adWordsLocationExtensions.adPhone', $notification)?false:true,
               

            ];
        } else {
            $adwPhone = null;
        }
        $post = Post::leftJoin('categoriesproduits', 'posts.catprod_id', '=', 'categoriesproduits.id')
                ->leftJoin('metadatas', 'metadatas.fiche_id', '=', 'posts.fiche_id')
                ->leftJoin('postfiches', 'postfiches.post_id', '=', 'posts.id')
                ->where('postfiches.fiche_id', $id)
                ->select('posts.id as post_id', 'categoriesproduits.displayName', 'posts.*')
                ->get();
        $postab = [];
        if (count($post) > 0) {
            $i = 0;
            foreach ($post as $pos) {
                $url = \Illuminate\Support\Facades\URL::to('/') .'/photos/'.$pos->media_url;
           
                $path = \Illuminate\Support\Facades\URL::to('/') .'/photos/'.$pos->media_url;
                $postab[] = ['Categorie_produit' => $pos->displayName,
                    'Description_produit' => $pos->summary,
                    'Nom_produit' => $pos->name,
                    'Prix_maximal' => $pos->prix_max,
                    'Prix_minimal' => $pos->prix_min,
                    'Prix_produit' => $pos->prix_produit,
                    'id_fiche' => $id,
                    'lien_produit' => $pos->action_url,
                    'media_type' => $pos->media_type,
                    'photo_produit' => $path,
                    'produit_id' => $pos->post_id,
                    'Googleurl' => $pos->newReviewUrl,
                    'calltoaction' => $pos->calltoaction];
                ++$i;
            }
        }
        if ($fiche->status === 'isVerified') {
        }
        $tab = $tabcategorie = Categorie::where('fiche_id', 1)->get();

        $ouverture = null;
        $OpenInfo_opening_date = [];
        $etatOpenInfo=true;
        $fiches = $fiche->toarray();
        if (isset($fiche->OpenInfo_opening_date)) {
            $ouverture = Carbon::parse($fiche->OpenInfo_opening_date)->translatedFormat('j F Y');
            $mois = Carbon::parse($fiche->OpenInfo_opening_date)->translatedFormat('F');
            $Annee = Carbon::parse($fiche->OpenInfo_opening_date)->translatedFormat('Y');
            $Jours = Carbon::parse($fiche->OpenInfo_opening_date)->translatedFormat('j');
            $nbmois = Carbon::parse($fiche->OpenInfo_opening_date)->Format('m');
            $OpenInfo_opening_date =
              ['OpenInfo_opening_date' => ['Annee' => $Annee,
                      'Mois' => ['Mois' => $mois, 'value' => $nbmois], 'Jours' => $Jours ,'etat'=>true]];
        }else{
            $OpenInfo_opening_date =
            ['OpenInfo_opening_date' => ['Annee' => '',
                    'Mois' => ['Mois' => '', 'value' => ''], 'Jours' => '','etat'=>false ]];
        }
       
        $etatouvertureold=true;
        $notification=explode(',',$fiche->notification);
       if(in_array("openInfo.openingDate",$notification)){
        $etatouvertureold=false;
       }
      
        $data[] = ['idfiche' =>$id,
        
          // 'locationName' => ['locationName'=>$fiche->locationName,'locationNamenew'=>$fiche->locationName!=$fiche->locationNamepre?$fiche->locationNamepre:$fiche->locationName,'locationNameold'=>$fiche->locationName!=$fiche->locationNamepre?$fiche->locationNamepre:'','etatvalidation'=>$fiche->locationName!=$fiche->locationNamepre?false:true],
          
        'locationName' =>['locationName'=>$fiche->locationName,'etatvalidation'=>FranchiseController::notif('locationName',$id)],
        'description' =>['description' => $fiche->description,'etatvalidation'=>FranchiseController::notif('profile.description',$id)],
        'adresse' =>['listadrese'=>['adresse' => $fiche->address,'etatvalidation'=>FranchiseController::notif('address.addressLines',$id)],'etatvalidation'=>FranchiseController::notif('address.addressLines',$id)],
            'numerotel' => ['listnumero'=>$numerotel,'etatvalidation'=>$etatvalidationtel],
            'urlsite' => ['urlsite'=>$fiche->websiteUrl,'etatvalidation'=>FranchiseController::notif('websiteUrl',$id)],
            'email'=>['email'=>$fiche->email,'etatvalidation'=>true],
            'adwPhone' => $adwPhone,
            'listlibelle' => $listlibelle,
            'menu' => '',
            'rendezvous' => AttributeController::priserendez($id),
            'categorie' => CategoriesController::categorie($id),
            'services' => CategoriesController::categorieservice($id),
            'zonedesservies' => ServiceareaController::servicebyfiche($id),
            'ouverture' => ['ouverture' => $ouverture,'etatvalidation'=>$etatouvertureold],
            'OpenInfo_opening_date' =>$OpenInfo_opening_date,
            'codemagasin' =>['codemagasin' => $fiche->storeCode,'etatvalidation'=>FranchiseController::notif('storeCode',$id)],
            'statusfiches' => $fiche->OpenInfo_status,
            'produits' => $postab,
            'numbreproduict'=>PostController::numbreproduict($id),
            'maps' => $metadatas[0]['mapsUrl'],
            'google' => $metadatas[0]['newReviewUrl'],
            'utilisateur' => $utilisateur->toarray(),
            'etiquette' => GroupeController::group_byfiche('Name_groupe',$id),
            'role' => Role::select('roles.name')->whereNotIn('roles.id',[1,2,3])->get(),
            'attributes' => AttributeController::detailsattribute($id),
            'horaire' => FichehourController::fichehoraire($id),
            'horairexceptionnels' => FichehourController::horaireexp($id),
            'morehours' =>  MorehoursController::morehours($id),
            'horairesupp' => MorehoursController::horaireexp($id),
            'statuts' => $this->etatfiche($id),
            'idfranchise' => $idfranchise,
            'etat'=>$this->statefiche($id),
            '$listutilisateur'=>$listutilisateur,
            'statut' => true ];

        return $data;
    }

    public function etatfiche($id)
    {
        $state = null;
        $listfiche = \App\Models\State::where('fiche_id', '=', $id)->get();
        foreach ($listfiche->toarray() as $fiche) {
            if ($fiche['isVerified'] && $fiche['isPendingReview']) {
                $state = 'Personnaliser';
            }
            if ($fiche['hasPendingVerification']) {
                $state = 'Code Google';
            }
            if ($fiche['isDuplicate']) {
                $state = 'Demande accés';
            }
        }

        return $state;
    }
    public static function notif($type,$id){
        $etatlocation=true;
        if(Notification::where('diffMask',$type)
        ->where('fiche_id',$id)->where('state','Inactif')->exists()){
            $etatlocation=false;
        }
        return $etatlocation;
    }
public static function statefiche($fiche_id){
    
  
    if((Etiquetgroupe::where('groupe_id', '=', 1)->where('fiche_id',$fiche_id)->where('state','=',1)->doesntExist() && Etiquetgroupe::Where('groupe_id', '=', 2)
    ->where('fiche_id',$fiche_id)->where('state','=',1)->exists())|| (Etiquetgroupe::where('groupe_id', '=', 2)->where('fiche_id',$fiche_id)->where('state','=',1)->doesntExist() && Etiquetgroupe::Where('groupe_id', '=', 1)
    ->where('fiche_id',$fiche_id)->where('state','=',1)->exists())
   
){
//Where('groupe_id',1)->orwhere('groupe_id',2)){
$iconfiche= Iconfiche::where('code','manque')->first();
return \Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path;
}
else if(Etiquetgroupe::where('groupe_id',1)->where('state','=',1)->where('fiche_id',$fiche_id)->exists()&& Etiquetgroupe::where('groupe_id',2)->where('state','=',1)->where('fiche_id',$fiche_id)->exists()){
    $iconfiche= Iconfiche::where('code','complet')->first();
    return \Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path;
 }
 else if(Etiquetgroupe::where('groupe_id','!=',1)->where('state','=',1)->where('fiche_id',$fiche_id)->doesntExist()&& Etiquetgroupe::where('groupe_id','!=',2)->where('state','=',1)->where('fiche_id',$fiche_id)->doesntExist()){
   $iconfiche= Iconfiche::where('code','aucune')->first();
   return \Illuminate\Support\Facades\URL::to('/') .'/'.$iconfiche->path;
}

  
}
    
}
