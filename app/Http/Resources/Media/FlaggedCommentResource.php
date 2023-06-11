<?php

namespace App\Http\Resources\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Listing\PostComments;
use App\Models\Listing\PostIntration;
use App\Models\Listing\PostIntrationTypes;
use App\Models\User;
use App\Models\Auth\Profile;

use App\Models\Media\ListingItem;
use App\Models\Listing\PostFlaggedComment;

use App\Http\Resources\Media\ListingItemResource;

use App\Http\Resources\Profile\UserProfileLiteResource;
use App\Http\Resources\Media\PostCommentResource;



class FlaggedCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $comment = PostComments::where('id', $this->comment_id)->first();
        $commentBy = Profile::where('user_id', $comment->user_id)->first();
        $post = ListingItem::where('id', $this->post_id)->first();
        $flagged_by = Profile::where('user_id', $this->flagged_by)->first();
        

        return [
            "id" => $this->id,
            "post" => new ListingItemResource($post),
            "flagged_by" => new UserProfileLiteResource($flagged_by),
            "reason" => $this->comment,
            'comment' => new PostCommentResource($comment),
            "comment_by" => new UserProfileLiteResource($commentBy),
            "created_at" => $this->created_at,
        ];
    }
}
