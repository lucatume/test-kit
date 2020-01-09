<?php
/**
 * A Prophecy based mock factory.
 *
 * @link https://github.com/phpspec/prophecy
 *
 * @package lucatume\StreamWrappers
 */

namespace lucatume\StreamWrappers\MockFactories;

use Prophecy\Argument;
use Prophecy\Prophet;
use function lucatume\functions\slug;

/**
 * Class ProphecyMockFactory
 *
 * @package lucatume\StreamWrappers\MockFactories
 */
class ProphecyMockFactory
{
    /**
     * The current Prophet object.
     *
     * @var Prophet
     */
    protected $prophet;

    /**
     * Returns the current Prophet object, or builds a new one if not set yet.
     *
     * @return Prophet
     */
    protected function getProphet()
    {
        if ($this->prophet === null) {
            $this->prophet = new Prophet;
        }

        return $this->prophet;
    }

    public function replaceFunction($functionName, $returnValue = null)
    {
        $class = slug($functionName, '_');

        if (! class_exists($class)) {
            $classCode = sprintf('class %1$s { public function calledWith(){}}', $class);
            eval($classCode);
        }

        $prophecy = $this->getProphet()->prophesize($class);
        $prophecy->calledWith(Argument::cetera())->willReturn($returnValue);

        return static function (...$args) use ($prophecy) {
            return $prophecy->reveal()->calledWith(...$args);
        };
    }
}
