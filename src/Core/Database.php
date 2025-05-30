<?php

namespace App\Core;

use ByJG\DbMigration\Exception\DatabaseDoesNotRegistered;
use ByJG\DbMigration\Exception\DatabaseIsIncompleteException;
use ByJG\DbMigration\Exception\DatabaseNotVersionedException;
use ByJG\DbMigration\Exception\InvalidMigrationFile;
use ByJG\DbMigration\Exception\OldVersionSchemaException;
use ByJG\Util\Uri;
use Exception;
use ByJG\DbMigration\Database\PgsqlDatabase;
use ByJG\DbMigration\Migration;
use PgSql\Connection;

class Database
{
    // Connection resource
    private static Connection |null $connection = null;

    /**
     * Establishes or returns existing PostgreSQL connection
     * @return Connection PostgreSQL connection
     * @throws Exception on connection error
     */
    public static function getConnection(): Connection
    {
        if (self::$connection === null) {

            $connStr = sprintf("host=%s port=%s dbname=%s user=%s password=%s", PG_HOST, PG_PORT, PG_DB, PG_USER, PG_PASS);
            $pgConn = pg_connect($connStr);
            if (!$pgConn) {
                $err = error_get_last()['message'] ?? pg_last_error();
                throw new Exception("PostgreSQL connection failed: $err");
            }
            self::$connection = $pgConn;
        }
        return self::$connection;
    }

    /**
     * Closes the connection if open
     */
    public static function closeConnection(): void
    {
        if (self::$connection) {
            pg_close(self::$connection);
            self::$connection = null;
        }
    }

    /**
     * @throws DatabaseNotVersionedException
     * @throws DatabaseDoesNotRegistered
     * @throws DatabaseIsIncompleteException
     * @throws InvalidMigrationFile
     * @throws OldVersionSchemaException
     */
    public static function migrate(): void {
        $postgres_uri = sprintf("pgsql://%s:%s@%s:%s/%s", PG_USER, PG_PASS, PG_HOST, PG_PORT, PG_DB);

        $uri = new Uri($postgres_uri);

        Migration::registerDatabase(PgsqlDatabase::class);

        $migration = new Migration($uri, BASE_PATH . "/src");

        $migration->prepareEnvironment();
        $migration->withTransactionEnabled();

        $migration->createVersion();

        $migration->update();
    }


}
