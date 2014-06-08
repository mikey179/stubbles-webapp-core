<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
use stubbles\lang;
use stubbles\peer\http\HttpUri;
/**
 * Helper class for the test.
 */
abstract class TestWebApp extends WebApp
{
    /**
     * call method with given name and parameters and return its return value
     *
     * @param   string  $methodName
     * @param   string  $param1      optional
     * @param   string  $param2      optional
     * @return  Object
     */
    public static function callMethod($methodName, $param = null)
    {
        return self::$methodName($param);
    }
}
/**
 * Tests for stubbles\webapp\WebApp.
 *
 * @since  1.7.0
 * @group  core
 */
class WebAppTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  TestWebApp
     */
    private $webApp;
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
     * partially mocked routing
     *
     * @type  Routing
     */
    private $routing;
    /**
     * mocked exception logger
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockExceptionLogger;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest  = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockRequest->expects($this->any())
                          ->method('getMethod')
                          ->will($this->returnValue('GET'));
        $this->mockRequest->expects($this->any())
                          ->method('uri')
                          ->will($this->returnValue(HttpUri::fromString('http://example.com/hello')));
        $this->mockResponse     = $this->getMock('stubbles\webapp\response\Response');
        $mockResponseNegotiator = $this->getMockBuilder('stubbles\webapp\response\ResponseNegotiator')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $mockResponseNegotiator->expects($this->any())
                               ->method('negotiateMimeType')
                               ->will($this->returnValue($this->mockResponse));
        $this->routing = $this->getMockBuilder('stubbles\webapp\Routing')
                              ->disableOriginalConstructor()
                              ->getMock();
        $this->mockExceptionLogger = $this->getMockBuilder('stubbles\lang\errorhandler\ExceptionLogger')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $this->webApp  = $this->getMock('stubbles\webapp\TestWebApp',
                                        ['configureRouting'],
                                        [$this->mockRequest,
                                         $mockResponseNegotiator,
                                         $this->routing,
                                         $this->mockExceptionLogger
                                        ]
                         );
    }

    /**
     * @test
     */
    public function annotationPresentOnConstructor()
    {
        $this->assertTrue(lang\reflectConstructor($this->webApp)->hasAnnotation('Inject'));
    }

    /**
     * @test
     */
    public function canCreateIoBindingModuleWithSession()
    {
        $this->assertInstanceOf('stubbles\webapp\ioc\IoBindingModule',
                                TestWebApp::callMethod('createIoBindingModuleWithSession')
        );
    }

    /**
     * @test
     */
    public function canCreateIoBindingModuleWithoutSession()
    {
        $this->assertInstanceOf('stubbles\webapp\ioc\IoBindingModule',
                                TestWebApp::callMethod('createIoBindingModuleWithoutSession')
        );
    }

    /**
     *
     * @param type $mockRoute
     */
    private function createMockRoute()
    {
        $mockRoute = $this->getMockBuilder('stubbles\webapp\ProcessableRoute')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockRoute->expects($this->once())
                  ->method('getSupportedMimeTypes')
                  ->will($this->returnValue(new response\SupportedMimeTypes([])));
        $this->routing->expects($this->once())
                      ->method('findRoute')
                      ->will($this->returnValue($mockRoute));
        return $mockRoute;
    }

    /**
     * @test
     */
    public function doesNothingIfResponseNegotiationFails()
    {
        $this->createMockRoute();
        $this->mockResponse->expects($this->once())
                          ->method('isFixed')
                          ->will($this->returnValue(true));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function respondsWithRedirectHttpsUriIfRequiresHttps()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('getHttpsUri')
                  ->will($this->returnValue('https://example.net/admin'));
        $this->mockResponse->expects($this->once())
                           ->method('redirect')
                           ->with($this->equalTo('https://example.net/admin'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function doesNotExecuteRouteAndPostInterceptorsIfPreInterceptorCancelsRequest()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->never())
                  ->method('process');
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromPreInterceptors()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $exception = new \Exception('some error');
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->throwException($exception));
        $mockRoute->expects($this->never())
                  ->method('process');
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->mockExceptionLogger->expects($this->once())
                                  ->method('log')
                                  ->with($this->equalTo($exception));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('some error'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function doesNotExecutePostInterceptorsIfRouteCancelsRequest()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromRoute()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $exception = new \Exception('some error');
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->throwException($exception));
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->mockExceptionLogger->expects($this->once())
                                  ->method('log')
                                  ->with($this->equalTo($exception));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('some error'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function doesExecuteEverythingIfRequestNotCancelled()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('applyPostInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    );
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromPostInterceptors()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $exception = new \Exception('some error');
        $mockRoute->expects($this->once())
                  ->method('applyPostInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->throwException($exception));
        $this->mockExceptionLogger->expects($this->once())
                                  ->method('log')
                                  ->with($this->equalTo($exception));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('some error'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function executesEverythingButSendsHeadOnlyWhenRequestMethodIsHead()
    {
        $this->mockRequest  = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockRequest->expects($this->any())
                          ->method('method')
                          ->will($this->returnValue('HEAD'));
        $this->mockRequest->expects($this->any())
                          ->method('uri')
                          ->will($this->returnValue(HttpUri::fromString('http://example.com/hello')));
        $mockResponseNegotiator = $this->getMockBuilder('stubbles\webapp\response\ResponseNegotiator')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $mockResponseNegotiator->expects($this->any())
                               ->method('negotiateMimeType')
                               ->will($this->returnValue($this->mockResponse));
        $this->webApp  = $this->getMock('stubbles\webapp\TestWebApp',
                                        ['configureRouting'],
                                        [$this->mockRequest,
                                         $mockResponseNegotiator,
                                         $this->routing,
                                         $this->mockExceptionLogger
                                        ]
                         );
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('switchToHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('applyPostInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    );
        $this->mockResponse->expects($this->once())
                           ->method('sendHead');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }
}
