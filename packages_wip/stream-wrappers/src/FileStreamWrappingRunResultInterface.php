<?php
/**
 * ${CARET}
 *
 * @since TBD
 */

namespace lucatume\StreamWrappers;


/**
 * Class Run
 *
 * @package lucatume\StreamWrappers
 */
interface FileStreamWrappingRunResultInterface
{
    /**
     * Returns the code or message the file exited with.
     *
     * @return int|string|false The code or message the file called `die` or `exit` with, or `false` if the file
     *                          did not `exit` or `die`.
     *
     * @see Run::fileDidExit() to discriminate between a `null` due to a file not exiting or just a `null` `die` or
     * `exit` parameter.
     */
    public function getExitCodeOrMessage();

    /**
     * Checks whether the file inclusion terminated calling `exit` or `die` or not.
     *
     * @return bool Whether the file inclusion terminated calling `exit` or `die` or not.
     */
    public function fileDidExit();

    /**
     * Returns an associative list of headers sent by the file during load.
     *
     * @return array An associative list of headers sent by the file during load.
     */
    public function getSentHeaders();

    /**
     * Returns the HTTP response code sent either by an `HTTP` header, or by the last header that defined a response
     * code.
     *
     * @return int|string The last sent response code, or `200` if no response code was returned.
     */
    public function getSentResponseCode();

    /**
     * Returns the output produced during the stream wrapper run.
     *
     * @return string The output produced during the stream wrapper run.
     */
    public function getOutput();

    /**
     * Returns a list of files included during the stream wrapper run.
     *
     * @return array A list of files included or required during the stream wrapper run.
     */
    public function getIncludedFiles();

    public function hash();
}
