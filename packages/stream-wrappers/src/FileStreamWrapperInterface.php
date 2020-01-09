<?php
/**
 * The API exposed by a file stream wrapper.
 *
 * @since TBD
 */

namespace lucatume\StreamWrappers;


/**
 * Class SandboxStreamWrapper
 *
 * @package lucatume\StreamWrappers
 */
interface FileStreamWrapperInterface
{
    /**
     * Loads a file, and with it any file that might be consequentially loaded, in a "sandbox".
     *
     * @param string $file The path to the file to load.
     *
     * @return Run The stream wrapper run result.
     *
     * @throws StreamWrapperException If the file does not exist or there's an issue registering the wrapper.
     */
    public function loadFile($file);

    /**
     * Replaces a function to return the specified value in wrapped files.
     *
     * @param string $functionName The fully-qualified name of the function to replace.
     * @param mixed $returnValue The value that will be returned when the function is called.
     */
    public function replaceFn($functionName, $returnValue);

    /**
     * Enables or disables the patched contents cache.
     *
     * @param bool $usePatchCache Whether patched cache contents should be enabled or not.
     *
     * @return FileStreamWrapperInterface This, for chaining.
     */
    public function usePatchCache(bool $usePatchCache): FileStreamWrapperInterface;

    /**
     * Sets the list of directories or files this stream wrapper should wrap.
     *
     * @param array $whiteList The list of directories or files this stream wrapper should wrap.
     *
     * @return FileStreamWrapperInterface This, for chaining.
     */
    public function setWhitelist(array $whiteList);
}
