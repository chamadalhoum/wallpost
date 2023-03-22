<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AvertirPhoto extends Mailable
{
    use Queueable, SerializesModels;
public $fiche;
public $photo;
public $user;
public $message;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($fiche,$photo,$user,$message)
    {
       $this->fiche =$fiche['locationName']; //Fiche::where("id",$fiche_id)->find();
        $this->photo = $photo['file'] ;
        $this->user =$user['email']; //User::where("id",$user_id)->find();
        $this->message =$message; //User::where("id",$user_id)->find();
         }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return   $this->subject('Avertissement d\'une photo GMB')

                    ->markdown('Email.AvertirPhoto');
    }
}
