<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\interceptor;
use stubbles\input\ValueReader;
use stubbles\lang;
/**
 * Tests for net\stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader.
 *
 * @since  3.4.0
 * @group  interceptor
 */
class AddAccessControlAllowOriginHeaderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  AddAccessControlAllowOriginHeader
     */
    private $addAccessControlAllowOriginHeader;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResponse;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->addAccessControlAllowOriginHeader = new AddAccessControlAllowOriginHeader();
        $this->mockRequest  = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
    }

    /**
     * @test
     */
    public function annotationsPresentOnAllowOriginHostsMethod()
    {
        $method = lang\reflect($this->addAccessControlAllowOriginHeader, 'allowOriginHosts');
        $this->assertTrue($method->hasAnnotation('Inject'));
        $this->assertTrue($method->getAnnotation('Inject')->isOptional());
        $this->assertTrue($method->hasAnnotation('Property'));
        $this->assertEquals(
                'net.stubbles.webapp.origin.hosts',
                $method->getAnnotation('Property')->getValue()
        );
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenNoAllowedOriginHostConfigured()
    {
        $this->mockResponse->expects($this->never())
                           ->method('addHeader');
        $this->addAccessControlAllowOriginHeader->postProcess($this->mockRequest, $this->mockResponse);
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenRequestContainsNoOriginHeader()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasHeader')
                          ->will($this->returnValue(false));
        $this->mockResponse->expects($this->never())
                           ->method('addHeader');
        $this->addAccessControlAllowOriginHeader->allowOriginHosts('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$')
                                                ->postProcess($this->mockRequest, $this->mockResponse);
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenOriginFromRequestDoesNotMatchAllowedOriginHosts()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readHeader')
                          ->will($this->returnValue(ValueReader::forValue('http://example.net')));
        $this->mockResponse->expects($this->never())
                           ->method('addHeader');
        $this->addAccessControlAllowOriginHeader->allowOriginHosts('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$')
                                                ->postProcess($this->mockRequest, $this->mockResponse);
    }

    /**
     * @test
     */
    public function addsHeaderWhenOriginFromRequestIsAllowed()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readHeader')
                          ->will($this->returnValue(ValueReader::forValue('http://foo.example.com:9039')));
        $this->mockResponse->expects($this->once())
                           ->method('addHeader')
                           ->with(
                                $this->equalTo('Access-Control-Allow-Origin'),
                                $this->equalTo('http://foo.example.com:9039')
                             );
        $this->addAccessControlAllowOriginHeader->allowOriginHosts('^http://[a-zA-Z0-9-\.]+example\.net(:[0-9]{4})?$|^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$')
                                                ->postProcess($this->mockRequest, $this->mockResponse);
    }
}
