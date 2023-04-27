<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatType extends Model
{
    use HasFactory;

    const ChatTypeSimple = 1;
}
