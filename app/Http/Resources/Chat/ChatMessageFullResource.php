<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
// use App\Models\Role;
// use App\Models\AccountStatus;
use App\Models\Auth\Profile;
use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Chat\ChatUserResource;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Chat\ChatResource;
use App\Models\Chat\ChatThread;

class ChatMessageFullResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $profile = Profile::where('user_id', $this->user_id)->first();
        $url = null;
        $debug = env('APP_DEBUG');
        $vidUrl = null;

        $base = \Config::get('constants.chat_images');
        if($debug === true){
            $base = \Config::get('constants.chat_images_clone');
        }
        if($this->image_url){
            $url = $base . $this->image_url;
        }
        if($this->video_url){
            $vidUrl = $base . $this->video_url;
        }
        $chat = ChatThread::where('id', $this->chat_id)
->first();
        return [
            "id" => $this->id,
            "message" => $this->message,
            "image_url" => $url,
            'chat_id' => $this->chat_id,
            'chat' => new ChatResource($chat),
            "image_width" => $this->image_width,
            'image_height' => $this->image_height,
            "user" => new ChatUserResource($profile),
            "created_at" => $this->created_at,
            "video_url" => $vidUrl,
            // 'ids' => $profiles,
        ];
    }
}
