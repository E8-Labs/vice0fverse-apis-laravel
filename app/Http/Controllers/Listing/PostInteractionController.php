<?php

namespace App\Http\Controllers\Listing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Listing\PostComments;
use App\Models\Listing\PostIntration;
use App\Models\Listing\PostIntrationTypes;

use App\Models\User;
use App\Models\Auth\Profile;
use App\Models\Auth\VerificationCode;

use App\Models\Notification;
use App\Models\NotificationType;

use App\Models\Media\ListingItem;

use Illuminate\Support\Facades\Mail;

use App\Http\Resources\Profile\UserProfileLiteResource;
use App\Http\Resources\Media\PostCommentResource;
use Pusher;

class PostInteractionController extends Controller
{

	const ItemsToFetch = 10;
    const InteractionChannelName = "Community";
    const LikeEventName = "Like";
    const ViewPostEventName = "ViewPost";

    const NewPost = "NewPost";
    const NewChannel = "NewChannel";

    const CommentCountEventName = "CommentCount";
    const CommentAddedEventName = "NewComment";

	const CommentLikeEventName = "CommentLike";
	
    const ReplyToCommentCount = "ReplyToCommentCount";
    const NewReplyToComment = "NewReplyToComment";
    
    const MentionToCommentCount = "MentionToCommentCount";
    const MentionToCommentLike = "MentionToCommentLike";
    const NewMentionToComment = "NewMentionToComment";

    function likePost(Request $request){
    	$validator = Validator::make($request->all(), [
			'post_id' => 'required',
			]);

		if($validator->fails()){
			return response()->json(['status' => false,
				'message'=> 'validation error',
				'data' => null, 
				'validation_errors'=> $validator->errors()]);
		}
		$post = ListingItem::where('id', $request->post_id)->first();

		$user = Auth::user();

		$liked = PostIntration::where('type', PostIntrationTypes::TypeLike)
				->where('user_id', $user->id)
				->where('post_id', $request->post_id)
				->first();

		$options = [
        		  'cluster' => env('PUSHER_APP_CLUSTER'),
        		  'useTLS' => false
        		];
        $pusher = new Pusher\Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), $options);

        // $post = ListingItem::where('id', $request->post_id)->first();
        if(!$post){
        	return response()->json(['status' => false,
					'message'=> 'Post does not exist',
					'data' => null, 
				]);
        }

		if($liked){
			PostIntration::where('type', PostIntrationTypes::TypeLike)
				->where('user_id', $user->id)
				->where('post_id', $request->post_id)->delete();

				$likes = PostIntration::where('post_id', $request->post_id)
						->where('type', PostIntrationTypes::TypeLike)
						->count('id');

				
			// $admin = User::where('id', $post->user_id)->first();
			
			// Notification::add(NotificationType::PostUnLike, $user->id, $admin->id, $post);
        		$pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::LikeEventName, ["post_id" => (int)$request->post_id, "likes" => $likes]);

				return response()->json(['status' => true,
					'message'=> 'Post unliked',
					'data' => null, 
				]);

		}
		else{
			$like = new PostIntration;
			$like->user_id = $user->id;
			$like->post_id = $request->post_id;
			$like->type = PostIntrationTypes::TypeLike;
			$saved = $like->save();
			if($saved){
				$type = get_class($post);
				$not = Notification::where('from_user', $user->id)->where('to_user', $post->user_id)->where('notification_type', NotificationType::PostLike)
				->where('notifiable_id', $post->id)->first();
				if(!$not){
					Notification::add(NotificationType::PostLike, $user->id, $post->user_id, $post);
				}
				
				$likes = PostIntration::where('post_id', $request->post_id)
						->where('type', PostIntrationTypes::TypeLike)
						->count('id');
				
			// $admin = User::where('id', $post->user_id)->first();
			$p = Profile::where('user_id', $user->id)->first();
			// Notification::add(NotificationType::PostLike, $user->id, $admin->id, $post);
         		$pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::LikeEventName, ["post_id" => (int)$request->post_id, "likes" => $likes, "profile" => new UserProfileLiteResource($p)]);

				return response()->json(['status' => true,
					'message'=> 'Post liked',
					'data' => $like, 
				]);
			}
			else{
				return response()->json(['status' => false,
					'message'=> 'Post not liked',
					'data' => null, 
				]);
			}
		}
    }


    function commentOnPost(Request $request){
    	$validator = Validator::make($request->all(), [
			'post_id' => 'required',
			'comment' => 'required',
			]);

		if($validator->fails()){
			return response()->json(['status' => false,
				'message'=> 'validation error',
				'data' => null, 
				'validation_errors'=> $validator->errors()]);
		}

		$user = Auth::user();

		$post = ListingItem::where('id', $request->post_id)->first();

		$comment = new PostComments;
		$comment->post_id = $request->post_id;
		$comment->comment = $request->comment;
		$comment->user_id = $user->id;
		if($request->has('reply_to')){ // if replying to comment
			$reply_to = $request->reply_to;
			$comment->reply_to = $reply_to;
		}
		$saved = $comment->save();
		if($saved){
			$comments = PostComments::where('post_id', $request->post_id)->count('id');

			$options = [
        		  'cluster' => env('PUSHER_APP_CLUSTER'),
        		  'useTLS' => false
        		];


        	$type = get_class($post);
			$not = Notification::where('from_user', $user->id)->where('to_user', $post->user_id)->where('notification_type', NotificationType::NewComment)
			->where('notifiable_id', $post->id)->first();
			// if(!$not){ // maybe count the number of comments then stop sending the notifications?
			Notification::add(NotificationType::NewComment, $user->id, $post->user_id, $post);
			// }

        	$pusher = new Pusher\Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), $options);

        	$p = Profile::where('user_id', $user->id)->first();

			$pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::CommentCountEventName.$request->post_id, ["post_id" => (int)$request->post_id, "comments" => $comments]);
			$pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::CommentCountEventName, ["post_id" => (int)$request->post_id, "comments" => $comments, "profile" => new UserProfileLiteResource($p)]);

			$pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::CommentAddedEventName.$request->post_id, new PostCommentResource($comment));

			return response()->json(['status' => true,
				'message'=> 'Comment posted',
				'data' => new PostCommentResource($comment), 
			]);
		}
		else{
			return response()->json(['status' => false,
				'message'=> 'comment not posted',
				'data' => null, 
			]);
		}

    }

}
