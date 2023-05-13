<?php

namespace App\Http\Resources\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\User;
use App\Models\Auth\Profile;
use App\Models\Media\ListingItem;
use App\Http\Resources\Profile\UserProfileLiteResource;
use App\Http\Resources\Media\ListingItemResource;

class FlaggedListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $user = Profile::where('user_id', $this->from_user)->first();
        $listing = ListingItem::where('id', $this->listing_id)->first();


        return[
            'id' => $this->id,
            "from_user" => new UserProfileLiteResource($user),
            "listing" => new ListingItemResource($listing),
            "comment" => $this->comment,
            "reason" => $this->reason,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
