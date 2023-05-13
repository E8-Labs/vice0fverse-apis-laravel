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
use App\Models\Auth\UserRole;
use App\Models\Auth\VerificationCode;

use App\Models\Media\ListingItem;
use App\Models\User\Follower;

use App\Models\Auth\FlaggedUser;
use App\Models\Listing\FlaggedListing;
use App\Http\Resources\Media\FlaggedListingResource;

use Illuminate\Support\Facades\Mail;

use App\Models\Notification;
use App\Models\NotificationType;

use App\Models\Listing\PostComments;
use App\Models\Listing\PostIntration;
use App\Models\Listing\PostIntrationTypes;

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Media\ListingItemResource;
use App\Http\Resources\Media\PostCommentResource;
use Illuminate\Support\Facades\Http;

class UserListingController extends Controller
{
    function addListing(Request $request){
    	$user = Auth::user();

    	if(!$user){
    		return response()->json(['status' => false,
					'message'=> 'Unauthenticated user',
					'data' => $user,
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
    	if($request->hasFile('song_image'))
		{
			$data=$request->file('song_image')->store('Songs/');
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
            $admin = Profile::where('role', UserRole::RoleAdmin)->first();
            $type = get_class($item);
                // $not = Notification::where('from_user', $user->id)->where('to_user', $admin->user_id)->where('notification_type', NotificationType::NewPost)
                // ->where('notifiable_id', $post->id)->first();
                // if(!$not){
            Notification::add(NotificationType::NewPost, $user->id, $admin->user_id, $item);
                // }
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
        if($user->isAdmin()){
            $list = ListingItem::orderBy('created_at', 'DESC')->skip($offset)->take(20)->get();
            return response()->json([
                'status' => true,
                'message' => 'List',
                'data' => ListingItemResource::collection($list),
            ]);

        }


        $flaggedListingIds = FlaggedListing::where('from_user', $user->id)->pluck('listing_id')->toArray();
        
    	$type = "Recent";
    	$list = ListingItem::whereNotIn('id', $flaggedListingIds)->orderBy('created_at', 'DESC')->skip($offset)->take(20)->get();
    	if($request->has('type')){
    		$type = $request->type;
    	}

    	if($type == "Popular"){
    		//load from most views
    		$list = ListingItem::select('listing_items.*')
    		->selectSub(function ($query) {
    		    $query->selectRaw('COUNT(*)')
    		        ->from('post_intrations')
    		        ->whereRaw('post_intrations.post_id = listing_items.id');
    		}, 'post_interactions_count')
            ->whereNotIn('id', $flaggedListingIds)
    		->orderByDesc('post_interactions_count')
            ->skip($offset)->take(20)
    		->get();
    	}
    	else if ($type == "Feeling"){
    		//Load from feeling
            $following = Follower::where('follower', $user->id)->orderBy('created_at', 'DESC')->pluck('followed')->toArray();
            $list = ListingItem::whereIn('user_id', $following)->whereNotIn('id', $flaggedListingIds)->orderBy('created_at', 'DESC')->skip($offset)->take(20)->get();
    	}


    	return response()->json(['status' => true,
					'message' => 'List',
					'data' => ListingItemResource::collection($list),
				]);

    }



    function getPostComments(Request $request){
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
        
        $list = PostComments::orderBy('created_at', 'DESC')->where('post_id', $request->post_id)->skip($offset)->take(20)->get();
        

        


        return response()->json(['status' => true,
                    'message' => 'List Comments',
                    'data' => PostCommentResource::collection($list),
                ]);

    }

    function flagListing(Request $request){
        $user = Auth::user();
        if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthenticated user',
                    'data' => null,
                ]);
        }

        $listing = FlaggedListing::where('listing_id', $request->listing_id)->where('from_user', $user->id)->first();
            if($listing){
                return response()->json(['status' => false,
                    'message'=> 'Already flagged',
                    'data' => null, 
                ]);
            }

        $flagged = new FlaggedListing;
        if($request->has('reason')){
            $flagged->reason = $request->reason;
        }

        if($request->has('comment')){
            $flagged->comment = $request->comment;
        }
        $flagged->from_user = $user->id;
        $flagged->listing_id = $request->listing_id;
        $saved = $flagged->save();
        if($saved){
            $f = new FlaggedListingResource($flagged);
            return response()->json(['status' => true,
                    'message'=> 'Listing flagged',
                    'data' => $f,
                ]);
        }
        else{
            return response()->json(['status' => false,
                    'message'=> 'Error flagging user',
                    'data' => null,
                ]);
        }

    }


    function getFlaggedListings(Request $request){
        $user = Auth::user();
        if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthorized access',
                    'data' => null, 
                ]);
        }
        $userid = $user->id;
        
        if($request->has('off_set')){
            $off_set = $request->off_set;
        }
        
        $list = FlaggedListing::skip($off_set)->take(20)->get();
        


        return response()->json(['status' => true,
                    'message'=> 'Flagged Listings',
                    'data' => FlaggedListingResource::collection($list), 
                ]);
    }



}
