<?php

namespace App\Console\Commands;

use App\Helper\Helper;
use App\Models\Fiche;
use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\profilincomplete;
use App\Models\Franchise;

class PhotodddCron extends Command
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

            try {
                do {
                    try{
                        $this->mybusinessService = Helper::GMB();
                        $nextPageToken = null;
                        $media = $this->mybusinessService->accounts_locations_media->listAccountsLocationsMedia($fiche->name, ['pageSize' => 100, 'pageToken' => $nextPageToken]);
              
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
                    }   catch (\Throwable $th) {
                        print_r($th->getMessage());
        sleep(2);
                        $this->mybusinessService = Helper::GMB();
                       
                        continue;
                    }
                
                    sleep(1);
                } while ($nextPageToken != null);
            } catch (\Throwable $th) {
                print_r($th->getMessage());

                $this->mybusinessService = Helper::GMB();

                continue;
            }

            try { 
                 $nextPageTokens = null;
                do {
                    try{
               //     $this->mybusinessService = Helper::GMB();
echo'<pre>-----';

                  
                  
                    $mediacustomers = $this->mybusinessService->accounts_locations_media_customers->listAccountsLocationsMediaCustomers($fiche->name, ['pageSize' => 100, 'pageToken' => $nextPageTokens]);
                   
                    foreach ($mediacustomers as $key => $values) {
                        print_r('debut Photo client MediaCustomers :: '.$value->googleUrl);

                        $data = [];
                        $data['category'] = 'CUSTOMER';
                        $data['name'] = $values->name;
                        $data['views'] = $values->insights->viewCount;
                        $data['file'] = $values->googleUrl;
                        $data['thumbnail'] = $values->thumbnailUrl;
                        $data['format'] = $values->mediaFormat;
                        $data['width'] = $values->dimensions->widthPixels;
                        $data['height'] = $values->dimensions->heightPixels;
                        $data['profileName'] = $values->attribution->profileName;
                        $data['profilePhotoUrl'] = $values->attribution->profilePhotoUrl;
                        $data['profileUrl'] = $values->attribution->profileUrl;
                        $data['takedownUrl'] = $values->attribution->takedownUrl;
                        $data['fiche_id'] = $fiche->id;

                        $data['created_at'] = Carbon::parse($values->createTime)->translatedFormat('Y-m-d H:i:s');
                       Photo::updateOrCreate(
                             ['name' => $values->name],
                             $data
                         );
                        
                    }
                 
                    if (isset($mediacustomers->nextPageToken)) {
                        $nextPageTokens = $mediacustomers->nextPageToken;
                    }
                    sleep(1);
                }
                catch (\Throwable $th) {
                    print_r($th->getMessage());
    
            $this->mybusinessService = Helper::GMB();
            sleep(2);
                
                    continue;
                }
    
                } while ($nextPageTokens != null);
            } catch (\Throwable $th) {
                print_r($th->getMessage());

                $this->mybusinessService = Helper::GMB();
                sleep(2);
                $mediacustomers = $this->mybusinessService->accounts_locations_media_customers->listAccountsLocationsMediaCustomers($fiche->name, ['pageSize' => 100, 'pageToken' => $nextPageTokens]);
                var_dump($mediacustomers);
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
