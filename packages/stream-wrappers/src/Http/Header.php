<?php
/**
 * A value object representing an HTTP header.
 *
 * @package lucatume\StreamWrappers\Http
 */


namespace lucatume\StreamWrappers\Http;

/**
 * Class Header
 *
 * @package lucatume\StreamWrappers\Http
 */
class Header
{
    /**
     * The header line, in the format `<Key>: <Value`.
     *
     * @var string
     */
    protected $value;

    /**
     * Whether this header replaces an existing one or not.
     *
     * @var bool
     */
    protected $replace;

    /**
     *  The HTTP response code for this header.
     *
     * @var int|null
     */
    protected $httpResponseCode;
    /**
     * The header name.
     *
     * @var string
     */
    protected $name;

    /**
     * Header constructor.
     *
     * @param string $value The raw header value.
     * @param bool $replace Whether this header replaces a previous one or not.
     * @param null $http_response_code The HTTP response code for the header; only set if the `$value` is not empty.
     */
    public function __construct($value, $replace = true, $http_response_code = null)
    {
        $frags = explode(':', $value, 2);

        if (empty($frags)) {
            throw new \InvalidArgumentException('Header value should be in the `<name>: <value>` format.');
        }

        $this->name = trim($frags[0]);
        $this->value = trim(end($frags));
        $this->replace = $replace;
        $this->httpResponseCode = !empty($value) ? $http_response_code : null;
    }

    /**
     * Builds the correct header depending on the value.
     *
     * @param string $value The raw header value.
     * @param bool $replace Whether this header replaces a previous one or not.
     * @param int|null $httpResponseCode The HTTP response code for the header; only set if the `$value` is not empty.
     * @param int|null $defaultResponseCode The default HTTP response code, ignored if `$httpResponseCode` is set.
     *
     * @return Header The correct instance of the class.
     */
    public static function make($value, $replace, $httpResponseCode = null, $defaultResponseCode = null)
    {
        if (stripos($value, 'HTTP/') === 0) {
            return new HttpHeader($value, $replace, $httpResponseCode);
        }

        if (stripos($value, 'Location:') === 0) {
            $responseCode = $httpResponseCode ?: $defaultResponseCode;

            return new LocationHeader($value, $replace, $responseCode);
        }

        return new self($value, $replace, $httpResponseCode);
    }

    /**
     * Returns the header name, the part before the first `:`.
     *
     * @return string The header name, the part before the first `:`.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the header value, after the first `:`.
     *
     * @return string The header value, after the first `:`.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the header HTTP response code, if any.
     *
     * @return int|null The header HTTP response code, if any.
     */
    public function getResponseCode()
    {
        return $this->httpResponseCode;
    }
}
