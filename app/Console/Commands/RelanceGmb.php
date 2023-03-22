<?php

namespace App\Console\Commands;

use App\Models\Fiche;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;
use App\Mail\RelanceEmail;

class RelanceGmb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quote:daily';

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

        $users = Fiche::leftJoin('ficheusers', 'ficheusers.fiche_id', '=', 'fiches.id')->
                leftJoin('users', 'users.id', '=', 'ficheusers.user_id')
              ->select('fiches.locationName', 'fiches.name', 'users.lastname','users.firstname',
                      'users.sex','users.email','fiches.closedatestrCode','fiches.address')
                //    ->where('ficheusers.user_id', '=', Auth()->user()->id)
                ->where('fiches.state','LIKE', 'Code Google')
                ->get();
       foreach ($users as $user) {
      
            if($user->email){
               
                        $datelast = Carbon::now();
                        
                        $datefirst=Carbon::parse($user->closedatestrCode);
 $intar = $datelast->diffInDays($datefirst);
   


         $email = $user->email;
         $user->dates=$datefirst->translatedFormat('l j F Y');
         $user->nombreday=$intar;
          if($intar >=7){
        Mail::to($email)->send(new RelanceEmail($user));
        }
    
            }
          $this->info('Successfully sent daily quote to everyone.');
        }


    }

}
