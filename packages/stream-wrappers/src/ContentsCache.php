<?php
/**
 * File-based contents cache.
 *
 * @package tad\StreamWrappers
 */

namespace tad\StreamWrappers;

use function tad\functions\pathJoin;

/**
 * Class ContentsCache
 *
 * @package tad\StreamWrappers
 */
class ContentsCache
{
    /**
     * The root cache seed for this instance.
     *
     * @var string
     */
    protected $cacheSeed;

    /**
     * A list of file misses by this cache, a map of file paths to cached file paths.
     *
     * @var array<string,string>
     */
    protected $misses = [];

    /**
     * A list of file hits by this cache, a map of file paths to cached file paths.
     *
     * @var array<string,string>
     */
    protected $hits = [];

    /**
     * A list mapping the files cached by this cache to the path of the cached file.
     *
     * @var array<string,string>
     */
    protected $cached;

    /**
     * ContentsCache constructor.
     *
     * @param string      $cacheSeed The root cache seed key for this cache instance.
     * @param string|null $cacheDir  The cache root directory to use, defaults to `sys_get_temp_dir()`.
     *
     * @throws StreamWrapperException If the cache directory cannot be created.
     */
    public function __construct($cacheSeed, $cacheDir = null)
    {
        $cacheDir = $cacheDir ?: pathJoin(sys_get_temp_dir(), 'tad_sw_cache');

        if (! is_dir($cacheDir) && (mkdir($cacheDir) && ! is_dir($cacheDir))) {
            throw new StreamWrapperException('Unable to create the file contents cache directory "' . $cacheDir . '".');
        }

        $this->cacheDir  = $cacheDir;
        $this->cacheSeed = $cacheSeed;
    }

    /**
     * Returns the contents of a cached file.
     *
     * @param string $file     The path to the file to fetch the contents for.
     * @param string $hash A method-level hash.
     *
     * @return string|false Either the contents of the cached files of `false` if not found.
     */
    public function getFileContentsFor($file, $hash)
    {
        $cached   = false;
        $filename = $this->getFileName($file, $hash);

        if (file_exists($filename)) {
            $cached            = file_get_contents($filename);
            $this->hits[$file] = $filename;
        } else {
            $this->misses[$file] = $filename;
        }

        return $cached;
    }

    /**
     * Returns the basename of the cached file for a file path and contents.
     *
     * @param string $file     The name of the file to return the name for.
     * @param string       $hash A method level hash.
     *
     * @return string The path to the cached file for the file and contents.
     */
    public function getFileName($file, $hash)
    {
        $filemtime = file_exists($file) ? filemtime($file) : false;

        if ($filemtime === false) {
        	// If file modification time is not readable, then always refresh.
            $filemtime = microtime();
        }

        $basename  = md5($this->cacheSeed . $file . $hash . $filemtime);
        $filename  = pathJoin($this->getCacheDir(), "{$basename}.php");

        return $filename;
    }

    /**
     * Returns the absolute path to the cache root directory.
     *
     * @return string The absolute path to the cache root directory.
     */
    protected function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Sets the contents of a file to be cached under a specific key.
     *
     * @param string $file     The cache key to store the contents for.
     * @param string $data     The contents to cache.
     * @param string $hash     A method level hash
     *
     * @return string|false The written file path or `false` if the file was not stored.
     */
    public function putFileContents($file, $hash, $data)
    {
        $filename = $this->getFileName($file, $hash);

        $this->cached[$file] = $filename;

        $put = file_put_contents($filename, $data);

        if ($put === false) {
            return false;
        }

        return $filename;
    }

    /**
     * Returns the list of file cached by this cache.
     *
     * @return array<string,string> The list of file cached by this cache, a map relating file paths to the cache file.
     */
    public function getCachedFiles()
    {
        return $this->cached;
    }

    /**
     * Returns the list of file misses by this cache.
     *
     * @return array<string,string> The list of file misses by this cache, a map relating file paths to the cache file.
     */
    public function getMisses()
    {
        return $this->misses;
    }

    /**
     * Returns the list of file hits by this cache.
     *
     * @return array<string,string> The list of file hits by this cache, a map relating file paths to the cache file.
     */
    public function getHits()
    {
        return $this->hits;
    }
}
