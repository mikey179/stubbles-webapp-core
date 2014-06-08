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
 * Tests for stubbles\webapp\response\WebResponse.
 *
 * @group  response
 */
class WebResponseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  WebResponse
     */
    private $response;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->response = $this->getMock('stubbles\webapp\response\WebResponse',
                                         ['header', 'sendBody']
                          );
    }

    /**
     * @test
     */
    public function versionIs1_1ByDefault()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 200 OK'));
        $this->response->send();
    }

    /**
     * @test
     */
    public function versionCanBeSetOnConstruction()
    {
        $response = $this->getMock('stubbles\webapp\response\WebResponse',
                                   ['header', 'sendBody'],
                                   ['1.0']
                          );
        $response->expects($this->at(0))
                 ->method('header')
                 ->with($this->equalTo('HTTP/1.0 200 OK'));
        $response->send();
    }

    /**
     * @test
     */
    public function clearingResponseDoesNotResetVersion()
    {
        $response = $this->getMock('stubbles\webapp\response\WebResponse',
                                   ['header', 'sendBody'],
                                   ['1.0']
                          );
        $response->expects($this->at(0))
                 ->method('header')
                 ->with($this->equalTo('HTTP/1.0 200 OK'));
        $response->clear()
                 ->send();
    }

    /**
     * @since  1.5.0
     * @test
     * @expectedException  stubbles\lang\exception\IllegalArgumentException
     * @group  bug251
     */
    public function settingStatusCodeToInvalidValueThrowsIllegalArgumentException()
    {
        $this->response->setStatusCode(313);
    }

    /**
     * @test
     */
    public function clearResetsStatusCodeTo200()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 404 Not Found'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 200 OK'));
        $this->response->setStatusCode(404)
                       ->send()
                       ->clear()
                       ->send();
    }

    /**
     * @test
     */
    public function statusCodeInCgiSapi()
    {
        $this->response = $this->getMock('stubbles\webapp\response\WebResponse',
                                         ['header', 'sendBody'],
                                         ['1.1', 'cgi']
                          );
        $this->response->expects($this->once())
                       ->method('header')
                       ->with($this->equalTo('Status: 200 OK'));
        $this->response->send();
    }

    /**
     * @test
     */
    public function addedHeadersAreSend()
    {
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('name: value1'));
        $this->response->addHeader('name', 'value1')
                       ->send();
    }

    /**
     * @test
     */
    public function addingHeaderWithSameNameReplacesExistingHeader()
    {
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('name: value2'));
        $this->response->addHeader('name', 'value1')
                       ->addHeader('name', 'value2')
                       ->send();
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function headersReturnsHeaderList()
    {
        $this->assertTrue(
                $this->response->addHeader('X-Foo', 'bar')
                               ->headers()
                               ->contain('X-Foo')
        );
    }

    /**
     * @test
     */
    public function clearingResponseRemovesAllHeaders()
    {
        $this->response->expects($this->once())
                       ->method('header');
        $this->response->addHeader('name', 'value1')
                       ->clear()
                       ->send();
    }

    /**
     * creates mock cookie
     *
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockCookie()
    {
        $mockCookie = $this->getMock('stubbles\webapp\response\Cookie',
                                     [],
                                     ['foo', 'bar']
                      );
        $mockCookie->expects($this->any())
                   ->method('getName')
                   ->will($this->returnValue('foo'));
        return $mockCookie;
    }

    /**
     * @test
     */
    public function cookiesAreSend()
    {
        $mockCookie = $this->createMockCookie();
        $mockCookie->expects($this->once())->method('send');
        $this->response->addCookie($mockCookie)
                       ->send();
    }

    /**
     * @test
     */
    public function addingCookieWithSameNameReplacesExistingCookie()
    {
        $mockCookie = $this->createMockCookie();
        $mockCookie->expects($this->once())->method('send');
        $this->response->addCookie($mockCookie)
                       ->addCookie($mockCookie)
                       ->send();
    }

    /**
     * @test
     */
    public function clearingResponseRemovesAllCookies()
    {
        $this->response->expects($this->once())
                       ->method('header');
        $this->response->addCookie(Cookie::create('foo', 'bar'))
                       ->clear()
                       ->send();
    }

    /**
     * @test
     */
    public function hasNoBodyByDefault()
    {
        $this->response->expects($this->never())
                       ->method('sendBody');
        $this->response->send();
    }

    /**
     * @test
     */
    public function bodyIsSend()
    {
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Content-Length: 3'));
        $this->response->expects($this->once())
                       ->method('sendBody')
                       ->with($this->equalTo('foo'));
        $this->response->write('foo')
                       ->send();
    }

    /**
     * @test
     */
    public function doesNotWriteBodyIfNoBodyPresent()
    {
        $this->response->expects($this->never())
                       ->method('sendBody');
        $this->response->send();
    }

    /**
     * @test
     */
    public function clearingResponseRemovesBody()
    {
        $this->response->expects($this->never())
                       ->method('sendBody');
        $this->response->write('foo')
                       ->clear()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function isNotFixedByDefault()
    {
        $this->assertFalse($this->response->isFixed());
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function clearUnfixesResponse()
    {
        $this->assertFalse($this->response->forbidden()->clear()->isFixed());
    }

    /**
     * @since  1.3.0
     * @test
     */
    public function redirectAddsLocationHeaderAndStatusCode()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 301 Moved Permanently'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Location: http://example.com/'));
        $this->response->redirect('http://example.com/', 301)
                       ->send();
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug251
     */
    public function redirectWithoutStatusCodeAndReasonPhraseAddsLocationHeaderAndStatusCode302()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 302 Found'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Location: http://example.com/'));
        $this->response->redirect('http://example.com/')
                       ->send();
    }

    /**
     * @since  1.5.0
     * @test
     */
    public function sendReturnsItself()
    {
        $this->assertSame($this->response, $this->response->send());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function forbiddenSetsStatusCodeTo403()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 403 Forbidden'));
        $this->response->forbidden()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function forbiddenFixatesResponse()
    {
        $this->assertTrue($this->response->forbidden()->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notFoundSetsStatusCodeTo404()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 404 Not Found'));
        $this->response->notFound()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function notFoundFixatesResponse()
    {
        $this->assertTrue($this->response->notFound()->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodNotAllowedSetsStatusCodeTo405()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 405 Method Not Allowed'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Allow: GET, HEAD'));
        $this->response->methodNotAllowed('POST', ['GET', 'HEAD'])
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function methodNotAllowedFixatesResponse()
    {
        $this->assertTrue($this->response->methodNotAllowed('POST', ['GET', 'HEAD'])->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notAcceptableSetsStatusCodeTo406()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 406 Not Acceptable'));
        $this->response->notAcceptable()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function notAcceptableFixatesResponse()
    {
        $this->assertTrue($this->response->notAcceptable()->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notAcceptableWithSupportedMimeTypesSetsStatusCodeTo406()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 406 Not Acceptable'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('X-Acceptable: application/json, application/xml'));
        $this->response->notAcceptable(['application/json', 'application/xml'])
                       ->send();
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function internalServerErrorSetsStatusCodeTo500()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 500 Internal Server Error'));
        $this->response->expects($this->once())
                       ->method('sendBody')
                       ->with($this->equalTo('ups!'));
        $this->response->internalServerError('ups!')
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function internalServerErrorFixatesResponse()
    {
        $this->assertTrue($this->response->internalServerError('ups')->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function httpVersionNotSupportedSetsStatusCodeTo505()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 505 HTTP Version Not Supported'));
        $this->response->expects($this->once())
                       ->method('sendBody')
                       ->with($this->equalTo('Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1'));
        $this->response->httpVersionNotSupported()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function httpVersionNotSupportedFixatesResponse()
    {
        $this->assertTrue($this->response->httpVersionNotSupported()->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function sendHeadDoesNotSendBody()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 200 OK'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Content-Length: 3'));
        $this->response->expects($this->never())
                       ->method('sendBody');
        $this->response->write('foo')->sendHead();
    }
}
