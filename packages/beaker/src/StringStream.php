<?php
/**
 * The stream interface, implemented on a string input.
 *
 * @package lucatume\Beaker
 */

namespace lucatume\Beaker;

use Psr\Http\Message\StreamInterface;

/**
 * Class StringStream
 *
 * @package lucatume\Beaker
 */
class StringStream implements StreamInterface
{
    /**
     * The output string the stream is built from.
     *
     * @var string
     */
    protected $output;

    /**
     * The memory stream built on the output.
     *
     * @var false|resource
     */
    protected $stream;

    /**
     * StringStream constructor.
     *
     * @param string $output The output to build the stream from.
     */
    public function __construct(string $output = '')
    {
        $this->output = $output;
        $this->stream = fopen('php://memory', 'rb+');
        fwrite($this->stream, $this->output);
        rewind($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->output;
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        fclose($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        $this->ensureResource();
        $resource = $this->stream;
        unset($this->stream);
        return $resource;
    }

    /**
     * @inheritDoc
     */
    public function tell()
    {
        if (empty($this->output)) {
            return 0;
        }

        $this->ensureResource();
        $tell = ftell($this->stream);

        if ($tell === false) {
            throw new \RuntimeException('Cannot tell the current stream position.');
        }

        return $tell;
    }

    /**
     * @inheritDoc
     */
    public function eof()
    {
        if (empty($this->output)) {
            return true;
        }

        $this->ensureResource();
        return feof($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (empty($this->output)) {
            return 0;
        }

        $this->ensureResource();
        $seek = fseek($this->stream, $offset, $whence);

        if (-1 === $seek) {
            throw new \RuntimeException('Cannot seek the current stream position.');
        }

        return $seek;
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->ensureResource();
        $rewind = rewind($this->stream);

        if ($rewind === false) {
            throw new \RuntimeException('Cannot rewind the stream.');
        }

        return $rewind;
    }

    /**
     * @inheritDoc
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function write($string)
    {
        throw new \RuntimeException('Response stream is not writable!');
    }

    /**
     * @inheritDoc
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getContents()
    {
        if (empty($this->output)) {
            return '';
        }

        $this->ensureResource();
        return $this->read($this->getSize());
    }

    /**
     * @inheritDoc
     */
    public function read($length)
    {
        if (empty($this->output)) {
            return '';
        }

        $this->ensureResource();
        $read = fread($this->stream, $length);

        if ($read === false) {
            throw new \RuntimeException('Cannot read from stream.');
        }

        return $read;
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        if (empty($this->output)) {
            return 0;
        }
        return strlen($this->output);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        return null;
    }

    /**
     * Checks the underlying stream resource is available and open.
     *
     * @throw \RuntimeException If the underlying stream resource is not available or not open.
     */
    protected function ensureResource()
    {
        if($this->stream === null || !is_resource($this->stream)){
            throw new \RuntimeException('Stream was closed or detached.');
        }
    }
}
