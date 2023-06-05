<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Models\User\Follower;
use Illuminate\Support\Facades\Auth;

class UserProfileFullResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $user = User::where('id', $this->user_id)->first();
        // $url = $this->image_url;
        $p = $user->provider_name;
        if($p === NULL){
            $p = "email";
        }

        $is_following = false;
        $follower = Follower::where('follower', Auth::user()->id)->where('followed', $this->user_id)->first();
        if($follower){
            $is_following = true;
        }
        
        return [
            "id" => $this->user_id,
            "email" => $user->email,
            "name" => $this->name,
            "username" => $this->username,
            "role" => $this->role,
            "profile_image" => \Config::get('constants.profile_images').$this->image_url,
            "authProvider" => $p,
            'city' => $this->city,
            "state" => $this->state,
            'lat' => $this->lat,
            'lang' => $this->lang,
             "user_id" => $this->user_id,
             'nationality' => $this->nationality,
             "posts" => $user->getPostsCount(),
             "followers" => $user->getFollowersCount(),
             "following" => $user->getFollowingCount(),
             "am_i_following" => $is_following,
             "top_artists" => $user->getUserTopArtists(),
             "top_genres" => $user->getUserTopGenres(),
             "user_questions" => $user->getUserQuestions(),
             "created_at" => $this->created_at,
        ];
    }
}
