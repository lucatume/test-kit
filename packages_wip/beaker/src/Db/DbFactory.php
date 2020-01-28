<?php
/**
 * Handles the creation of test database instances.
 *
 * @package lucatume\Beaker
 */

namespace lucatume\Beaker\Db;

use function lucatume\functions\pathJoin;
use function lucatume\functions\pathResolve;

/**
 * Class DbFactory
 *
 * @package lucatume\Beaker
 */
class DbFactory
{
    const TYPE_SQLITE3 = 'sqlite3';

    /**
     * Builds a database abstraction for a specific directory.
     *
     * @param string $dir The path to the directory to create the database instance for.
     * @param string $type The type of database to build the for the installation, of the `TYPE_` constants.
     * @param array $args Additional arguments to customize the built database, vary depending on the type of built
     *                    database type.
     *
     * @return DbInterface A database abstraction implementation.
     */
    public function forDir(string $dir, string $type = 'sqlite3', ...$args): DbInterface
    {
        $dir = pathResolve($dir);

        $buildMap = [
            static::TYPE_SQLITE3 => function($dir, ...$args){
                return $this->buildSqlite3Db($dir);
            }
        ];

        if (!array_key_exists($type, $buildMap)) {
            throw new \InvalidArgumentException('Db type "' . $type . '" is not supported.');
        }

        $db = call_user_func($buildMap[$type], $dir, ...$args);

        $db->install();

        return $db;
    }

    /**
     * Builds and returns a Sqlite3 PDO based database wrapper.
     *
     * @param string $dir The absolute path to the installation root directory.
     *
     * @return DbInterface The Sqlite3 based database wrapper.
     */
    public function buildSqlite3Db(string $dir): DbInterface
    {
        $db = new Sqlite3Db($dir);

        return $db;
    }
}
