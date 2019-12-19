<?php
/**
 * Replaces calls to `(include|require)(_once)*` in instruction and function form.
 *
 * @package tad\StreamWrappers\Patches
 */

namespace tad\StreamWrappers\Patches;

use PhpParser\Node;
use PhpParser\Node\Expr\Include_;

/**
 * Class IncludeRequirePatch
 *
 * @package tad\StreamWrappers\Patches
 */
class IncludeRequirePatch extends Patch
{
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Include_) {
            return null;
        }

        $type     = $node->type;
        $method   = $type === Include_::TYPE_INCLUDE_ONCE || $type === Include_::TYPE_REQUIRE_ONCE ?
            'includeFileOnce'
            : 'includeFile';

        $replace = sprintf(
            '$GLOBALS["%s"]->%s(%s, __DIR__, get_defined_vars());',
            $this->streamWrapperGlobalVar,
            $method,
            $this->printer->prettyPrintExpr($node->expr)
        );

        return $this->parser->parse('<?php' . PHP_EOL . $replace);
    }
}
