-- migration: create sessions table for PHP session storage
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT NOT NULL,
    last_updated TIMESTAMP NOT NULL DEFAULT NOW()
);

