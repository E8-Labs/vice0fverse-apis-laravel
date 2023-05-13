<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\NotificationType;

class CreateNotificationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default("");
            $table->timestamps();
        });

        \DB::table('notification_types')->insert([
            ['id'=> NotificationType::NewMessage, 'name' => 'New Message'],
            ['id'=> NotificationType::NewApplicant, 'name' => 'New Applicant'],
            ['id'=> NotificationType::PendingApproval, 'name' => 'Profile Pending Approval'],
            ['id'=> NotificationType::NewComment, 'name' => 'New Comment'],
            ['id'=> NotificationType::FlaggedUser, 'name' => 'Flagged User'],
            ['id'=> NotificationType::FlaggedJob, 'name' => 'Flagged Job'],
            ['id'=> NotificationType::NewHire, 'name' => 'New Hire'],
            ['id'=> NotificationType::NewJobApplication, 'name' => 'New Job Application'],
            ['id'=> NotificationType::NewCompany, 'name' => 'New Company'],
            ['id'=> NotificationType::NewRecruiter, 'name' => 'New Recruiter'],
            ['id'=> NotificationType::NewUitMember, 'name' => 'New Uit Member'],
            ['id'=> NotificationType::JobApproved, 'name' => 'Job Approved'],
            ['id'=> NotificationType::JobApplicationRejected, 'name' => 'Job Application Rejected'],
            ['id'=> NotificationType::PostLike, 'name' => 'Community Post Like'],
            ['id'=> NotificationType::PostUnLike, 'name' => 'Community Post Dislike'],
            ['id'=> NotificationType::NewPost, 'name' => 'New Post'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_types');
    }
}
