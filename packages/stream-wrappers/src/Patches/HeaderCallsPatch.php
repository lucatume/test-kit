<?php
/**
 * Replaces calls to `header` related functions.
 *
 * @package tad\StreamWrappers\Patches
 */

namespace tad\StreamWrappers\Patches;

use PhpParser\Node;

/**
 * Class HeaderCallsPatch
 *
 * @package tad\StreamWrappers\Patches
 */
class HeaderCallsPatch extends Patch
{
    /**
     * @inheritDoc
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

        return $this->parser->parse('<?php' . PHP_EOL . $replace)[0];
    }
}
