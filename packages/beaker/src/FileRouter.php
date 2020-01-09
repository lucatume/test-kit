<?php
/**
 * Routes URI requests to the correct file.
 *
 * @package lucatume\Beaker
 */

namespace lucatume\Beaker;

use Cache\Adapter\PHPArray\ArrayCachePool;
use lucatume\Beaker\Traits\WithCache;
use Psr\SimpleCache\CacheInterface;
use function lucatume\functions\pathJoin;
use function lucatume\functions\pathResolve;

/**
 * Class FileRouter
 *
 * @package lucatume\Beaker
 */
class FileRouter
{
    use WithCache;

    /** * The absolute path to the file router root directory.
     *
     * @var string
     */
    protected $rootDir;

    /**
     * FileRouter constructor.
     *
     * @param string $rootDir The absolute path to the installation root directory.
     *
     * @param CacheInterface $cache A Cache implementation.
     */
    public function __construct(string $rootDir, CacheInterface $cache = null)
    {
        if (!is_dir($rootDir)) {
            throw new \InvalidArgumentException('Root directory is not a directory.');
        }

        if (!file_exists(pathJoin($rootDir, 'wp-load.php'))) {
            throw new \InvalidArgumentException('Root directory does not contain the wp-load.php file.');
        }

        $this->rootDir = pathResolve($rootDir,getcwd());
        if ($this->rootDir === false) {
            throw new \RuntimeException('Root directory ("' . $rootDir . '") is not valid.');
        }
        $this->cache = $cache ?? new ArrayCachePool(100);
    }

    /**
     * Converts a URI to a real file path, if possible.
     *
     * @param string $uri The URI that should be converted to file.
     *
     * @return array<string,string> The URI resolved to a set of `$_SERVER` vars like `PATH_INFO`, `REQUEST_URI` etc.
     */
    public function getServerVarsForUri(string $uri = '/'): array
    {
        $originalUri = $uri;
        $uri = $this->normalizeUri($uri);
        $key = $this->cacheKey(__METHOD__, $uri);
        $serverVars = $this->cacheSafeGet($key, false);

        if (is_array($serverVars)) {
            return $serverVars;
        }

        $serverVars = $this->buildServerVars($originalUri, $this->getFileForUri($uri));

        $this->cacheSafeSet($key, $serverVars);

        return $serverVars;
    }

    /**
     * Returns the `$_SERVER` vars for a resolved file.
     *
     * @param string $uri The originally requested URI.
     * @param string $file The absolute path to the resolved file.
     *
     * @return array<string,string> An array of `$_SERVER` vars.
     */
    protected function buildServerVars(string $uri, string $file): array
    {
        $fileBasename = '/' . basename($file);
        $pathInfo = rtrim('/' . ltrim($fileBasename, '/'), '/');
        if (strpos($pathInfo, $fileBasename) === 0) {
            $pattern = '#^' . preg_quote($fileBasename, '#') . '#';
            $pathInfo = preg_replace($pattern, '', $pathInfo);
        }

        return [
            'DOCUMENT_ROOT' => $this->rootDir,
            'PATH_INFO' => $pathInfo,
            'REQUEST_URI' => $uri,
            'PHP_SELF' => '/' . trim(str_replace($this->rootDir, '', $file), '/'),
        ];
    }

    /**
     * Returns the path to the file to load for a URI.
     *
     * @param string $uri The URI to resolve to file.
     * @return string The file corresponding to the specified URI.
     */
    public function getFileForUri(string $uri): string
    {
        $uri = $this->normalizeUri($uri);
        $key = $this->cacheKey(__METHOD__, $uri);
        $file = $this->cacheSafeGet($key,false) ;

        if ($file !== false) {
            return $file;
        }

        $joined = pathJoin($this->rootDir, $uri);
        if (is_file($joined)) {
            $file = $joined;
        } elseif (is_file($indexFile = pathJoin($joined, 'index.php'))) {
            $file = $indexFile;
        } else {
            $file = pathJoin($this->rootDir, 'index.php');
        }

        $this->cacheSafeSet($key,$file);

        return $file;
    }

    /**
     * Normalizes the URI.
     *
     * @param string $uri The URI to normalized.
     * @return string The normalized URI.
     */
    protected function normalizeUri(string $uri): string
    {
        return rtrim($uri, '/') . '/';
    }

    /**
     * Returns the file router root directory absolute path.
     *
     * @return string The file router root directory absolute path.
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }
}
