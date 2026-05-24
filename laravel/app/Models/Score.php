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

    /**
     * Get the user who achieved this score.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the game this score was achieved on.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Get the game version this score was achieved on.
     */
    public function gameVersion()
    {
        return $this->belongsTo(GameVersion::class, 'game_version_id');
    }
}
