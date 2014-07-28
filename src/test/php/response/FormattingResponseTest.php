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
 * Tests for stubbles\webapp\response\FormattingResponse.
 *
 * @group  response
 */
class FormattingResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  FormattingResponse
     */
    private $formattingResponse;
    /**
     * decorated response
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $decoratedResponse;
    /**
     * mocked formatter
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFormatter;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->decoratedResponse  = $this->getMock('stubbles\webapp\response\Response');
        $this->mockFormatter      = $this->getMock('stubbles\webapp\response\format\Formatter');
        $this->formattingResponse = new FormattingResponse($this->decoratedResponse,
                                                           $this->mockFormatter,
                                                           'text/plain'
                                    );
    }

    /**
     * @test
     */
    public function clearsDataFromDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('clear');
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->clear()
        );
    }

    /**
     * @test
     */
    public function setsStatusCodeOnDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('setStatusCode')
                                ->with($this->equalTo(418));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->setStatusCode(418)
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function returnsStatusCodeFromDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('statusCode')
                                ->will($this->returnValue(418));
        $this->assertEquals(418, $this->formattingResponse->statusCode());
    }

    /**
     * @test
     */
    public function addsHeaderOnDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('addHeader')
                                ->with($this->equalTo('X-Binford'), $this->equalTo('6100'));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->addHeader('X-Binford', '6100')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function headersReturnsHeaderList()
    {
        $headers = new Headers();
        $this->decoratedResponse->expects($this->once())
                                ->method('headers')
                                ->will($this->returnValue($headers));
        $this->assertSame(
                $headers,
                $this->formattingResponse->headers()
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function containsHeaderWhenDecoratedResponseContainsHeader()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('containsHeader')
                                ->with($this->equalTo('X-Binford'), $this->equalTo('6100'))
                                ->will($this->returnValue(true));
        $this->assertTrue($this->formattingResponse->containsHeader('X-Binford', '6100'));
    }

    /**
     * @test
     */
    public function addsCookieOnDecoratedResponse()
    {
        $cookie = Cookie::create('foo', 'bar');
        $this->decoratedResponse->expects($this->once())
                                ->method('addCookie')
                                ->with($this->equalTo($cookie));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->addCookie($cookie)
        );
    }

    /**
     * @test
     */
    public function removesCookieOnDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('removeCookie')
                                ->with($this->equalTo('foo'));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->removeCookie('foo')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function containsCookieWhenDecoratedResponseContainsCookie()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('containsCookie')
                                ->with($this->equalTo('foo'), $this->equalTo('bar'))
                                ->will($this->returnValue(true));
        $this->assertTrue($this->formattingResponse->containsCookie('foo', 'bar'));
    }

    /**
     * @test
     */
    public function writesOnDecoratedResponse()
    {
        $this->mockFormatter->expects($this->never())
                            ->method('format');
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('foo'));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->write('foo')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function returnsBodyFromDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('body')
                                ->will($this->returnValue('Some cool response data'));
        $this->assertEquals('Some cool response data', $this->formattingResponse->body());
    }

    /**
     * @test
     */
    public function writesUsingFormatterIfBodyNoStringOnDecoratedResponse()
    {
        $headers = new Headers();
        $this->decoratedResponse->expects($this->once())
                                ->method('headers')
                                ->will($this->returnValue($headers));
        $this->mockFormatter->expects($this->once())
                            ->method('format')
                            ->with($this->equalTo(['foo' => 'bar']), $this->equalTo($headers))
                            ->will($this->returnValue('foo: bar'));
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('foo: bar'));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->write(['foo' => 'bar'])
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function isFixedWhenDecoratedResponseIsFixed()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('isFixed')
                                ->will($this->returnValue(true));
        $this->assertTrue($this->formattingResponse->isFixed());
    }

    /**
     * @test
     */
    public function addsRedirectOnDecoratedResponseWithDefaultStatusCode()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('redirect')
                                ->with($this->equalTo('http://example.net/'), $this->equalTo(302));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->redirect('http://example.net/')
        );
    }

    /**
     * @test
     */
    public function addsRedirectOnDecoratedResponseWithGivenStatusCode()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('redirect')
                                ->with($this->equalTo('http://example.net/'), $this->equalTo(301));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->redirect('http://example.net/', 301)
        );
    }

    /**
     * @test
     */
    public function writeForbiddenUsesFormatterOnDecoratedResponse()
    {
        $this->mockFormatter->expects($this->once())
                            ->method('formatForbiddenError')
                            ->will($this->returnValue('No access granted here'));
        $this->decoratedResponse->expects($this->once())
                                ->method('forbidden')
                                ->will($this->returnSelf());
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('No access granted here'));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->forbidden()
        );
    }

    /**
     * @test
     */
    public function notFoundUsesFormatterOnDecoratedResponse()
    {
        $this->mockFormatter->expects($this->once())
                            ->method('formatNotFoundError')
                            ->will($this->returnValue('Not found'));
        $this->decoratedResponse->expects($this->once())
                                ->method('notFound')
                                ->will($this->returnSelf());
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('Not found'));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->notFound()
        );
    }

    /**
     * @test
     */
    public function methodNotAllowedUsesFormatterOnDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('methodNotAllowed')
                                ->with($this->equalTo('POST'), $this->equalTo(['GET', 'HEAD']))
                                ->will($this->returnSelf());
        $this->mockFormatter->expects($this->once())
                            ->method('formatMethodNotAllowedError')
                            ->with($this->equalTo('POST'), $this->equalTo(['GET', 'HEAD']))
                            ->will($this->returnValue('No way to POST here, use GET or HEAD'));
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('No way to POST here, use GET or HEAD'));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->methodNotAllowed('POST', ['GET', 'HEAD'])
        );
    }

    /**
     * @test
     */
    public function notAcceptablePassesToDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('notAcceptable')
                                ->with($this->equalTo(['application/json', 'application/xml']));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->notAcceptable(['application/json', 'application/xml'])
        );
    }

    /**
     * @test
     */
    public function internalServerErrorUsesFormatterOnDecoratedResponse()
    {
        $this->mockFormatter->expects($this->once())
                            ->method('formatInternalServerError')
                            ->with($this->equalTo('Ups!'))
                            ->will($this->returnValue('Something wrent wrong: Ups!'));
        $this->decoratedResponse->expects($this->once())
                                ->method('internalServerError')
                                ->with($this->equalTo('Something wrent wrong: Ups!'));
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->internalServerError('Ups!')
        );
    }

    /**
     * @test
     */
    public function httpVersionNotSupportedPassesToDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('httpVersionNotSupported');
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->httpVersionNotSupported()
        );
    }

    /**
     * @test
     */
    public function sendsDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                 ->method('addHeader')
                                 ->with($this->equalTo('Content-type'), $this->equalTo('text/plain'))
                                 ->will($this->returnSelf());
        $this->decoratedResponse->expects($this->once())
                                ->method('send');
        $this->assertSame($this->formattingResponse,
                          $this->formattingResponse->send()
        );
    }
}
