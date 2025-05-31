<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Database;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Exception;

class HistoryController extends BaseController
{
    private static array $preparedStatementsStatus = [];

    /**
     * @throws Exception
     */
    private function prepareAllStatements(): void
    {
        $db = Database::getConnection();
        $statementsToPrepare = [
            'get_user_games' => 'SELECT g.id, g.start_score, g.single_in, g.double_in, g.single_out, g.double_out, g.created_at, g.is_active,
                                      COUNT(gp.id) as player_count
                                 FROM games g
                                 LEFT JOIN game_players gp ON g.id = gp.game_id
                                 WHERE g.owner_id = $1
                                 GROUP BY g.id, g.start_score, g.single_in, g.double_in, g.single_out, g.double_out, g.created_at, g.is_active
                                 ORDER BY g.created_at DESC',
            'get_game_top_players' => 'SELECT gp.player_name, gp.finish_position, gp.finished_at
                                      FROM game_players gp
                                      WHERE gp.game_id = $1
                                      ORDER BY 
                                        CASE WHEN gp.finish_position IS NULL THEN 1 ELSE 0 END,
                                        gp.finish_position ASC,
                                        gp.id ASC
                                      LIMIT 3',
            'delete_game' => 'DELETE FROM games WHERE id = $1 AND owner_id = $2',
            'check_game_ownership' => 'SELECT id FROM games WHERE id = $1 AND owner_id = $2'
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
     * Display the game history page
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @throws Exception
     */
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login'); 
            exit;
        }
        
        $this->prepareAllStatements();
        $db = Database::getConnection();
        
        // Get all games for the current user
        $gamesResult = pg_execute($db, 'get_user_games', [$_SESSION['user_id']]);
        $games = [];
        
        while ($game = pg_fetch_assoc($gamesResult)) {
            // Get top 3 players for this game
            $playersResult = pg_execute($db, 'get_game_top_players', [$game['id']]);
            $topPlayers = [];
            
            while ($player = pg_fetch_assoc($playersResult)) {
                $topPlayers[] = [
                    'name' => $player['player_name'],
                    'finishPosition' => $player['finish_position'] ? (int)$player['finish_position'] : null,
                    'finishedAt' => $player['finished_at']
                ];
            }
            
            $games[] = [
                'id' => (int)$game['id'],
                'startScore' => (int)$game['start_score'],
                'singleIn' => $game['single_in'] === 't',
                'doubleIn' => $game['double_in'] === 't',
                'singleOut' => $game['single_out'] === 't',
                'doubleOut' => $game['double_out'] === 't',
                'createdAt' => $game['created_at'],
                'isActive' => $game['is_active'] === 't',
                'playerCount' => (int)$game['player_count'],
                'topPlayers' => $topPlayers
            ];
        }
        
        $this->render('history', ['games' => $games]);
    }

    /**
     * Delete a game
     * @throws Exception
     */
    public function delete(): void
    {
        header('Content-Type: application/json');
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Not authenticated', 401);
            }

            $this->prepareAllStatements();
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['gameId'])) {
                throw new Exception('Missing game ID', 400);
            }

            $gameId = (int)$input['gameId'];
            $userId = $_SESSION['user_id'];
            
            $db = Database::getConnection();
            
            // Verify ownership
            $ownershipResult = pg_execute($db, 'check_game_ownership', [$gameId, $userId]);
            if (pg_num_rows($ownershipResult) == 0) {
                throw new Exception('Game not found or access denied', 404);
            }
            
            // Delete the game (cascade will handle related records)
            $deleteResult = pg_execute($db, 'delete_game', [$gameId, $userId]);
            
            if (pg_affected_rows($deleteResult) > 0) {
                echo json_encode(['success' => true, 'message' => 'Game deleted successfully']);
            } else {
                throw new Exception('Failed to delete game', 500);
            }

        } catch (Exception $e) {
            $statusCode = $e->getCode();
            if (!is_int($statusCode) || $statusCode < 400 || $statusCode >= 600) {
                $statusCode = 500;
            }
            http_response_code($statusCode);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
