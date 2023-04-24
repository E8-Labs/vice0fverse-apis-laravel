<?php

namespace App\Http\Resources\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\User;
use App\Models\Auth\Profile;

use App\Http\Resources\Profile\UserProfileLiteResource;

class ListingItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
    	$user = User::where('id', $this->user_id)->first();
    	$profile = $user->getProfileLite();

        return [
        	'id' => $this->id,
        	'song_name' => $this->song_name,
        	'image_path' => \Config::get('constants.profile_images').$this->image_path,
        	'lyrics' => $this->lyrics,
        	"created_at" => $this->created_at,
        	'song_file' => $this->song_file,
        	"user" => new UserProfileLiteResource($profile),
        ];
    }
}
