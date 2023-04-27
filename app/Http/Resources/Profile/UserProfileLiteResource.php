<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Models\User\Follower;

class UserProfileLiteResource extends JsonResource
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
            "name" => $this->username,
            "username" => $this->username,
            "profile_image" => \Config::get('constants.profile_images').$this->image_url,
             "user_id" => $user->id,
            
            "posts" => $user->getPostsCount(),
             "followers" => $user->getFollowersCount(),
             "following" => $user->getFollowingCount(),
            "am_i_following" => $is_following,
            // "unread_notifications" => $count,
            // "unread_messages" => $unread_messages,

        ];
    }
}
