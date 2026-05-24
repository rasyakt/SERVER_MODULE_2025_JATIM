<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameVersion extends Model
{
    use HasFactory;

    protected $table = 'game_versions';

    protected $fillable = [
        'game_id',
        'version',
        'path',
        'thumbnail',
    ];

    /**
     * Get the game this version belongs to.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
