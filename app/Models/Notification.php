<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Profile;
use App\Models\JobPosts;
use Pusher;

class Notification extends Model
{
    use HasFactory;
    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];



    public static function add(int $notification_type, int $from_user, int $to_user, $notification_for = null)
    {

      
        $notifiable_type = null;
        $notifiable_id   = null;

        if ($notification_for) {
            $primary_key = $notification_for->getKeyName();
            $notifiable_type  = get_class($notification_for);
            $notifiable_id    = $notification_for->$primary_key;
        }

        $notification = self::create([
            'notification_type' => $notification_type,
            'from_user'         => $from_user,
            'to_user'           => $to_user,
            'notifiable_id'     => $notifiable_id,
            'notifiable_type'   => $notifiable_type,
        ]);

        $options = [
                  'cluster' => env('PUSHER_APP_CLUSTER'),
                  'useTLS' => false
                ];
        $pusher = new Pusher\Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), $options);
        $count = Notification::where('to_user', $to_user)->where('is_read', 0)->count('id');
        $pusher->trigger("Notification", "NotificationUnread" . $to_user, ["count" => $count]);
        self::sendFirebasePushNotification($notification);
    }

    public static function sendFirebasePushNotification(Notification $notification)
    {



        $sendToUser = User::find($notification->to_user);
        if (isset($sendToUser->fcm_key) && $sendToUser->fcm_key)
        {
            $SERVER_API_KEY = env('FCM_SERVER_API_KEY');
            // $message = $notification->getMessageAttribute();
            $data = [
                "registration_ids" => [$sendToUser->fcm_key],
                "notification" => [
                    "title" => $notification->title,
                    "body" => $notification->message,
                ]
            ];
            $dataString = json_encode($data);

            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

           
            return curl_exec($ch);
        }
        return null;
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function getTitleAttribute()
    {
        $title = "";

        switch ($this->notification_type) {
            case NotificationType::NewApplicant:
                $title = "New User";
                break;
            case NotificationType::NewCompany:
                $title = "New User";
                break;
            case NotificationType::NewRecruiter:
                $title = "New User";
                break;
            case NotificationType::NewUitMember:
                $title = "New User";
                break;
            case NotificationType::PendingApproval:
                $title = "Pending Approval";
                break;
            case NotificationType::NewComment:
                $title = "New Comment";
                break;
            case NotificationType::NewMessage:
                $title = "New Message";
                break;
            case NotificationType::FlaggedUser:
                if (auth()->user()->isAdmin()) {
                    $title = "User Flagged";
                } else {
                    $title = "Payment Pending";
                }
                break;
            case NotificationType::FlaggedJob:
                if (auth()->user()->isAdmin()) {
                    $title = "Job Flagged";
                } else {
                    $title = "Payment In Review";
                }
                break;
            case NotificationType::NewHire:
                $title = "New Hire";
                break;
            case NotificationType::NewJobApplication:
                // if (auth()->user()->isAdmin()) {
                    $title = "New Job Application";
                // } else {
                //     $title = "Delivery Complete";
                // }
                break;
            
        }

        return $title;
    }

    public function getMessageAttribute()
    {
        $message = "";
        $from = Profile::where('user_id', $this->from_user)->first();
        $to = Profile::where('user_id', $this->to_user)->first();
 
       

        switch ($this->notification_type) {
            case NotificationType::NewApplicant:
                $message = $from->name . " is joining as an applicant.";
                break;
            case NotificationType::NewCompany:
                $message = $from->name . " is waiting for your approval.";
                break;
            case NotificationType::NewRecruiter:
                $message = $from->name . " wants to join your team.";
                break;
            case NotificationType::NewUitMember:
                $message = $from->name . " is waiting for your approval.";
                break;
            case NotificationType::PendingApproval:
                $message = $from->name . " is pending approval";
                break;
            case NotificationType::NewComment:
                $message = $from->name . " added a comment on your post";
                break;
            case NotificationType::NewMessage:
                $message = $from->name . " sent you a message";
                break;
            case NotificationType::FlaggedUser:
                // get flagged user and set name
            $flagged = Profile::where('user_id', $this->notifiable_id)->first();
                    $message = $from->name . " flagged " . $flagged->name;
                
                break;
            case NotificationType::FlaggedJob:
            $flagged = JobPosts::where('id', $this->notifiable_id)->first();
                $message = $from->name . " flagged " . $flagged->job_title;
                break;
            case NotificationType::NewHire:
            $hired = Profile::where('user_id', $this->notifiable_id)->first();
            $jobs = JobPosts::where('id', $this->notifiable_id)->first();
                if (auth()->user()->isAdmin()) {
                    $message = $from->name . " was hired for  " . $jobs['job_title'].'  Base Salary '.$jobs['min_salary'];
                } else {
                    $message = $from->name . " hired you.";
                }
                break;
            case NotificationType::NewJobApplication:
                // if (auth()->user()->isAdmin()) {
                    $message = $from->name . " applied to a job you posted";
                // } else {
                //     $title = "Delivery Complete";
                // }
                break;
            
        }
        return $message;
    }

    // public function getIconAttribute()
    // {
    //     $icon = "";

    //     switch ($this->notification_type) {
    //         case NotificationType::NewBid:
    //             $icon = asset('notifications-icons/newBidIcon@3x.png');
    //             break;
    //         case NotificationType::BidAccepted:
    //             $icon = asset('notifications-icons/bidAcceptedIcon@3x.png');
    //             break;
    //         case NotificationType::ItemApproval:
    //             $icon = asset('notifications-icons/listingApprovedIcon@3x.png');
    //             break;
    //         case NotificationType::NewMessage:
    //             $icon = asset('notifications-icons/newMessageIcon@3x.png');
    //             break;
    //         case NotificationType::PaymentPending:
    //             $icon = asset('notifications-icons/paymentPendingIcon@3x.png');
    //             break;
    //         case NotificationType::PaymentReviewPending:
    //             $icon = asset('notifications-icons/paymentInReviewIcon@3x.png');
    //             break;
    //         case NotificationType::DeliveryPending:
    //             $icon = asset('notifications-icons/deliveryPendingIcon@3x.png');
    //             break;
    //         case NotificationType::PayoutPending:
    //             $icon = asset('notifications-icons/payoutPendingIcon@3x.png');
    //             break;
    //         case NotificationType::ReviewSeller:
    //         case NotificationType::ReviewedByBuyer:
    //             $icon = asset('notifications-icons/reviewSellerIcon@3x.png');
    //             break;
    //         case NotificationType::PayoutSent:
    //             $icon = asset('notifications-icons/payoutSentIcon@3x.png');
    //             break;
    //         case NotificationType::NewOrder:
    //             $icon = asset('notifications-icons/newOrderIcon@3x.png');
    //             break;
    //         case NotificationType::ItemReviewPending:
    //             $icon = asset('notifications-icons/listingNeedReviewIcon@3x.png');
    //             break;
    //     }

    //     return $icon;
    // }
}
