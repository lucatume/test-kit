<?php

namespace lucatume\Beaker;

use PHPUnit\Framework\TestCase;
use function lucatume\functions\vendor;

class FileRouterTest extends TestCase
{
    /**
     * It should throw if building file router on not dir
     *
     * @test
     */
    public function should_throw_if_building_file_router_on_not_dir()
    {
        $this->expectException(\InvalidArgumentException::class);

        new FileRouter(__FILE__);
    }

    /**
     * It should throw if building file router from a dir that does not contain the wp-load.php file
     *
     * @test
     */
    public function should_throw_if_building_file_router_from_a_dir_that_does_not_contain_the_wp_load_php_file()
    {
        $this->expectException(\InvalidArgumentException::class);

        new FileRouter(__DIR__);
    }

    public function urisCorrectPathDataSet()
    {
        return [
            'root' => [
                '/',
                [
                    'PATH_INFO' => '',
                    'REQUEST_URI' => '/',
                    'PHP_SELF' => '/index.php',
                    'DOCUMENT_ROOT' =>  vendor('wordpress/wordpress')
                ]
            ],
            'root_index_file' => [
                '/index.php',
                [
                    'PATH_INFO' => '',
                    'REQUEST_URI' => '/index.php',
                    'PHP_SELF' => '/index.php',
                    'DOCUMENT_ROOT' =>  vendor('wordpress/wordpress')
                ]
            ],
            'existing_file' => [
                '/xmlrpc.php',
                [
                    'PATH_INFO' => '',
                    'REQUEST_URI' => '/xmlrpc.php',
                    'PHP_SELF' => '/xmlrpc.php',
                    'DOCUMENT_ROOT' =>  vendor('wordpress/wordpress')
                ]
            ],
            'post_permalink' => [
                '/hello-world/?preview_id=1&preview_nonce=8c1bf93665&preview=true',
                [
                    'PATH_INFO' => '',
                    'REQUEST_URI' => '/hello-world/?preview_id=1&preview_nonce=8c1bf93665&preview=true',
                    'PHP_SELF' => '/index.php',
                    'DOCUMENT_ROOT' =>  vendor('wordpress/wordpress')
                ]
            ]
        ];
    }

    /**
     * It should return the correct path for diff. URIs
     *
     * @test
     * @dataProvider urisCorrectPathDataSet
     */
    public function should_return_the_correct_path_for_diff_uris($uri, $expected)
    {
        $rootDir = vendor('wordpress/wordpress');
        $fileRouter = new FileRouter($rootDir);

        $resolved = $fileRouter->getServerVarsForUri($uri);

        $this->assertEquals($expected, $resolved);
    }
}
