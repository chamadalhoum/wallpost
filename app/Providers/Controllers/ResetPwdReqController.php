<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Carbon\Carbon;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class ResetPwdReqController extends Controller
{
    public function reqForgotPassword(Request $request){
        if(!$this->validEmail($request->email)) {
       
            return response()->json([
                'message' => 'Verifier Votre Email.',
                'success'=>false,
                'status'=>Response::HTTP_NOT_FOUND
            ], Response::HTTP_NOT_FOUND);
        } else {
            $this->sendEmail($request->email);
            return response()->json([
                'message' => 'Un mail vous a été envoyé. Merci de vérifier vos mails.',
                'success'=>true,
                'status'=>Response::HTTP_OK
            ], Response::HTTP_OK);
        }
    }


    public function sendEmail($email){
        $token = $this->createToken($email);
        $user = User::whereEmail($email)->first();
        $subject=$user->lastname. ' '.$user->firstname.' : Ré-initialisation de votre mot de passe';
        $path='Email.forgotPassword';
        Mail::to($email)->send(new SendMail($token,$user,$subject,$path));
    }

    public function validEmail($email) {
        return !!User::where('email', $email)->first();
    }

    public function createToken($email){

        $token = Str::random(8);
        $this->saveToken($token, $email);

        return $token;
    }

    public function saveToken($token, $email){
        $user = User::whereEmail($email)->first();
        $user->update([
            'password'=>bcrypt($token),
           
        ]);
       // $this->validateToken($request)->delete();
        return response()->json([
            'data' => 'Password changed successfully.'
        ],Response::HTTP_CREATED);
    }

}
