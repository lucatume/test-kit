<?php
/**
 * The base class for the Stream Wrappers.
 *
 * This class is heavily inspired by the antecedent/patchwork package.
 * @author  Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @license GPL-3.0+
 *
 * @package tad\StreamWrappers
 */

namespace tad\StreamWrappers;

use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard as Printer;
use tad\StreamWrappers\Cache\CacheInterface;
use tad\StreamWrappers\Cache\ContentsCache;
use tad\Utils\Traits\WithTestNames;
use function tad\functions\isDebug;

/**
 * Class Stream
 *
 * @package tad\StreamWrappers
 */
abstract class StreamWrapper
{
    use WithTestNames;

    const STREAM_OPEN_FOR_INCLUDE = 128;
    const STAT_MTIME_NUMERIC_OFFSET = 9;
    const STAT_MTIME_ASSOC_OFFSET = 'mtime';

    /**
     * The protocols this stream wrapper will handle.
     *
     * @var array
     */
    protected static $protocols = [ 'file', 'phar' ];
    /**
     * The parser used by instances of the stream wrapper to visit and patch the code.
     *
     * @var Parser
     */
    protected static $parser;

    /**
     * The current run report object.
     *
     * @var Run
     */
    protected static $run;

    /**
     * The traverser used to traverse nodes.
     *
     * @var  NodeTraverserInterface
     */
    protected static $traverser;

    /**
     * The contents cache shared by all stream wrappers.
     *
     * @var ContentsCache
     */
    protected static $patchedContentsCache;

    /**
     * The lexer instance shared by all stream wrappers.
     *
     * @var Lexer\Emulative
     */
    protected static $lexer;

    /*
     * The code printer that will be used to print and format the code.
     *
     * @var PrettyPrinterAbstract
     */
    protected static $printer;

    /**
     * The current stream context.
     *
     * @var resource
     */
    public $context;

    /**
     * The underlying resource for this stream.
     *
     * @var resource
     */
    public $resource;

    /**
     * Whether patch cache is enabled or not.
     *
     * @var bool
     */
    protected $patchCacheEnabled = false;

    /**
     * The current object cache instance.
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Returns the current shared instance of the Run object.
     *
     * @return Run The currently shared instance of the run log.
     */
    protected static function runLog()
    {
        if (! static::$run instanceof Run) {
            static::$run = new Run;
        }

        return static::$run;
    }

    /**
     * Opens the stream, this method is called by any function opening a file to read or write.
     *
     * @param string $path       The path to the file to open.
     * @param string $mode       The mode used to open the file, see the `fopen` function.
     * @param int    $options    The stream open bit mask.
     * @param string $openedPath The full path to the effectively opened file, set by reference.
     *
     * @return bool Whether the stream opening was successful or not.
     */
    public function stream_open($path, $mode, $options, &$openedPath)
    {
        static::unwrap();
        $including = (bool) ( $options & self::STREAM_OPEN_FOR_INCLUDE );
        if ($including && $this->shouldTransform($path)) {
            $this->resource = $this->openAndTransform($path);
            self::wrap();

            return true;
        }
        if (isset($this->context)) {
            $this->resource = fopen($path, $mode, $options, $this->context);
        } else {
            $this->resource = fopen($path, $mode, $options);
        }
        static::wrap();

        return $this->resource !== false;
    }

    /**
     * Restores the default wrappers for the supported protocols and stops wrapping files.
     */
    public static function unwrap()
    {
        foreach (static::$protocols as $protocol) {
            set_error_handler(function () {
            });
            stream_wrapper_restore($protocol);
            restore_error_handler();
        }
    }

    /**
     * Checks whether this file should be manipulated or not.
     *
     * @param string $path The path to the file to manipulate.
     *
     * @return bool Whether this file should be manipulated or not.
     */
    abstract protected function shouldTransform($path);

