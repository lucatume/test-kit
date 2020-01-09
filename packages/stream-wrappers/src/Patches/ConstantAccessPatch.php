<?php
/**
 * Replaces calls to the `constant` function or STRING access to constant values for a controlled set of constants.
 *
 * @package lucatume\StreamWrappers\Patches
 */

namespace lucatume\StreamWrappers\Patches;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use lucatume\StreamWrappers\StreamWrapperException;

/**
 * Class ConstantAccessPatch
 *
 * @package lucatume\StreamWrappers\Patches
 */
class ConstantAccessPatch extends Patch
{
    /**
     * @inheritDoc
     *
     * @throws StreamWrapperException If the replacement code cannot be parsed or is not of the correct type.
     */
    public function leaveNode(Node $node)
    {
        $excluded = [ 'true', 'false', 'null' ];

        $isConstantFunctionCall = $node instanceof Node\Expr\FuncCall && $node->name->toString() === 'constant';
        $isConstantFetch        = $node instanceof ConstFetch && !in_array($node->name->toString(), $excluded, true);

        if (! ( $isConstantFetch || $isConstantFunctionCall )) {
            return null;
        }

        $rawConst = $isConstantFetch ? $node->name->toString() : $this->prettyPrintArgs($node->args);
        $const    = trim($rawConst, '\'"');

        $controlledConstants =array_merge(
            array_keys($this->run->getDefinedConstants()),
            array_keys($this->run->getContextDefinedConstants()),
            $this->run->getReplacedConstants()
        );

        if (! in_array($const, $controlledConstants, true)) {
            return null;
        }

        $this->run->addReplacedConstant($const);

        $replace = sprintf(
            '$GLOBALS["%s"]->getConst("%s");',
            $this->streamWrapperGlobalVar,
            $const
        );

        return $this->getWrappedExpression($replace);
    }
}
