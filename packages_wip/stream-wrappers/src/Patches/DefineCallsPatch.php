<?php
/**
 * Replaces calls to the `define` function with calls to the `define` method of the stream wrapper.
 *
 * @package lucatume\StreamWrappers\Patches
 */

namespace lucatume\StreamWrappers\Patches;

use PhpParser\Node;
use lucatume\StreamWrappers\StreamWrapperException;

/**
 * Class DefineCallsPatch
 *
 * @package lucatume\StreamWrappers\Patches
 */
class DefineCallsPatch extends Patch
{
    /**
     * @inheritDoc
     *
     * @throws StreamWrapperException If the replacement code cannot be parsed or is not of the correct type.
     */
    public function leaveNode(Node $node)
    {
        if (! ( $node instanceof Node\Expr\FuncCall && $node->name->toString() === 'define' )) {
            return null;
        }

        $replace = sprintf(
            '$GLOBALS["%s"]->define(%s);',
            $this->streamWrapperGlobalVar,
            $this->prettyPrintArgs($node->args)
        );

//        $callArgs = array_map( static function (Node\Arg $arg) {
//            $argValue = $arg->value;
//            if($argValue instanceof Node\Expr\ConstFetch){
//                return $argValue->name->toCodeString();
//            }
//            return $argValue->value;
//        }, $node->args);

        $constName = $node->args[0]->value->value;
        $this->run->addReplacedConstant($constName);

        return $this->getWrappedExpression($replace);
    }
}
