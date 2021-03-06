<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp\session
 */
namespace stubbles\webapp\websession;
use stubbles\lang;
/**
 * Tests for stubbles\webapp\websession\SessionBindingScope.
 *
 * @group  websession
 * @group  ioc
 */
class SessionBindingScopeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  SessionScope
     */
    private $sessionScope;
    /**
     * mocked session id
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSession;
    /**
     * mocked injection provider
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjectionProvider;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->mockSession           = $this->getMock('stubbles\webapp\session\Session');
        $this->sessionScope          = new SessionBindingScope($this->mockSession);
        $this->mockInjectionProvider = $this->getMock('stubbles\ioc\InjectionProvider');
    }

    /**
     * @test
     */
    public function returnsInstanceFromSessionIfPresent()
    {
        $instance = new \stdClass();
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(true));
        $this->mockSession->expects($this->once())
                          ->method('value')
                          ->will($this->returnValue($instance));
        $this->mockInjectionProvider->expects($this->never())
                          ->method('get');
        $this->assertSame($instance,
                          $this->sessionScope->getInstance(lang\reflect('\stdClass'),
                                                           $this->mockInjectionProvider
                                               )
        );
    }

    /**
     * @test
     */
    public function createsInstanceIfNotPresent()
    {
        $instance = new \stdClass();
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(false));
        $this->mockSession->expects($this->never())
                          ->method('value');
        $this->mockInjectionProvider->expects($this->once())
                          ->method('get')
                          ->will($this->returnValue($instance));
        $this->assertSame($instance,
                          $this->sessionScope->getInstance(lang\reflect('\stdClass'),
                                                           $this->mockInjectionProvider
                                               )
        );
    }
}