    /**
     * Transforms the file and opens it.
     *
     * @param string $path The path to the file to open and transform.
     */
    protected function openAndTransform($path)
    {
        $contents = file_get_contents($path, true);
        $contents = $this->patch($contents);

        $resource = fopen('php://memory', 'rb+');
        fwrite($resource, $contents);
        rewind($resource);

        return $resource;
    }

    /**
     * Replaces the default stream wrapper for the supported protocols and starts wrapping.
     */
    public static function wrap()
    {
        foreach (static::$protocols as $protocol) {
            stream_wrapper_unregister($protocol);
            stream_wrapper_register($protocol, static::class);
        }
    }

    /**
     * Closes the stream.
     *
     * @return bool Whether the stream closing was successful or not.
     */
    public function stream_close()
    {
        return fclose($this->resource);
    }

    /**
     * Checks whether the stream is at the end or not.
     *
     * @return bool Whether the stream is at the end or not.
     */
    public function stream_eof()
    {
        return feof($this->resource);
    }

    /**
     * Flushes the stream output.
     *
     * @return bool Whether the data was successfully flushed or not.
     */
    public function stream_flush()
    {
        return fflush($this->resource);
    }

    /**
     * Read from the stream.
     *
     * @param int $count How many bytes of data should be read from the stream at the most.
     *
     * @return false|string The requested number of chars, or less if less are available, or `false` if no more data
     *                      is available.
     */
    public function stream_read($count)
    {
        return fread($this->resource, $count);
    }

    /**
     * Moves to a specific location in the stream.
     *
     * @param int $offset The offset to start seeking from.
     * @param int $whence One of the `SEEK_` constants values.
     *
     * @return bool Whether the move was successful or not.
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return fseek($this->resource, $offset, $whence) === 0;
    }

    /**
     * Retrieves information about a file resource.
     *
     * @return array The resource information.
     */
    public function stream_stat()
    {
        $result = fstat($this->resource);
        if ($result) {
            $result[ self::STAT_MTIME_ASSOC_OFFSET ] ++;
            $result[ self::STAT_MTIME_NUMERIC_OFFSET ] ++;
        }

        return $result;
    }

    /**
     * Returns the current position in the stream.
     *
     * @return false|int The current position in the stream or `false` if the position is at the end of the file.
     */
    public function stream_tell()
    {
        return ftell($this->resource);
    }

    /**
     * Retrieves information about a file.
     *
     * @param string $path  The path to the file.
     * @param int    $flags A bit mask of options.
     *
     * @return array|false|null The stat elements or `false` on failure to stat.
     */
    public function url_stat($path, $flags)
    {
        static::unwrap();
        set_error_handler(function () {
        });
        try {
            $result = stat($path);
        } catch (\Exception $e) {
            $result = null;
        }
        restore_error_handler();
        static::wrap();
        if ($result) {
            $result[ self::STAT_MTIME_ASSOC_OFFSET ] ++;
            $result[ self::STAT_MTIME_NUMERIC_OFFSET ] ++;
        }

        return $result;
    }

    /**
     * Closes a directory handle.
     *
     * @return bool Whether the closing was successful or not.
     */
    public function dir_closedir()
    {
        closedir($this->resource);

        return true;
    }

    /**
     * Opens a directory handle.
     *
     * @param string $path    The path to the directory to open.
     * @param int    $options A bit mask of options.
     *
     * @return bool Whether the opening was successful or not.
     */
    public function dir_opendir($path, $options)
    {
        static::unwrap();
        if (isset($this->context)) {
            $this->resource = opendir($path, $this->context);
        } else {
            $this->resource = opendir($path);
        }
        static::wrap();

        return $this->resource !== false;
    }

    /**
     * Reads the next entry from a directory handle.
     *
     * @return false|string Either the next entry or `false` if there are no more entries.
     */
    public function dir_readdir()
    {
        return readdir($this->resource);
    }

