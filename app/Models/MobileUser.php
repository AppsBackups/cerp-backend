<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class MobileUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['username', 'email', 'password', 'cnic', 'name','phonenumber','designation','status'];

    protected $hidden = ['password'];
}
