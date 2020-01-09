<?php

namespace lucatume\Beaker;

use PHPUnit\Framework\TestCase;
use SteveGrunwell\PHPUnit_Markup_Assertions\MarkupAssertionsTrait;

class BeakerTest extends TestCase
{
    use MarkupAssertionsTrait;

    /**
     * It should allow testing the page HTML w/o setup
     *
     * @test
     */
    public function should_allow_testing_the_page_html_w_o_setup()
    {
        $beaker = Beaker::fromDir('vendor/wordpress/wordpress');
        $response = $beaker->get('/');

        $html = $response->getBody()->getContents();

        $this->assertNotEmpty($html);
        $this->assertContainsSelector('body.home', $html);
    }
}
