<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    




    protected $table = 'users';

    




    protected $fillable = [
        'username',
        'password',
        'role',
        'is_blocked',
        'block_reason',
        'last_login_at',
    ];

    




    protected $hidden = [
        'password',
    ];

    




    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    


    public function authoredGames()
    {
        return $this->hasMany(Game::class, 'author_id');
    }

    


    public function scores()
    {
        return $this->hasMany(Score::class, 'user_id');
    }
}
