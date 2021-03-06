<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth;
/**
 * Test for stubbles\webapp\auth\Token.
 *
 * @since  5.0.0
 * @group  auth
 */
class TokenTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function canCreateTokenFromUser()
    {
        $this->assertInstanceOf(
                'stubbles\webapp\auth\Token',
                Token::create($this->getMock('stubbles\webapp\auth\User'), 'some caramel salt')
        );
    }

    /**
     * @return  array
     */
    public function tokenValues()
    {
        $tokenValues = $this->emptyValues();
        $tokenValues[] = ['someTokenValue'];
        return $tokenValues;
    }

    /**
     * @param  string  $tokenValue
     * @test
     * @dataProvider  tokenValues
     */
    public function tokenCanBeCastedToString($tokenValue)
    {
        $token = new Token($tokenValue);
        $this->assertEquals($tokenValue, (string) $token);
    }

    /**
     * @return  array
     */
    public function emptyValues()
    {
        return [[null], ['']];
    }

    /**
     * @param  string  $emptyValue
     * @test
     * @dataProvider  emptyValues
     */
    public function tokenIsEmptyWhenValueIsEmpty($emptyValue)
    {
        $token = new Token($emptyValue);
        $this->assertTrue($token->isEmpty());
    }

    /**
     * @test
     */
    public function tokenIsNotEmptyWhenValueNotEmpty()
    {
        $token = new Token('someTokenValue');
        $this->assertFalse($token->isEmpty());
    }
}
