<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Database;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Exception;

class ScoreController extends BaseController
{
    private static array $preparedStatementsStatus = [];

    /**
     * @throws Exception
     */
    private function prepareAllStatements(): void
    {
        $db = Database::getConnection();
        $statementsToPrepare = [
            'get_player_total_score' => 'SELECT COALESCE(SUM(score),0) AS total FROM score_history WHERE game_player_id = $1',
            'get_ordered_players_for_game' => 'SELECT id, player_name FROM game_players WHERE game_id = $1 ORDER BY id',
            'get_active_game_with_info' => 'SELECT id, start_score, single_in, double_in, single_out, double_out FROM games WHERE owner_id = $1 AND is_active = TRUE',
            'insert_score_history' => 'INSERT INTO score_history (game_player_id, score, is_bust_shot, is_turn_ender) VALUES ($1, $2, $3, $4)',
            'set_game_inactive' => 'UPDATE games SET is_active = FALSE WHERE id = $1',
            'delete_score_by_id' => 'DELETE FROM score_history WHERE id = $1',
        ];

        foreach ($statementsToPrepare as $name => $query) {
            if (isset(self::$preparedStatementsStatus[$name]) && self::$preparedStatementsStatus[$name] === true) {
                continue;
            }

            $result = @pg_prepare($db, $name, $query);
            if ($result !== false) {
                self::$preparedStatementsStatus[$name] = true;
            } else {
                $last_error = pg_last_error($db);
                throw new Exception("Failed to prepare statement $name: $last_error");
            }
        }
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @throws Exception
     */
    public function score(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login'); exit;
        }
        $this->prepareAllStatements();
        $db = Database::getConnection();

        // Fetch active game and prepare game state
        $gameState = $this->getGameState($db);
        if (!$gameState) {
            header('Location: /game/create');
            exit;
        }

        $this->render('scoring', ['gameState' => $gameState]);
    }

    public function submit(): void
    {
        header('Content-Type: application/json');
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Not authenticated', 401);
            }
            $this->prepareAllStatements();
            $input = json_decode(file_get_contents('php://input'), true);

            // Validate input
            if (!isset($input['score']) || !isset($input['multiplier'])) {
                throw new Exception('Missing score or multiplier', 400);
            }

            $baseScore = (int)$input['score'];
            $multiplier = (int)$input['multiplier'];
            $score = $baseScore * $multiplier;

            // Check if score is valid
            if ($baseScore < 0 || $baseScore > 25 || !in_array($multiplier, [1, 2, 3])) {
                throw new Exception('Invalid score or multiplier', 400);
            }
            if ($baseScore == 25 && $multiplier == 3) {
                throw new Exception('No triple 25 allowed', 400);
            }
            // Check for impossible dart scores
            if ($baseScore > 20 && $baseScore != 25) {
                throw new Exception('Invalid dart score', 400);
            }

            $db = Database::getConnection();
            $gameState = $this->getGameState($db);
            if (!$gameState) {
                throw new Exception('No active game', 400);
            }

            // Check if game is already won
            $gameWon = false;
            foreach ($gameState['players'] as $player) {
                if ($player['remaining'] == 0) {
                    $gameWon = true;
                    break;
                }
            }
            if ($gameWon) {
                throw new Exception('Game is already finished', 400);
            }

            $currentPlayer = $gameState['currentPlayer'];
            $currentPlayerData = $gameState['players'][$currentPlayer];
            $newRemaining = $currentPlayerData['remaining'] - $score;
            $isBust = false;
            $isTurnEnder = false;

            // Check for double in rule
            if ($gameState['game']['double_in'] && $currentPlayerData['remaining'] == $gameState['game']['start_score']) {
                // First dart must be a double
                if ($multiplier != 2) {
                    $isBust = true;
                    $isTurnEnder = true;
                    $score = 0;
                }
            }

            // Check for bust or win conditions
            if (!$isBust && $newRemaining < 0) {
                $isBust = true;
                $isTurnEnder = true;
                $score = 0; // Don't actually subtract score on bust
            } elseif (!$isBust && $newRemaining == 0) {
                // Check if double out is required
                if ($gameState['game']['double_out'] && $multiplier != 2) {
                    $isBust = true;
                    $isTurnEnder = true;
                    $score = 0;
                } else {
                    // Game won!
                    $isTurnEnder = true;
                    // Set game as inactive
                    pg_execute($db, 'set_game_inactive', [$gameState['game']['id']]);
                }
            } elseif (!$isBust && $newRemaining == 1 && $gameState['game']['double_out']) {
                // Can't finish with 1 if double out required
                // No bust, just continue
            }

