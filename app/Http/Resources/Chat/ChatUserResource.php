<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Models\Auth\Profile;
 // for company

class ChatUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = User::where('id', $this->user_id)->first();
        $comProfile = NULL;
        if($user->parent_user_id != null){
            $company = User::where('id', $user->parent_user_id)->first();
            if($company){
                $comProfile = Profile::where('user_id', $company->id)->first();
                if(!empty($job_title)){
                    $job_title = $job_title . " at " . $comProfile->name;
                }
            }
        }

        // $debug = env('APP_DEBUG');
        // $base = \Config::get('constants.profile_images');
        // if($debug === true){
        //     $base = \Config::get('constants.profile_images_clone');
        // }

        return [
            "id" => $this->user_id,
            "name" => $this->name,
            "email" => $user->email,
            "role" => $this->role,
            "profile_image" => \Config::get('constants.profile_images').$this->image_url,
            "user_id" =>$user->id,
            "unread_messages" => $this->unread_count,

        ];
    }
}
