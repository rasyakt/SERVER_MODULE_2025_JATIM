<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $table = 'games';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'author_id',
    ];

    


    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    


    public function versions()
    {
        return $this->hasMany(GameVersion::class, 'game_id');
    }

    


    public function latestVersion()
    {
        return $this->hasOne(GameVersion::class, 'game_id')->latestOfMany();
    }

    


    public function scores()
    {
        return $this->hasMany(Score::class, 'game_id');
    }
}
