<?php

namespace App\Models\Listing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostIntrationTypes extends Model
{
    use HasFactory;
    const TypeLike = 1;
    const TypeShare = 3;
    const TypeComment = 2;
    const TypePostOpened = 4;
}
