<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use App\Models\User;
use App\Models\Useraction;
use Illuminate\Http\Request;
use mysql_xdevapi\Exception;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;
use GoogleMyBusinessService;
use Google;
use App\Helper\Helper;

class UseractionController extends Controller
{

 public function __construct()
    {    $client = Helper::googleClient();
        $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);  
        $tab = $serviceAccount->accounts_admins->listAccountsAdmins('locations/15244426876986655176',array());
       
        foreach($tab["admins"] as $list){
             $listadmin[]=$list;
        }
        var_dump($listadmin);exit;
        $client = Helper::googleClient();
        $client = Helper::googleClient();
        $serviceLocation = new Google\Service\MyBusinessVerifications($client); 
        $pinverification = new Google\Service\MyBusinessVerifications\CompleteVerificationRequest($client); 
    
        $pinverification->pin='12345';
       /// $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);  
            $list_accounts = $serviceLocation->locations_verifications->complete('locations/15954465534746280452/verifications/1T1652264614456',$pinverification);
            var_dump($list_accounts);exit;
        $mybusinessService = new Google\Service\MyBusinessBusinessInformation($client); 
        $serviceLocation = new Google\Service\MyBusinessBusinessInformation($client); 
        $serviceAccount = new Google\Service\MyBusinessAccountManagement($client);  
        $list_accounts = $serviceAccount->accounts->listAccounts();
        $params=["readMask"=>"name,storeCode,title,phoneNumbers,websiteUri,labels,latlng,profile,locationName,metadata"];
        foreach ($list_accounts->accounts as $keyAccount => $account) {
            $accountsList[]=$account;
        }
        foreach ($accountsList as $keyLocation => $account) {
            do {
                echo $idLoc = @explode('/', $loc->name)[3];

                $list_locations = $serviceLocation->accounts_locations->listAccountsLocations($account->name,$params);
               
                foreach ($list_locations->locations as $key => $location) {
                    $dossier = $location->storeCode;
                    $locs[] = $location->locationName;
                    $idLoc=@explode('/', $location->name)[1];
                    $idaccount =@explode('/', $account->name)[1];
                    $upd1 = $publisiteModel->updategmbaccount($idaccount, $idLoc, $dossier);
                    ecrirLog($cron_file, " Account : " . $idLoc . "/Name" . $loc->name . " Update Gmb account : " . json_encode($dossier));

                    $row = $publisiteModel->getPublisitedossier($dossier);
                    $upd = $moduleModel->updateModulemybisness(1, $row["publisite_id"]);
                    foreach ($row as $column) {
                        echo ($column["publisite_id"]);
                    }
                    ob_flush();
                    if ($idLoc == 0 || ($uniquelocation && ($uniquelocation != $idLoc))) {
                        continue;
                    }

                    $select = $publisiteModel->select()->where('publisite_mybusiness_location like ?', trim($idLoc));
                    $cp = $publisiteModel->fetchRow($select);
                    if (!$cp) {
                        echo '<br />No publisite_mybusiness_location On DB';
                        echo '<hr>';
                        echo '<br />';
                        ob_flush();
                        continue;
                    }

                    $cp = $cp->toArray();

                    $selecta = $locationModel->select()->where('mybusiness_location_code = ?', $idLoc);
                    $lo = $locationModel->fetchRow($selecta);
                    if (!$lo) {

                        var_dump(array($idLoc, $location->locationName, '', $location->metadata["mapsUri"], $cp["publisite_id"]));
                        $resLo = $locationModel->addLocation(
                            $idLoc,
                            $location->locationName,
                            '',
                            $location->metadata["mapsUrl"],
                            $cp["publisite_id"]
                        );
                        ecrirLog($cron_file, " Site : " .  $cp["publisite_id"] . "/Account" . $idLoc . " Add Location: " . json_encode($idLoc, $location->locationName, '', $location->metadata["mapsUrl"], $cp["publisite_id"]));
                    } else {
                        $resLo = $locationModel->updateLocation($lo['mybusiness_location_id'], $idLoc, $location->locationName, '', $location->metadata["mapsUri"], $cp["publisite_id"]);
                        $resLo = $lo['mybusiness_location_id'];
                        ecrirLog($cron_file, "Site:" . $cp["publisite_id"] . "/Location : " .  $lo['mybusiness_location_id'] . "/Account" . $idLoc . "Update Location: " . json_encode($lo['mybusiness_location_id'], $idLoc, $location->locationName, '', $location->metadata["mapsUrl"], $cp["publisite_id"]));
                    }
                    $reviews = $mybusinessService->accounts_locations_reviews->listAccountsLocationsReviews($loc->name)->getReviews();

                    foreach ($reviews as $review) {
                        $select = $reviewModel->select()->where('review_code = ?', $review->reviewId);
                        $rv = $reviewModel->fetchRow($select);
                        //$rates["FIVE"]
                        if (!$rv) {
                            $reply = "";
                            $replyTime = "";
                            if (isset($review->reviewReply->comment)) {
                                $reply = $review->getReviewReply()->getComment();
                                $replyTime = $review->getReviewReply()->getUpdateTime();
                            }
                            ecrirLog($cron_file, "Review:" . $review->reviewId . "/ Site : " . $cp["publisite_id"] .  "add Review: " . json_encode($review->reviewId, $review->reviewer->displayName, $review->starRating, $review->comment, $review->createTime, $review->updateTime, $reply, $replyTime, 1, $resLo));

                            $reviewModel->addReview(
                                $review->reviewId,
                                $review->reviewer->displayName,
                                $review->starRating,
                                $review->comment,
                                $review->createTime,
                                $review->updateTime,
                                $reply,
                                $replyTime,
                                1,
                                $resLo
                            );
                            ecrirLog($cron_file, "Review:" . $review->reviewId . "/ Site : " . $cp["publisite_id"] .  "add Review: " . json_encode($review->reviewId, $review->reviewer->displayName, $review->starRating, $review->comment, $review->createTime, $review->updateTime, $reply, $replyTime, 1, $resLo));
                        }
                    }
                    ob_flush();
                        
                }
            } while ($list_locations->locations->nextPageToken == null);
                
            }
 

