<?php
/**
 * Replaces calls to the `die` or `exit` functions.
 *
 * @package tad\StreamWrappers\Patches
 */

namespace tad\StreamWrappers\Patches;

use PhpParser\Node;

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

        return $this->parser->parse('<?php' . PHP_EOL . $replace)[0];
    }
}
