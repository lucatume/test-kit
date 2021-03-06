<?php
/**
 * Replaces calls to the `defined` function with calls to the `define` method of the stream wrapper.
 *
 * @package lucatume\StreamWrappers\Patches
 */

namespace lucatume\StreamWrappers\Patches;

use PhpParser\Node;
use lucatume\StreamWrappers\StreamWrapperException;

/**
 * Class DefinedCallsPatch
 *
 * @package lucatume\StreamWrappers\Patches
 */
class DefinedCallsPatch extends Patch
{
    /**
     * @inheritDoc
     *
     * @throws StreamWrapperException If the replacement code cannot be parsed or is not of the correct type.
     */
    public function leaveNode(Node $node)
    {
        if (!($node instanceof Node\Expr\FuncCall && $node->name->toString() === 'defined')) {
            return null;
        }

        $replace = sprintf(
            '$GLOBALS["%s"]->defined(%s);',
            $this->streamWrapperGlobalVar,
            $this->prettyPrintArgs($node->args)
        );

        return $this->getWrappedExpression($replace);
    }
}