    /**
     * Moves the directory handle pointer back to the first index.
     *
     * @return bool Whether the rewinding was successful or not.
     */
    public function dir_rewinddir()
    {
        rewinddir($this->resource);

        return true;
    }

    /**
     * Creates a new directory.
     *
     * @param string $path    The path to the directory to create.
     * @param int    $mode    The value passed to `mkdir`.
     * @param int    $options A bit mask of options.
     *
     * @return bool Whether the directory creation was succesful or not.
     */
    public function mkdir($path, $mode, $options)
    {
        static::unwrap();
        if (isset($this->context)) {
            $result = mkdir($path, $mode, $options, $this->context);
        } else {
            $result = mkdir($path, $mode, $options);
        }
        static::wrap();

        return $result;
    }

    /**
     * Renames a file or a directory.
     *
     * @param string $path_from The old file name.
     * @param string $path_to   The new file name.
     *
     * @return bool Whether the renaming was successful or not.
     */
    public function rename($path_from, $path_to)
    {
        static::unwrap();
        if (isset($this->context)) {
            $result = rename($path_from, $path_to, $this->context);
        } else {
            $result = rename($path_from, $path_to);
        }
        static::wrap();

        return $result;
    }

    /**
     * Removes a directory.
     *
     * @param string $path    The path to the directory to open.
     * @param int    $options A bit mask of options.
     *
     * @return bool Whether the directory was removed or not.
     */
    public function rmdir($path, $options)
    {
        static::unwrap();
        if (isset($this->context)) {
            $result = rmdir($path, $this->context);
        } else {
            $result = rmdir($path);
        }
        static::wrap();

        return $result;
    }

    /**
     * Retrieves the underlying resource from a stream.
     *
     * @param int $cast_as On of the `STREAM_CAST_` constant options.
     *
     * @return mixed|false The underlying resource used by the wrapper, or `false`.
     */
    public function stream_cast($cast_as)
    {
        return $this->resource;
    }

    /**
     * Advisory file locking.
     *
     * @param int $operation One of the `LOCK_` constants.
     *
     * @return bool Whether the file was locked or not.
     */
    public function stream_lock($operation)
    {
        if ($operation === '0' || $operation === 0) {
            $operation = LOCK_EX;
        }

        return flock($this->resource, $operation);
    }

    /**
     * Changes the stream options.
     *
     * @param int   $option One of the `STREAM_OPTION_` constants.
     * @param mixed $arg1   First argument for the option change.
     * @param mixed $arg2   Second argument for the option change.
     *
     * @return bool Whether the option update was successful or not.
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                return stream_set_blocking($this->resource, $arg1);
            case STREAM_OPTION_READ_TIMEOUT:
                return stream_set_timeout($this->resource, $arg1, $arg2);
            case STREAM_OPTION_WRITE_BUFFER:
                return stream_set_write_buffer($this->resource, $arg1);
            case STREAM_OPTION_READ_BUFFER:
                return stream_set_read_buffer($this->resource, $arg1);
        }
    }

    /**
     * Writes to the file.
     *
     * @param mixed $data The data to write to file.
     *
     * @return false|int The amount of written bytes, or `false` if the writing failed.
     */
    public function stream_write($data)
    {
        return fwrite($this->resource, $data);
    }

    /**
     * Removes a file.
     *
     * @param string $path The path to the file to remove.
     *
     * @return bool Whether the file removal was successful or not.
     */
    public function unlink($path)
    {
        static::unwrap();
        if (isset($this->context)) {
            $result = unlink($path, $this->context);
        } else {
            $result = unlink($path);
        }
        static::wrap();

        return $result;
    }

