<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Listing\PostComments;
use App\Models\Listing\PostIntration;
use App\Models\Listing\PostIntrationTypes;

use App\Models\User;
use App\Models\Auth\Profile;
use App\Models\Auth\VerificationCode;

use App\Models\User\Follower;

use App\Models\Media\ListingItem;

use App\Models\Notification;
use App\Models\NotificationType;

use Illuminate\Support\Facades\Mail;

use App\Http\Resources\Profile\UserProfileLiteResource;
use App\Http\Resources\Media\PostCommentResource;
use Pusher;

class SocialController extends Controller
{
    public function followUser(Request $request){

    	$user = Auth::user();
    	if($user === NULL){
    		return response()->json(['status' => false,
                    'message'=> 'Unauthenticated user',
                    'data' => null,
                ]);
    	}

    	$followed_id = $request->user_id;
    	if($user->id == $followed_id){
    		return response()->json(['status' => false,
                    'message'=> 'Action not allowed (Following self)',
                    'data' => null,
                ]);
    	}

    	$exists = Follower::where('follower', $user->id)->where('followed', $request->user_id)->first();
    	if($exists){
    		return response()->json(['status' => true,
                    'message'=> 'Already following',
                    'data' => null,
                ]);
    	}

    	$follower = new Follower;
    	$follower->follower = $user->id;
    	$follower->followed = $followed_id;

    	$saved = $follower->save();
    	if($saved){

            Notification::add(NotificationType::NewFollower, $user->id, $request->user_id, $user);
    		return response()->json(['status' => true,
                    'message'=> 'User followed',
                    'data' => null,
                ]);
    	}
    	else{
			return response()->json(['status' => false,
                    'message'=> 'User could not be followed',
                    'data' => null,
                ]);
    	}

    }
}
