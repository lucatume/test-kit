<?php
/**
 * Patches a class-like (class, interface, trait) definition node to wrap it in ax `exists` check.
 *
 * @package lucatume\StreamWrappers\Patches
 */

namespace lucatume\StreamWrappers\Patches;

use PhpParser\Node;

/**
 * Class ClassLikeDefinitionPatch
 *
 * @package lucatume\StreamWrappers\Patches
 */
class ClassLikeDefinitionPatch extends Patch
{

    /**
     * @inheritDoc
     */
    public function leaveNode(Node $node)
    {
        if (!($node instanceof Node\Stmt\ClassLike)) {
            return null;
        }

        $classFQN = $node->namespacedName->toString();
        $classCode = $this->printer->prettyPrint([$node]);
        $function = $this->getExistsCheckFunctionForNode($node);
        $wrappedClassCode = sprintf('if( ! %s("%s")){%s}', $function, $classFQN, $classCode);

        return $this->parser->parse("<?php\n" . $wrappedClassCode);
    }

    /**
     * Returns the function to check for a class-like existence.
     *
     * @param Node\Stmt\ClassLike $node The node to return the function for.
     * @return string The function to check for the class-like existence; one of `(class|interface|trait)_exists`.
     */
    protected function getExistsCheckFunctionForNode(Node\Stmt\ClassLike $node): string
    {
        if ($node instanceof Node\Stmt\Interface_) {
            return 'interface_exists';
        }

        if ($node instanceof Node\Stmt\Trait_) {
            return 'trait_exists';
        }

        return 'class_exists';
    }
}
