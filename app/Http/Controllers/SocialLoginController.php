<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Auth\Profile;
use App\Models\Auth\VerificationCode;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
// use JWTAuth;
use App\Http\Resources\Profile\UserProfileFullResource;
// use App\Http\Resources\User\UserProfileLiteResource;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

// use App\Http\Controllers\AuthController;

use App\Models\User\UserQuestion;
use App\Models\User\UserTopArtists;
use App\Models\User\UserTopGenres;

use App\Http\Controllers\Auth\UserAuthController;

class SocialLoginController extends Controller
{

	function RegisterUserWithSocial(Request $req){
		$validator = Validator::make($req->all(), [
						//'email' => 'required|string|email|max:255|unique:users',
						// 'phone' => 'required|unique:users',
						'provider_id' => 'required|string|unique:users',
					]);

		if($validator->fails()){
			return response()->json(['status' => false,
				'message'=> 'validation error',
				'data' => null, 
				'validation_errors'=> $validator->errors()]);
		}


		DB::beginTransaction();
		$user=new User;
		$email = $this->getEmailForSocialLogin($req);
		$exists = User::where('email', $req->email)->first();
		if($exists){
		    if( ($exists->provider_name == 'facebook' && $req->provider_name == 'facebook') || ($exists->provider_name == 'google' && $req->provider_name == 'google') || ($exists->provider_name == 'apple' && $req->provider_name == 'apple') ){
			// the user is trying to login
		    }
			else{
			    return response()->json(['status' => false,
					'message'=> 'Email already exists',
					'socialStatus' => 'EmailAlreadyExistsWithDifferentProvider',
					'data' => null, 
				]);
			}
		}
		
		else{
// 			return response()->json(['status' => false,
// 					'message'=> 'Email is available',
// 					'socialStatus' => 'EmailAvailable',
// 					'data' => null, 
// 				]);
		}
			
		// $user->phone=$req->phone;
		$user->email=$email;
		// $user->role=$req->role;
		$user->password=Hash::make($req->provider_id);
		$user->provider_id = $req->provider_id;
		$user->provider_name = $req->provider_name;
		$result=$user->save();
		$token = Auth::login($user);
		
		$user_id = $user->id;
		if($result)
		{
		    	$reg = new UserAuthController();
				$response = $reg->AddProfile($req, $user);
			if($response == null){
				DB::rollBack();
				return response()->json([
					'message' => 'User not registered',
					'status' => false,
					'data' => null,
				]);
			}
			DB::commit();
			$token = Auth::login($user);
                    $profile = Profile::where('user_id', $user->id)->first();
        			return response()->json([
        			    'status' => true,
        			    'message' => 'User created successfully',
        			    'data' => [
        			    	'profile' => new UserProfileFullResource($profile),
        			        'access_token' => $token,
        			        'type' => 'bearer',
        			    	
        				]
        			]);
		}
		else
			{
				return response()->json([
					'message' => 'User not registered',
					'status' => false,
					'data' => null,

					
			]);
			}
	}


    function isSocialLoginAccountExists(Request $request){

		$validator = Validator::make($request->all(), [
			'provider_id' => 'required|string',
			'provider_name' =>'required|string',
// 			'email' => 'required'
				]);

			if($validator->fails()){
				return response()->json(['status' => false,
					'message'=> 'validation error',
					'data' => null, 
					'validation_errors'=> $validator->errors()]);
			}
			$loginid = $request->provider_id;
			$provider_name = $request->provider_name;
			
			

			$user = User::where('provider_id', $loginid)->first();

			
			if ($user == null)
			{
			    $email = $request->email;
			    if($email == ''){
			        $email = $this->getEmailForSocialLogin($request);
			    }


			    $exists = User::where('email', $email)->first();
			    // echo "Email " . $email;
				if($exists){
		            if( ($exists->provider_name == 'facebook' && $request->provider_name == 'facebook') || ($exists->provider_name == 'google' && $request->provider_name == 'google') || ($exists        ->provider_name == 'apple' && $request->provider_name == 'apple') ){
		        	// the user is trying to login
		            }
		        	else{
		        	    return response()->json(['status' => false,
		        			'message'=> 'Email already exists',
		        			'socialStatus' => 'EmailAlreadyExistsWithDifferentProvider',
		        			'data' => null, 
		        		]);
		        	}
		        }
		        
		        else{
		        	return response()->json(['status' => false,
		        			'message'=> 'Email is available',
		        			'socialStatus' => 'EmailAvailable',
		        			'data' => null, 
		        		]);
		        }


				
				
			}
			else{
			    $credentials = ["email" => $user->email, "password" => $loginid];
				// $credentials = $request->only('email', 'password');

        		
				try
				{
					$token = Auth::login($user);
					if(!$token )
					{
						return response()->json([
							'message' =>'Invalid_Credentials',
							'status' =>false,
							'data' => $credentials,
						]);
					}
				}
				catch (JWTException $e)
				{
					return response()->json([
					'message' => 'Could not create token '. $e->getMessage(),
					'status'=>false]);
				}

				$id = $user->id;

				$profile = Profile::where('user_id', $id)->first();
				$data = ["access_token" => $token];
				if ($profile == null){
					$data["profile"] = null; // means user is just regisetered his details are missing
				}
				else{
					$data["profile"] = new UserProfileFullResource($profile);
				}
				
					return response()->json([
					'message'=> 'Account logged in',
					'status' =>  true,
					'data'   => $data
					]);
			}
	}

	function getEmailForSocialLogin(Request $request){
		$email = '';
			if ($request->has('email')){
				$email = $request->email;
			}
			else{
				
			}
			if($email === '' || $email === NULL){
				$email = $request->provider_id."@".$request->provider_name.".com";
			}
			return $email;
	}
}
