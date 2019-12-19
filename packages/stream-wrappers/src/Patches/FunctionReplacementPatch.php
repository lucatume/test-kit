<?php
/**
 * Replaces calls to a set of functions with pre-determined values.
 *
 * @package tad\StreamWrappers\Patches
 */

namespace tad\StreamWrappers\Patches;

use PhpParser\Node;

/**
 * Class FunctionReplacementPatch
 *
 * @package tad\StreamWrappers\Patches
 */
class FunctionReplacementPatch extends Patch
{
    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
        $replacedFunctions = $this->run->getReplacedFunctions();

        if (! ($node instanceof Node\Expr\FuncCall && array_key_exists($node->name->toString(), $replacedFunctions) )) {
            return null;
        }

        $replace = sprintf(
            '$GLOBALS["%s"]->callFunc("%s",%s);',
            $this->streamWrapperGlobalVar,
            $node->name->toString(),
            $this->prettyPrintArgs($node->args)
        );

        return $this->parser->parse('<?php' . PHP_EOL . $replace)[0];
    }
}
