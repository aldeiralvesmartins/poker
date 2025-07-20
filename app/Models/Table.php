<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'name', 'owner_id', 'max_players', 'small_blind', 'big_blind', 'buy_in', 'status'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function players()
    {
        return $this->belongsToMany(User::class)->withPivot('chips')->withTimestamps();
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
