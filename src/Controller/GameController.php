<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Database;
use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class GameController extends BaseController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function create(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $this->render('create_game');
    }

    /**
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function store(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $ownerId = $_SESSION['user_id'];
        $startScore = filter_input(INPUT_POST, 'start_score', FILTER_VALIDATE_INT, ['options' => ['default' => 501, 'min_range' => 1]]);

        $gameInType = $_POST['game_in_type'] ?? 'any'; // 'any' or 'double'
        $singleIn = ($gameInType === 'any') ? "TRUE" : "FALSE";
        $doubleIn = ($gameInType === 'double') ? "TRUE" : "FALSE";

        $gameOutType = $_POST['game_out_type'] ?? 'any'; // 'any' or 'double'
        $singleOut = ($gameOutType === 'any') ? "TRUE" : "FALSE";
        $doubleOut = ($gameOutType === 'double') ? "TRUE" : "FALSE";

        $playerNames = $_POST['players'] ?? [];
        $playerNames = array_filter(array_map('trim', $playerNames)); // Remove empty names

        if (empty($playerNames)) {
            // Handle error: At least one player is required
            $this->render('create_game', ['error' => 'At least one player name is required.']);
            return;
        }

        if (count($playerNames) < 1) { // Or a different minimum like 2
             $this->render('create_game', ['error' => 'At least one player is required to start a game.']);
            return;
        }

        $db = Database::getConnection();
        try {
            pg_query($db, "BEGIN");

            // Deactivate any existing active games for this user
            $deactivateSql = 'UPDATE games SET is_active = FALSE WHERE owner_id = $1 AND is_active = TRUE';
            pg_prepare($db, "deactivate_games", $deactivateSql);
            pg_execute($db, "deactivate_games", [$ownerId]);

            // Insert new game
            $sql = "INSERT INTO games (owner_id, is_active, start_score, single_in, double_in, single_out, double_out) 
                    VALUES ($1, TRUE, $2, $3, $4, $5, $6) RETURNING id";
            pg_prepare($db, "create_game", $sql);
            $result = pg_execute($db, "create_game", [$ownerId, $startScore, $singleIn, $doubleIn, $singleOut, $doubleOut]);

            if (!$result) {
                throw new Exception("Failed to create game.");
            }
            $gameRow = pg_fetch_assoc($result);
            $gameId = $gameRow['id'];

            // Insert game players
            pg_prepare($db, "add_game_player", "INSERT INTO game_players (game_id, player_name) VALUES ($1, $2)");
            foreach ($playerNames as $playerName) {
                if (!empty($playerName)) {
                    pg_execute($db, "add_game_player", [$gameId, $playerName]);
                }
            }

            pg_query($db, "COMMIT");

            // Redirect to the game scoring page (assuming it will be /game/{id}/score)
            header('Location: /game/score'); // Or wherever the game play page is
            exit;

        } catch (Exception $e) {
            pg_query($db, "ROLLBACK");
            // Log error $e->getMessage()
            $this->render('create_game', ['error' => 'Could not save the game. ' . $e->getMessage()]);
        }
    }
}


