<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Auth\Profile;
use App\Models\Auth\UserRole;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Profile\UserProfileLiteResource;

use App\Models\Media\ListingItem;
use App\Models\User\Follower;

use App\Models\User\UserQuestion;
use App\Models\User\UserTopArtists;
use App\Models\User\UserTopGenres;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    public function profile()
    {
        // return $this->hasOne(UserProfile::class)->select(['user_id','status_id','name','business_name','image_url']);
        return $this->hasOne(Profile::class);
    }

    public function getProfile()
    {
        if ($this->profile) {
            return new UserProfileFullResource($this->profile);
        }
    }

    public function getProfileLite()
    {
        if ($this->profile) {
            return new UserProfileLiteResource($this->profile);
        }
    }
    function getUserTopArtists(){
        $artists = UserTopArtists::where('user_id', $this->id)->get();
        return $artists;
    }


    function getUserTopGenres(){
        $artists = UserTopGenres::where('user_id', $this->id)->get();
        return $artists;
    }

    function getUserQuestions(){
        $artists = UserQuestion::where('user_id', $this->id)->get();
        return $artists;
    }

    public function getFollowersCount(){
        $followers = Follower::where('followed', $this->id)->count('id');
        return $followers;
    }
    public function getFollowingCount(){
        $followers = Follower::where('follower', $this->id)->count('id');
        return $followers;
    }
    public function getPostsCount(){
        $followers = ListingItem::where('user_id', $this->id)->count('id');
        return $followers;
    }

    public function isAdmin(){

        if($this->profile->role == UserRole::RoleAdmin){
            return true;
        }
        return false;
    }

    
}
