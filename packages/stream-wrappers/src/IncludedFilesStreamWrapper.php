<?php
/**
 * Wraps a file to list the files that file included or required.
 *
 * @package tad\StreamWrappers
 */

namespace tad\StreamWrappers;

use function tad\functions\pathResolve;

/**
 * Class IncludedFilesStreamWrapper
 *
 * @package tad\StreamWrappers
 */
class IncludedFilesStreamWrapper extends SandboxStreamWrapper
{
    /**
     * A string of code that will be inserted right after the file opening PHP tag.
     *
     * @var string|null
     */
    protected static $prefixCode;

    /**
     * The file that is being included for inspection.
     *
     * @var string
     */
    protected static $targetFile;

    /**
     * Overrides the sandbox wrapper method to only log included files, not actually include them.
     *
     * {@inheritDoc}
     */
    public function includeFileOnce($file, $cwd, array $definedVars = [])
    {
        $filename = pathResolve($file, $cwd) ?: $file;

        static::$run->addIncludedFile($filename, false);

        return true;
    }

    /**
     * Overrides the sandbox wrapper method to only log included files, not actually include them.
     *
     * {@inheritDoc}
     */
    public function includeFile($file, $cwd, array $definedVars = [], $once = false)
    {
        $filename = pathResolve($file, $cwd) ?: $file;

        static::$run->addIncludedFile($filename, $once);

        return true;
    }

    /**
     * Returns a list of the files included by this file.
     *
     * Files are not included and any file that, due to flow, would not be included will not be included in the list.
     *
     * @param string      $file       The file to include.
     * @param string|null $prefixCode The code to prefix to the file contents, right after the opening PHP tag.
     *
     * @return array<string> A list of absolute path names included by this file.
     *
     * @throws StreamWrapperException If the file cannot be found.
     */
    public function getIncludedFiles($file, $prefixCode = null)
    {
        return array_column($this->run($file, $prefixCode)->getIncludedFiles(), 'file');
    }

    /**
     * Returns a list of the files included by this file.
     *
     * Files are not included and any file that, due to flow, would not be included will not be included in the list.
     *
     * @param string      $file       The file to include.
     * @param string|null $prefixCode The code to prefix to the file contents, right after the opening PHP tag.
     *
     * @return Run The stream wrapper run result.
     *
     * @throws StreamWrapperException If the file cannot be found.
     */
    public function run($file, $prefixCode = null)
    {
        static::$prefixCode = $prefixCode;
        $this->initSharedProps();

        if (! file_exists($file)) {
            throw new StreamWrapperException('File "' . $file . '" does not exist.');
        }

        $GLOBALS[ $this->getGlobalVarName() ] = $this;

        static::$targetFile = pathResolve($file);
        static::$run->setLastLoadedFile($file);

        static::wrap();
        include $file;
        static::unwrap();
	    $thisRun = static::$run;
	    static::$run = null;

	    return $thisRun;
    }

	/**
     * Overrides the parent method to handle prefix code.
     *
     * @param string $contents The contents to patch.
     *
     * @return string The modified contents.
     *
     * @throws StreamWrapperException If the included files code could not be prefixed.
     */
    protected function patch($contents)
    {
        $contents = parent::patch($contents);

        if (!empty(static::$prefixCode)) {
            $contents = preg_replace(
                '/^<\\?php/',
                '<?php ' . static::$prefixCode,
                $contents,
                1
            );

            if (empty($contents)) {
                throw new StreamWrapperException('Could not prefix included file code');
            }
        }

        return $contents;
    }

    /**
     * Whether a path should be transformed or not.
     *
     * @return bool Whether the path to transform is the current file or not.
     */
    protected function shouldTransform($path)
    {
        return pathResolve($path) === static::$targetFile;
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalVarName()
    {
        return 'tad_sw_includes';
    }
}
