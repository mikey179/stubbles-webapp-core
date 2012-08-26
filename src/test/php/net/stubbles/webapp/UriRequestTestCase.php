<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
/**
 * Tests for net\stubbles\webapp\UriRequest.
 *
 * @since  1.7.0
 * @group  core
 */
class UriRequestTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  UriRequest
     */
    private $uriRequest;
    /**
     * mocked http uri
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockHttpUri;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockHttpUri = $this->getMock('net\stubbles\peer\http\HttpUri');
        $this->uriRequest  = new UriRequest($this->mockHttpUri, 'GET');
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function canCreateInstanceFromString()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\UriRequest',
                                UriRequest::fromString('http://example.net/', 'GET')
        );
    }

    /**
     * mocks uri path
     *
     * @param  string  $path
     */
    private function mockUriPath($path)
    {
        $this->mockHttpUri->expects($this->any())
                          ->method('getPath')
                          ->will($this->returnValue($path));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodAlwaysEqualsNullMethod()
    {
        $this->assertTrue($this->uriRequest->methodEquals(null));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodAlwaysEqualsEmptyMethod()
    {
        $this->assertTrue($this->uriRequest->methodEquals(''));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodEqualsGivenMethod()
    {
        $this->assertTrue($this->uriRequest->methodEquals('GET'));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodDoesNotEqualsGivenMethod()
    {
        $this->assertFalse($this->uriRequest->methodEquals('POST'));
    }

    /**
     * data provider for satisfying path pattern tests
     *
     * @return  array
     */
    public function provideSatisfiedPathPattern()
    {
        return array(array('/hello/mikey', '\/hello\/([^\/]+)'),
                     array('/hello', '\/hello'),
                     array('/hello/world303', '\/hello\/[a-z0-9]+'),
                     array('/', '\/')
        );
    }

    /**
     * @test
     * @dataProvider  provideSatisfiedPathPattern
     */
    public function returnsTrueForSatisfiedPathPattern($mockPath, $pathPattern)
    {
        $this->mockUriPath($mockPath);
        $this->assertTrue($this->uriRequest->satisfiesPath($pathPattern));
    }

    /**
     * data provider for non satisfying path pattern tests
     *
     * @return  array
     */
    public function provideNonSatisfiedPathPattern()
    {
        return array(array('/rss/articles', '\/hello\/([^\/]+)'),
                     array('/hello/mikey', '\/hello'),
                     array('/hello/', '\/hello\/([^\/]+)'),
                     array('/hello/mikey', '\/'),
                     array('/hello/mikey', ''),
                     array('/hello/mikey', null)
        );
    }

    /**
     * @test
     * @dataProvider  provideNonSatisfiedPathPattern
     */
    public function returnsFalseForNonSatisfiedCondition($mockPath, $pathPattern)
    {
        $this->mockUriPath($mockPath);
        $this->assertFalse($this->uriRequest->satisfiesPath($pathPattern));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function isHttpsWhenRequestUriHasHttps()
    {
        $this->mockHttpUri->expects($this->once())
                          ->method('isHttps')
                          ->will($this->returnValue(true));
        $this->assertTrue($this->uriRequest->isHttps());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpReturnsTransformedUri()
    {
        $mockHttpUri = $this->getMock('net\stubbles\peer\http\HttpUri');
        $this->mockHttpUri->expects($this->once())
                          ->method('toHttp')
                          ->will($this->returnValue($mockHttpUri));
        $this->assertSame($mockHttpUri, $this->uriRequest->toHttp());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpsReturnsTransformedUri()
    {
        $mockHttpUri = $this->getMock('net\stubbles\peer\http\HttpUri');
        $this->mockHttpUri->expects($this->once())
                          ->method('toHttps')
                          ->will($this->returnValue($mockHttpUri));
        $this->assertSame($mockHttpUri, $this->uriRequest->toHttps());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function returnsStringRepresentationOfUri()
    {
        $this->mockHttpUri->expects($this->once())
                          ->method('__toString')
                          ->will($this->returnValue('http://example.net/foo/bar'));
        $this->assertEquals('http://example.net/foo/bar', (string) $this->uriRequest);
    }
}
?>