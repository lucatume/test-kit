<?php

namespace lucatume\Beaker;

use lucatume\StreamWrappers\Run;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    /**
     * It should build from file wrapper run result
     *
     * @test
     */
    public function should_build_from_file_wrapper_run_result()
    {
        /** @var Run $run */
        $run = $this->prophesize(Run::class);
        $run->getSentHeaders()->willReturn([
            'X-Test-Header-1' => ['foo'],
            'X-Test-Header-2' => ['bar', 'baz']
        ]);
        $run->getOutput()->willReturn('Hello world!');
        $run->getExitCodeOrMessage()->willReturn(200);

        $response = Response::fromFileWrapperRunResult($run->reveal());

        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals([
            'X-Test-Header-1' => ['foo'],
            'X-Test-Header-2' => ['bar', 'baz']
        ], $response->getHeaders());
        $this->assertEquals(['foo'], $response->getHeader('X-Test-Header-1'));
        $this->assertTrue($response->hasHeader('x-test-header-1'));
        $this->assertTrue($response->hasHeader('X-TEST-HEADER-2'));
        $this->assertFalse($response->hasHeader('X-Test-Header-3'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getReasonPhrase());
    }

    /**
     * It should allow building new response w/ diff. body
     *
     * @test
     */
    public function should_allow_building_new_response_w_diff_body()
    {
        $response = new Response;
        $cloneResponse = $response->withBody(new StringStream('Hello there!'));

        $this->assertNotSame($response,$cloneResponse);
        $this->assertEquals('Hello there!', $cloneResponse->getBody()->getContents());
    }

    /**
     * It should allow building a new response w/ diff. protocol version
     *
     * @test
     */
    public function should_allow_building_a_new_response_w_diff_protocol_version()
    {
        $response = new Response;
        $cloneResponse = $response->withProtocolVersion('2.0');

        $this->assertNotSame($response,$cloneResponse);
        $this->assertEquals('1.1',$response->getProtocolVersion());
        $this->assertEquals('2.0', $cloneResponse->getProtocolVersion());
    }

    /**
     * It should allow building a new response w/ diff header
     *
     * @test
     */
    public function should_allow_building_a_new_response_w_diff_header()
    {
        $response = new Response;
        $cloneResponse = $response->withHeader('X-Test-Header',['foo']);

        $this->assertNotSame($response,$cloneResponse);
        $this->assertEquals([],$response->getHeader('X-Test-Header'));
        $this->assertEquals(['foo'],$cloneResponse->getHeader('X-Test-Header'));
        $this->assertEquals([],$response->getHeaders());
        $this->assertEquals(['X-Test-Header' => ['foo']],$cloneResponse->getHeaders());

        return $cloneResponse;
    }

    /**
     * It should allow adding and removing headers
     *
     * @test
     * @depends should_allow_building_a_new_response_w_diff_header
     */
    public function should_allow_adding_headers(Response $response)
    {
        $clone =  $response->withAddedHeader('X-Test-Header',89);
        $clone =  $clone->withAddedHeader('X-Test-Header',[23]);
        $clone =  $clone->withAddedHeader('X-Test-Header-2',2389);

        $this->assertNotSame($response,$clone);

        $this->assertEquals(['foo',89,23], $clone->getHeader('X-Test-Header'));
        $this->assertEquals([2389], $clone->getHeader('X-Test-Header-2'));

        return $clone;
    }

    /**
     * It should allow removing headers
     *
     * @test
     * @depends should_allow_adding_headers
     */
    public function should_allow_removing_headers(Response $response)
    {
        $clone =  $response->withoutHeader('X-Test-Header');

        $this->assertNotSame($response,$clone);

        $this->assertEquals([], $clone->getHeader('X-Test-Header'));
        $this->assertEquals([2389], $clone->getHeader('X-Test-Header-2'));
    }

    /**
     * It should allow building a new response w/ diff. status
     *
     * @test
     */
    public function should_allow_building_a_new_response_w_diff_status()
    {
        $response = new Response;
        $cloneResponse = $response->withStatus(403, 'Not Authorized');

        $this->assertNotSame($response,$cloneResponse);
        $this->assertEquals(200,$response->getStatusCode());
        $this->assertEquals('',$response->getReasonPhrase());
        $this->assertEquals(403,$cloneResponse->getStatusCode());
        $this->assertEquals('Not Authorized',$cloneResponse->getReasonPhrase());
    }
}
