<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Fiche;
use App\Models\Photo;
use App\Models\User;
use App\Models\Ficheuser;
use App\Models\Photohistorie;
use App\Models\Statistique;
use App\Models\Etiquette;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Mail\AvertirPhoto;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use GoogleMyBusinessService;
use Google;

class PhotoController extends Controller
{
    public $mybusinessService;
    public $placeID;
    public $locationas;
    public $accounts;
    public $mediaphoto;
    public $lists;
    public $media;

    public function __construct()
    {

      /* $client = Helper::googleClient();
       $service = new Google\Service\MyBusinessBusinessInformation($client); 
       echo('<pre>');
       $params=["readMask"=>"name,storeCode,title,phoneNumbers,websiteUri,labels,latlng,profile"];
       $payload = json_encode($params);
       
       $read_mask="name,storeCode,title,phoneNumbers,websiteUri,labels,latlng,profile";
    
       $list_accounts_response = $service->locations->get("locations/1764130149800808953",$params);
         print_r($list_accounts_response);
         exit;
      */

     //   $this->placeID = Helper::GMBcreate();
     //  $this->accounts = $this->mybusinessService->accounts;
     //  $this->locations = $this->mybusinessService->accounts_locations;
//$this->lists = $this->accounts->listAccounts()->getAccounts();
$this->mybusinessService = Helper::GMB();

       $this->media = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_MediaItem();
        $this->mediaphoto = $this->mybusinessService->accounts_locations_media;
     $this->locationas = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_LocationAssociation();
    }
    public function signalier_photo(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        $messages = [
            'Photo_id.exists' => 'La Photo qui vous chercher est indisponible',
            'Photo_id.required' => 'Photo est obligatoire'
        ];
        $input = [
                'Photo_id' => $request['Photo_id']
            ];
        $validator = Validator::make(
            $input,
            [
                        'Photo_id' => 'required|exists:photos,id',
               ],
            $messages
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }
        
            return response()->json([
                                'success' => false,
                                'message' => $message,
                                'status' => 422,], 422);
        }
        $input = (object) $input;
        $photo = Photo::find($input->Photo_id);
        $photo->signials = $photo->signials +1;
        $photo->signial_date=Carbon::now()->translatedFormat('Y-m-d H:m:s');
        try {
            $photo->save();
            return response()->json([
                        'success' => true,
                        'message' => 'Operation success.',
                        'data' => null,
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
    public function add_photo(Request $request)
    {
    
        //  print_r($request->all());
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        $fiche=[];
        $input = [];
         
        
                  
        $messages = [
            'Fiche.*.exists' => 'La fiche qui vous chercher est indisponible',
            'Ettiquet.*.exists' => 'L éttiquet qui vous chercher est indisponible',
           
            'File.required' => 'Photo est obligatoire',
         
            'Type_photo.in' => 'Type_photo doit etres EXTERIOR,INTERIOR,AT_WORK,TEAMS,COVER,LOGO',
            'User.exists' => 'User qui vous chercher est indisponible',
            'User.required' => 'User est obligatoire',
            

        ];
        if (!isset($request['data']['File'])) {
            return response()->json([
        'success' => false,
        'message' => "Photo est obligatoire",
        'status' => 422,], 422);
        }
        $input = [
            
          
            'File' => $request['data']['File'],
            'User' => $request['User_id'],
        ];
        if (isset($request->Fiches) && !empty($request->Fiches)) {
            foreach ($request->Fiches as $key => $value) {
                if ($value['type'] == 'Fiche') {
                    $fiches[] = $value['id'];
                }
                if ($value['type'] == "Etiquette") {
                    $ettiquets[] = $value['id'];
                }
            }
        }
        


        if (!isset($fiches) && !isset($ettiquets)) {
            return response()->json([
                'success' => false,
                'message' => "Vérifier votre recherche",
                'status' => 422,], 422);
        }
        if (isset($fiches)) {
            foreach ($fiches as $key => $value) {
                $input['Fiche'][]=$value;
            }
        }
        if (isset($ettiquets)) {
            $ettiquetslist =  Fiche::join('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'fiches.id')->join('etiquettes', 'etiquette_id', '=', 'etiquettes.id')->whereIn("etiquettes.id", $ettiquets)->where('fiches.franchises_id', '=', $request->header('franchise'))->distinct('etiquettes.id')->get("fiches.id");
            foreach ($ettiquetslist as $key => $value) {
                $input['Fiche'][]=$value->id;
            }
        }
        $input['Fiche']= array_unique($input['Fiche']);
       




        if (isset($request['data']['Type_photoFilter']) && !empty($request['data']['Type_photoFilter'])) {
            $input['Category'] = $request['data']['Type_photoFilter'];
        }

        if (!isset($input['Category'])) {
            return response()->json([
                'success' => false,
                'message' => "catégorie  du photo est obligatoire",
                'status' => 422,], 422);
        }
      
        $validator = Validator::make(
            $input,
            [
                  
                    'User' => 'required|exists:users,id',
                    'Fiche.*' => 'exists:fiches,id',
                    'Ettiquet.*' => 'exists:etiquettes,id',
                 
                    'File' => 'required',
               
                    'Category' => 'required|in:EXTERIOR,INTERIOR,AT_WORK,TEAMS,LOGO,COVER',
                        ],
            $messages
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }
    
            return response()->json([
                            'success' => false,
                            'message' => $message,
                            'status' => 422,], 422);
        }
        $input = (object) $input;
       

        try {
            $photo = $input->File;

            foreach ($photo as $file) {
                $image_64 = $file;
                $time = time();
                $new_data = explode(';', $image_64);
                $type = $new_data[0];
                $extension = explode('/', $type);
                $datap = explode(',', $new_data[1]);
                $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];
                Storage::disk('public')->put($imageName, base64_decode(str_replace('%2B', '+', $datap[1])));
                try {
                    $path = public_path('/app/public/photos/' . $imageName);

                    $height = \Intervention\Image\Facades\Image::make($path)->height();
                    
                    $width = \Intervention\Image\Facades\Image::make($path)->width();

                    if ($height < 260 || $width < 250) {
                        return response()->json([
                                    'success' => false,
                                    'message' => "Catégorie PROFIL et COUVERTURE, toutes les photos doivent mesurer au minimum 250px sur le bord court, avec une taille de fichier d'au moins 10240 octets.",
                                    'status' => 422,], 422);
                    }



                    if (isset($input->Category) && !empty($input->Category)) {
                        $this->media->setMediaFormat('PHOTO');

                        $this->locationas->setCategory($input->Category);
                        $this->media->setLocationAssociation($this->locationas);
                        $this->media->setSourceUrl(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);

                        //$this->media->setSourceUrl('https://api-wallpost.b-forbiz.com/public/app/public/photos/photo_1643285972603.png');

                        if (isset($input->Fiche) && !empty($input->Fiche)) {
                            $fichess = Fiche::whereIn("id", $input->Fiche)->get();
                        }


                        foreach ($fichess as $key => $f) {
                            $result = $this->mediaphoto->create($f->name, $this->media);
                            $data = [];
                            $data['category'] = $result->locationAssociation->category;
                            $data['name'] = $result->name;
                            $data['views'] = $result->insights->viewCount;
                            $data['file'] = $result->googleUrl;
                            $data['thumbnail'] = $result->thumbnailUrl;
                            $data['format'] = $result->mediaFormat;
                            $data['width'] = $result->dimensions->widthPixels;
                            $data['height'] = $result->dimensions->heightPixels;
                            $data['fiche_id'] = $f->id;
                            $data['user_id'] = $input->User;
                            $data['created_at'] = Carbon::parse($result->createTime)->translatedFormat('Y-m-d H:i:s');
                            Storage::disk('public')->delete(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);
                           
                            $photo = Photo::create($data);
                        }
                    }
                } catch (\Throwable $th) {
                    $message = json_decode($th->getMessage());

                    if (isset($message->error->details[0]->errorDetails[0]->message)) {
                        $message = $message->error->details[0]->errorDetails[0]->message;
                    } else {
                        $message = $th->getMessage();
                    }

                    return response()->json(
                        [
                                'success' => false,
                                'message' => $message,
                                'status' => $th->getCode(),
                                    ],
                        $th->getCode()
                    );
                }
            }

            return response()->json([
                        'success' => true,
                        'message' => 'Photo ajouté avec succès',
                        'data' => $photo,
                        'status' => Response::HTTP_OK,
                            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return response()->json(
                [
                        'success' => false,
                        'message' => $e->getmessage(),
                        'status' => $e->getCode(),
                            ],
                $e->getCode()
            );
        }
    }
    public function get_localisation_service(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                'success' => false,
                'message' => $request->header('franchise'),
                'status' => 400,
            ]);
        }
        $data=[];
        try {
            $ettiquets =  Fiche::join('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'fiches.id')->join('etiquettes', 'etiquette_id', '=', 'etiquettes.id')->where('fiches.franchises_id', '=', $request->header('franchise'))->distinct('etiquettes.id')->get("etiquettes.*");
     
            if (isset($ettiquets) && !empty($ettiquets)) {
                foreach ($ettiquets as $key => $value) {
                    $data["Location"][]=['id'=>$value->id,'Name' => $value->name,'isSelected'=>false];
                }
            } else {
                $data["Location"]=null;
            }
            $services =  Fiche::join('categories', 'categories.fiche_id', '=', 'fiches.id')->where('fiches.franchises_id', '=', $request->header('franchise'))->distinct('categories.id')->get("categories.*");
            if (isset($services) && !empty($services)) {
                foreach ($services as $key => $value) {
                    $data["Services"][]=['id'=>$value->id,'Name' => $value->displayName,'isSelected'=>false];
                }
            } else {
                $data["Services"]=null;
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
    public function dernier_photo(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                    'success' => false,
                    'message' => $request->header('franchise'),
                    'status' => 400,
                ]);
        }
        $fiche=[];
        $input = [];
         
        if (isset($request->Fiches) && !empty($request->Fiches)) {
            foreach ($request->Fiches as $key => $value) {
                if ($value['type'] == 'Fiche') {
                    $fiches[] = $value['id'];
                }
            }
            $input['Fiche'] = $fiches;
        } else {
            $input['Fiche'] = Fiche::where('fiches.franchises_id', '=', $request->header('franchise'))->limit(50)->get('id')->toArray();
        }
          
        $messages = [
                'Fiche.*.id.exists' => 'La fiche qui vous chercher est indisponible',

            ];
    
           
    
        $validator = Validator::make(
            $input,
            [
                      
                        'Fiche.*' => 'exists:fiches,id'
                            ],
            $messages
        );
    
        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }
    
            return response()->json([
                            'success' => false,
                            'message' => $message,
                            'status' => 422,], 422);
        }
        $input = (object) $input;
        try {
            $photo_prop = Photo::Leftjoin('users', 'users.id', '=', 'photos.user_id')->join('fiches', 'fiches.id', '=', 'photos.fiche_id')->whereIn("fiche_id", $input->Fiche)->where('category', '!=', 'CUSTOMER')->orderBy('photos.created_at', 'DESC')->limit(6)->get(['photos.*', 'fiches.locationName', 'users.firstName', 'users.lastName', 'users.photo']);
            $photo_customer = Photo::Leftjoin('users', 'users.id', '=', 'photos.user_id')->join('fiches', 'fiches.id', '=', 'photos.fiche_id')->whereIn("fiche_id", $input->Fiche)->where('category', '=', 'CUSTOMER')->orderBy('photos.created_at', 'DESC')->limit(4)->get(['photos.*', 'fiches.locationName', 'users.firstName', 'users.lastName', 'users.photo']);
            foreach ($photo_prop as $key => $photo) {

                $available_category = [];
                $categories = ['ADDITIONAL' => 'Additionnelle', 'EXTERIOR' => 'Extérieur', 'INTERIOR' => 'Intérieur', 'LOGO' => 'Logo', 'PROFILE' => 'Profile', 'COVER' => 'Couverture', 'TEAMS' => 'Equipe', 'AT_WORK' => 'Au travail'];

                
                if (array_key_exists($photo->category, $categories)) {
                $photo['categorie_fr']=$categories[$photo->category];

                    unset($categories[$photo->category]);
                    foreach ($categories as $key => $cat) {
                        $available_category[] = ['name' => $key, 'label' => $categories[$key]];
                    }
                } else {
                    foreach ($categories as $key => $cat) {
                        $available_category[] = ['name' => $key, 'label' => $categories[$key]];
                    }
                }

                $photo->available_category = $available_category;
                $now = Carbon::now();
                $end = Carbon::parse($photo->created_at);

                if ($years = $end->diffInYears($now)) {
                    $dateleft = $years . ' ans';
                } elseif ($months = $end->diffInMonths($now)) {
                    $dateleft = $months . ' mois';
                } elseif ($weeks = $end->diffInWeeks($now)) {
                    $dateleft = $weeks . ' sem';
                } else {
                    $days = $end->diffInDays($now);
                    $dateleft = $days . ' jours';
                }
                $photo['date']=$dateleft;
              
                
              //  $photo['categorie_fr']=$photo->category;
                $photo->views = $this->shortNumber($photo->views);
                if ($photo->photo) {
                    //  $photo->photo = "https://api-wallpost.b-forbiz.com/public/app/public/photos/".  $photo->photo;
                    $photo->photo = \Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/'.  $photo->photo;
                }
            }
            foreach ($photo_customer as $key => $photo) {
                $now = Carbon::now();
                $end = Carbon::parse($photo->created_at);
                $content = file_get_contents($photo->takedownUrl);
                $photo->takedownUrls = ($content);
                if ($years = $end->diffInYears($now)) {
                    $dateleft = $years . ' ans';
                } elseif ($months = $end->diffInMonths($now)) {
                    $dateleft = $months . ' mois';
                } elseif ($weeks = $end->diffInWeeks($now)) {
                    $dateleft = $weeks . ' sem';
                } else {
                    $days = $end->diffInDays($now);
                    $dateleft = $days . ' jours';
                }
                $photo['date']=$dateleft;
                $photo['signial_date'] = Carbon::parse($photo->signial_date)->translatedFormat('d/m/Y');
                ;
                $photo->views = $this->shortNumber($photo->views);
                if ($photo->photo) {
                    //$photo->photo = "https://api-wallpost.b-forbiz.com/public/app/public/photos/".  $photo->photo;
                    $photo->photo = \Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/'.  $photo->photo;
                }
            }
            $data['PROPERITARY']=$photo_prop;
            $data['CUSTOMER']=$photo_customer;
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
    public function add_photo_autocompele(Request $request)
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
            'Location.*.exists' => 'Etiquettes qui vous chercher est indisponible',
            'Services.*.exists' => 'Services qui vous chercher est indisponible',
        ];

        $input = [
            'Filtre_search' => $request->Filtre_search,
            
        ];
        if (isset($request['Location']) && !empty($request['Location'])) {
            foreach ($request["Location"] as $key => $value) {
                $input['Location'][] = $value['id'];
            }
        }
        if (isset($request['Services']) && !empty($request['Services'])) {
            foreach ($request["Services"] as $key => $value) {
                $input['Services'][] = $value['id'];
            }
        }
        $validator = Validator::make(
            $input,
            [
                            'Filtre_search' => 'required|string',
                            'Location.*' => 'exists:etiquettes,id',
                            'Services.*' => 'exists:categories,id',
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
                $fiches = Fiche::where('fiches.locationName', 'LIKE', '%'.$input->Filtre_search.'%')->where('fiches.franchises_id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
              
                if (isset($input->Location) && !empty($input->Location)) {
                    $fiches->join("etiquetgroupes", "etiquetgroupes.fiche_id", "=", "fiches.id")->whereIn("etiquetgroupes.etiquette_id", $input->Location);
                }
                if (isset($input->Services) && !empty($input->Services)) {
                    $fiches->join("categories", "categories.fiche_id", "=", "fiches.id")->whereIn("categories.id", $input->Services);
                }
                $fichelist=  $fiches->distinct()->get(['fiches.id', 'fiches.locationName']);
                $datacomplete = [];
                foreach ($etiquettes as $key => $value) {
                    $datacomplete[] = ['id' => $value->id, 'name' => $value->name, 'color' => $value->color, 'type' => 'Etiquette'];
                }
                foreach ($fichelist as $key => $value) {
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
    public function photo_autocompele(Request $request)
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
                $fiches = Fiche::where('fiches.locationName', 'LIKE', '%'.$input->Filtre_search.'%')->where('fiches.franchises_id', '=', $request->header('franchise'))->where('state', 'LIKE', 'COMPLETED')
            ->get(['fiches.id', 'fiches.locationName']);
                $datacomplete = [];

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

    public function classify_photo(Request $request)
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
            $photos = Photo::join('fiches', 'fiches.id', '=', 'photos.fiche_id')->where('franchises_id', '=', $request->header('franchise'))->Limit(10)->orderBy('photos.views', "DESC")->get(["photos.*","fiches.locationName","fiches.city"]);
            foreach ($photos as $key=>$photo) {
                $now = Carbon::now();
                $end = Carbon::parse($photo->created_at);

                if ($years = $end->diffInYears($now)) {
                    $dateleft = $years . ' ans';
                } elseif ($months = $end->diffInMonths($now)) {
                    $dateleft = $months . ' mois';
                } elseif ($weeks = $end->diffInWeeks($now)) {
                    $dateleft = $weeks . ' sem';
                } else {
                    $days = $end->diffInDays($now);
                    $dateleft = $days . ' jours';
                }
                $photo->classify = $key;
                $photo->date = $dateleft;
                $photo->views = $this->shortNumber($photo->views);
                $data[] = $photo;
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

    public function avertir_photo(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }

        $messages = [
            'User.exists' => 'L\'utilisateur est invalide',
            'Fiche.exists' => 'La fiche qui vous chercher est indisponible',
            'Photo.exists' => 'Photo est invalide',
            'Message' => 'le message est vide',
        ];

        $input = [
            'User' => $request->User_id,
            'Fiche' => $request->Fiche_id,
            'Photo' => $request->Photo_id,
            'Message' => $request->Message,
        ];

        $validator = Validator::make(
            $input,
            [
                    'User' => 'required|exists:users,id',
                    'Fiche' => 'required|exists:fiches,id',
                    'Photo' => 'required|exists:photos,id',
                    'Message' => 'required',
                        ],
            $messages
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }
        $input = (object) $input;
        try {
            $fiche = Fiche::find($input->Fiche);
            $user = User::find($input->User);
            $photo = Photo::find($input->Photo);
            $admin = Ficheuser::join('fiches', 'fiches.id', '=', 'ficheusers.fiche_id')->join('users', 'users.id', '=', 'ficheusers.user_id')->where("fiches.id", $input->Fiche)->where('users.role_id', '1')->get();
            //$admin['email']
            Mail::to("osahraoui70@gmail.com")->send(new AvertirPhoto($fiche, $photo, $user, $input->Message));
            $photo->avertir = 1;
            $photo->messageAvertir = $input->Message;
            $photo->dateAvertir = Carbon::now()->translatedFormat('Y-m-d H:m:s');
            $photo->userAvertir = $input->User;
            $photo->save();
            return response()->json([
                        'success' => true,
                        'message' => 'Operation success.',
                        'data' => null,
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

    public function delete_photo(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $messages = [
            'Photo.required' => 'Vérifier Votre Photo!',
            'Photo.exists' => 'Photo n\'est pas valable!',
        ];

        $input = [
            'Photo' => $request['Photo_id'],
        ];
        $validator = Validator::make(
            $input,
            [
                    'Photo' => 'required|exists:photos,id',
                        ],
            $messages
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }
        $input = (object) $input;
        try {
            $photo = Photo::find($input->Photo);

            try {
                $result = $this->mediaphoto->delete($photo->name);
            } catch (\Throwable $th) {
                $message = json_decode($th->getMessage());

                if (isset($message->error->details[0]->errorDetails[0]->message)) {
                    $message = $message->error->details[0]->errorDetails[0]->message;
                } else {
                    $message = $th->getMessage();
                }

                return response()->json(
                    [
                            'success' => false,
                            'message' => $message,
                            'status' => $th->getCode(),
                                ],
                    $th->getCode()
                );
            }
            $photo->delete();

            return response()->json([
                        'success' => true,
                        'message' => 'Operation success.',
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

    public function add_category(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $messages = [
            'Category.required' => 'Vérifier Votre Catégorie!',
            'Photo.required' => 'Vérifier Votre photo!',
            'Fiche.required' => 'Vérifier Votre fiche!',
            'User.required' => 'Vérifier Votre user!',
        ];

        $input = [
            'Category' => $request['Category'],
            'Fiche' => $request['Fiche_id'],
            'Photo' => $request['Photo_file'],
            'User' => $request['User_id'],
        ];
        $validator = Validator::make(
            $input,
            [
                    'Fiche' => 'exists:fiches,id',
                    'Photo' => 'required',
                    'User' => 'exists:users,id',
                    'Category' => 'required|in:EXTERIOR,INTERIOR,TEAMS,AT_WORK,ADDITIONAL',
                        ],
            $messages
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }
        $input = (object) $input;

        try {
            try {
                $this->media->setMediaFormat('PHOTO');

                $this->locationas->setCategory($input->Category);
                $this->media->setLocationAssociation($this->locationas);
                $this->media->setSourceUrl($input->Photo);

                // $this->media->setSourceUrl('https://api-wallpost.b-forbiz.com/public/app/public/photos/photo_1643285972603.png');

                $fiche = Fiche::find($input->Fiche);

                $result = $this->mediaphoto->create($fiche->name, $this->media);
            } catch (\Throwable $th) {
                $message = json_decode($th->getMessage());

                if (isset($message->error->details[0]->errorDetails[0]->message)) {
                    $message = $message->error->details[0]->errorDetails[0]->message;
                } else {
                    $message = $th->getMessage();
                }

                return response()->json(
                    [
                            'success' => false,
                            'message' => $message,
                            'status' => $th->getCode(),
                                ],
                    $th->getCode()
                );
            }
            $data = [];
            $data['category'] = $result->locationAssociation->category;
            $data['name'] = $result->name;
            $data['views'] = $result->insights->viewCount;
            $data['file'] = $result->googleUrl;
            $data['thumbnail'] = $result->thumbnailUrl;
            $data['format'] = $result->mediaFormat;
            $data['width'] = $result->dimensions->widthPixels;
            $data['height'] = $result->dimensions->heightPixels;
            $data['fiche_id'] = $input->Fiche;
            $data['created_at'] = Carbon::parse($result->createTime)->translatedFormat('Y-m-d H:i:s');
            $data['user_id'] = $input->User;

            $photo = Photo::create($data);

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

    public function get_Gallery(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $messages = [
            'Category.required' => 'Vérifier Votre Catégorie!',
            'Fiche.required' => 'Vérifier Votre fiche!',
        ];

        $input = [
            'Category' => $request['Category'],
            'Count' => $request['Count'],
            'Fiche_id' => $request['Fiche_id'],
        ];

        $validator = Validator::make(
            $input,
            [
                    'Fiche_id' => 'exists:fiches,id',
                    'Count' => 'required|int',
                    'Category' => 'required|in:EXTERIOR,INTERIOR,VIDEO,IDENTITY,ALL,TEAMS,AT_WORK,PROPERITARY,CUSTOMER',
                        ],
            $messages
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }
        $input = (object) $input;
        try {
            $CategArray = ['ADDITIONAL' => 'Additionnelle', 'ALL' => 'Vue d\'ensemble', 'VIDEO' => 'Vidéo', 'EXTERIOR' => 'Extérieur', 'INTERIOR' => 'Intérieur', 'LOGO' => 'Logo', 'PROFILE' => 'Profile', 'COVER' => 'Couverture', 'TEAMS' => 'Equipe', 'AT_WORK' => 'Au travail', 'PROPERITARY' => 'Photos du propriétaire', 'CUSTOMER' => 'Photos clients', 'FOOD_AND_DRINK' => 'Nourriture et boisson', 'MENU' => 'Menu', 'PRODUCT' => 'Produit', 'COMMON_AREA' => 'Espace commun', 'ROOMS' => 'Pi�ces'];
            $photosQuery = Photo::where('fiche_id', $input->Fiche_id);
            if ($input->Category == 'VIDEO') {
                $photosQuery->where('format', $input->Category);
            } elseif ($input->Category == 'IDENTITY') {
                $photosQuery->whereIn('category', ['PROFILE', 'LOGO', 'COVER']);
            } elseif ($input->Category == 'ALL') {
                $photosQuery->whereIn('category', ['PROFILE', 'LOGO', 'COVER', 'EXTERIOR', 'INTERIOR', 'TEAMS', 'AT_WORK', 'CUSTOMER', 'ADDITIONAL']);
            } elseif ($input->Category == 'PROPERITARY') {
                $photosQuery->where('category', "!=", "CUSTOMER");
            } else {
                $photosQuery->where('category', $input->Category);
            }
            $photos = $photosQuery->join('fiches', 'fiche_id', '=', 'fiches.id')->Leftjoin('users', 'user_id', '=', 'users.id')->limit($input->Count)->get(['photos.*', 'fiches.locationName', 'users.firstName', 'users.lastName', 'users.photo']);
            $photosCount = $photosQuery->count();
            foreach ($photos as $key => $photo) {
                $available_category = [];
                $categories = ['ADDITIONAL' => 'Additionnelle', 'EXTERIOR' => 'Extérieur', 'INTERIOR' => 'Intérieur', 'TEAMS' => 'Equipe', 'AT_WORK' => 'Au travail'];
                if (array_key_exists($photo->category, $categories)) {
                    unset($categories[$photo->category]);
                    foreach ($categories as $key => $cat) {
                        $available_category[] = ['name' => $key, 'label' => $categories[$key]];
                    }
                } else {
                    foreach ($categories as $key => $cat) {
                        $available_category[] = ['name' => $key, 'label' => $categories[$key]];
                    }
                }

                $photo->available_category = $available_category;
                $photo->views = $this->shortNumber($photo->views);
                $now = Carbon::now();
                $end = Carbon::parse($photo->created_at);

                if ($years = $end->diffInYears($now)) {
                    $dateleft = $years . ' ans';
                } elseif ($months = $end->diffInMonths($now)) {
                    $dateleft = $months . ' mois';
                } elseif ($weeks = $end->diffInWeeks($now)) {
                    $dateleft = $weeks . ' sem';
                } else {
                    $days = $end->diffInDays($now);
                    $dateleft = $days . ' jours';
                }
                $photo->date = $dateleft;
            
                    $photo->categoryFr = $CategArray[$photo->category];
  
                if ($photo->category == 'CUSTOMER') {
                    $photo->takedownUrl = str_replace(' ', "", $photo->takedownUrl);
                    $photo->takedownUrl = trim(str_replace("en-US", "fr-FR", $photo->takedownUrl));
                    $content = file_get_contents($photo->takedownUrl);
                    $photo->takedownUrls = ($content);
                }
                if ($photo->photo) {
                    // $photo->photo = "https://api-wallpost.b-forbiz.com/public/app/public/photos/".  $photo->photo;
                    $photo->photo = \Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/'.  $photo->photo;
                }
                $data['List_photos'][] = $photo;
            }
            $data['Count'] = $photosCount;
            $data['Photo_customer_count'] = Photo::where('fiche_id', $input->Fiche_id)->where('category', '=', 'CUSTOMER')->count();

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

    public function upload_photo(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $messages = [

            'File.required' => 'Vérifier Votre file!',
            'Fiche.required' => 'Vérifier Votre fiche!',
            'User.required' => 'Vérifier Votre user!',
        ];

        $input = [
            'File' => $request['data']['File'],
            'Fiche' => $request['Fiche_id'],
            'User' => $request['User_id'],
        ];


        if (isset($request['data']['Type_photo']) && !empty($request['data']['Type_photo'])) {
            $input['Type_photo'] = $request['data']['Type_photo'];
        }

        $validator = Validator::make(
            $input,
            [
                    'User' => 'exists:users,id',
                    'Fiche' => 'exists:fiches,id',
                    'Type_photo' => 'required|in:EXTERIOR,INTERIOR,AT_WORK,TEAMS,LOGO,COVER',
                    
                    'File' => 'required',
                        ],
            $messages
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }

        $input = (object) $input;

        try {
            $photo = $input->File;

            foreach ($photo as $file) {
                $image_64 = $file;
                $time = time();
                $new_data = explode(';', $image_64);
                $type = $new_data[0];
                $extension = explode('/', $type);
                $datap = explode(',', $new_data[1]);
                $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];
                Storage::disk('public')->put($imageName, base64_decode(str_replace('%2B', '+', $datap[1])));

                try {
                    $path = public_path('/app/public/photos/' . $imageName);

                    $height = \Intervention\Image\Facades\Image::make($path)->height();
                    $width = \Intervention\Image\Facades\Image::make($path)->width();
                    if ($height < 260 || $width < 250) {
                        return response()->json([
                                    'success' => false,
                                    'message' => "Catégorie PROFIL et COUVERTURE, toutes les photos doivent mesurer au minimum 250px sur le bord court, avec une taille de fichier d'au moins 10240 octets.",
                                    'status' => 422,], 422);
                    }


                    if (isset($input->Type_photo) && !empty($input->Type_photo)) {
                        $this->media->setMediaFormat('PHOTO');

                        $this->locationas->setCategory($input->Type_photo);
                        $this->media->setLocationAssociation($this->locationas);
                        $this->media->setSourceUrl(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);
                        //$this->media->setSourceUrl('https://api-wallpost.b-forbiz.com/public/app/public/photos/photo_1643285972603.png');

                        $fiche = Fiche::find($input->Fiche);

                        $result = $this->mediaphoto->create($fiche->name, $this->media);

                        $data = [];
                        $data['category'] = $result->locationAssociation->category;
                        $data['name'] = $result->name;
                        $data['views'] = $result->insights->viewCount;
                        $data['file'] = $result->googleUrl;
                        $data['thumbnail'] = $result->thumbnailUrl;
                        $data['format'] = $result->mediaFormat;
                        $data['width'] = $result->dimensions->widthPixels;
                        $data['height'] = $result->dimensions->heightPixels;
                        $data['fiche_id'] = $input->Fiche;
                        $data['user_id'] = $input->User;
                        $data['created_at'] = Carbon::parse($result->createTime)->translatedFormat('Y-m-d H:i:s');
                        Storage::disk('public')->delete(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);
                        $photo = Photo::create($data);
                    }
                } catch (\Throwable $th) {
                    $message = json_decode($th->getMessage());

                    if (isset($message->error->details[0]->errorDetails[0]->message)) {
                        $message = $message->error->details[0]->errorDetails[0]->message;
                    } else {
                        $message = $th->getMessage();
                    }

                    return response()->json(
                        [
                                'success' => false,
                                'message' => $message,
                                'status' => $th->getCode(),
                                    ],
                        $th->getCode()
                    );
                }
            }

            return response()->json([
                        'success' => true,
                        'message' => 'Photo ajouté avec succès',
                        'data' => $photo,
                        'status' => Response::HTTP_OK,
                            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return response()->json(
                [
                        'success' => false,
                        'message' => $e->getmessage(),
                        'status' => $e->getCode(),
                            ],
                $e->getCode()
            );
        }
    }
    ///////
    public function sidbar_ficheadmin(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $messages = [
            'Category.required' => 'Vérifier Votre Catégorie!',
            'File.required' => 'Vérifier Votre file!',
            'Listfiche.required' => 'Vérifier Votre liste fiche!',
            'User.required' => 'Vérifier Votre user!',
        ];

        $input = [
            'File' => $request['data']['File'],
            'Listfiche' => $request['listfiche'],
            'User' => $request['User_id'],
        ];

        if (isset($request['data']['Category']) && !empty($request['data']['Category'])) {
            $input['Category'] = $request['data']['Category'];
        }
        if (isset($request['data']['Type_photo']) && !empty($request['data']['Type_photo'])) {
            $input['Type_photo'] = $request['data']['Type_photo'];
        }

        $validator = Validator::make(
            $input,
            [
                    'User' => 'exists:users,id',
                    'Type_photo' => 'in:EXTERIOR,INTERIOR,AT_WORK,TEAMS',
                    'Category' => 'in:LOGO,COVER',
                    'File' => 'required',
                    'Listfiche' => 'required',
                        ],
            $messages
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }

        $input = (object) $input;

        try {
            $photo = $input->File;
            

            foreach ($photo as $file) {
                $image_64 = $file;
                $time = time();
                $new_data = explode(';', $image_64);
                $type = $new_data[0];
                $extension = explode('/', $type);
                $datap = explode(',', $new_data[1]);
                $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];
                Storage::disk('public')->put($imageName, base64_decode(str_replace('%2B', '+', $datap[1])));

                try {
                    $path = public_path('/app/public/photos/' . $imageName);

                    $height = \Intervention\Image\Facades\Image::make($path)->height();
                    $width = \Intervention\Image\Facades\Image::make($path)->width();
                    if ($height < 260 || $width < 250) {
                        return response()->json([
                                    'success' => false,
                                    'message' => "Catégorie PROFIL et COUVERTURE, toutes les photos doivent mesurer au minimum 250px sur le bord court, avec une taille de fichier d'au moins 10240 octets.",
                                    'status' => 422,], 422);
                    }
                    foreach ($input->Listfiche as $fiches) {
                        if ($fiches['status'] === true) {
                            $id = $fiches['id'];
                  
                            if (isset($input->Category) && !empty($input->Category)) {
                                try {
                                    $this->media->setMediaFormat('PHOTO');

                                    $this->locationas->setCategory($input->Category);
                                    $this->media->setLocationAssociation($this->locationas);
                                    $this->media->setSourceUrl(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);

                                    // $this->media->setSourceUrl('https://api-wallpost.b-forbiz.com/public/app/public/photos/photo_1643285972603.png');


                                    $fiche = Fiche::find($id);

                                    $result = $this->mediaphoto->create($fiche->name, $this->media);
                                } catch (\Throwable $th) {
                                    $message = json_decode($th->getMessage());

                                    if (isset($message->error->details[0]->errorDetails[0]->message)) {
                                        $message = $message->error->details[0]->errorDetails[0]->message;
                                    } else {
                                        $message = $th->getMessage();
                                    }

                                    return response()->json(
                                        [
                                        'success' => false,
                                        'message' => $message,
                                        'status' => $th->getCode(),
                                            ],
                                        $th->getCode()
                                    );
                                }
                                $data = [];
                                $data['category'] = $result->locationAssociation->category;
                                $data['name'] = $result->name;
                                $data['views'] = $result->insights->viewCount;
                                $data['file'] = $result->googleUrl;
                                $data['thumbnail'] = $result->thumbnailUrl;
                                $data['format'] = $result->mediaFormat;
                                $data['width'] = $result->dimensions->widthPixels;
                                $data['height'] = $result->dimensions->heightPixels;
                                $data['fiche_id'] = $id;
                                $data['created_at'] = Carbon::parse($result->createTime)->translatedFormat('Y-m-d H:i:s');
                                $data['user_id'] = $input->User;
                                $photo = Photo::where('fiche_id', $id)->where('category', $input->Category)->delete();

                                $photo = Photo::create($data);
                            }
                            if (isset($input->Type_photo) && !empty($input->Type_photo)) {
                                $this->media->setMediaFormat('PHOTO');

                                $this->locationas->setCategory($input->Type_photo);
                                $this->media->setLocationAssociation($this->locationas);
                                $this->media->setSourceUrl(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);
                                //$this->media->setSourceUrl('https://api-wallpost.b-forbiz.com/public/app/public/photos/photo_1643285972603.png');

                                $fiche = Fiche::find($id);

                                $result = $this->mediaphoto->create($fiche->name, $this->media);

                                $data = [];
                                $data['category'] = $result->locationAssociation->category;
                                $data['name'] = $result->name;
                                $data['views'] = $result->insights->viewCount;
                                $data['file'] = $result->googleUrl;
                                $data['thumbnail'] = $result->thumbnailUrl;
                                $data['format'] = $result->mediaFormat;
                                $data['width'] = $result->dimensions->widthPixels;
                                $data['height'] = $result->dimensions->heightPixels;
                                $data['fiche_id'] = $id;
                                $data['user_id'] = $input->User;
                                $data['created_at'] = Carbon::parse($result->createTime)->translatedFormat('Y-m-d H:i:s');
                                Storage::disk('public')->delete(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);
                                $photo = Photo::create($data);
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    $message = json_decode($th->getMessage());

                    if (isset($message->error->details[0]->errorDetails[0]->message)) {
                        $message = $message->error->details[0]->errorDetails[0]->message;
                    } else {
                        $message = $th->getMessage();
                    }

                    return response()->json(
                        [
                                'success' => false,
                                'message' => $message,
                                'status' => $th->getCode(),
                                    ],
                        $th->getCode()
                    );
                }
            }

            return response()->json([
                        'success' => true,
                        'message' => 'Photo ajouté avec succès',
                        'data' => $photo,
                        'status' => Response::HTTP_OK,
                            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return response()->json(
                [
                        'success' => false,
                        'message' => $e->getmessage(),
                        'status' => $e->getCode(),
                            ],
                $e->getCode()
            );
        }
    }
    ///


    public function photo_missing(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        try {
            $photo_fiches_id = Photo::distinct()->pluck('fiche_id')->toArray();
            $fiches_missing_photos = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')->whereNotIn('fiches.id', $photo_fiches_id);
           // $data['All'] = ['count' => $fiches_missing_photos->count(), 'list_fiches' => $fiches_missing_photos->get(['fiches.*'])->toArray()];
            $data['All'] = ['count' => 0, 'list_fiches' =>[]];
            $fiches_missing_logo = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')->whereNotIn('fiches.id', Photo::distinct()->whereIn('category', ['PROFILE', 'LOGO'])->get('fiche_id')->toArray())->distinct();
            $data['Logo'] = ['count' => $fiches_missing_logo->count(), 'list_fiches' => $fiches_missing_logo->get(['fiches.*'])->toArray()];

            $fiches_missing_cover = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')->whereNotIn('fiches.id', Photo::distinct()->where('category', 'COVER')->get('fiche_id')->toArray())->distinct();
            $data['Cover'] = ['count' => $fiches_missing_cover->count(), 'list_fiches' => $fiches_missing_cover->get(['fiches.*'])->toArray()];

            $fiches_missing_teams = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')->whereNotIn('fiches.id', Photo::distinct()->where('category', 'TEAMS')->get('fiche_id')->toArray())->distinct();
            $data['MissingTeams'] = ['count' => $fiches_missing_teams->count(), 'list_fiches' => $fiches_missing_teams->get(['fiches.*'])->toArray()];

            $fiches_interior = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
            $fiches_interior_list = $fiches_interior->limit(10)->get(['fiches.*'])->toArray();
            $data_interior = [];

            foreach ($fiches_interior_list as $value) {
                $value['photo_count'] = Photo::where('category', 'INTERIOR')->where('fiche_id', $value['id'])->count();
                $data_interior[] = $value;
            }
            $data['Interior'] = ['count' => $fiches_interior->count(), 'list_fiches' => $data_interior];

            $fiches_exterior = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
            $fiches_exterior_list = $fiches_exterior->limit(10)->get(['fiches.*'])->toArray();
            $data_exterior = [];

            foreach ($fiches_exterior_list as $value) {
                $value['photo_count'] = Photo::where('category', 'EXTERIOR')->where('fiche_id', $value['id'])->count();
                $data_exterior[] = $value;
            }
            $data['Exterior'] = ['count' => $fiches_exterior->count(), 'list_fiches' => $data_exterior];

            $fiches_work = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
            $fiches_work_list = $fiches_work->limit(50)->get(['fiches.*'])->toArray();
            $data_work = [];

            foreach ($fiches_work_list as $value) {
                $value['photo_count'] = Photo::where('category', 'AT_WORK')->where('fiche_id', $value['id'])->count();
                $data_work[] = $value;
            }
            $data['Work'] = ['count' => $fiches_work->count(), 'list_fiches' => $data_work];

            $fiches_teams = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
            $fiches_teams_list = $fiches_teams->limit(10)->get(['fiches.*'])->toArray();
            $data_teams = [];

            foreach ($fiches_teams_list as $value) {
                $value['photo_count'] = Photo::where('category', 'Teams')->where('fiche_id', $value['id'])->count();
                $data_teams[] = $value;
            }
            $data['Teams'] = ['count' => $fiches_teams->count(), 'list_fiches' => $data_teams];

            $fiches_identity = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
            $fiches_identity_list = $fiches_identity->limit(10)->get(['fiches.*'])->toArray();
            $data_identity = [];

            foreach ($fiches_identity_list as $value) {
                $value['photo_count'] = Photo::whereIn('category', ['PROFILE', 'LOGO'])->where('fiche_id', $value['id'])->count();
                $data_identity[] = $value;
            }
            $data['Identity'] = ['count' => $fiches_identity->count(), 'list_fiches' => $data_identity];

            // $fiches_360 = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED')->whereIn('fiches.id', Photo::distinct()->where('format', 'MEDIA_FORMAT_UNSPECIFIED')->get('fiche_id')->toArray())->distinct();
            // $fiches_360_list = $fiches_360->get(['fiches.*'])->toArray();
            // $data_threeD = [];
            // foreach ($fiches_360_list as $value) {
            //     $value['photo_count'] = Photo::where('format', 'MEDIA_FORMAT_UNSPECIFIED')->where('fiche_id', $value['id'])->count();
            //     $data_threeD[] = $value;
            // }
            // $data['threeD'] = ['count' => $fiches_360->count(), 'list_fiches' => $data_threeD];

            $fiches_video = Fiche::Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
            $fiches_video_list = $fiches_video->limit(10)->get(['fiches.*'])->toArray();
            $data_video = [];

            foreach ($fiches_video_list as $value) {
                $value['photo_count'] = Photo::where('format', 'VIDEO')->where('fiche_id', $value['id'])->count();
                $data_video[] = $value;
            }
            $data['Video'] = ['count' => $fiches_video->count(), 'list_fiches' => $data_video];

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

    public function shortNumber($num)
    {
        $units = ['', 'K', 'M', 'B', 'T'];
        for ($i = 0; $num >= 1000; ++$i) {
            $num /= 1000;
        }

        return round($num, 1) . $units[$i];
    }

    public function photo_stats(Request $request)
    {
        $etiquetes = [];
        $fiches = [];
        $stats = [];
        if (isset($request->Ettiquets)) {
            foreach ($request->Ettiquets as $key => $value) {
                // code...
                if ($value['type'] == 'Fiche') {
                    $fiches[] = $value;
                } else {
                    $etiquetes[] = $value;
                }
            }
        }

        $messages = [
            'Etiquette.*.id.exists' => 'L\'étiquette qui vous chercher est indisponible',
            'Fiche.*.id.exists' => 'La fiche qui vous chercher est indisponible',
            'date_debut' => 'Date de début est invalide',
            'date_fin' => 'Date de fin est invalide',
        ];

        $input = [
            'Etiquette' => $etiquetes,
            'Fiche' => $fiches,
            'date_debut' => $request->Date_debut,
            'date_fin' => $request->Date_fin,
        ];

        $validator = Validator::make(
            $input,
            [
                    'Etiquette.*.id' => 'exists:etiquettes,id',
                    'Fiche.*.id' => 'exists:fiches,id',
                    'date_debut' => 'required|Date',
                    'date_fin' => 'required|Date',
                        ],
            $messages
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $input = (object) $input;

        try {
            DB::enableQueryLog();
            $stats = Statistique::query();
            $stats->join('fiches', 'statistiques.fiche_id', '=', 'fiches.id')->Join('franchises', 'franchises.id', '=', 'fiches.franchises_id')->where('franchises.id', '=', $request->header('franchise'))->where('fiches.state', 'LIKE', 'COMPLETED');
            $stats->Leftjoin('categories', 'categories.fiche_id', '=', 'fiches.id');
            if (isset($input->Etiquette) && !empty($input->Etiquette)) {
                $stats->Leftjoin('etiquetgroupes', 'etiquetgroupes.fiche_id', '=', 'statistiques.fiche_id')
                        ->Where(function ($stats) use ($input) {
                            $stats->whereIn('etiquetgroupes.etiquette_id', array_column($input->Etiquette, 'id'));
                            if (isset($input->Fiche) && !empty($input->Fiche)) {
                                $stats->orwhereIn('statistiques.fiche_id', array_column($input->Fiche, 'id'));
                            }
                        });
            }
            if ((empty($input->Etiquette)) && ((!empty($input->Fiche) && (isset($input->Fiche))))) {
                $stats->whereIn('statistiques.fiche_id', array_column($input->Fiche, 'id'));
            }
            $list_categories = array_unique($stats->pluck('categories.categorieId')->toArray());
            $list_ids_fiches = array_unique($stats->pluck('fiches.id')->toArray());
            $compare = Statistique::join('fiches', 'statistiques.fiche_id', '=', 'fiches.id')->Leftjoin('categories', 'categories.fiche_id', '=', 'fiches.id')
                            ->whereNotIn('fiches.id', $list_ids_fiches)->whereIn('categories.categorieId', $list_categories);
            $customerViews = $stats->sum('statistiques.photosViewsCustomers');
            $properitaryViews = $stats->sum('statistiques.photosViewsMerchant');
            $statsdate = clone $stats;

            $Min_date = $statsdate->orderBy('statistiques.date', 'ASC')->first('statistiques.date');

            $data['DateRestriction'] = (isset($Min_date) && !empty($Min_date)) ? Carbon::parse($Min_date->date)->translatedFormat('Y-m-d') : null;
            $TotalViews = $customerViews + $properitaryViews;
            $TotalViews = ($TotalViews > 0) ? $TotalViews : 1;
            $data['viewsCount'] = ['Customer' => number_format((float) ($TotalViews - $properitaryViews) / $TotalViews * 100, 1, '.', ''), 'Properitary' => number_format((float) ($TotalViews - $customerViews) / $TotalViews * 100, 1, '.', ''), 'Total' => $this->shortNumber($TotalViews)];
            if (isset($input->date_debut) && isset($input->date_fin)) {
                $dateRange = CarbonPeriod::create($input->date_debut, $input->date_fin);
                foreach ($dateRange as $key => $date) {
                    $dateListe[] = Carbon::parse($date)->translatedFormat('Y-m-d');
                    $labsX[] = Carbon::parse($date)->translatedFormat('d') . '/' . Carbon::parse($date)->translatedFormat('m');
                    // print_r($statsCustomer->where('statistiques.date', 'LIKE', '"%'.Carbon::parse($date)->translatedFormat('Y-m-d').'%"')->sum('statistiques.photosViewsCustomers'));
                    // exit;

                    $statsCustomer = clone $stats;

                    $statsC = null;
                    $statsC = $statsCustomer->where('statistiques.date', 'LIKE', '%' . Carbon::parse($date)->translatedFormat('Y-m-d') . '%')->get(['statistiques.photosViewsCustomers', 'statistiques.photosViewsMerchant', 'statistiques.date'])->toArray();

                    $data['Customer'][$key] = array_sum(array_column($statsC, 'photosViewsCustomers'));

                    $data['Marchant'][$key] = array_sum(array_column($statsC, 'photosViewsMerchant'));
                }
                $maxCustomer = max($data['Customer']);
                $maxMarchant= max($data['Marchant']);
                if ($maxCustomer > $maxMarchant) {
                    foreach ($dateRange as $key => $date) {
                        $data['grayBar'][$key] = $maxCustomer;
                    }
                } else {
                    foreach ($dateRange as $key => $date) {
                        $data['grayBar'][$key] = $maxMarchant;
                    }
                }

                $data['DatesList'] = $dateListe;
                $data['AxeLabels'] = $labsX;
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

    public function store(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $messages = [

            'File.required' => 'Vérifier Votre file!',
            'Fiche.required' => 'Vérifier Votre fiche!',
            'User.required' => 'Vérifier Votre user!',
            'Type_photo' => 'Type_photo doit etres EXTERIOR,INTERIOR,VIDEO,IDENTITY,ALL,TEAMS,AT_WORK,PROPERITARY,CUSTOMER,COVER,LOGO',
        ];

        $input = [
            'File' => $request['file'],
            'Fiche' => $request['fiche_id'],
            'User' => $request['user_id'],
        ];


        if (isset($request['type_photo']) && !empty($request['type_photo'])) {
            $input['Type_photo'] = $request['type_photo'];
        }

        $validator = Validator::make(
            $input,
            [
                    'User' => 'exists:users,id',
                    'Fiche' => 'exists:fiches,id',
                    'Type_photo' => 'required|in:EXTERIOR,INTERIOR,VIDEO,IDENTITY,ALL,TEAMS,AT_WORK,PROPERITARY,CUSTOMER,COVER,LOGO', 
                    'File' => 'required',
                        ],
            $messages
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }

        $input = (object) $input;
     
        if ($validator->passes()) {
            try {
                    $photo = $input->File;
                    foreach ($photo as $file) {
                        $image_64 = $file;
                        $time = time();
                        $new_data = explode(';', $image_64);
                        $type = $new_data[0];
                        $extension = explode('/', $type);
                        $datap = explode(',', $new_data[1]);
                        $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];
                        Storage::disk('public')->put($imageName, base64_decode(str_replace('%2B', '+', $datap[1])));
                        try {
                            $path = public_path('/app/public/photos/'  . $imageName);
        
                            $height = \Intervention\Image\Facades\Image::make($path)->height();
                            
                            $width = \Intervention\Image\Facades\Image::make($path)->width();
        
                            if ($height < 260 || $width < 250) {
                                return response()->json([
                                            'success' => false,
                                            'message' => "Catégorie PROFIL et COUVERTURE, toutes les photos doivent mesurer au minimum 250px sur le bord court, avec une taille de fichier d'au moins 10240 octets.",
                                            'status' => 422,], 422);
                            }
        
        
        
                            if (isset($input->Type_photo) && !empty($input->Type_photo)) {
                                $this->media->setMediaFormat('PHOTO');
        
                                $this->locationas->setCategory($input->Type_photo);
                                $this->media->setLocationAssociation($this->locationas);
                                $this->media->setSourceUrl(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/'  . $imageName);
        
                                if (isset($input->Fiche) && !empty($input->Fiche)) {
                                    $fichess = Fiche::where("id", $input->Fiche)->get();
                                }
        
        
                                foreach ($fichess as $key => $f) {
                                    $result = $this->mediaphoto->create($f->name, $this->media);
                                
                                    $data = [];
                                    $data['category'] = $result->locationAssociation->category;
                                    $data['name'] = $result->name;
                                    $data['views'] = $result->insights->viewCount;
                                    $data['file'] = $result->googleUrl;
                                    $data['thumbnail'] = $result->thumbnailUrl;
                                    $data['format'] = $result->mediaFormat;
                                    $data['width'] = $result->dimensions->widthPixels;
                                    $data['height'] = $result->dimensions->heightPixels;
                                    $data['fiche_id'] = $f->id;
                                    $data['user_id'] = $input->User;
                                    $data['created_at'] = Carbon::parse($result->createTime)->translatedFormat('Y-m-d H:i:s');
                                    Storage::disk('public')->delete(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);
                                    $photo = Photo::create($data);
                                }
                            }
                        } catch (\Throwable $th) {
                            $message = json_decode($th->getMessage());
        
                            if (isset($message->error->details[0]->errorDetails[0]->message)) {
                                $message = $message->error->details[0]->errorDetails[0]->message;
                            } else {
                                $message = $th->getMessage();
                            }
        
                            return response()->json(
                                [
                                        'success' => false,
                                        'message' => $message,
                                        'status' =>401,
                                            ], $th->getCode()
                            );
                        }
                    }




                    return response()->json([
                                'success' => true,
                                'message' => 'Photo ajouté avec succès',
                                'data' => $photo,
                                'status' => Response::HTTP_OK,
                                    ], Response::HTTP_OK);
                
             } catch (\Throwable $e) {
                return response()->json(
                    [
                            'success' => false,
                            'message' => $e->getmessage(),
                            'status' => 4012,//$e->getCode(),
                                ], $e->getCode()
                );
            }
    }
}

    //notificationphoto
    public function notificationphoto(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $franchises = $request->header('franchise');
        $totals = [];
        $ficheid = $request->fiche_id;

        $totalfiche = Fiche::where('franchises_id', $franchises)
                        ->leftjoin('photos', 'fiches.id', 'photos.fiche_id')
                        ->select('fiches.*', 'photos.id as photo_id')
                        ->when($ficheid, function ($query) use ($ficheid) {
                            $query->where('fiches.id', $ficheid);
                        })->get();
        foreach ($totalfiche as $fiches) {
            $nbnotif = 0;
            $details = [];
            $fiche_id = $fiches->id;
            $photo = Photo::join('photohistories', 'photohistories.photo_id', '=', 'photos.id')
                    ->select('photos.file', 'photohistories.photo_id', 'photohistories.file as fileprecedent')
                    ->when($ficheid, function ($query) use ($ficheid) {
                        $query->where('fiches.id', $ficheid);
                    })
                    ->where('photos.id', $fiches->photo_id)
                    ->whereColumn('photos.file', '<>', 'photohistories.file');

            if ($photo->exists()) {
                $detailsphoto = $photo->get();
                foreach ($detailsphoto as $pho) {
                    ++$nbnotif;
                    $details[] = ['fiche_id' => $fiche_id, 'file' => $pho->file, 'idphoto' => $pho->photo_id];
                }
                $totals[] = ['fiche_count' => $photo->count(), 'fiche_id' => $fiche_id, 'locationName' => $fiches->locationName, 'details' => $details,
                    'countnotif' => $photo->count(),];
            }
        }

        return response()->json([
                    'success' => true,
                    'message' => 'Liste notification photos',
                    'totalnotifphotos' => count($totals),
                    'data' => $totals,
                    'status' => 200,
                        ], 200);
    }

    public function destroy(Photo $photo)
    {
        try {
            $photo->delete();

            return response()->json([
                        'success' => true,
                        'message' => 'Supprimer avec succées',
                        'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                        'success' => false,
                        'message' => 'Photo could not be deleted',
                        'status' => 500,
                            ], 500);
        }
    }

    public static function photo($image_64)
    {
        $width = 400;
        $height = 300;

        if ((strpos($image_64, 'data:image/') !== false) || (strpos($image_64, 'data:video/') !== false)) {
            $time = time();
            $new_data = explode(';', $image_64);
            $type = $new_data[0];
            $extension = explode('/', $type);
            $datap = explode(',', $new_data[1]);
            $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];

            Storage::disk('public')->put($imageName, base64_decode(str_replace('%2B', '+', $datap[1])));

            $path = public_path('/app/public/photos/' . $imageName);
            $image = \Intervention\Image\Facades\Image::make($path)->resize($width, $height);
            $result = $image->save($path);
            $imageName = (\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);
        } else {
            $imageName = $image_64;
        }

        return $imageName;
    }

    // update fiche photo
    public function updatefichephoto(Request $request)
    {
        try {
            $messages = [
                'franchises_id' => 'Vérifier Votre franchises!',
                'size' => 'The :attribute must be exactly :size.',
                'between' => 'The :attribute must be between :min - :max.',
                'in' => 'The :attribute must be one of the following otherlocationadresses: :values',
            ];

            $validator = Validator::make($request->all(), [
                        [
                            'franchises_id' => 'exists:franchises,id',
                        ], $messages,
            ]);

            if ($validator->fails()) {
                return response()->json(['succes' => false,
                            'message' => $validator->errors()->toArray(),
                            'status' => 422,
                                ], 422);
            }

            $updateMask = null;
            if ($validator->passes()) {
                try {
                    $message = null;
                    $listfiche = $request->listfiche;
                    $file = $request->photo;
                    $id = '';
                    $message = 'Photo logo ajouter avec succes';
                    $i = 0;
                    foreach ($listfiche as $fiches) {
                        $id = '';
                        if ($request->fiche_id && $fiches['status'] === false) {
                            $id = $request->fiche_id;
                        } elseif ($fiches['status'] === true) {
                            $id = $fiches['id'];
                        }
                        if ($id) {
                            try {
                                $fiche = Fiche::find($id);

                                $image_64 = $file;

                                $time = time();
                                $new_data = explode(';', $image_64);
                                $type = $new_data[0];
                                $extension = explode('/', $type);
                                $datap = explode(',', $new_data[1]);
                                $imageName = 'photo_' . $time . rand(10, 900) . '.' . $extension[1];
                                Storage::disk('public')->put($imageName, base64_decode(str_replace('%2B', '+', $datap[1])));

                                $data['file'] = $imageName;

                                $this->media->setMediaFormat('PHOTO');
                                $this->locationas->setCategory('LOGO');
                                $this->media->setLocationAssociation($this->locationas);
                                $this->media->setSourceUrl(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);

                                $result = $this->mediaphoto->create($fiche->name, $this->media);
                                Storage::disk('public')->delete(\Illuminate\Support\Facades\URL::to('/') . '/app/public/photos/' . $imageName);

                                $data['name'] = $result->name;
                                $data['user_id'] = Auth()->user()->id;
                                $data['fiche_id'] = $id;
                                $data['category'] = $result->locationAssociation->category;
                                $data['name'] = $result->name;
                                $data['views'] = $result->insights->viewCount;
                                $data['file'] = $result->googleUrl;
                                $data['thumbnail'] = $result->thumbnailUrl;
                                $data['format'] = $result->mediaFormat;
                                $data['width'] = $result->dimensions->widthPixels;
                                $data['height'] = $result->dimensions->heightPixels;
                                $fiche->logo = $result->googleUrl;
                                $fiche->update();
                                $photo = Photo::create($data);
                                $data['photo_id'] = $photo->id;
                                Photohistorie::create($data);
                            } catch (\Google_Service_Exception $ex) {
                                return response()->json(
                                    [
                                            'success' => false,
                                            'message' => 'La requête contient un argument invalide',
                                            'status' => 400,
                                                ],
                                    $ex->getCode()
                                );
                            }
                            ++$i;
                        }
                        ++$i;
                    }

                    return response()->json([
                                'success' => true,
                                'message' => $message,
                                'data' => [],
                                'status' => Response::HTTP_OK,
                                    ], Response::HTTP_OK);
                } catch (QueryException $ex) {
                    return response()->json(
                        [
                                'success' => false,
                                'message' => $ex->getMessage(),
                                'status' => 400,
                                    ],
                        $ex->getCode()
                    );
                }
            }
        } catch (GlobalException $ex) {
            return response()->json([
                        'success' => false,
                        'message' => 'Désole, fiches not found.',
                        'status' => 400,
                            ], 400);
        }
    }
    public function get_count_photo(Request $request)
    {
        if (!is_numeric($request->header('franchise'))) {
            return response()->json([
                        'success' => false,
                        'message' => $request->header('franchise'),
                        'status' => 400,
            ]);
        }
        $messages = [
           
            'Fiche.required' => 'Vérifier Votre fiche!',
        ];

        $input = [
            
            'Fiche_id' => $request['Fiche_id'],
        ];

        $validator = Validator::make(
            $input,
            [
                    'Fiche_id' => 'exists:fiches,id',
                       ],
            $messages
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $key => $value) {
                $message = $value[0];
            }

            return response()->json([
                        'success' => false,
                        'message' => $message,
                        'status' => 422,], 422);
        }
        $input = (object) $input;
        try {
            $photosQuery = Photo::where('fiche_id', $input->Fiche_id);
            $photosCount = $photosQuery->count();
            $data['Count'] = $photosCount;
            $data['Fiche_id'] = $input->Fiche_id;
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
}
