<?php
/**
 * Represents a code patch applied to the wrapped code.
 *
 * @package tad\StreamWrappers\Patches
 */

namespace tad\StreamWrappers\Patches;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use tad\StreamWrappers\Run;

/**
 * Class Patch
 *
 * @package tad\StreamWrappers\Patches
 */
abstract class Patch extends NodeVisitorAbstract
{
    const T_ANYTHING = 'T_ANYTHING';
    /**
     * The name of the `$GLOBALS` variable that stores the main stream wrapper instance.
     *
     * @var string
     */
    protected $streamWrapperGlobalVar;

    /**
     * The stream wrapper run reporter.
     *
     * @var Run
     */
    protected $run;
    /**
     * The currently used parser.
     *
     * @var Parser
     */
    protected $parser;

    /**
     * The printer used to print code by instances of the patch.
     *
     * @var Standard
     */
    protected $printer;

    /**
     * Patch constructor.
     *
     * @param string                     $streaWrapperGlobalVar The name of the `$GLOBALS` var that stores the main
     *                                                          stream wrapper instance.
     * @param Run                        $run                   The object that is storing the result of the stream
     *                                                          wrapper run.
     * @param Parser                     $parser                The parser used to parse the PHP code.
     * @param PrettyPrinterAbstract|null $printer               The printer used to print the code, defaults to the
     *                                                          Standard one.
     */
    public function __construct(
        $streaWrapperGlobalVar,
        Run $run,
        Parser $parser,
        PrettyPrinterAbstract $printer = null
    ) {
        $this->streamWrapperGlobalVar = $streaWrapperGlobalVar;
        $this->run                    = $run;
        $this->parser                 = $parser;
        $this->printer                = $printer ?: new Standard();
    }

    /**
     * Replaces calls to a function.
     *
     * @param string   $function The name of the function to replace.
     * @param callable $patchFn  The callable that will replace the function; it will receive the function name as first
     *                           parameter and the captured buffer as second. This buffer is in the format
     *                           `<fn>(<args>)`.
     * @param string   $contents The code contents to process.
     *
     * @return string The patched code contents.
     */
    protected function replaceFunctionCallWith($function, callable $patchFn, $contents)
    {
        $tokens = token_get_all($contents);

        $patched        = '';
        $capturingLevel = 0;
        $buffer         = '';

        foreach ($tokens as $token) {
            if ($capturingLevel && $token === ')' && -- $capturingLevel === 1) {
                $patched        .= $patchFn($function, $buffer . ')');
                $buffer         = '';
                $capturingLevel = 0;
                continue;
            }

            $tokenType  = is_array($token) ? $token[0] : $token;
            $tokenValue = is_array($token) ? $token[1] : $token;

            if ($tokenType === T_STRING && $tokenValue === $function) {
                $capturingLevel = 1;
            } elseif ($capturingLevel && $tokenValue === '(') {
                $capturingLevel ++;
            }

            if ($capturingLevel) {
                $buffer .= $tokenValue;
            } else {
                $patched .= $tokenValue;
            }
        }

        return $patched;
    }

    /**
     * Prints, separating them with a comma, a list of arguments.
     *
     *
     * @param array<Node\Arg> $args The arguments to print.
     *
     * @return string The pretty-printed list of arguments, separated by a comma.
     */
    protected function prettyPrintArgs(array $args)
    {
        return implode(', ', array_map(function (Node\Arg $arg) {
            return $this->printer->prettyPrint([ $arg ]);
        }, $args));
    }
}
