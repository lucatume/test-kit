<?php
/**
 * An Db implementation to support Sqlite3 databases.
 *
 * @package lucatume\Beaker\Db
 */

namespace lucatume\Beaker\Db;

use function lucatume\functions\pathJoin;

/**
 * Class Sqlite3Db
 *
 * @package lucatume\Beaker\Db
 */
class Sqlite3Db implements DbInterface
{
    /**
     * The database wrapper PDO handler.
     *
     * @var \PDO
     */
    protected $pdo;
    /**
     * The absolute 
     * @var string
     */
    protected $dir;

    /**
     * Sqlite3Db constructor.
     */
    public function __construct(string $dir)
    {
        $this->dir = $dir;
        $this->pdo = new \PDO('sqlite3:memory:');

        $GLOBALS['wpdb'] = new WP_SQLite_DB\wpsqlitedb();
        $sqliteDropInDestPath = pathJoin($dir, 'wp-content');
    }
}
