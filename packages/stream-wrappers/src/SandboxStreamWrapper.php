<?php
/**
 * Loads a single file, and all the files required or included by it, in a "sandbox" environment that allows rolling
 * back side-effects.
 *
 * @package lucatume\StreamWrappers
 */

namespace lucatume\StreamWrappers;

use lucatume\StreamWrappers\Http\Header;
use lucatume\StreamWrappers\MockFactories\ProphecyMockFactory;
use lucatume\StreamWrappers\Patches\ConstantAccessPatch;
use lucatume\StreamWrappers\Patches\DefineCallsPatch;
use lucatume\StreamWrappers\Patches\DefinedCallsPatch;
use lucatume\StreamWrappers\Patches\ExitDieCallsPatch;
use lucatume\StreamWrappers\Patches\FunctionReplacementPatch;
use lucatume\StreamWrappers\Patches\HeaderCallsPatch;
use lucatume\StreamWrappers\Patches\IncludeRequirePatch;
use lucatume\StreamWrappers\Patches\Patch;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use function lucatume\functions\pathJoin;
use function lucatume\functions\pathNormalize;

/**
 * Class SandboxStreamWrapper
 *
 * @package lucatume\StreamWrappers
 */
class SandboxStreamWrapper extends StreamWrapper
{
    /**
     * @var array<Patch> An array of patches shared across the stream wrapper instances.
     */
    protected static $patches = [];
    /**
     * The current object mock factory.
     *
     * @var ProphecyMockFactory
     */
    protected $mockFactory;

    /**
     * @inheritDoc
     */
    protected static function scheme()
    {
        return 'file';
    }

    /**
     * Loads a file, and with it any file that might be consequentially loaded, in a "sandbox".
     *
     * @param string $file The path to the file to load.
     *
     * @return Run The stream wrapper run result.
     *
     * @throws StreamWrapperException If the file does not exist or there's an issue registering the wrapper.
     */
    public function loadFile($file)
    {
        if (! file_exists($file)) {
            throw new StreamWrapperException(sprintf('File "%s" does not exist.', $file));
        }

        $this->initSharedProps();

        if (! empty(static::$run->getContextDefinedConstants())) {
            foreach (static::$run->getContextDefinedConstants() as $key => $value) {
                $this->define($key, $value, false);
            }
        }

//        static::$run->snapshotEnv('before');

        $GLOBALS[ $this->getGlobalVarName() ] = $this;


        $this->startWrapping();
        static::$run->setLastLoadedFile($file);
        try {
            $this->safelyRequireFile($file);
        } catch (ExitSignal $e) {
            static::$run->setFileExit($e->getMessage());
        } catch (\Exception $e) {
            $this->throwFormattedException($e);
        } finally {
            $this->stopWrapping();
        }

//        static::$run->snapshotEnv('after');

        $thisRun = static::$run;
        static::$run = null;

        return $thisRun;
    }

    /**
     * A replacement for the `define` functions that will set environment vars instead and log the constant definition.
     *
     * @param string $key   The defined constant key.
     * @param mixed  $value The defined constant value.
     * @param bool   $track Whether the definition of this virtual constant should be tracked or not.
     *
     * @return bool Whether the "virtual" constant was correctly defined or not.
     */
    public function define($key, $value, $track = true)
    {
        if ($track) {
            static::$run->addDefinedConstant($key, $value);
        }

        return putenv("{$key}={$value}");
    }

    /**
     * @inheritDoc
     */
    public function getGlobalVarName()
    {
        return 'tad_sw_sandbox';
    }

    /**
     * Safely include a file, attempting to gracefully handle failure.
     *
     * @param string $file The path to the file to include.
     * @param array|null $definedVars An array of variables that will be extracted to provide them as context to the
     *                                file inclusion.
     *
     * @return mixed The file `include` return value, if any.
     *
     * @throws StreamWrapperException An as helpful as it can get exception text.
     * @throws ExitSignal If the code would terminate by means of an `exit` or `die` call.
     */
    protected function safelyRequireFile($file, array $definedVars = null)
    {
        try {
           return $this->unsafelyRequireFile($file,$definedVars);
        } catch (StreamWrapperException $e) {
            $this->stopWrapping();
            // Pass thru.
            throw $e;
        } catch (\ParseError $e) {
            $this->stopWrapping();
            $this->throwFormattedParseError($e);
        }
    }

