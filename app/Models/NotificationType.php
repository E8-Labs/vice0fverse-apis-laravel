<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationType extends Model
{
    use HasFactory;
    const NewMessage = 1;
    const NewApplicant = 2;
    const PendingApproval = 3;
    const NewComment = 4;
    const FlaggedUser = 5;
    const FlaggedJob = 6;
    const NewHire = 7;
    const NewJobApplication = 8;
    const NewCompany = 9;
    const NewRecruiter = 10;
    const NewUitMember = 11;
    const JobApproved = 12;
    const JobApplicationRejected = 13;
    const PostLike = 14;
    const PostUnLike = 15;

}
