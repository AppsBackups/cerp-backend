<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPropertyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'circle',
        'pin',
        'info',
        'latitude',
        'longitude',
        'floors_num',
        'basement',
        'land_area',
        'covered_area',
        'land',
        'other',
        'comments',
        'picture_path',
        'capture_time',
        'submission_time',
        'resubmission',
        'Store_front',
        'picture2_path',
    ];

   

    // Optional: relationship to MobileUser
    public function user()
    {
        return $this->belongsTo(MobileUser::class, 'user_id');
    }
}
