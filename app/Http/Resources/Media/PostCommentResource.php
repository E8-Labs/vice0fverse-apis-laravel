<?php

namespace App\Http\Resources\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Models\Auth\Profile;
use App\Models\Listing\PostComments;
use App\Models\Listing\PostIntration;
use App\Models\Listing\PostIntrationTypes;
// use App\Models\Feed\PostFlaggedComment;
use Auth;

use App\Http\Resources\Profile\UserProfileLiteResource;

class PostCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = Profile::where('user_id', $this->user_id)->first();
        // $commentRepliedTo = PostComments::where('id', $this->reply_to)->first();
        $commentCount = PostComments::where('reply_to', $this->id)->orWhere('mention_to', $this->id)->count('id');
        $likes = PostIntration::where('comment_id', $this->id)->count('id');
        // $flaggedByMe = false;
        $authUser = Auth::user();
        // if($authUser){
        //     $flagged = PostFlaggedComment::where('comment_id', $this->id)->where('flagged_by', Auth::user()->id)->first();
        //     if($flagged){
        //         $flaggedByMe = true;
        //     }
        // }
        // else{
        //     $flaggedByMe = false;
        // }
        
        return [
            "id" => $this->id,
            "comment" => $this->comment,
            "reply_to" => (int)$this->reply_to,
            "mention_to" => $this->mention_to,
            "post_id" => (int)$this->post_id,
            "user_id" => $this->user_id,
            'user' => new UserProfileLiteResource($user),
            "created_at" => $this->created_at,
            'comments' => $commentCount,
            'likes' => $likes,
            'isLiked' => $this->isLiked,
            // 'flaggedByMe' => $flaggedByMe,
        ];
    }
}
