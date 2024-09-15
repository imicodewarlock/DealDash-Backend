<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'category_id',
        'image',
        'address',
        'about',
        'latitude',
        'longitude',
    ];

    protected $dates = ['deleted_at'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class, 'store_id');
    }

    public function favoriteByUsers()
    {
        return $this->belongsToMany(User::class, 'store_favorites')->withTimestamps();
    }

    public function favoritesCount()
    {
        return $this->favoriteByUsers()->count();
    }
}