            // Check if this is the third dart
            if ($gameState['currentDart'] >= 3) {
                $isTurnEnder = true;
            }

            // Store score in database
            pg_execute($db, 'insert_score_history', [$currentPlayerData['id'], $score, $isBust ? 'true' : 'false', $isTurnEnder ? 'true' : 'false']);

            // Return updated game state
            $updatedGameState = $this->getGameState($db);
            echo json_encode(['success' => true, 'gameState' => $updatedGameState]);

        } catch (Exception $e) {
            $statusCode = $e->getCode();
            if (!is_int($statusCode) || $statusCode < 400 || $statusCode >= 600) {
                $statusCode = 500;
            }
            http_response_code($statusCode);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function undo(): void
    {
        header('Content-Type: application/json');
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Not authenticated', 401);
            }
            $this->prepareAllStatements();
            $db = Database::getConnection();

            // Undo last score
            $gameState = $this->getGameState($db);
            if (!$gameState) {
                throw new Exception('No active game', 400);
            }

            // Get the last score entry for any player in this game
            $lastScoreQuery = 'SELECT sh.id FROM score_history sh 
                              JOIN game_players gp ON sh.game_player_id = gp.id 
                              WHERE gp.game_id = $1 
                              ORDER BY sh.recorded_at DESC, sh.id DESC 
                              LIMIT 1';
            $result = pg_query_params($db, $lastScoreQuery, [$gameState['game']['id']]);
            
            if (pg_num_rows($result) > 0) {
                $lastScore = pg_fetch_assoc($result);
                pg_execute($db, 'delete_score_by_id', [$lastScore['id']]);
            }

            // Recalculate game state
            $updatedGameState = $this->getGameState($db);

            // Return updated game state
            echo json_encode(['success' => true, 'gameState' => $updatedGameState]);
        } catch (Exception $e) {
            $statusCode = $e->getCode();
            if (!is_int($statusCode) || $statusCode < 400 || $statusCode >= 600) {
                $statusCode = 500;
            }
            http_response_code($statusCode);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @throws Exception
     */
    private function getGameState($db): ?array
    {
        $userId = $_SESSION['user_id'];
        
        // Get active game info
        $gameResult = pg_execute($db, 'get_active_game_with_info', [$userId]);
        if (pg_num_rows($gameResult) == 0) {
            return null;
        }
        $game = pg_fetch_assoc($gameResult);
        
        // Get players for this game
        $playersResult = pg_execute($db, 'get_ordered_players_for_game', [$game['id']]);
        $players = [];
        
        while ($player = pg_fetch_assoc($playersResult)) {
            // Get total score for this player
            $scoreResult = pg_execute($db, 'get_player_total_score', [$player['id']]);
            $scoreData = pg_fetch_assoc($scoreResult);
            $totalScore = (int)$scoreData['total'];
            
            $players[] = [
                'id' => (int)$player['id'],
                'name' => $player['player_name'],
                'remaining' => (int)$game['start_score'] - $totalScore,
                'lastDarts' => [] // Will be populated after calculating current player
            ];
        }
        
        // Calculate current player and dart based on turn management
        // Find player with incomplete turn (has darts but last dart is not turn ender)
        $currentPlayer = 0;
        $currentDart = 1;
        
        foreach ($players as $index => $player) {
            // Check if this player has an incomplete turn
            $incompleteQuery = 'SELECT COUNT(*) as count 
                              FROM score_history sh 
                              WHERE sh.game_player_id = $1 
                              AND sh.id > COALESCE((
                                  SELECT MAX(id) FROM score_history 
                                  WHERE game_player_id = $1 AND is_turn_ender = TRUE
                              ), 0)';
            
            $incompleteResult = pg_query_params($db, $incompleteQuery, [$player['id']]);
            $incompleteData = pg_fetch_assoc($incompleteResult);
            $dartsInCurrentTurn = (int)$incompleteData['count'];
            
            if ($dartsInCurrentTurn > 0 && $dartsInCurrentTurn < 3) {
                $currentPlayer = $index;
                $currentDart = $dartsInCurrentTurn + 1;
                break;
            }
        }
        
        // If no player has incomplete turn, find next player based on completed turns
        if ($currentDart === 1) {
            $totalCompletedTurns = 0;
            foreach ($players as $player) {
                $completedQuery = 'SELECT COUNT(*) as turns FROM score_history WHERE game_player_id = $1 AND is_turn_ender = TRUE';
                $completedResult = pg_query_params($db, $completedQuery, [$player['id']]);
                $completedData = pg_fetch_assoc($completedResult);
                $totalCompletedTurns += (int)$completedData['turns'];
            }
            
            $currentPlayer = $totalCompletedTurns % count($players);
        }
        
        // Now populate lastDarts for each player based on whether they are current player or not
        foreach ($players as $index => &$player) {
            if ($index === $currentPlayer) {
                // For current player: show only darts from current incomplete turn
                $currentTurnQuery = 'SELECT score FROM score_history sh 
                                   WHERE sh.game_player_id = $1 
                                   AND sh.id > COALESCE((
                                       SELECT MAX(id) FROM score_history 
                                       WHERE game_player_id = $1 AND is_turn_ender = TRUE
                                   ), 0)
                                   ORDER BY sh.recorded_at ASC, sh.id ASC';
                
                $currentTurnResult = pg_query_params($db, $currentTurnQuery, [$player['id']]);
                $currentTurnDarts = [];
                while ($dart = pg_fetch_assoc($currentTurnResult)) {
                    $currentTurnDarts[] = (int)$dart['score'];
                }
                $player['lastDarts'] = $currentTurnDarts;
            } else {
                // For other players: show darts from their last completed turn (up to 3)
                // Get the most recent turn-ending dart
                $lastTurnEndQuery = 'SELECT id FROM score_history 
                                   WHERE game_player_id = $1 AND is_turn_ender = TRUE 
                                   ORDER BY recorded_at DESC, id DESC LIMIT 1';
                $lastTurnEndResult = pg_query_params($db, $lastTurnEndQuery, [$player['id']]);
                
                if ($lastTurnEndRow = pg_fetch_assoc($lastTurnEndResult)) {
                    $lastTurnEndId = $lastTurnEndRow['id'];
                    
                    // Get the previous turn-ending dart (if any)
                    $prevTurnEndQuery = 'SELECT id FROM score_history 
                                       WHERE game_player_id = $1 AND is_turn_ender = TRUE 
                                       AND id < $2
                                       ORDER BY recorded_at DESC, id DESC LIMIT 1';
                    $prevTurnEndResult = pg_query_params($db, $prevTurnEndQuery, [$player['id'], $lastTurnEndId]);
                    $prevTurnEndId = 0;
                    if ($prevTurnEndRow = pg_fetch_assoc($prevTurnEndResult)) {
                        $prevTurnEndId = $prevTurnEndRow['id'];
                    }
                    
                    // Get darts from the last complete turn
                    $lastTurnQuery = 'SELECT score FROM score_history 
                                    WHERE game_player_id = $1 
                                    AND id > $2 AND id <= $3
                                    ORDER BY recorded_at ASC, id ASC';
                    $lastTurnResult = pg_query_params($db, $lastTurnQuery, [$player['id'], $prevTurnEndId, $lastTurnEndId]);
                    $lastTurnDarts = [];
                    while ($dart = pg_fetch_assoc($lastTurnResult)) {
                        $lastTurnDarts[] = (int)$dart['score'];
                    }
                    $player['lastDarts'] = $lastTurnDarts;
                } else {
                    // No completed turns yet
                    $player['lastDarts'] = [];
                }
            }
        }
        unset($player); // Break reference
        
        return [
            'game' => [
                'id' => (int)$game['id'],
                'start_score' => (int)$game['start_score'],
                'single_in' => $game['single_in'] == 't',
                'double_in' => $game['double_in'] == 't',
                'single_out' => $game['single_out'] == 't',
                'double_out' => $game['double_out'] == 't'
            ],
            'players' => $players,
            'currentPlayer' => $currentPlayer,
            'currentDart' => $currentDart
        ];
    }
}