    /**
     * Formats and throws a parse error to provide context.
     *
     * @param \ParseError $e The error to format.
     *
     * @throws StreamWrapperException The formatted exception.
     */
    protected function throwFormattedParseError(\ParseError $e)
    {
        throw new StreamWrapperException(
            sprintf(
                "Wrapping of file \"%s\" caused a parse error in file : %s\n\n%s",
                $e->getFile(),
                $e->getMessage(),
                $this->getLastFileLineSurroundings($e->getLine())
            )
        );
    }

    /**
     * Return the surroundings (prev and next 10 lines) of a line in the last patched file code.
     *
     * @param int $line The line number to get the surroundings of.
     *
     * @return string The lines preceding and following the target line.
     */
    protected function getLastFileLineSurroundings($line)
    {
        $implode = implode(
            PHP_EOL,
            array_slice(explode(PHP_EOL, static::$run->getLastLoadedFilePatchedCode()), $line - 10, 20)
        );

        return $implode;
    }

    /**
     * Formats and throws a captured exception to provide context.
     *
     * @param \Exception $e The exception to format and throw.
     *
     * @throws StreamWrapperException The formatted and contextualized exception.
     */
    protected function throwFormattedException(\Exception $e)
    {
        $message = $e->getMessage();

        if (preg_match('/^Use of undefined constant (\\w*)/', $message, $m)) {
            throw new StreamWrapperException(
                sprintf(
                    'Constant "%s" is undefined: define it with the "setContextDefinedConstants" method.',
                    $m[1]
                )
            );
        }

        $trace = $e->getTrace();
        $last  = reset($trace);
        $line  = $last['line'];
        throw new StreamWrapperException(
            sprintf(
                "%s; patched code:\n%s\n\n%s",
                $e->getMessage(),
                $line,
                $this->getLastFileLineSurroundings($line)
            )
        );
    }

    /**
     * Replaces calls to the `defined` function to check if a "virtual" constant was defined either in an included
     * file or before it by means of the `setContextDefinedConstants` method.
     *
     * @param string $const The constant to check for definition.
     *
     * @return bool Whether the "virtual" constant is defined or not.
     */
    public function defined($const)
    {
        return array_key_exists($const, static::$run->getContextDefinedConstants())
               || array_key_exists($const, static::$run->getDefinedConstants());
    }

    /**
     * Sets an associative array of virtual constants that will be defined before the file is loaded.
     *
     * @param array $definedConstants An associative array of virtual constants that will be defined before the file is
     *                                loaded.
     */
    public function setContextDefinedConstants(array $definedConstants)
    {
        static::runLog()->setContextDefinedConstants($definedConstants);
    }

    /**
     * Includes a file, replacement for the `includeOnce` function.
     *
     * @param string              $file        The path to the file to include.
     * @param string              $cwd         The current working directory, to resolve relative paths.
     * @param array<string,mixed> $definedVars The variables defined in the context including the file.
     *
     * @return mixed The included file return value, if any, or `true` if the file was already included..
     *
     * @throws StreamWrapperException If the file to include does not exist.
     */
    public function includeFileOnce($file, $cwd, array $definedVars = [])
    {
        return $this->includeFile($file, $cwd, $definedVars, true);
    }

    /**
     * Includes a file, replacement for the `include` function.
     *
     * @param string              $file        The path to the file to include.
     * @param string              $cwd         The current working directory, to resolve relative paths.
     * @param array<string,mixed> $definedVars The variables defined in the context including the file.
     * @param bool                $once        Whether to include this file once or not.
     *
     * @return mixed The included file return value, if any, or `true` if the file was already included..
     *
     * @throws StreamWrapperException If the file to include does not exist.
     */
    public function includeFile($file, $cwd, array $definedVars = [], $once = false)
    {
        $filename = pathNormalize(pathJoin($cwd, $file));

        if (file_exists($filename)) {
            $file = $filename;
        }

        if (! file_exists($file)) {
            throw new StreamWrapperException('Including file "' . $file . '" but it does not exist.');
        }

        if ($once && static::$run->fileWasIncluded($file)) {
            return true;
        }

        static::$run->addIncludedFile($file, $once);

        return $this->safelyRequireFile($file, $definedVars);
    }

    /**
     * Returns the value of a "virtual" constant.
     *
     * @param string $const The constant name.
     *
     * @return mixed|null The value of the "virtual" constant, or `null` if not defined.
     */
    public function getConst($const)
    {
        $controlledConstants = array_merge(
            static::$run->getContextDefinedConstants(),
            static::$run->getDefinedConstants()
        );

        if (isset($controlledConstants[ $const ])) {
            return $controlledConstants[ $const ];
        }

        return null;
    }

