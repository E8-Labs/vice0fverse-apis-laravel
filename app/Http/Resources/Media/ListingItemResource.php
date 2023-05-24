<?php

namespace App\Http\Resources\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\User;
use App\Models\Auth\Profile;

use App\Models\Listing\PostComments;
use App\Models\Listing\PostIntration;
use App\Models\Listing\PostIntrationTypes;

use App\Models\User\Follower;

use App\Http\Resources\Profile\UserProfileLiteResource;
use Illuminate\Support\Facades\Auth;

class ListingItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {

        $user = User::where('id', $this->user_id)->first();
        $profile = $user->getProfileLite();
        

        $likes = PostIntration::where('post_id', $this->id)
                 ->where('type', PostIntrationTypes::TypeLike)
                 // ->distinct('user_id')
                 ->count('id');

        // $postViews = PostIntration::where('post_id', $this->id)
        //          ->where('type', PostIntrationTypes::TypePostOpened)
        //          ->distinct('user_id')
        //          ->count('user_id');

        $comments = PostComments::where('post_id', $this->id) // old logic
                    // ->distinct('user_id')
                    ->count('id');

        $is_following = false;
        $follower = Follower::where('follower', Auth::user()->id)->where('followed', $this->user_id)->first();
        if($follower){
            $is_following = true;
        }


        $myLike = PostIntration::where('post_id', $this->id)->where('type', PostIntrationTypes::TypeLike)
        ->where('user_id', Auth::user()->id)->first();

        $is_liked = false;
        if($myLike){
            $is_liked = true;
        }
        return [
            'id' => $this->id,
            'song_name' => $this->song_name,
            "caption" => $this->caption,
            "artist" => $this->artist,
            "genre" => $this->genre,
            'image_path' => \Config::get('constants.profile_images').$this->image_path,
            'lyrics' => $this->lyrics,
            "created_at" => $this->created_at,
            'song_file' => $this->song_file,
            "likes" => $likes,
            "is_liked" => $is_liked,
            "comments" => $comments,
            "am_i_following" => $is_following,
            "user" => new UserProfileLiteResource($profile),
        ];
    }
}
