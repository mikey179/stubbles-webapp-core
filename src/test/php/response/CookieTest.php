<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
/**
 * Tests for stubbles\webapp\response\Cookie.
 *
 * @group  response
 */
class CookieTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function returnsGivenName()
    {
        $this->assertEquals('foo', Cookie::create('foo', 'bar')->name());
    }

    /**
     * @test
     */
    public function returnsGivenValue()
    {
        $this->assertEquals('bar', Cookie::create('foo', 'bar')->value());
    }

    /**
     * @test
     */
    public function hasNoExpirationDateByDefault()
    {
        $this->assertEquals(0, Cookie::create('foo', 'bar')->expiration());
    }

    /**
     * @test
     */
    public function hasNoPathByDefault()
    {
        $this->assertNull(Cookie::create('foo', 'bar')->path());
    }

    /**
     * @test
     */
    public function hasNoDomainByDefault()
    {
        $this->assertNull(Cookie::create('foo', 'bar')->domain());
    }

    /**
     * @test
     */
    public function isNotRestrictedToSslByDefault()
    {
        $this->assertFalse(Cookie::create('foo', 'bar')->isRestrictedToSsl());
    }

    /**
     * @test
     */
    public function isHttpOnlyByDefault()
    {
        $this->assertTrue(Cookie::create('foo', 'bar')->isHttpOnly());
    }

    /**
     * @test
     */
    public function expiresAtUsesGivenTimestamp()
    {
        $expires = time() + 100; // expire after 100 seconds
        $this->assertEquals($expires,
                            Cookie::create('foo', 'bar')
                                  ->expiringAt($expires)
                                  ->expiration()
        );
    }

    /**
     * @test
     * @group  bug255
     */
    public function expiresInAddsCurrentTime()
    {
        $this->assertGreaterThanOrEqual(time() + 100,
                                        Cookie::create('foo', 'bar')
                                              ->expiringIn(100)
                                              ->expiration()
        );
    }

    /**
     * @test
     */
    public function usesGivenPath()
    {
        $this->assertEquals('bar',
                            Cookie::create('foo', 'bar')
                                  ->forPath('bar')
                                  ->path()
        );
    }

    /**
     * @test
     */
    public function usesGivenDomain()
    {
        $this->assertEquals('.example.org',
                            Cookie::create('foo', 'bar')
                                  ->forDomain('.example.org')
                                  ->domain()
        );
    }

    /**
     * @test
     */
    public function isRestrictedToSslIfEnabled()
    {
        $this->assertTrue(Cookie::create('foo', 'bar')
                                ->restrictToSsl()
                                ->isRestrictedToSsl()

         );
    }

    /**
     * @test
     */
    public function httpOnlyCanBeDisabled()
    {
        $this->assertFalse(Cookie::create('foo', 'bar')
                                 ->disableHttpOnly()
                                 ->isHttpOnly()

         );
    }
}
