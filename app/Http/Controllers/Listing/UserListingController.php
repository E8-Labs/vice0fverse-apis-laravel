<?php

namespace App\Http\Controllers\Listing;

use App\Http\Controllers\Controller;
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

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Media\ListingItemResource;
use Illuminate\Support\Facades\Http;

class UserListingController extends Controller
{
    function addListing(Request $request){
    	$user = Auth::user();

    	if(!$user){
    		return response()->json(['status' => false,
					'message'=> 'Unauthenticated user',
					'data' => null,
				]);
    	}
	
	
    	DB::beginTransaction();
    	$item = new ListingItem;
    	$item->user_id = $user->id;
	
    	$item->song_name = $request->song_name;
    	$item->lyrics = $request->lyrics;
    	$item->song_file = "N/A atm";
    	if($request->has('song_file')){
    		$item->song_file = $request->song_file;
    	}
    	if($request->hasFile('image'))
		{
			$data=$request->file('image')->store('Songs/');
			$item->image_path = $data;
			
		}
		else
		{
			return response()->json(['status' => false,
					'message' => 'No profile image',
					'data' => null,
				]);
			
		}

		$saved = $item->save();
		if($saved){
			DB::commit();
			return response()->json(['status' => true,
					'message' => 'Song saved',
					'data' => new ListingItemResource($item),
				]);
		}
		else{
			DB::rollBack();
			return response()->json(['status' => false,
					'message' => 'Some error occurred',
					'data' => null,
				]);
		}
    }


    function getListings(Request $request){
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
    	$type = "";

    	$list = ListingItem::orderBy('created_at', 'DESC')->skip($offset)->take(20)->get();
    	return response()->json(['status' => true,
					'message' => 'List',
					'data' => ListingItemResource::collection($list),
				]);

    }

}
