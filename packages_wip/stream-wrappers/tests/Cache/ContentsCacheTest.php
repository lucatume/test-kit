<?php
namespace lucatume\StreamWrappers\Cache;

use lucatume\Utils\Traits\WithTestNames;
use PHPUnit\Framework\TestCase;
use function lucatume\functions\data;
use function lucatume\functions\pathJoin;

class ContentsCacheTest extends TestCase
{
    use WithTestNames;

    /**
     * It should return false when trying to get uncached file
     *
     * @test
     */
    public function should_return_false_when_trying_to_get_uncached_file()
    {
        $cache = new ContentsCache(__METHOD__, $this->methodCacheDir(__METHOD__));

        $this->assertFalse($cache->getFileContentsFor(__FILE__, $this->getTestMethodName()));
    }

    /**
     * It should return the cached contents when file is cached
     *
     * @test
     */
    public function should_return_the_cached_contents_when_file_is_cached()
    {
        $cache = new ContentsCache(__METHOD__, $this->methodCacheDir(__METHOD__));

        $patchedContents  = '<?php echo "bar";';
        $cache->putFileContents(__FILE__, $this->getTestMethodName(), $patchedContents);

        $this->assertEquals($patchedContents, $cache->getFileContentsFor(__FILE__, $this->getTestMethodName()));
    }

    /**
     * It should return false if cached file deleted during request
     *
     * @test
     */
    public function should_return_false_if_cached_file_deleted_during_request()
    {
        $cache = new ContentsCache(__METHOD__, $this->methodCacheDir(__METHOD__));

        $patchedContents  = '<?php echo "bar";';
        $cacheFile = $cache->putFileContents(__FILE__, $this->getTestMethodName(), $patchedContents);

        unlink($cacheFile);

        $this->assertFalse($cache->getFileContentsFor(__FILE__, $this->getTestMethodName()));
    }

    /**
     * It should report cached files
     *
     * @test
     */
    public function should_report_cached_files()
    {
        $cache = new ContentsCache(__METHOD__, $this->methodCacheDir(__METHOD__));

        $patchedContents  = '<?php echo "bar";';
        $cacheFile = $cache->putFileContents(__FILE__, $this->getTestMethodName(), $patchedContents);

        $this->assertEquals([__FILE__ => $cacheFile], $cache->getCachedFiles());
    }

    /**
     * It should report cache hits
     *
     * @test
     */
    public function should_report_cache_hits()
    {
        $cache = new ContentsCache(__METHOD__, $this->methodCacheDir(__METHOD__));

        $patchedContents  = '<?php echo "bar";';
        $cacheFile = $cache->putFileContents(__FILE__, $this->getTestMethodName(), $patchedContents);

        $cache->getFileContentsFor(__FILE__, $this->getTestMethodName());

        $this->assertEquals([__FILE__ => $cacheFile], $cache->getHits());
    }

    /**
     * It should report cache misses
     *
     * @test
     */
    public function should_report_cache_misses()
    {
        $cache = new ContentsCache(__METHOD__, $this->methodCacheDir(__METHOD__));

        $canary    = data('wrap/canary.php');
        $cacheFile = $cache->getFileName($canary, $this->getTestMethodName());
        $cache->getFileContentsFor($canary, $this->getTestMethodName());

        $this->assertEquals([$canary => $cacheFile], $cache->getMisses());
    }

    protected function methodCacheDir($method)
    {
        return pathJoin(sys_get_temp_dir(), md5($method));
    }
}
