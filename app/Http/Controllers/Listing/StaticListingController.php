<?php

namespace App\Http\Controllers\Listing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media\Artist;
use App\Models\Media\Genre;

class StaticListingController extends Controller
{
    function listGenres(Request $request){

    	if($request->has('api_key')){
    		$key = $request->api_key;
    		if($key === env('api_key')){
    			$list = Genre::all();
    			return response()->json([
    			'status' => true,
    			'message'=> 'Genre list',
    			'data' => $list
    			]);
    		}
    		else{
    			return response()->json([
    				'status' => false,
    				'message'=> 'Api key invalid'
    			]);
    		}
    	}
    	else{
    		return response()->json([
    			'status' => false,
    			'message'=> 'Api key not provided'
    		]);
    	}

    }

    function listArtists(Request $request){
    	if($request->has('api_key')){
    		$key = $request->api_key;
    		if($key === env('api_key')){
                
    			$list = Artist::whereIn('genre_id', $request->genre_id)->get();
    			return response()->json([
    			'status' => true,
    			'message'=> 'Artist list',
    			'data' => $list
    			]);
    		}
    		else{
    			return response()->json([
    				'status' => false,
    				'message'=> 'Api key invalid'
    			]);
    		}
    	}
    	else{
    		return response()->json([
    			'status' => false,
    			'message'=> 'Api key not provided'
    		]);
    	}
    }
}
