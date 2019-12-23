<?php
/**
 * Replaces calls to the `die` or `exit` functions.
 *
 * @package tad\StreamWrappers\Patches
 */

namespace tad\StreamWrappers\Patches;

use PhpParser\Node;
use tad\StreamWrappers\StreamWrapperException;

/**
 * Class ExitDieCallsPatch
 *
 * @since   TBD
 *
 * @package tad\StreamWrappers\Patches
 */
class ExitDieCallsPatch extends Patch
{

    /**
     * @inheritDoc
     *
     * @throws StreamWrapperException If the replacement code cannot be parsed or is not of the correct type.
     */
    public function leaveNode(Node $node)
    {
        if (! ( $node instanceof Node\Expr\Exit_ )) {
            return null;
        }

        $replace = sprintf(
            '$GLOBALS["%s"]->throwExit(%s);',
            $this->streamWrapperGlobalVar,
            empty($node->expr) ? '0' : $this->printer->prettyPrintExpr($node->expr)
        );

        return $this->getWrappedExpression($replace);
    }
}
