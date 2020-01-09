<?php

namespace lucatume\Beaker;

use PHPUnit\Framework\TestCase;

class StringStreamTest extends TestCase
{
    /**
     * It should allow building a stream from a string
     *
     * @test
     */
    public function should_allow_building_a_stream_from_a_string()
    {
        $stream = new StringStream('Hello there');

        $this->assertEquals('Hello there', $stream->getContents());
        $this->assertEquals(strlen('Hello there'), $stream->getSize());
    }

    /**
     * It should be seekable
     *
     * @test
     */
    public function should_be_seekable()
    {
        $stream = new StringStream('Hello there');
        $stream->seek(6);

        $this->assertTrue($stream->isSeekable());
        $this->assertEquals('th',$stream->read(2));
        $this->assertEquals(8, $stream->tell());
        $this->assertEquals('ere', $stream->getContents());
        $stream->rewind();
        $this->assertEquals('Hello there', $stream->getContents());
    }

    /**
     * It should not be writeable
     *
     * @test
     */
    public function should_not_be_writeable()
    {
        $stream = new StringStream('Hello there');

        $this->assertFalse($stream->isWritable());
        $this->expectException(\RuntimeException::class);
        $stream->write('test write');
    }

    /**
     * It should return the original output w/ __toString
     *
     * @test
     */
    public function should_return_the_original_output_w_to_string()
    {
        $stream = new StringStream('Hello there');
        $stream->seek(6);

        $this->assertEquals('Hello there', $stream->__toString());
    }

    /**
     * It should allow detaching the stream
     *
     * @test
     */
    public function should_allow_detaching_the_stream()
    {
        $stream = new StringStream('Hello there');

        $resource = $stream->detach();
        $this->assertInternalType('resource', $resource);
        fclose($resource);
    }

    /**
     * It should allow closing the stream
     *
     * @test
     */
    public function should_allow_closing_the_stream()
    {
        $stream = new StringStream('Hello there');

        $stream->close();

        $this->expectException(\RuntimeException::class);
        $stream->seek(23);
    }
}
