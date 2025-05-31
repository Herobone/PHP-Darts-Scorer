-- migration: add is_bust_shot and is_turn_ender to score_history

ALTER TABLE score_history
ADD COLUMN is_bust_shot BOOLEAN NOT NULL DEFAULT FALSE,
ADD COLUMN is_turn_ender BOOLEAN NOT NULL DEFAULT FALSE;

