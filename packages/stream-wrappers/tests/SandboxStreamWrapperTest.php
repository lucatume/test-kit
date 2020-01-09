<?php

namespace lucatume\StreamWrappers;

use PHPUnit\Framework\TestCase;
use function lucatume\functions\data;

class SandboxStreamWrapperTest extends TestCase
{
    /**
     * It should allow loading a single file w/o other inclusions
     *
     * @test
     */
    public function should_allow_loading_a_single_file_w_o_other_inclusions()
    {
        $file = data('wrap/file_w_const_defs.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $run = $wrapper->loadFile($file);

        $expectedConstants = ['CONST_ONE' => 23, 'CONST_TWO' => 89, 'CONST_THREE' => 2389];
        $this->assertEquals($expectedConstants, $run->getDefinedConstants());
        $this->assertFalse(defined('CONST_ONE'));
        $this->assertFalse(defined('CONST_TWO'));
        $this->assertFalse(defined('CONST_THREE'));
    }

    /**
     * It should replace `defined` calls
     *
     * @test
     */
    public function should_replace_defined_calls()
    {
        $file = data('wrap/file_w_defined.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $wrapper->setContextDefinedConstants(['CONST_ONE' => true]);
        $run = $wrapper->loadFile($file);

        $this->assertEquals(['CONST_TWO' => 23], $run->getDefinedConstants());
        $this->assertFalse(defined('CONST_ONE'));
        $this->assertFalse(defined('CONST_TWO'));
    }

    /**
     * It should allow inclusion of files from within the loaded file
     *
     * @test
     */
    public function should_allow_inclusion_of_files_from_within_the_loaded_file()
    {
        $file = data('wrap/file_incl_files.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $run = $wrapper->loadFile($file);

        foreach (['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT'] as $v) {
            $this->assertArrayHasKey('CONST_' . $v, $run->getDefinedConstants());
            $this->assertEquals($v, $run->getDefinedConstants()['CONST_' . $v]);
            $this->assertFalse(defined('CONST_' . $v));
        }
    }

    /**
     * It should buffer the output of the included file
     *
     * @test
     */
    public function should_buffer_the_output_of_the_included_file()
    {
        $file = data('wrap/file_w_output.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $run = $wrapper->loadFile($file);

        $this->assertEquals('Hello world!', $run->getOutput());
        $this->assertFalse($this->hasOutput());
    }

    /**
     * It should buffer the output of files included by the loaded file
     *
     * @test
     */
    public function should_buffer_the_output_of_files_included_by_the_loaded_file()
    {
        $file = data('wrap/file_incl_files_w_output.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $run = $wrapper->loadFile($file);

        $this->assertEquals("one\ntwo\nthree", $run->getOutput());
        $this->assertFalse($this->hasOutput());
    }

    /**
     * It should handle access to constant values
     *
     * @test
     */
    public function should_handle_access_to_constant_values()
    {
        $file = data('wrap/file_w_const_access.php');
        define('TEST_CONST_ZERO', 'NO');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $wrapper->setContextDefinedConstants(['TEST_CONST_ZERO' => 'YES']);
        $run  = $wrapper->loadFile($file);

        $this->assertEquals(
            ['CONST_ONE' => 'ONE', 'CONST_TWO' => 'TWO', 'CONST_THREE' => 'THREE', 'CONST_FOUR' => 'FOUR'],
            $run->getDefinedConstants()
        );
        $this->assertFalse(defined('CONST_ONE'));
        $this->assertFalse(defined('CONST_TWO'));
        $this->assertFalse(defined('CONST_THREE'));
        $this->assertFalse(defined('CONST_FOUR'));
        $this->assertFalse(defined('CONST_FIVE'));
    }

    /**
     * It should handle exit calls
     *
     * @test
     */
    public function should_handle_exit_calls()
    {
        $file = data('wrap/eventually_dies.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $run  = $wrapper->loadFile($file);

        $this->assertTrue($run->fileDidExit());
        $this->assertEquals("You cannot do that.\n", $run->getOutput());
        $this->assertEquals('The page is missing.', $run->getExitCodeOrMessage());
    }

    /**
     * It should collect headers sent during file load
     *
     * @test
     */
    public function should_collect_headers_sent_during_file_load()
    {
        $file = data('wrap/file_sending_headers.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $run  =$wrapper->loadFile($file);

        $this->assertEquals([
            'X-Test-Header-1' => ['one'],
            'X-Test-Header-2' => ['two'],
            'X-Test-Header-3' => ['three'],
            'X-Test-Header-4' => ['four'],
            'X-Test-Header-5' => ['five'],
        ], $run->getSentHeaders());
    }

    /**
     * It should correctly handle const define edge case
     *
     * @test
     */
    public function should_correctly_handle_const_define_edge_case()
    {
        $file = data('wrap/wp_settings_like.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setContextDefinedConstants(['ABSPATH' => data('wrap') . '/']);
        $wrapper->setWhitelist([data('wrap')]);
        $run = $wrapper->loadFile($file);

        $this->assertEquals(['WPINC' => 'wp-includes'], $run->getDefinedConstants());
    }

    /**
     * It should allow replacing global user functions in the wrapped files
     *
     * @test
     */
    public function should_allow_replacing_global_user_functions_in_the_wrapped_files()
    {
        include data('wrap/global_user_function_1.php');
        $file = data('wrap/file_calling_global_user_function.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $wrapper->replaceFn('global_user_function', 'test test test');
        $run = $wrapper->loadFile($file);

        $this->assertEquals('test test test', $run->getOutput());
    }

    /**
     * It should preserve line formatting when patching code
     *
     * @test
     */
    public function should_preserve_line_formatting_when_patching_code()
    {
        $this->markTestSkipped('Format-preserving printer is not there yet.');

        $file = data('wrap/file_w_lotsa_empty_lines.php');

        $wrapper = new SandboxStreamWrapper();
        $wrapper->setWhitelist([data('wrap')]);
        $run = $wrapper->loadFile($file);
        $lastLoadedFilePatchedCode = $run->getLastLoadedFilePatchedCode();

        $this->assertStringEqualsFile($file, $lastLoadedFilePatchedCode);
    }
}