        $accountsList = $accounts->listAccounts()->getAccounts();
        var_dump($accountsList);exit;
        $params=["regionCode"=>"FR","languageCode"=>"fr","view"=>"FULL",'filter'=>'name=["categories/gcid:fitness_center"]'];
       $list_accounts_response = $service->categories->listcategories($params);
         var_dump($list_accounts_response);
         exit; 
        $params=["regionCode"=>"FR","languageCode"=>"fr","view"=>"FULL",'names'=>['categories/gcid:fitness_center']];
        $list_accounts_response = $service->categories->batchGet($params);
          var_dump($list_accounts_response);
       exit;
        $client = Helper::googleClient();
       $service = new Google\Service\MyBusinessBusinessInformation($client); 
       echo('<pre>');
       $params=["readMask"=>"serviceItems"];
       $payload = json_encode($params);
       $client = Helper::googleClient();
       $service = new Google\Service\MyBusinessBusinessInformation($client); 
       $read_mask="name,storeCode,title,phoneNumbers,websiteUri,labels,latlng,profile";
    
       $list_accounts_response = $service->locations->get("locations/1764130149800808953",$params);
       var_dump($list_accounts_response);exit;
       $params=["regionCode"=>"FR","languageCode"=>"fr","showAll"=>false,'categoryName'=>'categories/gcid:accountant'];
       $list_accounts_response = $service->attributes->listAttributes($params);

      var_dump($tab);exit;
       $Location= new Google\Service\MyBusinessBusinessInformation\Location($client); 
     //var_dump($Location);exit;
       $postBody=['updateMask'=>'title' ,'validateOnly' => false];
       $Location->title="20'NEXTFIT STUDIO EMS Avignonsfs";
           $list_accounts_response = $service->locations->patch('locations/3548549369906011722',$Location,$postBody);
           var_dump($list_accounts_response);exit;
           return json_decode($list_accounts_response,1);
       echo('<pre>');
       
       $payload = json_encode($params);
       
