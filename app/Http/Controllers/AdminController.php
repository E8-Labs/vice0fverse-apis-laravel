<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\User\UserQuestion;
use App\Models\User\UserTopArtists;
use App\Models\User\UserTopGenres;
use App\Models\Auth\Profile;
use App\Models\Auth\UserRole;
use App\Models\Auth\VerificationCode;

use App\Models\Media\ListingItem;

use Illuminate\Support\Facades\Mail;

use App\Models\Listing\PostComments;
use App\Models\Listing\PostIntration;
use App\Models\Listing\PostIntrationTypes;

use App\Http\Resources\Profile\UserProfileFullResource;
use App\Http\Resources\Profile\UserProfileLiteResource;
use App\Http\Resources\Media\ListingItemResource;
use App\Http\Resources\Media\PostCommentResource;
use Illuminate\Support\Facades\Http;

use Carbon\Carbon;

class AdminController extends Controller
{
    const Page_Limit = 20;

    // admin function to load user profiles
    public function getUsers(Request $request){
        $user = Auth::user();
        $off_set = 0;
        if($request->has('off_set')){
            $off_set = $request->off_set;
        }
        // $sort = SorterModel::DescendingAlphabetically;

        // if($request->has('sorter')){
        //     $sort = $request->sorter;
        // }

        if($user ){//&& $user->isAdmin()

            // $profiles = Profile::where('user_id', '!=', $user->id)->where('account_status', AccountStatus::StatusActive)->orderBy('created_at', 'DESC')->skip($off_set)->take($this->Page_Limit)->get();
            if($request->has('search')){
                // return "Search";
                $search = $request->search;
                if($search != ''){
                    $tokens = explode(" ", $search);
                    // return $tokens;
                    $query = Profile::where('user_id', '!=', $user->id);
                    
                    $query->where(function($query) use($tokens){
                        foreach($tokens as $tok){

                            $query->where('name', 'LIKE', "%$tok%");
                        }
                    });
                    if($request->has('location')){
                        $location = $request->location;
                        $tokens = explode(" ", $location);

                        if(count($tokens) == 1){
                            $tok = trim($tokens[0]);
                            $query->where('city', 'LIKE', "%$tok%")->orWhere('state', 'LIKE', "%$tok%");
                        }
                        else if(count($tokens) == 2){
                            $tok = trim($tokens[0]);
                            $tok2 = trim($tokens[1]);
                            $query->where('city', 'LIKE', "%$tok%")->orWhere('state', 'LIKE', "%$tok2%");
                        }
                    }
                    $profiles = $query->orderBy('created_at', 'DESC')->skip($off_set)->take(AdminController::Page_Limit)->get();
                    
                }
            }
            else{
                $query = Profile::where('user_id', '!=', $user->id);
                if($request->has('location')){
                        $location = $request->location;
                        $tokens = explode(",", $location);

                        if(count($tokens) == 1){
                            $tok = trim($tokens[0]);
                            
                            // echo '1 Token' . $tok;
                            $query->where('city', 'LIKE', "%$tok%")->orWhere('state', 'LIKE', "%$tok%");
                        }
                        else if(count($tokens) == 2){
                            $tok = trim($tokens[0]);
                            $tok2 = trim($tokens[1]);
                            // echo 'Tokens ' . $tok . ' 2nd ' . $tok2;
                            $query->where('city', 'LIKE', "%$tok%")->where('state', 'LIKE', "%$tok2%");
                        }
                    }
                
                    $profiles = $query->orderBy('name', 'ASC')->skip($off_set)->take(AdminController::Page_Limit)->get();
            }
            return response()->json([
                'status' => true,
                'message' => 'Users found',
                'data' => UserProfileLiteResource::collection($profiles),
                'off_set' => $off_set,
            ], 200);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Only authenticated users can perform this action',
            ], 401);
        }
    }




    function getGraphData(Request $request){
        $user = Auth::user();
        // $off_set = 0;
        //by default show for one month
        $months = 1;
        if($request->has('months')){
            $months = $request->months;
        }
        if($months > 12){
            $months = 1200;
        }
        $date = Carbon::now()->subMonths($months);//->subDays(7);//->subYears(5)    
        // if($request->has('off_set')){
        //  $off_set = $request->off_set;
        // }
        $currentSelectedDate = Carbon::now();
        if($request->has('current_date')){
            $dateString = $request->current_date;
            $currentSelectedDate = Carbon::createFromFormat(\Config::get('constants.Date_Format'),$dateString);
        }
        $startOfYear = $currentSelectedDate->copy()->startOfYear();
        $dateMonthAgo = $currentSelectedDate->copy()->subMonths(1);
        $endOfYear   = $currentSelectedDate->copy()->endOfYear();

        $total_users = User::where('role', '!=', UserRole::RoleAdmin)->where('created_at', '>=', $dateMonthAgo)
                    ->where('created_at', '<=', Carbon::now())->count('id'); // users last 30 days

        // $usersInLast7Days = User::where('role', '!=', UserRole::RoleAdmin)->where('created_at', '>=', $date)
            // ->count('id');


        
        if($user && $user->isAdmin()){
   //       $users = Profile::select(DB::raw('count(id) as users, left(DATE(created_at),10) as registeredDate'))
            //  ->where(function($q) use($user, $date, $startOfYear, $endOfYear){
            //      $q->where('user_id', '!=', $user->id)->where('created_at', '>=', $startOfYear)
   //                  ->where('created_at', '<=', $endOfYear);
            //  })
            // // ->offset($off_set)
            // // ->limit($this->Page_Limit)
            // ->groupBy('registeredDate')
            // ->get();

        $graph = $this->getUsersGraphData(1);
        


        $mintGraphData = $this->getMintsGraphData(1);
            // return $users;
        $listings_count = ListingItem::where('created_at', '>=', $dateMonthAgo)
                    ->where('created_at', '<=', Carbon::now())->count('id');

        $recentUsers = Profile::orderBy('created_at', 'DESC')->take(3)->get();

            return response()->json([
                'status' => true,
                'message' => 'Users found',
                'data' => [
                    "user_graph_data" => $graph,
                    'current_year_start' => $startOfYear, 'current_year_end' => $endOfYear, 'total_users' => $total_users, "listings" => $listings_count, 
                    "listing_graph_data" => $mintGraphData, "active_users" => $total_users, 
                    "users" => UserProfileLiteResource::collection($recentUsers),
                ],
                
            ], 200);
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Only admin can perform this action',
            ], 401);
        }
    }

    function getUsersGraphData($fromMonths){

        $date = Carbon::now()->subMonths($fromMonths);//->subDays(7);//->subYears(5)  
        $graph = Array();
        // $newD = $startOfYear->copy(); // this was t0 get all the users from start of current month to the end
        $newD = $date->copy();
        // return $newD;
        // while($newD <= $endOfYear){ //old logic
        while($newD <= Carbon::now()){

            $users = Profile::select(DB::raw('count(id) as users'))
                ->where(function($q) use($newD){
                    $startDay = $newD->copy()->startOfDay();
                    $endDay   = $newD->copy()->endOfDay();
                    $q->where('created_at', '>=', $startDay)
                    ->where('created_at', '<=', $endDay);
                })
                ->first();
                if($users){
                    $data = ["users" => $users['users'], 'registeredDate' => $newD->copy()];
                    // return $data;
                    $graph[] = $data;
                }
                $newD->addDay();
                // return $newD;
        }
        return $graph;
    }

    function getMintsGraphData($forMonths){
         $graph = Array();
         $date = Carbon::now()->subMonths($forMonths);
        $newD = $date->copy();
        // return $newD;
        // while($newD <= $endOfYear){ //old logic
        while($newD <= Carbon::now()){

            $users = ListingItem::select(DB::raw('count(id) as listings'))
                ->where(function($q) use( $newD){
                    $startDay = $newD->copy()->startOfDay();
                    $endDay   = $newD->copy()->endOfDay();
                    $q->where('created_at', '>=', $startDay)
                    ->where('created_at', '<=', $endDay);
                })
                ->first();
                if($users){
                    $data = ["users" => $users['listings'], 'registeredDate' => $newD->copy()];
                    // return $data;
                    $graph[] = $data;
                }
                $newD->addDay();
                // return $newD;
        }
        return $graph;
    }


    function deleteUser(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
                ]);

            if($validator->fails()){
                return response()->json(['status' => false,
                    'message'=> 'validation error',
                    'data' => null, 
                    'validation_errors'=> $validator->errors()]);
            }


        $user = Auth::user();
        if ($user){
            $userDeleted = Profile::where('user_id', $request->user_id)->update(['account_status' => AccountStatus::StatusDeleted]);
            if($userDeleted){
                return response()->json([
                    'status' => true,
                    'message' => 'User Deleted',
                ]);
            }
            else{
                return response()->json([
                 'status' => false,
                 'message' => 'Error deleting user',
                ]);
            }
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ]);
        }
    }

    function disableUser(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
                ]);

            if($validator->fails()){
                return response()->json(['status' => false,
                    'message'=> 'validation error',
                    'data' => null, 
                    'validation_errors'=> $validator->errors()]);
            }


        $user = Auth::user();
        if ($user){
            $userDeleted = Profile::where('user_id', $request->user_id)->update(['account_status' => AccountStatus::StatusDisabled]);
            if($userDeleted){
                return response()->json([
                    'status' => true,
                    'message' => 'User Disabled',
                ]);
            }
            else{
                return response()->json([
                 'status' => false,
                 'message' => 'Error disabling user',
                ]);
            }
        }
        else{
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ]);
        }
    }


    function getUserListings(Request $request){
    	$user = Auth::user();
    	if(!$user){
    		return response()->json(['status' => false,
					'message'=> 'Unauthenticated user',
					'data' => null,
				]);
    	}

    	$offset = $request->off_set;
    	if($offset == NULL){
    		$offset = 0;
    	}
    	
    	$list = ListingItem::where('user_id', $request->user_id)->orderBy('created_at', 'DESC')->skip($offset)->take(20)->get();
    	


    	return response()->json(['status' => true,
					'message' => 'List',
					'data' => ListingItemResource::collection($list),
				]);

    }

    function getAllListingsAdmin(Request $request){
        $user = Auth::user();
        if(!$user){
            return response()->json(['status' => false,
                    'message'=> 'Unauthenticated user',
                    'data' => null,
                ]);
        }

        $offset = $request->off_set;
        if($offset == NULL){
            $offset = 0;
        }
        
        $list = ListingItem::orderBy('created_at', 'DESC')->skip($offset)->take(20)->get();
        


        return response()->json(['status' => true,
                    'message' => 'List',
                    'data' => ListingItemResource::collection($list),
                ]);

    }

    function deleteListing(Request $request){
    	$user = Auth::user();
    	if(!$user){
    		return response()->json(['status' => false,
					'message'=> 'Unauthenticated user',
					'data' => null,
				]);
    	}
    	$deleted = ListingItem::where('id', $request->post_id)->delete();
    	if($deleted){
    		return response()->json(['status' => true,
					'message' => 'Item delted',
					'data' => null,
				]);
    	}
    	else{
    		return response()->json(['status' => false,
					'message' => 'Item not delted',
					'data' => null,
				]);
    	}
    }
}
