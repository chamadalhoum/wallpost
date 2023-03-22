<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Crypt\Hash;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;
    public $token;
    public $user;
    public $date;
    public $subject;
    public $path;
    public $password;
    public $login;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token,$user,$subject,$path)
    {
        $this->token = $token;
        $this->user=$user->lastname.' '.$user->fir;
        $this->date=$user->updated_at;
        $this->subject=$subject;
        $this->path=$path;
        $this->password=$user->password;
        $this->login=$user->email;

        if($user->sex='femme'){
            $this->sex='Mme';
        }else{
            $this->sex='Mr';
        }

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown($this->path)->subject($this->subject)->with([
            'token' => $this->token,
            'user'=>$this->user,
            'date'=>$this->date,
            'sex'=>$this->sex,
            'password'=>$this->password,
            'login'=>$this->login

        ]);
    }
}
