<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Models\Role;
use App\Models\AccountStatus;
use App\Models\Profile;
use App\Http\Resources\Company\CompanyProfileExtraLiteResource;
use App\Http\Resources\Chat\ChatUserResource;
use Illuminate\Support\Facades\DB;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $profiles = DB::table('profiles')
        ->join('chat_users', 'profiles.user_id', '=', 'chat_users.user_id')
        ->where('chat_users.chat_id', '=', $this->id)
        ->select("*")
        ->get();

        return [
            "id" => $this->id,
            "last_message" => $this->lastmessage,
            "chat_type" => $this->chat_type,

            "last_message_date" => $this->updated_at,
            "updated_at" => $this->updated_at,
            "users" => ChatUserResource::collection($profiles),
            // 'ids' => $profiles,
        ];
    }
}
