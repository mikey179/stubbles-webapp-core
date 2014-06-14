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
use stubbles\input\web\WebRequest;
use stubbles\webapp\response\Response;
/**
 * Class with annotations for tests.
 *
 * @RequiresHttps
 * @RequiresLogin
 */
class AnnotatedProcessor implements Processor
{
    /**
     * processes the request
     *
     * @param  WebRequest  $request   current request
     * @param  Response    $response  response to send
     * @param  UriPath     $uriPath   information about called uri path
     */
    public function process(WebRequest $request, Response $response, UriPath $uriPath)
    {
        // intentionally empty
    }
}
/**
 * Class with annotations for tests.
 *
 * @RequiresRole('superadmin')
 */
class OtherAnnotatedProcessor implements Processor
{
    /**
     * processes the request
     *
     * @param  WebRequest  $request   current request
     * @param  Response    $response  response to send
     * @param  UriPath     $uriPath   information about called uri path
     */
    public function process(WebRequest $request, Response $response, UriPath $uriPath)
    {
        // intentionally empty
    }
}
/**
 * Tests for stubbles\webapp\Route.
 *
 * @since  2.0.0
 * @group  core
 */
class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException  stubbles\lang\exception\IllegalArgumentException
     */
    public function constructRouteWithInvalidCallbackThrowsIllegalArgumentException()
    {
        new Route('/hello', 500, 'GET');
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\IllegalArgumentException
     * @since  4.0.0
     */
    public function constructRouteWithInvalidRequestMethodThrowsIllegalArgumentException()
    {
        new Route('/hello', function() {}, 500);
    }

    /**
     * creates instance to test
     *
     * @param   string  $method
     * @return  Route
     */
    private function createRoute($method = 'GET')
    {
        return new Route('/hello/{name}',
                         function(WebRequest $request, Response $response, UriPath $uriPath)
                         {
                             $response->setStatusCode(418)
                                      ->write('Hello ' . $uriPath->readArgument('name')->asString());
                             $request->cancel();
                         },
                         $method
        );
    }

    /**
     * @test
     */
    public function allowedRequestMethodsContainAllIfNoneGiven()
    {
        $this->assertEquals(
                ['GET', 'HEAD', 'POST', 'PUT', 'DELETE'],
                $this->createRoute(null)->allowedRequestMethods()
        );
    }

    /**
     * @test
     */
    public function allowedRequestMethodsContainGivenSingleMethodOnly()
    {
        $this->assertEquals(['GET'], $this->createRoute()->allowedRequestMethods());
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function allowedRequestMethodsContainGivenListOfMethodOnly()
    {
        $this->assertEquals(['POST', 'PUT'], $this->createRoute(['POST', 'PUT'])->allowedRequestMethods());
    }

    /**
     * @test
     */
    public function doesNotMatchUriRequestIfRequestMethodsDiffer()
    {
        $this->assertFalse($this->createRoute()->matches(new UriRequest('http://example.com/hello/world', 'DELETE')));
    }

    /**
     * @test
     */
    public function doesNotMatchUriRequestIfRequestPathsDiffers()
    {
        $this->assertFalse($this->createRoute()->matches(new UriRequest('http://example.com/other', 'GET')));
    }

    /**
     * @test
     */
    public function matchesIfPathAndMethodAreOk()
    {
        $this->assertTrue($this->createRoute()->matches(new UriRequest('http://example.com/hello/world', 'GET')));
    }

    /**
     * @test
     */
    public function doesNotMatchPathIfDiffers()
    {
        $this->assertFalse($this->createRoute()->matchesPath(new UriRequest('http://example.com/other', 'GET')));
    }

    /**
     * @test
     */
    public function matchesPathIfPathOk()
    {
        $this->assertTrue($this->createRoute()->matchesPath(new UriRequest('http://example.com/hello/world', 'GET')));
    }

    /**
     * @test
     */
    public function matchesForHeadIfPathOkAndAllowedMethodIsGet()
    {
        $this->assertTrue($this->createRoute()->matches(new UriRequest('http://example.com/hello/world', 'HEAD')));
    }

    /**
     * @test
     */
    public function returnsGivenPath()
    {
        $this->assertEquals('/hello/{name}', $this->createRoute()->configuredPath());
    }

    /**
     * @test
     */
    public function returnsGivenCallback()
    {
        $route = new Route('/hello/{name}', __CLASS__);
        $this->assertEquals(__CLASS__, $route->callback());
    }

    /**
     * @test
     */
    public function hasNoPreInterceptorsByDefault()
    {
        $this->assertEquals([],
                            $this->createRoute()->preInterceptors()
        );
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\IllegalArgumentException
     */
    public function addInvalidPreInterceptorThrowsIllegalArgumentException()
    {
        $this->createRoute()->preIntercept(303);
    }

    /**
     * @test
     */
    public function hasGivenListOfPreInterceptors()
    {
        $preInterceptor     = function() {};
        $mockPreInterceptor = $this->getMock('stubbles\webapp\interceptor\PreInterceptor');
        $mockPreFunction    = 'array_map';
        $this->assertEquals([get_class($mockPreInterceptor),
                             $preInterceptor,
                             $mockPreInterceptor,
                             $mockPreFunction
                            ],
                            $this->createRoute()->preIntercept(get_class($mockPreInterceptor))
                                                ->preIntercept($preInterceptor)
                                                ->preIntercept($mockPreInterceptor)
                                                ->preIntercept($mockPreFunction)
                                                ->preInterceptors()
        );
    }

    /**
     * @test
     */
    public function hasNoPostInterceptorsByDefault()
    {
        $this->assertEquals([],
                            $this->createRoute()->postInterceptors()
        );
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\IllegalArgumentException
     */
    public function addInvalidPostInterceptorThrowsIllegalArgumentException()
    {
        $this->createRoute()->postIntercept(303);
    }

    /**
     * @test
     */
    public function hasGivenListOfPostInterceptors()
    {
        $postInterceptor     = function() {};
        $mockPostInterceptor = $this->getMock('stubbles\webapp\interceptor\PostInterceptor');
        $mockPostFunction    = 'array_map';
        $this->assertEquals([get_class($mockPostInterceptor),
                             $postInterceptor,
                             $mockPostInterceptor,
                             $mockPostFunction
                            ],
                            $this->createRoute()->postIntercept(get_class($mockPostInterceptor))
                                                ->postIntercept($postInterceptor)
                                                ->postIntercept($mockPostInterceptor)
                                                ->postIntercept($mockPostFunction)
                                                ->postInterceptors()
        );
    }

    /**
     * @test
     */
    public function doesNotRequireHttpsByDefault()
    {
        $this->assertFalse($this->createRoute()->requiresHttps());
    }

    /**
     * @test
     */
    public function requiresHttpsWhenWhenRestrictedToHttps()
    {
        $this->assertTrue($this->createRoute()->httpsOnly()->requiresHttps());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresHttpsWhenCallbackInstanceAnnotatedWithRequiresHttps()
    {
        $route = new Route('/hello/{name}',
                           new AnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->requiresHttps());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresHttpsWhenCallbackClassAnnotatedWithRequiresHttps()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\AnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->requiresHttps());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function doesNotRequireAuthByDefault()
    {
        $this->assertFalse($this->createRoute()->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenLoginIsRequired()
    {
        $this->assertTrue($this->createRoute()->withLoginOnly()->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresLogin()
    {
        $route = new Route('/hello/{name}',
                           new AnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRequiresLogin()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\AnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenRoleIsRequired()
    {
        $this->assertTrue($this->createRoute()->withRoleOnly('admin')->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           new OtherAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\OtherAnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenLoginAndRoleIsRequired()
    {
        $this->assertTrue($this->createRoute()->withLoginOnly()->withRoleOnly('admin')->requiresAuth());
    }

    /**
     * @test
     */
    public function doesNotRequireRoleByDefault()
    {
        $this->assertFalse($this->createRoute()->requiresRole());
    }

    /**
     * @test
     */
    public function returnsNullForRequiredRoleByDefault()
    {
        $this->assertNull($this->createRoute()->requiredRole());
    }

    /**
     * @test
     */
    public function requiresRoleWhenRoleIsSet()
    {
        $this->assertTrue($this->createRoute()->withRoleOnly('admin')->requiresRole());
    }

    /**
     * @test
     */
    public function returnsRequiredRoleWhenSet()
    {
        $this->assertEquals('admin',
                            $this->createRoute()
                                 ->withRoleOnly('admin')
                                 ->requiredRole()
        );
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresRoleWhenCallbackInstanceAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           new OtherAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->requiresRole());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresRoleWhenCallbackClassAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\OtherAnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->requiresRole());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function returnsRoleWhenCallbackInstanceAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           new OtherAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertEquals('superadmin', $route->requiredRole());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function returnsRoleWhenCallbackClassAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\OtherAnnotatedProcessor',
                           'GET'
                 );
        $this->assertEquals('superadmin', $route->requiredRole());
    }

    /**
     * @test
     */
    public function supportNoMimeTypeByDefault()
    {
        $this->assertEquals([],
                            $this->createRoute()
                                 ->supportedMimeTypes()
                                 ->asArray()
        );
    }

    /**
     * @test
     */
    public function returnsListOfAddedSupportedMimeTypes()
    {
        $this->assertEquals(['application/json', 'application/xml'],
                            $this->createRoute()
                                 ->supportsMimeType('application/json')
                                 ->supportsMimeType('application/xml')
                                 ->supportedMimeTypes()
                                 ->asArray()
        );
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function supportedMimeTypesContainSpecialFormatter()
    {
        $this->assertTrue($this->createRoute()
                               ->supportsMimeType('foo/bar', 'example\FooBarFormatter')
                               ->supportedMimeTypes()
                               ->provideFormatter('foo/bar')
        );
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function supportedMimeTypesContainSpecialFormatterClass()
    {
        $this->assertEquals('example\FooBarFormatter',
                            $this->createRoute()
                                 ->supportsMimeType('foo/bar', 'example\FooBarFormatter')
                                 ->supportedMimeTypes()
                                 ->formatterFor('foo/bar')
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationIsEnabledByDefault()
    {
        $this->assertFalse($this->createRoute()->supportedMimeTypes()->isContentNegotationDisabled());
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationCanBeDisabled()
    {
        $this->assertTrue($this->createRoute()
                               ->disableContentNegotiation()
                               ->supportedMimeTypes()
                               ->isContentNegotationDisabled()
        );
    }
}
