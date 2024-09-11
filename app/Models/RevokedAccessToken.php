<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevokedAccessToken extends Model
{
    use HasFactory;

    protected $fillable = ['token'];

    public $timestamps = false;
}
