<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\session;
use stubbles\lang;
use stubbles\webapp\auth\Roles;
/**
 * Tests for stubbles\webapp\auth\session\CachingAuthorizationProvider
 *
 * @since  5.0.0
 */
class CachingAuthorizationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\session\CachingAuthorizationProvider
     */
    private $cachingAuthorizationProvider;
    /**
     * mocked session
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSession;
    /**
     * mocked base authentication provider
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockAuthorizationProvider;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockSession                  = $this->getMock('stubbles\webapp\session\Session');
        $this->mockAuthorizationProvider    = $this->getMock('stubbles\webapp\auth\AuthorizationProvider');
        $this->cachingAuthorizationProvider = new CachingAuthorizationProvider(
                $this->mockSession,
                $this->mockAuthorizationProvider
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $constructor = lang\reflectConstructor($this->cachingAuthorizationProvider);
        $this->assertTrue($constructor->hasAnnotation('Inject'));

        $parameters = $constructor->getParameters();
        $this->assertTrue($parameters[1]->hasAnnotation('Named'));
        $this->assertEquals('original', $parameters[1]->getAnnotation('Named')->getName());
    }

    /**
     * @test
     */
    public function usesSessionValueIfRolesStoredInSession()
    {
        $roles = new Roles(['admin']);
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(true));
        $this->mockSession->expects($this->once())
                          ->method('value')
                          ->will($this->returnValue($roles));
        $this->mockAuthorizationProvider->expects($this->never())
                                        ->method('roles');
        $this->assertSame(
                $roles,
                $this->cachingAuthorizationProvider->roles(
                        $this->getMock('stubbles\input\web\WebRequest'),
                        $this->getMock('stubbles\webapp\auth\User')
                )
        );
    }

    /**
     * @test
     */
    public function storeReturnValueInSessionWhenOriginalAuthenticationProviderReturnsRoles()
    {
        $roles = new Roles(['admin']);
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(false));
        $this->mockAuthorizationProvider->expects($this->once())
                                         ->method('roles')
                                         ->will($this->returnValue($roles));
        $this->mockSession->expects($this->once())
                          ->method('putValue')
                          ->with($this->equalTo(Roles::SESSION_KEY), $this->equalTo($roles));
        $this->assertSame(
                $roles,
                $this->cachingAuthorizationProvider->roles(
                        $this->getMock('stubbles\input\web\WebRequest'),
                        $this->getMock('stubbles\webapp\auth\User')
                )
        );
    }
}
