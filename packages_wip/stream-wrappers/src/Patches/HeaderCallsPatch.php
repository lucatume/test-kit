<?php
/**
 * Replaces calls to `header` related functions.
 *
 * @package lucatume\StreamWrappers\Patches
 */

namespace lucatume\StreamWrappers\Patches;

use PhpParser\Node;
use lucatume\StreamWrappers\StreamWrapperException;

/**
 * Class HeaderCallsPatch
 *
 * @package lucatume\StreamWrappers\Patches
 */
class HeaderCallsPatch extends Patch
{
    /**
     * @inheritDoc
     *
     * @throws StreamWrapperException If the replacement code cannot be parsed or is not of the correct type.
     */
    public function leaveNode(Node $node)
    {
        if (! ( $node instanceof Node\Expr\FuncCall && $node->name->toString() === 'header' )) {
            return null;
        }

        $replace = sprintf(
            '$GLOBALS["%s"]->header(%s);',
            $this->streamWrapperGlobalVar,
            $this->prettyPrintArgs($node->args)
        );

        return $this->getWrappedExpression($replace);
    }
}
