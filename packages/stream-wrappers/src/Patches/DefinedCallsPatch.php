<?php
/**
 * Replaces calls to the `defined` function with calls to the `define` method of the stream wrapper.
 *
 * @package tad\StreamWrappers\Patches
 */

namespace tad\StreamWrappers\Patches;

use PhpParser\Node;

/**
 * Class DefinedCallsPatch
 *
 * @package tad\StreamWrappers\Patches
 */
class DefinedCallsPatch extends Patch
{
    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
        if (! ( $node instanceof Node\Expr\FuncCall && $node->name->toString() === 'defined' )) {
            return null;
        }

        $replace = sprintf(
            '$GLOBALS["%s"]->defined(%s);',
            $this->streamWrapperGlobalVar,
            $this->prettyPrintArgs($node->args)
        );

        return $this->parser->parse('<?php' . PHP_EOL . $replace)[0];
    }
}
