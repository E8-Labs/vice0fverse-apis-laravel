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
use App\Models\Listing\PostFlaggedComment;

use Illuminate\Support\Facades\Mail;

use App\Http\Resources\Profile\UserProfileLiteResource;
use App\Http\Resources\Media\PostCommentResource;

use App\Http\Resources\Media\FlaggedCommentResource;
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


    function likeComment(Request $request){
    	$validator = Validator::make($request->all(), [
			'comment_id' => 'required',
			]);

		if($validator->fails()){
			return response()->json(['status' => false,
				'message'=> 'validation error',
				'data' => null, 
				'validation_errors'=> $validator->errors()]);
		}

		$user = Auth::user();

		$liked = PostIntration::where('type', PostIntrationTypes::TypeLike)
				->where('user_id', $user->id)
				->where('comment_id', $request->comment_id)
				->first();

		$options = [
        		  'cluster' => env('PUSHER_APP_CLUSTER'),
        		  'useTLS' => false
        		];
        $pusher = new Pusher\Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), $options);

		if($liked){
			PostIntration::where('type', PostIntrationTypes::TypeLike)
				->where('user_id', $user->id)
				->where('comment_id', $request->comment_id)->delete();

				$likes = PostIntration::where('comment_id', $request->comment_id)
						->where('type', PostIntrationTypes::TypeLike)
						->count('id');
				
				$parentPost = PostComments::where('id', $request->comment_id)->first();
				// return $parentPost;
				if($parentPost["post_id"] != null){
                    // $parentPost = "Hi";
				}
				else{
					$parentComment = PostComments::where('id', $request->comment_id)->first();
					$parentPost = PostComments::where('id', $parentComment->reply_to)->first();
				}
                $parentid = $parentPost[0];
        		$pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::CommentLikeEventName . $parentPost['post_id'], ["comment_id" => (int)$request->comment_id, "likes" => $likes, "commentParent" => $parentPost]);

				return response()->json(['status' => true,
					'message'=> 'Comment unliked',
					'data' => null, 
				]);

		}
		else{
			$like = new PostIntration;
			$like->user_id = $user->id;
			$like->comment_id = $request->comment_id;
			$like->type = PostIntrationTypes::TypeLike;
			$saved = $like->save();
			if($saved){
				$likes = PostIntration::where('comment_id', $request->comment_id)
						->where('type', PostIntrationTypes::TypeLike)
						->count('id');
				

				$parentPost = PostComments::where('id', $request->comment_id)->first();
				// return $parentPost;
				if($parentPost["post_id"] != null){
                    // $parentPost = "Hi";
				}
				else{
					$parentComment = PostComments::where('id', $request->comment_id)->first();
					$parentPost = PostComments::where('id', $parentComment->reply_to)->first();
				}
        		$pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::CommentLikeEventName . $parentPost["post_id"], ["comment_id" => (int)$request->comment_id, "likes" => $likes, 'parent' => $parentPost]);

				return response()->json(['status' => true,
					'message'=> 'Comment liked',
					'data' => $like, 
					"parent" => $parentPost,
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


    function commentOnComment(Request $request){
    	$validator = Validator::make($request->all(), [
			'comment_id' => 'required',
			'comment' => 'required',
			]);

		if($validator->fails()){
			return response()->json(['status' => false,
				'message'=> 'validation error',
				'data' => null, 
				'validation_errors'=> $validator->errors()]);
		}



		$user = Auth::user();

		$mention_to = null;
		$comment_id = $request->comment_id;
		//Check if this comment is the top level comment in node
		$comment = PostComments::where('id', $request->comment_id)->first();
// 		return $comment;
		if($comment->reply_to == null){
			//this is a top level comment
		}
		else{
			$mention_to = $request->comment_id;
			$comment_id = $comment->reply_to;
		}

		$comment = new PostComments;
		$comment->reply_to = $comment_id;
		$comment->mention_to = $mention_to;
		$comment->comment = $request->comment;
		$comment->user_id = $user->id;
		// if($request->has('reply_to')){ // if replying to comment
		// 	$reply_to = $request->reply_to;
		// 	$comment->reply_to = $reply_to;
		// }
		$saved = $comment->save();

		$parentPost = PostComments::where('id', $request->comment_id)->first();
// 		return $parentPost;
				if($parentPost->post_id != null){
				// 	return "Parent post exists ". $parentPost;
				}
				else{
					$parentComment = PostComments::where('id', $request->comment_id)->first();
					$parentPost = PostComments::where('id', $parentComment->reply_to)->first();
				}

				$post = ListingItem::where('id', $parentPost->post_id)->first();
// 				return response()->json(['status' => false,
// 				'message'=> 'Comment not posted',
// 				'data' => $post, 
// 				'parent' => $parentPost,
// 			]);
// return $post;
		if($saved){

			$options = [
        		  'cluster' => env('PUSHER_APP_CLUSTER'),
        		  'useTLS' => false
        		];
        		
        		$admin = User::where('id', $post->user_id)->first();
			Notification::add(NotificationType::NewComment, $user->id, $admin->id, $comment);
        	$pusher = new Pusher\Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), $options);
        	
        	if($mention_to){
        	    $comments = PostComments::where('mention_to', $mention_to)->count('id');
        	    
			    $pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::MentionToCommentCount . $parentPost->post_id, ["comment_id" => (int)$mention_to, "comments" => $comments, "reply_to" => $comment_id]);
			 //   $pusher->trigger(PostInterationController::InteractionChannelName, PostInterationController::NewMentionToComment, new CommentResource($comment));
        	}
        	else{
        		
        	}
        	
        	$comments = PostComments::where('reply_to', $comment_id)->count('id');
			$pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::ReplyToCommentCount . $parentPost->post_id, ["comment_id" => (int)$comment_id, "comments" => $comments]);

			$pusher->trigger(PostInteractionController::InteractionChannelName, PostInteractionController::NewReplyToComment . $parentPost->post_id, new PostCommentResource($comment));

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

    function flagPostComment(Request $request){
        $validator = Validator::make($request->all(), [
                'comment_id' => 'required',
                ]);
        if($validator->fails()){
                return response()->json(['status' => false,
                    'message'=> 'validation error',
                    'data' => null, 
                    'validation_errors'=> $validator->errors()]);
            }

            $user = Auth::user();
            if($user){
                $alreadyFlagged = PostFlaggedComment::where('flagged_by', $user->id)->where('comment_id', $request->comment_id)->first();
                if($alreadyFlagged){
                    return response()->json(['status' => false,
                        'message'=> 'Comment already flagged',
                        'data' => null, 
                    ]);
                }



                $com = PostComments::where('id', $request->comment_id)->first();
                $flagged = new PostFlaggedComment;
                $flagged->post_id = $com->post_id;
                $flagged->comment_id = $request->comment_id;
                $flagged->flagged_by = $user->id;
                if($request->has('comment')){
                    $flagged->comment = $request->comment;
                }
                $saved = $flagged->save();

                // $u = User::where('role', Role::RoleAdmin)->first();
                // $adminProfile = Profile::where('user_id', $u->id)->first();
                // // return $u;
                // $data = array("email" => $u->email, "admin" => $adminProfile->name);
                // Mail::send('Mail/CommentFlaggedMail', $data, function ($message) use ($data) {
                //         $message->to('Dev@usintechnology.com','Admin')->subject('Comment Flagged');
                //         $message->from('Dev@usintechnology.com');
                // });

                return response()->json(['status' => true,
                    'message'=> 'Comment flagged',
                    'data' => new FlaggedCommentResource($flagged),
                ]);
            }
            else{
                return response()->json(['status' => false,
                    'message'=> 'Unauthenticated user',
                    'data' => null, 
                ]);
            }
    }


    function getRepliesToComments(Request $request){
    	$validator = Validator::make($request->all(), [
			'comment_id' => 'required',
			]);

		if($validator->fails()){
			return response()->json(['status' => false,
				'message'=> 'validation error',
				'data' => null, 
				'validation_errors'=> $validator->errors()]);
		}
    	$user = Auth::user();
    	$off_set = 0;
    	if($request->has('off_set')){
    		$off_set = $request->off_set;
    	}

    	$comments = PostComments::where('reply_to', $request->comment_id)
    // 	->whereNull('mention_to')
    	->orderBy('created_at', 'DESC')
    	->skip($off_set)
    	->take(PostInteractionController::ItemsToFetch * 10)->get();


    	$size = sizeof($comments);
        $array = array();
        for($i=$size-1; $i>=0; $i--){
        	$p = $comments[$i];
        	if($user){
        		$isliked = PostIntration::where('comment_id', $p->id)
                 ->where('type', PostIntrationTypes::TypeLike)
                 ->where('user_id', $user->id)
                 ->first();
                 if($isliked){
					$p->isLiked = true;
                 }
                 else{
					$p->isLiked = false;
                 }
        	}
        	else{
        		$p->isLiked = false;
        	}
        	
            $array[] = $p;//$comments[$i];
        }

    	return response()->json(['status' => true,
					'message'=> 'Comments obtained',
					'data' => PostCommentResource::collection($array),
				]);
    }

}
