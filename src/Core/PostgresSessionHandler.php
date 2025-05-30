<?php

namespace App\Core;

use Exception;
use PgSql\Connection;
use SessionHandlerInterface;

class PostgresSessionHandler implements SessionHandlerInterface
{
    private Connection $db;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function open(string $path, string $name): bool
    {
        // No action necessary because connection is established in constructor
        return true;
    }

    public function close(): bool
    {
        // Let PHP handle connection close
        return true;
    }

    public function read(string $id): string
    {
        $result = pg_query_params($this->db, 'SELECT data FROM sessions WHERE id = $1', [$id]);
        if ($result === false) {
            return '';
        }
        $row = pg_fetch_assoc($result);
        return $row['data'] ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $sql = "INSERT INTO sessions (id, data, last_updated) VALUES ($1, $2, NOW())"
             . " ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_updated = NOW()";
        $result = pg_query_params($this->db, $sql, [$id, $data]);
        return $result !== false;
    }

    public function destroy(string $id): bool
    {
        $result = pg_query_params($this->db, 'DELETE FROM sessions WHERE id = $1', [$id]);
        return $result !== false;
    }

    public function gc(int $max_lifetime): int|false
    {
        $threshold = time() - $max_lifetime;
        $ts = date('Y-m-d H:i:s', $threshold);
        $result = pg_query_params($this->db, 'DELETE FROM sessions WHERE last_updated < $1', [$ts]);
        if ($result === false) {
            return false;
        }
        return pg_affected_rows($result);
    }
}
