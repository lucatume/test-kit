<?php

namespace tad\StreamWrappers;

use PHPUnit\Framework\TestCase;
use function tad\functions\data;
use function tad\functions\isDebug;

class IncludedFilesStreamWrapperTest extends TestCase
{
    /**
     * It should allow getting the files included by a file
     *
     * @test
     * @throws StreamWrapperException
     */
    public function should_allow_getting_the_files_included_by_a_file()
    {
        $file = data('wrap/all_inclusion_types.php');

        $wrapper       = new IncludedFilesStreamWrapper;
        $includedFiles = $wrapper->getIncludedFiles($file);

        $this->assertEquals([
            data('wrap/file_1.php'),
            data('wrap/file_2.php'),
            data('wrap/file_3.php'),
            data('wrap/file_4.php'),
            data('wrap/dir/file_1.php'),
            data('wrap/dir/file_2.php'),
            data('wrap/dir/file_3.php'),
            data('wrap/dir/file_4.php'),
        ], $includedFiles);
    }

    /**
     * It should not include not dynamically included files.
     *
     * @test
     */
    public function should_not_include_not_dynamically_included_files_()
    {
        $file = data('wrap/file_1.php');

        $wrapper       = new IncludedFilesStreamWrapper;
        $includedFiles = $wrapper->getIncludedFiles($file);

        $this->assertEquals([
            data('wrap/file_2.php'),
        ], $includedFiles);
    }

    /**
     * It should allow setting code lines before file
     *
     * @test
     */
    public function should_allow_setting_code_lines_before_file()
    {
        $file = data('wrap/file_1.php');

        $wrapper = new IncludedFilesStreamWrapper;
        $code    = '$include3 = true;';

        $includedFiles = $wrapper->getIncludedFiles($file, $code);

        $this->assertEquals([
            data('wrap/file_2.php'),
            data('wrap/file_3.php'),
        ], $includedFiles);
    }

    /**
     * It should correctly store and fetch cached files
     *
     * @test
     * @depends should_allow_setting_code_lines_before_file
     */
    public function should_correctly_store_and_fetch_cached_files()
    {
        if (isDebug()) {
            $this->markTestSkipped('This test will always file during debug runs.');
        }

        $file = data('wrap/file_1.php');

        $wrapper = new IncludedFilesStreamWrapper;
        $code    = '$include3 = true;';

        $wrapper->getIncludedFiles($file, $code);

        $this->assertEquals([$file], array_keys($wrapper->getCache()->getHits()));
    }
}
