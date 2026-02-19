<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyRecord extends Model
{
    protected $fillable = [
        'pin', 'ratingarea', 'circle', 'Locality', 'Block',
        'Street_Address', 'OwnerName', 'Road'
    ];
}
