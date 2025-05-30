-- migration: create games, participants, and score history

-- games table: each game belongs to one user (player), only one active game per user
CREATE TABLE IF NOT EXISTS games (
    id            SERIAL PRIMARY KEY,
    owner_id      UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    is_active     BOOLEAN NOT NULL DEFAULT FALSE,
    start_score   INT NOT NULL DEFAULT 501,
    single_in     BOOLEAN NOT NULL DEFAULT TRUE, -- True if single in is allowed
    double_in     BOOLEAN NOT NULL DEFAULT FALSE, -- True if double in is required
    single_out    BOOLEAN NOT NULL DEFAULT TRUE, -- True if single out is allowed
    double_out    BOOLEAN NOT NULL DEFAULT FALSE, -- True if double out is required
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- auto-update updated_at
CREATE TRIGGER set_games_timestamp
    BEFORE UPDATE ON games
    FOR EACH ROW
  EXECUTE FUNCTION trigger_set_timestamp();

-- enforce one active game per user
CREATE UNIQUE INDEX IF NOT EXISTS uniq_owner_active_game ON games(owner_id) WHERE is_active;

-- participants in a game (unique per game)
CREATE TABLE IF NOT EXISTS game_players (
    id           SERIAL PRIMARY KEY,
    game_id      INT NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    player_name  TEXT NOT NULL,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- score history entries for each participant
CREATE TABLE IF NOT EXISTS score_history (
    id              SERIAL PRIMARY KEY,
    game_player_id  INT NOT NULL REFERENCES game_players(id) ON DELETE CASCADE,
    score           INT NOT NULL,
    recorded_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

