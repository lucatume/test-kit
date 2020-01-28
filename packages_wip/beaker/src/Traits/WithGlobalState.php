<?php
/**
 * Provides methods to store, restore and manipulate the global state.
 *
 * @package lucatume\Utils\Traits
 */

namespace lucatume\Beaker\Traits;

use SebastianBergmann\GlobalState\Blacklist;
use SebastianBergmann\GlobalState\Restorer;
use SebastianBergmann\GlobalState\Snapshot;

/**
 * Trait WithGlobalState
 *
 * @package lucatume\Beaker\Traits
 */
trait WithGlobalState
{
    /**
     * An array of global variables that should be excluded from the global state backup.
     *
     * @var array
     */
    protected $backupGlobalsBlacklist= [];

    /**
     * An array of static attributes that should be excluded from the global state backup.
     *
     * @var array<string,array> A map of fully-qualified class names to list of attributes.
     */
    protected $backupStaticAttributesBlacklist = [];

    /**
     * A copy-and-paste of the `PHPUnit\Framework\TestCase::createGlobalStateSnapshot` method.
     *
     * @return Snapshot The current global state snapshot.
     */
    private function globalStateSnapshotCreate()
    {
        $blacklist = new Blacklist;

        foreach ($this->backupGlobalsBlacklist as $globalVariable) {
            $blacklist->addGlobalVariable($globalVariable);
        }

        if (!\defined('PHPUNIT_TESTSUITE')) {
            $blacklist->addClassNamePrefix('PHPUnit');
            $blacklist->addClassNamePrefix('File_Iterator');
            $blacklist->addClassNamePrefix('SebastianBergmann\CodeCoverage');
            $blacklist->addClassNamePrefix('PHP_Invoker');
            $blacklist->addClassNamePrefix('PHP_Timer');
            $blacklist->addClassNamePrefix('PHP_Token');
            $blacklist->addClassNamePrefix('Symfony');
            $blacklist->addClassNamePrefix('Text_Template');
            $blacklist->addClassNamePrefix('Doctrine\Instantiator');
            $blacklist->addClassNamePrefix('Prophecy');

            foreach ($this->backupStaticAttributesBlacklist as $class => $attributes) {
                foreach ($attributes as $attribute) {
                    $blacklist->addStaticAttribute($class, $attribute);
                }
            }
        }

        return new Snapshot(
            $blacklist,
            true,
            true,
            false,
            false,
            false,
            false,
            false,
            false,
            false
        );
    }

    /**
     * Restores the global state to a previous one from a snapshot.
     *
     * @param Snapshot $snapshot The snapshot to restore the global state from.
     */
   protected function globalStateRestore(Snapshot $snapshot)
    {
        $restorer = new Restorer;

        $restorer->restoreGlobalVariables($snapshot);
        $restorer->restoreStaticAttributes($snapshot);
    }
}