    /**
     * Throws an `ExitSignal` that will be handled in the `run` method.
     *
     * @param int|string $status The `exit` or `die` status or code.
     *
     * @throws ExitSignal To signal the loaded code is willing to interrupt the flow and stop.
     */
    public function throwExit($status = 0)
    {
        throw new ExitSignal($status);
    }

    /**
     * Drop-in replacement for the `header` function.
     *
     * @param string   $value            The header value.
     * @param bool     $replace          Whether this header replaces a previous version of it or not.
     * @param int|null $httpResponseCode The header response code; ignored if the `$value` is empty.
     */
    public function header($value, $replace = true, $httpResponseCode = null)
    {
        $header = Header::make($value, $replace, $httpResponseCode, static::$run->getSentResponseCode());
        static::$run->addSentHeader($header, $replace);
    }

    /**
     * Sets the list of directories or files this stream wrapper should wrap.
     *
     * @param array $whiteList The list of directories or files this stream wrapper should wrap.
     *
     * @return FileStreamWrapperInterface This, for chaining.
     */
    public function setWhitelist(array $whiteList)
    {
        static::runLog()->setWhiteList($whiteList);

        return $this;
    }

    /**
     * Casts `E_PARSE` errors to exceptions.
     *
     * @param int      $errorNumber The error code.
     * @param string   $message     The error message.
     * @param string   $file        The file path.
     * @param null|int $line        The line number.
     *
     * @return bool `true` to indicate the error is handled.
     *
     * @throws StreamWrapperException The formatted exception.
     */
    public function castErrorToException($errorNumber, $message, $file, $line = 0)
    {
        throw new StreamWrapperException(
            sprintf(
                "Wrapping of file \"%s\" caused a parse error in file : %s\n\n%s",
                $file,
                $message,
                $this->getLastFileLineSurroundings($line)
            )
        );

        return true;
    }

    /**
     * Returns the cache instance used by the stream wrapper.
     *
     * @return ContentsCache The instance cache used by the stream wrapper.
     */
    public function getCache()
    {
        return static::$patchedContentsCache;
    }

    /**
     * Replaces a function to return the specified value in wrapped files.
     *
     * @param string $functionName The fully-qualified name of the function to replace.
     * @param mixed $returnValue The value that will be returned when the function is called.
     */
    public function replaceFn($functionName, $returnValue)
    {
        $mock =  $this->mockFactory()->replaceFunction($functionName, $returnValue);

        static::runLog()->addReplacedFunction($functionName, $mock);

        return $mock;
    }

    /**
     * @inheritDoc
     */
    protected function shouldTransform($path)
    {
        return static::$run->isPathWhitelisted($path);
    }

    /**
     * @inheritDoc
     */
    protected function setupTraverser(NodeTraverserInterface $traverser)
    {
        $var = $this->getGlobalVarName();
        $traverser->addVisitor(new CloningVisitor());
        $traverser->addVisitor(new NameResolver(null, ['replaceNodes' => false]));
        $traverser->addVisitor(new DefineCallsPatch($var, static::$run, static::$parser, static::$printer));
        $traverser->addVisitor(new DefinedCallsPatch($var, static::$run, static::$parser, static::$printer));
        $traverser->addVisitor(new ConstantAccessPatch($var, static::$run, static::$parser, static::$printer));
        $traverser->addVisitor(new IncludeRequirePatch($var, static::$run, static::$parser, static::$printer));
        $traverser->addVisitor(new ExitDieCallsPatch($var, static::$run, static::$parser, static::$printer));
        $traverser->addVisitor(new HeaderCallsPatch($var, static::$run, static::$parser, static::$printer));
        $traverser->addVisitor(new FunctionReplacementPatch($var, static::$run, static::$parser, static::$printer));
    }

    /**
     * Returns the current mock factory or builds a new one.
     *
     * @return ProphecyMockFactory The current mock factory instance or a freshly built one, if not set.
     */
    protected function mockFactory()
    {
        if ($this->mockFactory === null) {
            $this->mockFactory = new ProphecyMockFactory();
        }

        return $this->mockFactory;
    }

    /**
     * Deregister the stream wrapper from the supported protocols and stop the output buffering.
     */
    protected function stopWrapping()
    {
        static::unwrap();
        static::$run->setOutput(ob_get_clean());
    }

    /**
     * Attach the stream wrapper to the supported protocols and start buffering the output.
     */
    protected function startWrapping()
    {
        ob_start();
        static::wrap();
    }
}
