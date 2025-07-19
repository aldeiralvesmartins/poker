<?php

namespace App\Services\Poker;

class GameEngine
{
    protected array $deck = [];

    public function __construct()
    {
        $this->deck = $this->generateDeck();
        shuffle($this->deck);
    }

    public function generateDeck(): array
    {
        $suits = ['H', 'D', 'C', 'S']; // ♥ ♦ ♣ ♠
        $values = range(2, 14); // 11=J, 12=Q, 13=K, 14=A
        $deck = [];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = $value . $suit;
            }
        }

        return $deck;
    }

    public function dealHoleCards($players): array
    {
        $cards = [];
        foreach ($players as $player) {
            $cards[$player->id] = [array_pop($this->deck), array_pop($this->deck)];
        }
        return $cards;
    }

    public function dealFlop(): array
    {
        $this->burnCard();
        return [array_pop($this->deck), array_pop($this->deck), array_pop($this->deck)];
    }

    public function dealTurn(): string
    {
        $this->burnCard();
        return array_pop($this->deck);
    }

    public function dealRiver(): string
    {
        $this->burnCard();
        return array_pop($this->deck);
    }

    public function burnCard(): void
    {
        array_pop($this->deck);
    }

    public function getDeck(): array
    {
        return $this->deck;
    }

    /**
     * Avalia a mão de um jogador e retorna uma pontuação numérica.
     * Por enquanto, avalia apenas: par, trinca, quadra, carta alta.
     */
    public function evaluateHand(array $playerCards, array $communityCards): array
    {
        $allCards = array_merge($playerCards, $communityCards);
        $ranks = [];

        foreach ($allCards as $card) {
            $rank = intval(substr($card, 0, -1)); // remove o naipe
            $ranks[$rank] = ($ranks[$rank] ?? 0) + 1;
        }

        arsort($ranks); // ordena pelas maiores combinações

        $handType = 'high_card';
        $score = 0;

        if (in_array(4, $ranks)) {
            $handType = 'four_of_a_kind';
            $score = 7000;
        } elseif (in_array(3, $ranks) && in_array(2, $ranks)) {
            $handType = 'full_house';
            $score = 6000;
        } elseif (in_array(3, $ranks)) {
            $handType = 'three_of_a_kind';
            $score = 3000;
        } elseif (array_count_values($ranks)[2] ?? 0 >= 2) {
            $handType = 'two_pair';
            $score = 2000;
        } elseif (in_array(2, $ranks)) {
            $handType = 'one_pair';
            $score = 1000;
        } else {
            $handType = 'high_card';
            $score = max(array_keys($ranks));
        }

        return [
            'type' => $handType,
            'score' => $score,
            'ranks' => $ranks,
        ];
    }

    /**
     * Retorna o ID do jogador vencedor entre os jogadores, com base nas mãos.
     */
    public function determineWinner(array $playersCards, array $communityCards): int
    {
        $winnerId = null;
        $highestScore = -1;

        foreach ($playersCards as $playerId => $cards) {
            $result = $this->evaluateHand($cards, $communityCards);
            if ($result['score'] > $highestScore) {
                $winnerId = $playerId;
                $highestScore = $result['score'];
            }
        }

        return $winnerId;
    }
}
