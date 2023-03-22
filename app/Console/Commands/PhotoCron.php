<?php

namespace App\Console\Commands;

use App\Helper\Helper;
use App\Models\Fiche;
use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\profilincomplete;
use App\Models\Franchise;
use Google; 

class PhotoCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photo:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get photo stats';

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
        $this->mybusinessService = Helper::GMB();

        $fiches = Fiche::where('state', 'LIKE', 'COMPLETED')->get();
        foreach ($fiches as $fiche) {
            print_r('debut fiche :: '.$fiche->locationName);
            //Insert current locations  photo in database
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
      
            try {
                do {
                    $nextPageToken = null;
                    $media = $this->mybusinessService->accounts_locations_media->listAccountsLocationsMedia($fiche->name, ['pageSize' => 1500, 'pageToken' => $nextPageToken]);

                    foreach ($media as $key => $value) {
                       

                        $data = [];
                        $data['category'] = $value->locationAssociation->category;
                        $data['name'] = $value->name;
                        $data['views'] = $value->insights->viewCount;
                        $data['file'] = $value->googleUrl;
                        $data['thumbnail'] = $value->thumbnailUrl;
                        $data['format'] = $value->mediaFormat;
                        $data['width'] = $value->dimensions->widthPixels;
                        $data['height'] = $value->dimensions->heightPixels;
                        $data['fiche_id'] = $fiche->id;

                        $data['created_at'] = Carbon::parse($value->createTime)->translatedFormat('Y-m-d H:i:s');
                        Photo::updateOrCreate(
                             ['name' => $value->name],
                             $data
                         );
                    }
                    if (isset($media->nextPageToken)) {
                        $nextPageToken = $media->nextPageToken;
                    }
                    sleep(1);
                } while ($nextPageToken != null);
            } catch (\Throwable $th) {
                print_r($th->getMessage());

                continue;
            }

            //Insert Clients photos in database to the current location
            try {
                do {
                    $nextPageToken = null;
                    $mediacustomers = $this->mybusinessService->accounts_locations_media_customers->listAccountsLocationsMediaCustomers($fiche->name, ['pageSize' => 199, 'pageToken' => $nextPageToken]);
                // print_r($mediacustomers);exit;
                    foreach ($mediacustomers as $key => $value) {
                        

                        $data = [];
                        $data['category'] = 'CUSTOMER';
                        $data['name'] = $value->name;
                        $data['views'] = $value->insights->viewCount;
                        $data['file'] = $value->googleUrl;
                        $data['thumbnail'] = $value->thumbnailUrl;
                        $data['format'] = $value->mediaFormat;
                        $data['width'] = $value->dimensions->widthPixels;
                        $data['height'] = $value->dimensions->heightPixels;
                        $data['profileName'] = $value->attribution->profileName;
                        $data['profilePhotoUrl'] = $value->attribution->profilePhotoUrl;
                        $data['profileUrl'] = $value->attribution->profileUrl;
                        $data['takedownUrl'] = $value->attribution->takedownUrl;
                        $data['fiche_id'] = $fiche->id;

                        $data['created_at'] = Carbon::parse($value->createTime)->translatedFormat('Y-m-d H:i:s');
                        Photo::updateOrCreate(
                             ['name' => $value->name],
                             $data
                         );
                    }
                    if (isset($media->nextPageToken)) {
                        $nextPageToken = $media->nextPageToken;
                    }
                    sleep(1);
                } while ($nextPageToken != null);
            } catch (\Throwable $th) {
                print_r($th->getMessage());
                continue;
            }

            $photos=Photo::Where('category','LOGO')->Where('fiche_id',$fiche->id);
            $photosprofil=Photo::Where('category','PROFILE')->Where('fiche_id',$fiche->id);
            if($photos->exists()){
                $photo=$photos->first();
                $dataprofil['logostorelocatore']=$photo->file;
            }elseif($photosprofil->exists()){
                $photopro=$photosprofil->first();
                $dataprofil['logostorelocatore']=$photopro->file;
            }
                else {
                $franchise= Franchise::find($fiche->franchises_id);
                $dataprofil['logostorelocatore']="https://api-wallpost.bforbiz-dev.com/public/".$franchise->logo;
            }  
      
           
            profilincomplete::updateOrCreate(['fiche_id'=>$fiche->id],$dataprofil);
        }
    }
}
