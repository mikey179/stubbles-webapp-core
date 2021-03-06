<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\interceptor;
use stubbles\input\web\WebRequest;
use stubbles\webapp\response\Response;
/**
 * Tests for stubbles\webapp\interceptor\Interceptors.
 *
 * @since  2.2.0
 * @group  interceptor
 */
class InterceptorsTest extends \PHPUnit_Framework_TestCase
{
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
     * mocked injector instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjector;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest  = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('stubbles\webapp\response\Response');
        $this->mockInjector = $this->getMockBuilder('stubbles\ioc\Injector')
                                   ->disableOriginalConstructor()
                                   ->getMock();
    }

    /**
     * creates instance to test
     *
     * @param   array         $preInterceptors
     * @param   array         $postInterceptors
     * @return  Interceptors
     */
    private function createInterceptors(array $preInterceptors = [], array $postInterceptors = [])
    {
        return new Interceptors($this->mockInjector, $preInterceptors, $postInterceptors);
    }

    /**
     * a callback
     *
     * @param  WebRequest  $request
     * @param  Response    $response
     */
    public function callableMethod(WebRequest $request, Response $response)
    {
        $response->addHeader('X-Binford', '6100 (More power!)');
    }

    /**
     * @test
     */
    public function respondsWithInternalServerErrorIfPreInterceptorDoesNotImplementInterface()
    {
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue(new \stdClass()));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError');
        $this->assertFalse($this->createInterceptors(['some\PreInterceptor',
                                                      'other\PreInterceptor'
                                                     ]
                                  )
                                ->preProcess($this->mockRequest,
                                             $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function doesNotCallOtherPreInterceptorsIfOneReturnsFalse()
    {
        $preInterceptor = $this->getMock('stubbles\webapp\interceptor\PreInterceptor');
        $preInterceptor->expects($this->once())
                       ->method('preProcess')
                       ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                       ->will($this->returnValue(false));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue($preInterceptor));
        $this->assertFalse($this->createInterceptors(['some\PreInterceptor',
                                                      'other\PreInterceptor'
                                                     ]
                                  )
                                ->preProcess($this->mockRequest,
                                             $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function returnsTrueWhenNoPreInterceptorReturnsFalse()
    {
        $mockPreInterceptor = $this->getMock('stubbles\webapp\interceptor\PreInterceptor');
        $mockPreInterceptor->expects($this->exactly(2))
                           ->method('preProcess')
                           ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue($mockPreInterceptor));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->once())
                           ->method('addHeader');
        $this->assertTrue($this->createInterceptors(['some\PreInterceptor',
                                                     $mockPreInterceptor,
                                                     function(WebRequest $request, Response $response)
                                                     {
                                                         $response->setStatusCode(418);
                                                     },
                                                     [$this, 'callableMethod']
                                                    ]
                                  )
                                ->preProcess($this->mockRequest,
                                             $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function respondsWithInternalServerErrorIfPostInterceptorDoesNotImplementInterface()
    {
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue(new \stdClass()));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError');
        $this->assertFalse($this->createInterceptors([],
                                                     ['some\PostInterceptor',
                                                      'other\PostInterceptor'
                                                     ]
                                  )
                                ->postProcess($this->mockRequest,
                                              $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function doesNotCallOtherPostInterceptorsIfOneReturnsFalse()
    {
        $postInterceptor = $this->getMock('stubbles\webapp\interceptor\PostInterceptor');
        $postInterceptor->expects($this->once())
                        ->method('postProcess')
                        ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                        ->will($this->returnValue(false));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($postInterceptor));
        $this->assertFalse($this->createInterceptors([],
                                                     ['some\PostInterceptor',
                                                      'other\PostInterceptor'
                                                     ]
                                  )
                                ->postProcess($this->mockRequest,
                                              $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function returnsTrueWhenNoPostInterceptorReturnsFalse()
    {
        $mockPostInterceptor = $this->getMock('stubbles\webapp\interceptor\PostInterceptor');
        $mockPostInterceptor->expects($this->exactly(2))
                            ->method('postProcess')
                            ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($mockPostInterceptor));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->once())
                           ->method('addHeader');
        $this->assertTrue($this->createInterceptors([],
                                                    ['some\PostInterceptor',
                                                     $mockPostInterceptor,
                                                     function(WebRequest $request, Response $response)
                                                     {
                                                         $response->setStatusCode(418);
                                                     },
                                                     [$this, 'callableMethod']
                                                    ]
                                  )
                                ->postProcess($this->mockRequest,
                                              $this->mockResponse
                                  )
        );
    }

}
