<?php
/**
 * Replaces calls to the `constant` function or STRING access to constant values for a controlled set of constants.
 *
 * @package tad\StreamWrappers\Patches
 */

namespace tad\StreamWrappers\Patches;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;

/**
 * Class ConstantAccessPatch
 *
 * @package tad\StreamWrappers\Patches
 */
class ConstantAccessPatch extends Patch
{
    /**
     * @inheritDoc
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

        return $this->parser->parse('<?php' . PHP_EOL . $replace)[0];
    }
}
