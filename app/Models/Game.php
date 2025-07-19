<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'table_id', 'community_cards', 'winner_id',
    ];

    protected $casts = [
        'community_cards' => 'array',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function actions()
    {
        return $this->hasMany(GameAction::class);
    }
}