    /**
     * Changes the stream metadata.
     *
     * @param string $path   The path or URL to set the metadata for.
     * @param int    $option One of the `STREAM_META_` options.
     * @param mixed  $value  The value for the option.
     *
     * @return bool Whether the metadata update was successful or not.
     */
    public function stream_metadata($path, $option, $value)
    {
        static::unwrap();
        switch ($option) {
            case STREAM_META_TOUCH:
                if (empty($value)) {
                    $result = touch($path);
                } else {
                    $result = touch($path, $value[0], $value[1]);
                }
                break;
            case STREAM_META_OWNER_NAME:
            case STREAM_META_OWNER:
                $result = chown($path, $value);
                break;
            case STREAM_META_GROUP_NAME:
            case STREAM_META_GROUP:
                $result = chgrp($path, $value);
                break;
            case STREAM_META_ACCESS:
                $result = chmod($path, $value);
                break;
        }
        static::wrap();

        return $result;
    }

    /**
     * Truncates the stream.
     *
     * @param int $new_size The new byte size for the stream.
     *
     * @return bool Whether the truncation was successful or not.
     */
    public function stream_truncate($new_size)
    {
        return ftruncate($this->resource, $new_size);
    }

    /**
     * Returns the name of the global variable used by the stream wrapper/.
     *
     * @return string
     */
    abstract public function getGlobalVarName();

    /**
     * Sets up the node traverser, usually to add the patches required by the stream wrapper.
     *
     * @param NodeTraverserInterface $traverser The traverser to setup.j:w
     */
    abstract protected function setupTraverser(NodeTraverserInterface $traverser);

    /**
     * Runs the stream wrapper on a file.
     *
     * @param string $file The path to the file to wrap and upon which the stream wrapper should run.
     *
     * @return Run The run result.
     */
    abstract public function run($file);

    /**
     * Patches the contents of a file.
     *
     * @param string $contents The file contents to patch.
     *
     * @return string The patched file contents.
     */
    protected function patch($contents)
    {
        $patchedContents  = false;
        $file = static::$run->getLastLoadedFile();
        $hash = static::$run->hash();

        if (!isDebug() && $this->patchCacheEnabled) {
            $patchedContents = static::$patchedContentsCache->getFileContentsFor($file, $hash);
        }

        if (false === $patchedContents) {
            $traversed       = static::$traverser->traverse(static::$parser->parse($contents));
            $oldStmts = static::$parser->parse($contents);
            $oldTokens = static::$lexer->getTokens();
            $patchedContents = static::$printer->printFormatPreserving($traversed, $oldStmts, $oldTokens);

            static::$patchedContentsCache->putFileContents($file, $hash, $patchedContents);
        }

        static::$run->setLastPatchedFileCachePath(static::$patchedContentsCache->getFileName($file, $hash));
        static::$run->setLastLoadedFileCode($patchedContents);

        return $patchedContents;
    }

    /**
     * Initializes the properties shared by a group of stream wrappers in the context of a run.
     *
     * @throws StreamWrapperException If the cache object cannot build its instance.
     */
    protected function initSharedProps()
    {
        $this->initPhpParser();
        static::$run       = static::$run instanceof Run ? static::$run : new Run();
        static::$printer   = new Printer();
        static::$traverser = new NodeTraverser();
        $this->initContentsCache();
        $this->setupTraverser(static::$traverser);
    }

    /**
     * This method is called on replaced functions to return the replaced value.
     *
     *
     * @param string $fn      The function fully qualified name.
     * @param mixed  ...$args The function call arguments.
     *
     * @throws StreamWrapperException If no replacement was defined for the function.
     */
    public function callFunc($fn, ...$args)
    {
        return static::$run->getFunctionReplacement($fn)(...$args);
    }

    /**
     * Inits the PHP parser and lexer instances all stream wrapper instances will share in the context of a run.
     */
    protected function initPhpParser()
    {
        static::$lexer = new Lexer\Emulative([
            'usedAttributes' => [
                'comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'
            ]
        ]);

        static::$parser = new Parser\Php7(static::$lexer);
    }

    protected function initContentsCache()
    {
        if ($this->cache instanceof CacheInterface) {
            static::$patchedContentsCache = $this->cache;

            return;
        }

        static::$patchedContentsCache = new ContentsCache(static::class);
    }
}
