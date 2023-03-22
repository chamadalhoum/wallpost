<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;
use App\Mail\SendMail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;
use JWTAuth;
use Illuminate\Http\Request;
use mysql_xdevapi\Exception;
use App\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
//use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

/*test*/
class ApiController extends Controller
{
    public $token = true;

    public function authenticate(Request $request)
    {


        if($request->input('email')){
            $email =$request->input('email');

            $users=User::Where('email',$email);

            if($users->exists()){

            $users=$users->first();

            $token =JWTAuth::fromUser($users);

            if ($token) {

                try {
                    User::where('id', $users->id)->update(['remember_token' => $token]);
                 $datause=User::join('roles','roles.id','=','users.role_id')
                    ->select('users.*','roles.name as roles')->where('users.id','=',$users->id)->get();
               
                    return response()->json([
                        'success' => true,
                        'token' => 'Bearer '.$token,
                        'message' => 'Connecté avec succés',
                        'data'=>$datause,
                       
                             'expires_in' => auth()->factory()->getTTL() ,
                        'status' => 200
                    ]);
                } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $exception) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token invalide',
                        'status' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY],
                        JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                    );
                } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $exception) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token est expiré',
                        'status' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                    ],
                        JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                } catch (Tymon\JWTAuth\Exceptions\JWTException $exception) {

                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de créer token.',
                        'status' => 500
                    ], 500);
                }

            }
        }else {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre Email invalide.',
                    'status' => 400
                ], 400);

            }
        }else{

        $login = $request->input('login');

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $request->merge([$field => $login]);
        $credentials = $request->only($field, 'password');
        //valid credential
        $validator = Validator::make($credentials, [
            'password' => 'required|string|min:6|max:50'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 400);
        }
        $token = JWTAuth::attempt($credentials);
        if ($token) {
            try {
                User::where('id', auth()->user()->id)->update(['remember_token' => $token]);
                $datause=User::where('users.id', auth()->user()->id)->join('roles','roles.id','=','users.role_id')
                ->select('users.*','roles.name as roles')->get();
                return response()->json([
                    'success' => true,
                    'token' => 'Bearer '.$token,
                    'message' => 'connecté avec succés',
                    //'data'=>User::where('id', auth()->user()->id)->get(),
                    'data'=>$datause,
                    'expires_in' => auth()->factory()->getTTL() ,
                    'status' => 200
                ]);
            } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $exception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide',
                    'status' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $exception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token est expiré',
                    'status' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                ],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            } catch (Tymon\JWTAuth\Exceptions\JWTException $exception) {

                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de créer token.',
                    'status' => 500
                ], 500);
            }

        } else {

            return response()->json([
                'success' => false,
                'message' => 'Login ou mot de passe invalide.',
                'status' => 400
            ], 400);

        }

    }
    }

    public function logout(Request $request)
    {

        //valid credential
        $validator = Validator::make($request->only('token'), [
            'token' => 'Bearer '.'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
                'status' => 422
            ], 422);
        }


        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'User has been logged out',
                'status' => 200
            ]);
        } catch (JWTException $exception) {

            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function refresh()
    {
        try {
            $token = auth()->refresh();
            User::where('id', auth()->user()->id)->update(['remember_token' => $token]);
            return response()->json([
                'token' => 'Bearer '.$token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL(),

            ]);
        } catch (TokenBlacklistedException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Token est expiré',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Token est expiré',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


    }
}
