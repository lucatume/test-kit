<?php
/**
 * The main Beaker class and factory.
 *
 * @package lucatume\Beaker
 */

namespace lucatume\Beaker;

use lucatume\Beaker\Db\DbFactory;
use lucatume\Beaker\Db\DbInterface;
use lucatume\Beaker\Traits\WithGlobalState;
use lucatume\StreamWrappers\FileStreamWrapperInterface;
use lucatume\StreamWrappers\SandboxStreamWrapper;
use lucatume\StreamWrappers\StreamWrapperException;
use function lucatume\functions\debug;

class Beaker
{
    use WithGlobalState;

    /**
     * An array defining the HTTP request methods supported by Beaker.
     *
     * @var array
     */
    protected static $supportedMethods = ['GET'];

    /**
     * The current file stream wrapper.
     *
     * @var FileStreamWrapperInterface
     */
    protected $streamWrapper;

    /**
     * The installation root directory, the directory that contains the `wp-load.php` file.
     *
     * @var string
     */
    protected $rootDir;

    /**
     * Backup of the original `$_SERVER` global contents.
     *
     * @var array
     */
    protected $serverBackup;
    /**
     * A file router instance, to translate URIs into file paths.
     *
     * @var FileRouter
     */
    protected $router;

    /**
     * The database controller for this beaker.
     *
     * @var Db\DbInterface
     */
    protected $db;

    /***
     * Beaker constructor.
     *
     * @param FileStreamWrapperInterface $streamWrapper The file stream wrapper that will be used to include the file.
     * @param FileRouter $router A router instance, in charge of translating request URIs into file paths.
     * @param DbInterface $db An instance of the database abstraction object.
     */
    public function __construct(FileStreamWrapperInterface $streamWrapper, FileRouter $router, DbInterface $db)
    {
        $this->streamWrapper = $streamWrapper;
        $this->rootDir = $router->getRootDir();
        $this->streamWrapper
            ->usePatchCache(true)
            ->setWhitelist([$this->rootDir]);
        $this->router = $router;
        $this->db = $db;
    }

    /**
     * Builds a beaker from a WordPress installation root directory.
     *
     * @param string $rootDir The path, absolute or relative to the current working directory, to the
     *
     *
     * @return static A Beaker to start testing.
     */
    public static function fromDir(string $rootDir): Beaker
    {
        $dbFactory = new DbFactory();
        return new static(new SandboxStreamWrapper, new FileRouter($rootDir), $dbFactory->forDir($rootDir));
    }

    /**
     * Sends a GET request to a path on the WordPress directory.
     *
     * @param string $uri The URI, relative to the WordPress root directory; permalinks are not supported (yet).
     * @param array $params An array of parameters for the request.
     *
     * @return Response The request response.
     *
     * @throws StreamWrapperException If there's an issue wrapping the directory files.
     * @throws \RuntimeException If there's an issue building and processing the response.
     */
    public function get(string $uri, array $params = [])
    {
        debug('Taking global state snapshot', 'Beaker');

        $globalState = $this->globalStateSnapshotCreate();

        $this->setupRequest($params, 'GET');
        $this->setupServer();
        $file = $this->router->getFileForUri($uri);

        try {
            $run = $this->streamWrapper->loadFile($file);
        } finally {
            $this->globalStateRestore($globalState);
        }

        return Response::fromFileWrapperRunResult($run);
    }

    /**
     * Sets up `$_REQUEST`, and the relevant request super-global, according to the current request parameters.
     *
     * @param array $params An array of parameters for the current request.
     * @param string $method The HTTP request method, one of `GET`
     */
    protected function setupRequest(array $params = [], string $method = 'GET')
    {
        $method = strtoupper($method);

        if (!in_array($method, static::$supportedMethods, true)) {
            $method = 'GET';
        }

        foreach ($params as $key => $value) {
            ${"_{$method}"}[$key] = $value;
            $_REQUEST[$key] = $value;
        }
    }

    /**
     * Sets up the `$_SERVER` super-global to emulate the request.
     */
    protected function setupServer()
    {
        $serverVars = $this->router->getServerVarsForUri();
        $_SERVER = array_merge($_SERVER, $serverVars);
    }
}
