<?php
/**
 * The API that contents cache implementation should provide.
 */

namespace lucatume\StreamWrappers\Cache;

/**
 * Class ContentsCache
 *
 * @package lucatume\StreamWrappers\Cache
 */
interface CacheInterface
{
    /**
     * Returns the contents of a cached file.
     *
     * @param string $file The path to the file to fetch the contents for.
     * @param string $hash A method-level hash.
     *
     * @return string|false Either the contents of the cached files of `false` if not found.
     */
    public function getFileContentsFor($file, $hash);

    /**
     * Returns the basename of the cached file for a file path and contents.
     *
     * @param string $file The name of the file to return the name for.
     * @param string $hash A method level hash.
     *
     * @return string The path to the cached file for the file and contents.
     */
    public function getFileName($file, $hash);

    /**
     * Sets the contents of a file to be cached under a specific key.
     *
     * @param string $file The cache key to store the contents for.
     * @param string $data The contents to cache.
     * @param string $hash A method level hash
     *
     * @return string|false The written file path or `false` if the file was not stored.
     */
    public function putFileContents($file, $hash, $data);

    /**
     * Returns the list of file cached by this cache.
     *
     * @return array<string,string> The list of file cached by this cache, a map relating file paths to the cache file.
     */
    public function getCachedFiles();

    /**
     * Returns the list of file misses by this cache.
     *
     * @return array<string,string> The list of file misses by this cache, a map relating file paths to the cache file.
     */
    public function getMisses();

    /**
     * Returns the list of file hits by this cache.
     *
     * @return array<string,string> The list of file hits by this cache, a map relating file paths to the cache file.
     */
    public function getHits();

    /**
     * Returns the path to the last patched file.
     *
     * @return string The path to the last patched file.
     */
    public function getLastPatchedFilePath();
}
