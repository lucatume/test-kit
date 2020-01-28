<?php
/**
 * Replaces calls to `(include|require)(_once)*` in instruction and function form.
 *
 * @package lucatume\StreamWrappers\Patches
 */

namespace lucatume\StreamWrappers\Patches;

use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use lucatume\StreamWrappers\StreamWrapperException;

/**
 * Class IncludeRequirePatch
 *
 * @package lucatume\StreamWrappers\Patches
 */
class IncludeRequirePatch extends Patch
{

    /**
     * @inheritDoc
     *
     * @throws StreamWrapperException If the replacement code cannot be parsed or is not of the correct type.
     */
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

        return $this->getWrappedExpression($replace);
    }
}
