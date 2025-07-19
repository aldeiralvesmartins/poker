<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Table;
use App\Models\GameAction;
use App\Services\Poker\GameEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    /**
     * Inicia um novo jogo em uma mesa.
     */
    public function start($tableId)
    {
        $table = Table::with('players')->findOrFail($tableId);

        if ($table->players->count() < 2) {
            return response()->json(['error' => 'Jogadores insuficientes para iniciar o jogo.'], 422);
        }

        $engine = new GameEngine();

        $holeCards = $engine->dealHoleCards($table->players);
        $flop = $engine->dealFlop();
        $turn = $engine->dealTurn();
        $river = $engine->dealRiver();

        $community = array_merge($flop, [$turn, $river]);

        $game = Game::create([
            'table_id' => $table->id,
            'community_cards' => json_encode($community),
            'player_hole_cards' => json_encode($holeCards),  // SALVA AQUI
        ]);


        return response()->json([
            'game_id' => $game->id,
            'community_cards' => $community,
            'hole_cards' => $holeCards,
        ]);
    }

    /**
     * Registra a ação de um jogador durante uma partida.
     */
    public function action(Request $request, $gameId)
    {
        $request->validate([
            'action' => 'required|string|in:fold,call,raise,check,bet',
            'amount' => 'nullable|integer|min:0',
        ]);

        $game = Game::findOrFail($gameId);

        $userId = Auth::id();

        // Verifica se o jogador está na mesa
        $isPlayer = $game->table->players()->where('user_id', $userId)->exists();
        if (!$isPlayer) {
            return response()->json(['error' => 'Você não está nesta mesa.'], 403);
        }

        $action = GameAction::create([
            'game_id' => $game->id,
            'user_id' => $userId,
            'action' => $request->action,
            'amount' => $request->amount,
        ]);

        return response()->json([
            'message' => 'Ação registrada com sucesso.',
            'data' => $action,
        ]);
    }

    /**
     * Exibe o estado atual do jogo.
     */
    public function show($gameId)
    {
        $game = Game::with(['actions.user', 'table.players'])->findOrFail($gameId);

        return response()->json($game);
    }

    public function finishGame($gameId)
    {
        $game = Game::with(['actions', 'table.players'])->findOrFail($gameId);

        if ($game->winner_id) {
            return response()->json(['message' => 'Jogo já finalizado.', 'winner_id' => $game->winner_id]);
        }

        $engine = new GameEngine();

        // Recupera cartas dos jogadores e cartas comunitárias
        $playersCards = json_decode($game->player_hole_cards, true);
        $communityCards = json_decode($game->community_cards, true);

        if (!$playersCards || !$communityCards) {
            return response()->json(['error' => 'Cartas incompletas, impossível finalizar.'], 400);
        }

        // Determina o vencedor
        $winnerId = $engine->determineWinner($playersCards, $communityCards);

        if (!$winnerId) {
            return response()->json(['error' => 'Não foi possível determinar o vencedor.'], 500);
        }

        // Calcula o pote: soma de todas as apostas na partida
        $pot = $game->actions->sum('amount');

        // Atualiza fichas do vencedor
        $winner = $game->table->players->find($winnerId);
        if (!$winner) {
            return response()->json(['error' => 'Vencedor não está na mesa.'], 500);
        }

        $winner->increment('chips', $pot);

        // Atualiza o registro do jogo com o vencedor
        $game->winner_id = $winnerId;
        $game->save();

        return response()->json([
            'message' => 'Jogo finalizado com sucesso.',
            'winner_id' => $winnerId,
            'winner_name' => $winner->name,
            'pot' => $pot,
            'winner_new_chips' => $winner->chips,
        ]);
    }
}
