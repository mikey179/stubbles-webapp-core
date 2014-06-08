<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\ioc;
use stubbles\input\web\BaseWebRequest;
use stubbles\input\web\WebRequest;
use stubbles\ioc\Binder;
use stubbles\ioc\module\BindingModule;
use net\stubbles\webapp\response\Response;
use net\stubbles\webapp\response\ResponseNegotiator;
/**
 * Module to configure the binder with instances for request, session and response.
 *
 * @since  1.7.0
 */
class IoBindingModule implements BindingModule
{
    /**
     * response class to be used
     *
     * @type  string
     */
    private $responseClass  = 'net\stubbles\webapp\response\WebResponse';
    /**
     * map of formatters for mime types
     *
     * @type  array
     */
    private $formatter      = array('application/json'    => 'net\stubbles\webapp\response\format\JsonFormatter',
                                    'text/json'           => 'net\stubbles\webapp\response\format\JsonFormatter',
                                    'text/html'           => 'net\stubbles\webapp\response\format\HtmlFormatter',
                                    'text/plain'          => 'net\stubbles\webapp\response\format\PlainTextFormatter'
                              );
    /**
     * map of xml formatters for mime types
     *
     * @var  array
     */
    private $xmlFormatter   = array('text/xml'            => 'net\stubbles\webapp\response\format\XmlFormatter',
                                    'application/xml'     => 'net\stubbles\webapp\response\format\XmlFormatter',
                                    'application/rss+xml' => 'net\stubbles\webapp\response\format\XmlFormatter'
                              );
    /**
     * name for the session
     *
     * @type  string
     */
    private $sessionName;
    /**
     * function that creates the session instance
     *
     * @type  \Closure
     */
    private $sessionCreator;

    /**
     * constructor
     *
     * @param  string  $sessionName
     */
    protected function __construct($sessionName = null)
    {
        $this->sessionName = $sessionName;
    }

    /**
     * factory method
     *
     * @param   string  $sessionName  name of session, will also be name of session cookie and param
     * @return  IoBindingModule
     * @since   1.3.0
     */
    public static function createWithSession($sessionName = 'PHPSESSID')
    {
        $self = new self($sessionName);
        return $self->useNativeSession();
    }

    /**
     * factory method
     *
     * @return  IoBindingModule
     */
    public static function createWithoutSession()
    {
        return new self();
    }

    /**
     * sets class name of response class to be used
     *
     * @param   string  $responseClass  name of response class to bind
     * @return  IoBindingModule
     * @since   1.1.0
     */
    public function setResponseClass($responseClass)
    {
        $this->responseClass = $responseClass;
        return $this;
    }

    /**
     * adds formatter class for given mime type
     *
     * @param   string  $mimeType   mime type that should be handled by given formatter class
     * @param   string  $formatter  class to handle given mime type
     * @return  IoBindingModule
     */
    public function addFormatter($mimeType, $formatter)
    {
        $this->formatter[$mimeType] = $formatter;
        return $this;
    }

    /**
     * use php's default session implementation
     *
     * @return  IoBindingModule
     * @since   1.7.0
     */
    public function useNativeSession()
    {
        $this->sessionCreator = function(WebRequest $request, Response $response, $sessionName)
                                {
                                    $native = new \net\stubbles\webapp\session\NativeSessionStorage($sessionName);
                                    return new \net\stubbles\webapp\session\WebSession($native,
                                                                                       $native,
                                                                                       md5($request->readHeader('HTTP_USER_AGENT')->unsecure())
                                    );
                                };
        return $this;
    }

    /**
     * use none durable session implementation
     *
     * @return  IoBindingModule
     * @since   1.7.0
     */
    public function useNoneDurableSession()
    {
        $this->sessionCreator = function(WebRequest $request, Response $response, $sessionName)
                                {
                                    return new \net\stubbles\webapp\session\NullSession(new \net\stubbles\webapp\session\NoneDurableSessionId($sessionName));
                                };
        return $this;
    }

    /**
     * use none storing session implementation
     *
     * @return  IoBindingModule
     * @since   1.7.0
     */
    public function useNoneStoringSession()
    {
        $this->sessionCreator = function(WebRequest $request, Response $response, $sessionName)
                                {
                                    return new \net\stubbles\webapp\session\NullSession(new \net\stubbles\webapp\session\WebBoundSessionId($request, $response, $sessionName));
                                };
        return $this;
    }

    /**
     * sets class name of session class to be used
     *
     * @param   \Closure  $sessionCreator  name of session class to bind
     * @return  IoBindingModule
     * @since   2.0.0
     */
    public function setSessionCreator(\Closure $sessionCreator)
    {
        $this->sessionCreator = $sessionCreator;
        return $this;
    }

    /**
     * configure the binder
     *
     * @param  Binder  $binder
     */
    public function configure(Binder $binder)
    {
        $request  = BaseWebRequest::fromRawSource();
        $response = ResponseNegotiator::negotiateHttpVersion($request, $this->responseClass);
        $binder->bind('stubbles\input\web\WebRequest')
               ->toInstance($request);
        $binder->bind('stubbles\input\Request')
               ->toInstance($request);
        $binder->bind('net\stubbles\webapp\response\Response')
               ->toInstance($response);
        $formatters = $this->getAvailableFormatters();
        $binder->bindConstant('net.stubbles.webapp.response.format.mimetypes')
               ->to(array_keys($formatters));
        foreach ($formatters as $mimeType => $formatter) {
            $binder->bind('net\stubbles\webapp\response\format\Formatter')
                   ->named($mimeType)
                   ->to($formatter);
        }

        if (null !== $this->sessionCreator) {
            $sessionCreator = $this->sessionCreator;
            $session        = $sessionCreator($request, $response, $this->sessionName);
            $binder->bind('net\stubbles\webapp\session\Session')
                   ->toInstance($session);
            $binder->setSessionScope(new \net\stubbles\webapp\session\SessionBindingScope($session));
        }
    }

    /**
     * returns map of available formatters
     *
     * @return  array
     */
    private function getAvailableFormatters()
    {
        $formatter = $this->formatter;
        foreach ($this->xmlFormatter as $mimeType => $xmlFormatter) {
            if (!isset($formatter[$mimeType]) && class_exists('stubbles\xml\serializer\XmlSerializerFacade')) {
                $formatter[$mimeType] = $xmlFormatter;
            }
        }

        return $formatter;
    }
}
?>