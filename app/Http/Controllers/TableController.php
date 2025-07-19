<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        return Table::with('players')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'max_players' => 'required|integer',
            'small_blind' => 'required|integer',
            'big_blind' => 'required|integer',
            'buy_in' => 'required|integer',
        ]);

        $table = Table::create([
            ...$data,
            'owner_id' => auth()->id(),
        ]);

        return response()->json($table, 201);
    }


    public function join($id) {
        $table = Table::findOrFail($id);
        $user = auth()->user();

        if ($user->chips < $table->buy_in) {
            return response()->json(['error' => 'Fichas insuficientes'], 400);
        }

        if ($table->players()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Você já está na mesa']);
        }

        $table->players()->attach($user->id, ['chips' => $table->buy_in]);
        $user->decrement('chips', $table->buy_in);

        return response()->json(['message' => 'Entrou na mesa com sucesso']);
    }

    public function show($id)
    {
        $table = Table::with('players')->findOrFail($id);
        return response()->json($table);
    }

    public function leave($id)
    {
        $table = Table::findOrFail($id);
        $user = auth()->user();

        if (!$table->players()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Você não está nessa mesa'], 400);
        }

        $table->players()->detach($user->id);

        return response()->json(['message' => 'Você saiu da mesa com sucesso']);
    }

}
