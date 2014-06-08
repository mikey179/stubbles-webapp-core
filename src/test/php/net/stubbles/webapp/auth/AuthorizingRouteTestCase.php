<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\auth;
use stubbles\peer\http\HttpUri;
use net\stubbles\webapp\Route;
use net\stubbles\webapp\response\SupportedMimeTypes;
/**
 * Tests for net\stubbles\webapp\auth\AuthorizingRoute.
 *
 * @since  3.0.0
 * @group  auth
 */
class AuthorizingRouteTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  AuthorizingRoute
     */
    private $authorizingRoute;
    /**
     * mocked auth handler
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockAuthHandler;
    /**
     * route configuration
     *
     * @type  Route
     */
    private $routeConfig;
    /**
     * actual route to execute
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockActualRoute;
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
        $this->mockAuthHandler  = $this->getMock('net\stubbles\webapp\auth\AuthHandler');
        $this->routeConfig      = new Route('/hello', function() {});
        $this->mockActualRoute  = $this->getMock('net\stubbles\webapp\ProcessableRoute');
        $this->authorizingRoute = new AuthorizingRoute($this->mockAuthHandler,
                                                       $this->routeConfig,
                                                       $this->mockActualRoute
                                  );
        $this->mockRequest      = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse     = $this->getMock('net\stubbles\webapp\response\Response');
    }

    /**
     * @test
     */
    public function requiresSwitchToHttpsWhenActualRouteDoes()
    {
        $this->mockActualRoute->expects($this->once())
                              ->method('switchToHttps')
                              ->will($this->returnValue(true));
        $this->assertTrue($this->authorizingRoute->switchToHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriOfActualRoute()
    {
        $httpsUri = HttpUri::fromString('https://example.com/hello');
        $this->mockActualRoute->expects($this->once())
                              ->method('getHttpsUri')
                              ->will($this->returnValue($httpsUri));
        $this->assertSame($httpsUri, $this->authorizingRoute->getHttpsUri());
    }

    /**
     * @test
     */
    public function returnsSupportedMimeTypesOfActualRoute()
    {
        $supportedMimeTypes = new SupportedMimeTypes(array());
        $this->mockActualRoute->expects($this->once())
                              ->method('getSupportedMimeTypes')
                              ->will($this->returnValue($supportedMimeTypes));
        $this->assertSame($supportedMimeTypes, $this->authorizingRoute->getSupportedMimeTypes());
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsReturnsFalseWhenAuthenticationThrowsAuthHandlerException()
    {
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->throwException(AuthHandlerException::internal('error')));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersInternalServerErrorWhenAuthenticationThrowsInternalAuthHandlerException()
    {
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->throwException(AuthHandlerException::internal('error')));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('error'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersStatusCode503WhenAuthenticationThrowsExternalAuthHandlerException()
    {
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->throwException(AuthHandlerException::external('error')));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(503))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('error'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPreInterceptorsReturnsFalseWhenNotAuthenticated()
    {
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(false));
        $this->mockAuthHandler->expects($this->once())
                              ->method('getLoginUri')
                              ->will($this->returnValue('https://login.example.com/'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPreInterceptorsTriggersRedirectToLoginUriWhenNotAuthenticated()
    {
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(false));
        $this->mockAuthHandler->expects($this->once())
                              ->method('getLoginUri')
                              ->will($this->returnValue('https://login.example.com/'));
        $this->mockResponse->expects($this->once())
                           ->method('redirect')
                           ->with($this->equalTo('https://login.example.com/'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsReturnsFalseWhenAuthorizationThrowsAuthHandlerException()
    {
        $this->routeConfig->withRoleOnly('admin');
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthorized')
                              ->with($this->equalTo('admin'))
                              ->will($this->throwException(AuthHandlerException::internal('error')));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersInternalServerErrorWhenAuthorizationThrowsInternalAuthHandlerException()
    {
        $this->routeConfig->withRoleOnly('admin');
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthorized')
                              ->with($this->equalTo('admin'))
                              ->will($this->throwException(AuthHandlerException::internal('error')));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('error'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersStatusCode503WhenAuthorizationThrowsExternalAuthHandlerException()
    {
        $this->routeConfig->withRoleOnly('admin');
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthorized')
                              ->with($this->equalTo('admin'))
                              ->will($this->throwException(AuthHandlerException::external('error')));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(503))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('error'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPreInterceptorsReturnsFalseWhenNotAuthorized()
    {
        $this->routeConfig->withRoleOnly('admin');
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthorized')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(false));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPreInterceptorsTriggers403ForbiddenWhenNotAuthorized()
    {
        $this->routeConfig->withRoleOnly('admin');
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthorized')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(false));
        $this->mockResponse->expects($this->once())
                           ->method('forbidden');
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndAuthorized()
    {
        $this->routeConfig->withRoleOnly('admin');
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthorized')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(true));
        $this->mockActualRoute->expects($this->once())
                              ->method('applyPreInterceptors')
                              ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                              ->will($this->returnValue(true));
        $this->assertTrue($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndNoSpecificAuthorizationRequired()
    {
        $this->routeConfig->withLoginOnly();
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->never())
                              ->method('isAuthorized');
        $this->mockActualRoute->expects($this->once())
                              ->method('applyPreInterceptors')
                              ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                              ->will($this->returnValue(true));
        $this->assertTrue($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function doesNotCallsProcessOfActualRouteWhenNotAuthorized()
    {
        $this->mockActualRoute->expects($this->never())
                              ->method('process');
        $this->assertFalse($this->authorizingRoute->process($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function processCallsProcessOfActualRouteWhenAuthorized()
    {
        $this->routeConfig->withRoleOnly('admin');
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthorized')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(true));
        $this->mockActualRoute->expects($this->once())
                              ->method('applyPreInterceptors')
                              ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                              ->will($this->returnValue(true));
        $this->mockActualRoute->expects($this->once())
                              ->method('process')
                              ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                              ->will($this->returnValue(true));
        $this->assertTrue($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
        $this->assertTrue($this->authorizingRoute->process($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPostInterceptorsDoesNotCallActualRouteWhenNotAuthorized()
    {
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPostInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPostInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPostInterceptorsCallsActualRouteWhenAuthorized()
    {
        $this->routeConfig->withRoleOnly('admin');
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthenticated')
                              ->will($this->returnValue(true));
        $this->mockAuthHandler->expects($this->once())
                              ->method('isAuthorized')
                              ->with($this->equalTo('admin'))
                              ->will($this->returnValue(true));
        $this->mockActualRoute->expects($this->once())
                              ->method('applyPreInterceptors')
                              ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                              ->will($this->returnValue(true));
        $this->mockActualRoute->expects($this->once())
                              ->method('applyPostInterceptors')
                              ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                              ->will($this->returnValue(true));
        $this->assertTrue($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
        $this->assertTrue($this->authorizingRoute->applyPostInterceptors($this->mockRequest, $this->mockResponse));
    }
}