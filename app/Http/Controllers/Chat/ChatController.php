<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat\ChatThread;
use App\Models\Chat\ChatType;
use App\Models\Chat\ChatUser;
use App\Models\Chat\ChatMessage;

use App\Models\User;
use App\Models\Auth\Profile;
use App\Models\Job\FlaggedUser;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Auth;
// use JWTAuth;
use App\Models\User\Follower;
use App\Models\Notification;
use App\Models\NotificationType;
// use App\Http\Resources\Company\CompanyProfileExtraLiteResource;
use App\Http\Resources\Profile\UserProfileLiteResource;
// use App\Http\Resources\User\FlaggedUserResource;
use App\Http\Resources\Chat\ChatResource;
use App\Http\Resources\Chat\ChatMessageResource;
use Carbon\Carbon;
use Pusher;


class ChatController extends Controller
{
    //
    const Rows_To_Fetch = 50;


    const ChatChannel = "Chat";

    const NewMessageEvent = "NewMessage";
    const NewChatEvent = "NewChat";
    const ChatDeletedEvent = "ChatDeleted";
    const OtherUserStartedTyping = "StartedTyping";
    const OtherUserStoppedTyping = "StoppedTyping";

    private function getPusher(){
        $options = [
                  'cluster' => env('PUSHER_APP_CLUSTER'),
                  'useTLS' => false
                ];
        $pusher = new Pusher\Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), $options);
        return $pusher;
    }
    
    private function getChatWithId($id){
        $chat = ChatThread::where('id', $id)->first();
        return $chat;
    }

    public function createChat(Request $request){
        $validator = Validator::make($request->all(), [
                'user_id' => 'required'
                ]);

            if($validator->fails()){
                return response()->json(['status' => false,
                    'message'=> 'validation error',
                    'data' => null, 
                    'validation_errors'=> $validator->errors()]);
            }


            try{
                $otherUser = $request->user_id;
            $user = Auth::user();
            $users = [$user->id, $otherUser];

            
            
            $follower = Follower::where('follower', Auth::user()->id)->where('followed', $otherUser)->first();
            
            $followed = Follower::where('followed', Auth::user()->id)->where('follower', $otherUser)->first();
            if($follower && $followed){
                
            }
            else{
                return response()->json(['status' => false,
                    'message'=> 'Both users should follow each other in order to chat',
                    'data' => null,
                ]);
            }





            if (count($users) == 2){
                $idString = $users[0] . '-' . $users[1];
                $dbChat = $this->getChatWithId($idString);
                if($dbChat){
                    return response()->json(['status' => false,
                        'message'=> 'Chat already exists',
                        'data' => new ChatResource($dbChat),
                    ]);
                }
                else{
                    
                }
                // return "Chat id " . (int)$idString;
                DB::beginTransaction();
                $chat = new ChatThread;
                $chat->id = $idString;
                $chat->chat_type = ChatType::ChatTypeSimple;
                $saved = $chat->save();
                if($saved){

                    $chatUsers = array();
                    foreach($users as $userid){
                        $chatUser = new ChatUser;
                        $chatUser->user_id = $userid;
                        $chatUser->chat_id = $chat->id;
                        $userSaved = $chatUser->save();
                        if($userSaved){
                            $chatUsers[] = $chatUser;
                        }
                        else{
                            DB::rollBack();
                            return response()->json(['status' => false,
                                'message'=> 'Error creating chat',
                                'data' => null
                            ]);
                        }
                    }
                    $chat->users = $chatUsers;
                    $dbChat = ChatThread::where('id', $chat->id)->first();
                    DB::commit();

                    $pusher = $this->getPusher();
                    $pusher->trigger(ChatController::ChatChannel, ChatController::NewChatEvent . $otherUser, ["chat"=> new ChatResource($dbChat)]);
                    $pusher->trigger(ChatController::ChatChannel, ChatController::NewChatEvent . Auth::user()->id, ["chat"=> new ChatResource($dbChat)]);
                    return response()->json(['status' => false,
                        'message'=> 'Chat Created',
                        'data' => new ChatResource($dbChat),
                    ]);

                }
                else{
                    return response()->json(['status' => false,
                        'message'=> 'Error creating chat',
                        'data' => null
                    ]);
                }

            }
            else{
                return response()->json(['status' => false,
                    'message'=> 'There must be two users to start a chat',
                    'data' => null
                ]);
            }
            }
            catch(\Exception $e){
                \Log::info('------------------------_Error In Chat -----------------');
                \Log::info($user);
                 \Log::info('-----------------------------------------');
                \Log::info($e);
                return response()->json(['status' => false,
                    'message'=> $e->getMessage() . ' Exception',
                    'error' => $e,
                    'data' => null
                ]);
            }
    }

    function deleteChat(Request $request){
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false,
                'message' => 'validation error',
                'data' => null,
                'validation_errors' => $validator->errors()]);
        }
        $user = Auth::user();
        $chatid = $request->chat_id;
        $deleted = ChatThread::where('id', $request->chat_id)->delete();
        if ($deleted){
            $pusher = $this->getPusher();
                // $newMessage = ChatMessage::where('id', $message->id)->first();
            $pusher->trigger(ChatController::ChatChannel, ChatController::ChatDeletedEvent . $chatid, ["chat_id"=> $chatid]);
            return response()->json(['status' => true,
                'message' => 'Chat deleted',
                'data' => null,
            ]);
        }
        else{
            return response()->json(['status' => false,
                'message' => 'Chat not deleted',
                'data' => null,
            ]);
        }


    }


    function uploadChatImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required',
            'chat_image' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false,
                'message' => 'validation error',
                'data' => null,
                'validation_errors' => $validator->errors()]);
        }
        $user = Auth::user();
        $chatid = $request->chat_id;
        $userid = $user->id;
        $updated_at = Carbon::today()->toDateTimeString();

        if ($request->hasFile('chat_image')) {
            $data = $request->file('chat_image')->store('Chat/Images');

            $message = new ChatMessage;
            $message->chat_id = $chatid;
            $message->user_id = $userid;
            $message->message = '';
            $message->image_url = $data;
            $saved = $message->save();
            if($saved){
                ChatThread::where('id', $chatid)->update(['lastmessage' => 'Image']);
                ChatUser::where('chat_id', $chatid)->where('user_id', $userid)->update(["unread_count" => 0]);//set own count 0
                ChatUser::where('chat_id', $chatid)->where('user_id', '!=', $userid)->increment("unread_count");// increment other's count

                $pusher = $this->getPusher();
                $newMessage = ChatMessage::where('id', $message->id)->first();
                $pusher->trigger(ChatController::ChatChannel, ChatController::NewMessageEvent . $chatid, new ChatMessageResource($newMessage));


                return response()->json([
                    'status' => true,
                    'message' => "Message sent",
                    'data' => new ChatMessageResource($newMessage),
                 ]);
            }
            else{
                return response()->json([
                    'status' => false,
                    'message' => "Chat not updated",
                    'data' => null,
                 ]);
            }

            

        } else {
            return response()->json([
                'status' => false,
                'message' => "Message sent",
                'data' => $data,
            ]);
        }
    }



    public function sendMessage(Request $request){
        // $validator = Validator::make($request->all(), [
        //     'chat_id' => 'required',
        //     'message' => 'required',
        // ]);
        // if ($validator->fails()) {
        //     return response()->json(['status' => false,
        //         'message' => 'validation error',
        //         'data' => null,
        //         'validation_errors' => $validator->errors()]);
        // }
        $user = Auth::user();
        $chatid = $request->chat_id;
        $userid = $user->id;
        $updated_at = Carbon::today();

        $message = new ChatMessage;
            $message->chat_id = $chatid;
            $message->user_id = $userid;
            
            $lastmessage = "";

            if($request->has('post_video')){
                // \Log::info('------------------Has video----------------------');
                $data=$request->file('post_video')->store('Chat/Images');
                $message->video_url = $data;   
            }
        if ($request->hasFile('chat_image')) {
            $data = $request->file('chat_image')->store('Chat/Images');
            $message->message = '';
            $message->image_url = $data;
            $lastmessage = "Media";
        }


        else if ($request->has('message')) {
            $data = $request->message;
            $lastmessage = $data;
            $data = str_replace(Controller::PercentageEncode, "%", $data);
            $message->message = $data;
            if($data == ""){
                ChatUser::where('chat_id', $chatid)->where('user_id', $userid)->update(["unread_count" => 0]);//set own count 0
            return response()->json([
                'status' => true,
                'message' => "Chat count reset",
                'data' => null,
            ]);
            }

        } else {
            ChatUser::where('chat_id', $chatid)->where('user_id', $userid)->update(["unread_count" => 0]);//set own count 0
            return response()->json([
                'status' => false,
                'message' => "Message not sent",
                'data' => null,
            ]);
        }
        $saved = $message->save();
            if($saved){
                $newMessage = ChatMessage::where('id', $message->id)->first();
                $otherUser = ChatUser::where('chat_id', $chatid)->where('user_id', '!=', $userid)->first();;
                // $admin = User::where('id', $post->user_id)->first();
                Notification::add(NotificationType::NewMessage, $user->id, $otherUser->user_id, $newMessage);
                ChatThread::where('id', $chatid)->update(['lastmessage' => $lastmessage]);
                ChatUser::where('chat_id', $chatid)->where('user_id', $userid)->update(["unread_count" => 0]);//set own count 0
                ChatUser::where('chat_id', $chatid)->where('user_id', '!=', $userid)->increment("unread_count");// increment other's count
                
                $pusher = $this->getPusher();
                $pusher->trigger(ChatController::ChatChannel, ChatController::NewMessageEvent . $chatid, new ChatMessageResource($newMessage));

                $otherUserId = ChatUser::where('chat_id', $chatid)->where('user_id', '!=', $userid)->get("user_id");// increment
                $pusher->trigger(ChatController::ChatChannel, ChatController::NewMessageEvent . $otherUserId, new ChatMessageResource($newMessage));
                return response()->json([
                    'status' => true,
                    'message' => "Message sent",
                    'data' => new ChatMessageResource($newMessage),
                 ]);
            }
            else{
                return response()->json([
                    'status' => false,
                    'message' => "Message not sent",
                    'data' => null,
                 ]);
            }
    }



    function getChatList(Request $request){
        $off_set = 0;

        if($request->has('off_set')){
            $off_set = $request->off_set;
        }

        $user = Auth::user();
        $userid = $user->id;

        $chats = DB::table('chat_threads')
            ->join('chat_users', 'chat_users.chat_id', '=', 'chat_threads.id')
            ->select("chat_threads.*")->distinct()
            ->where('chat_users.user_id', $userid)
            
            ->skip($off_set)
            ->take(ChatController::Rows_To_Fetch)
            ->orderBy('chat_threads.updated_at', 'desc')
            ->get();
            if($request->has("search")){
                $search = $request->search;
                if($search === ""){

                }
                else{
                    $chatids = ChatUser::where('user_id', $userid)->pluck('chat_id')->toArray();
                    $otherUsers = ChatUser::whereIn('chat_id', $chatids)->where('user_id', '!=', $user->id)->pluck('user_id')->toArray();// all the users he has chatted with
                    $profiles = Profile::where('name', 'LIKE', "%$search%")->orWhere('username', 'LIKE', "%$search%")->pluck('user_id')->toArray();
                    $chats = DB::table('chat_threads')
                    ->join('chat_users', 'chat_users.chat_id', '=', 'chat_threads.id')
                    
                    ->select("chat_threads.*")->distinct()
                    ->whereIn('chat_users.user_id', $otherUsers)
                    ->whereIn("chat_users.user_id", $profiles)
                    // ->where("profiles.name", "LIKE", "%$search%")
                    
                    ->skip($off_set)
                    ->take(ChatController::Rows_To_Fetch)
                    ->orderBy('chat_threads.updated_at', 'desc')
                    ->get();
                }
            }

        return response()->json([
            "data" => ChatResource::collection($chats),
            'message' => 'Chats list',
            'status' => true,

        ]);
    }

    function getMessagesForChat(Request $request){
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false,
                'message' => 'validation error',
                'data' => null,
                'validation_errors' => $validator->errors()]);
        }

        $user = Auth::user();
        $chat_id = $request->chat_id;

        $last_message_id = 0;
        if($request->has('last_message_id')){
            if($request->has('load_new')){
                $last_message_id = $request->last_message_id;
                $lastMessages = ChatMessage::where('chat_id', $chat_id)
                ->where('id', '>', $last_message_id)
                ->orderBy('created_at', 'DESC')
                ->take(500)->get();
            }
            else{
                $last_message_id = $request->last_message_id;
                $lastMessages = ChatMessage::where('chat_id', $chat_id)
                ->where('id', '<', $last_message_id)
                ->orderBy('created_at', 'DESC')
                ->take(50)->get();
            }
        }
        else{
            $lastMessages = ChatMessage::where('chat_id', $chat_id)
            ->orderBy('created_at', 'DESC')
            ->take(50)->get();
        }

        $messages = array();
        $size = sizeof($lastMessages);
        for($i=$size-1; $i>=0; $i--){
            $m = $lastMessages[$i];
            $messages[] = $m;
        }

        

        return response()->json([
            "data" => ChatMessageResource::collection($messages),
            'message' => 'Message list',
            'status' => true,

        ]);

    }


    
}



















