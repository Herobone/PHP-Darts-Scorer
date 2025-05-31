-- migration: add finish_position to game_players to track completion order

ALTER TABLE game_players
ADD COLUMN finish_position INTEGER DEFAULT NULL,
ADD COLUMN finished_at TIMESTAMPTZ DEFAULT NULL;

-- Add index for finished players ordering
CREATE INDEX IF NOT EXISTS idx_game_players_finish_position ON game_players(game_id, finish_position) WHERE finish_position IS NOT NULL;
