<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory;

    protected $table = 'scores';

    protected $fillable = [
        'user_id',
        'game_id',
        'game_version_id',
        'score',
    ];

    


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    


    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    


    public function gameVersion()
    {
        return $this->belongsTo(GameVersion::class, 'game_version_id');
    }
}
