<?php
/**
 * Replaces calls to the `define` function with calls to the `define` method of the stream wrapper.
 *
 * @package tad\StreamWrappers\Patches
 */

namespace tad\StreamWrappers\Patches;

use PhpParser\Node;

/**
 * Class DefineCallsPatch
 *
 * @package tad\StreamWrappers\Patches
 */
class DefineCallsPatch extends Patch
{
    /**
     * @inheritDoc
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

        $callArgs = array_map( static function (Node\Arg $arg) {
            return $arg->value->value;
        }, $node->args);

        $this->run->addReplacedConstant(...$callArgs);

        return $this->parser->parse('<?php' . PHP_EOL . $replace)[0];
    }
}
