<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\User\UserQuestion;
use App\Models\User\UserTopArtists;
use App\Models\User\UserTopGenres;
use App\Models\Auth\Profile;
use App\Models\Auth\VerificationCode;

use App\Models\Media\ListingItem;

use Illuminate\Support\Facades\Mail;

use App\Models\Listing\PostComments;
use App\Models\Listing\PostIntration;
use App\Models\Listing\PostIntrationTypes;

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Media\ListingItemResource;
use App\Http\Resources\Media\PostCommentResource;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    function getUserListings(Request $request){
    	$user = Auth::user();
    	if(!$user){
    		return response()->json(['status' => false,
					'message'=> 'Unauthenticated user',
					'data' => null,
				]);
    	}

    	$offset = $request->off_set;
    	if($offset == NULL){
    		$offset = 0;
    	}
    	
    	$list = ListingItem::where('user_id', $request->user_id)->orderBy('created_at', 'DESC')->skip($offset)->take(20)->get();
    	


    	return response()->json(['status' => true,
					'message' => 'List',
					'data' => ListingItemResource::collection($list),
				]);

    }

    function deleteListing(Request $request){
    	$user = Auth::user();
    	if(!$user){
    		return response()->json(['status' => false,
					'message'=> 'Unauthenticated user',
					'data' => null,
				]);
    	}
    	$deleted = ListingItem::where('id', $request->post_id)->delete();
    	if($deleted){
    		return response()->json(['status' => true,
					'message' => 'Item delted',
					'data' => null,
				]);
    	}
    	else{
    		return response()->json(['status' => false,
					'message' => 'Item not delted',
					'data' => null,
				]);
    	}
    }
}
