<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\User;
use App\Models\Auth\Profile;
use App\Models\Media\ListingItem;
use App\Http\Resources\Profile\UserProfileLiteResource;

class FlaggedProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $user = Profile::where('user_id', $this->from_user)->first();
        $flaggedUser = Profile::where('user_id', $this->flagged_user)->first();


        return[
            'id' => $this->id,
            "from_user" => new UserProfileLiteResource($user),
            "flagged_user" => new UserProfileLiteResource($flaggedUser),
            "comment" => $this->comment,
            "reason" => $this->reason,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
