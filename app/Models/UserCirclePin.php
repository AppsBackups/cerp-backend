<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCirclePin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'circle',
        'pin',
        'ratingarea',
        'Locality',
        'Block',
        'Street_Address',
        'OwnerName',
        'Road'
    ];

}
