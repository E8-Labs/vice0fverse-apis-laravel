<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationType extends Model
{
    use HasFactory;
    const NewMessage = 1;
    const NewUser = 2;
    const NewComment = 4;
    const FlaggedUser = 5;
    const FlaggedJob = 6;
    const NewFollower = 7;
    
    const PostLike = 14;
    const PostUnLike = 15;

}