       $read_mask="name,storeCode,title,phoneNumbers,websiteUri,labels,latlng,profile";
       
         
        $client = Helper::googleClient();
       $service = new Google\Service\MyBusinessBusinessInformation($client); 
       echo('<pre>');
       $params=["regionCode"=>"FR","languageCode"=>"fr","view"=>"FULL",'names'=>['categories/gcid:fitness_center']];
       $payload = json_encode($params);
       
       $read_mask="name,storeCode,title,phoneNumbers,websiteUri,labels,latlng,profile";
  
         exit;
      

    //    $this->placeID = Helper::GMBcreate();
     //   $this->accounts = $this->mybusinessService->accounts;
    //    $this->locations = $this->mybusinessService->accounts_locations;
//$this->lists = $this->accounts->listAccounts()->getAccounts();
    //    $this->media = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_MediaItem();
     //   $this->mediaphoto = $this->mybusinessService->accounts_locations_media;
      //  $this->locationas = new \Scottybo\LaravelGoogleMyBusiness\Google_Service_MyBusiness_LocationAssociation();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {



        try {
            $log = Activity::query();
            $s = request('search');
            if ($s) {
                $logs = $log->where('log_name', 'LIKE', '%' . $s . '%')->
                orWhere('description', 'LIKE', '%' . $s . '%')->
                orWhere('subject_type', 'LIKE', '%' . $s . '%')->
                orWhere('causer_type', 'LIKE', '%' . $s . '%')->
                orWhere('properties', 'LIKE', '%' . $s . '%')
                    ->get();
                if ($logs->count() > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => $logs,

                        'status' => 200
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Désole, Log not found.',

                        'status' => 400
                    ], 400);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => $log->orderBy('id', 'DESC')->paginate(10),

                    'status' => 200
                ], 200);
            }

        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, Log not found.',

                'status' => 400
            ], 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Useraction $useraction
     * @return \Illuminate\Http\Response
     */
    public function show(Activity $useraction)
    {


        if (!$useraction) {
            return response()->json([
                'success' => false,
                'message' => 'Désole, User action not found.',

                'status' => 400
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'User Action id ' . $useraction->id,
            'data' => $useraction,

            'status' => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Useraction $useraction
     * @return \Illuminate\Http\Response
     */
    public function edit(Useraction $useraction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Useraction $useraction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Activity $useraction)
    {

        $messages = [
            'log_name.required' => 'Vérifier Votre email!',
            'description.required' => 'Vérifier Votre description!',
            'subject_type.required' => "Vérifier votre type d'objet!",
            'subject_id.required' => "Vérifier votre id d'objet!",
            'causer_type.required' => "Vérifier votre Model User!",
            'causer_id.required' => "Vérifier votre id user !",
            'properties.required' => "Vérifier votre action!",
            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute must be between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values',
        ];

        $validator = Validator::make($request->all(),
            [   "log_name" => 'required|max:45',
                "description" => 'required',
                "subject_type" => 'required|max:200',
                "causer_type" => 'required|max:190',
                "causer_id" => 'numeric|min:1',
                "subject_id" => 'numeric|min:1',
                "properties" => 'required',


            ], $messages
        );

        if ($validator->fails()) {
            return response()->json(['succes' => false,
                'message' => $validator->errors()->toArray(),
                'status' => 422,
             ],
                422);
        }
        if ($validator->passes()) {
            try {

                $useraction->log_name = $request->log_name;
                $useraction->description = $request->description;
                $useraction->subject_type = $request->subject_type;
                $useraction->subject_id = $request->subject_id;
                $useraction->causer_type = $request->causer_type;
                $useraction->causer_id = $request->causer_id;
                $useraction->properties = json_decode($request->properties);

                $useraction->update();
                return response()->json([
                    'success' => true,
                    'message' => 'Mise a jour traitée avec succes',
                    'data' => $useraction,

                    'status' => Response::HTTP_OK
                ], Response::HTTP_OK);
            } catch (QueryException $ex) {
                return response()->json([
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
     * @param \App\Models\Useraction $useraction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Activity $useraction)
    {

        try {
            $useraction->delete();
            return response()->json([
                'success' => true,

                'message' => 'Supprimer avec succées',
                'status' => 200,
            ]);
        } catch (TokenInvalidException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Role could not be deleted',
                'status' => 500,

            ], 500);
        }
    }
}
