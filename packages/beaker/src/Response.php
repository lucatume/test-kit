<?php
/**
 * A Beaker request response.
 *
 * @package lucatume\Beaker
 */

namespace lucatume\Beaker;

use lucatume\StreamWrappers\FileStreamWrappingRunResultInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 *
 * @package lucatume\Beaker
 */
class Response implements ResponseInterface
{
    /**
     * The response HTTP protocol version, e.g. '1.1' or '1.0'.
     *
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * An associative array of the response headers, each one an array of each header value.
     *
     * @var array<string,array>
     */
    protected $headers = [];

    /**
     * The file wrapping output.
     *
     * @var string
     */
    protected $output = '';
    /**
     * The response underlying body stream.
     *
     * @var StringStream
     */
    protected $body;

    /**
     * The file wrapping exit status code or message.
     *
     * @var int
     */
    protected $exitStatusCode = 200;

    /**
     * The exit status reason or message.
     *
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * Builds a response instance from the result of a stream wrapper run.
     *
     * @param FileStreamWrappingRunResultInterface $run The result of a stream wrapper run.
     *
     * @return static A response instance, built from the result of a stream wrapper run.
     */
    public static function fromFileWrapperRunResult(FileStreamWrappingRunResultInterface $run)
    {
        $instance = new static();

        $instance->headers = $run->getSentHeaders();
        $instance->output = $run->getOutput();
        $instance->body = new StringStream($instance->output);
        $exitCodeOrMessage = $run->getExitCodeOrMessage();
        if(is_numeric($exitCodeOrMessage)){
            $instance->exitStatusCode = (int) $exitCodeOrMessage;
            $instance->reasonPhrase = '';
        } else {
            $instance->exitStatusCode = 200;
            $instance->reasonPhrase = $exitCodeOrMessage;
        }

        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        foreach (array_keys($this->headers) as $header) {
            if (strcasecmp($name, $header) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        foreach ($this->headers as $header => $values) {
            if (strcasecmp($name, $header) === 0) {
                return $values;
            }
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        return $this->hasHeader($name) ? implode(',', $this->getHeaders($name)) : '';
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers[$name] = (array)$value;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $clone = clone $this;
        $clone->headers[$name] = array_merge($clone->headers[$name], (array)$value);
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        $clone = clone $this;

        foreach (array_keys($clone->headers) as $header) {
            if (strcasecmp($name, $header) === 0) {
                unset($clone->headers[$header]);
                break;
            }
        }

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return new StringStream($this->output);
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;

        $clone->body =  $body;
        $clone->output = $body->getContents();

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode()
    {
        return $this->exitStatusCode;
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $clone = clone $this;

        $clone->exitStatusCode = $code;
        $clone->reasonPhrase = $reasonPhrase;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
}
