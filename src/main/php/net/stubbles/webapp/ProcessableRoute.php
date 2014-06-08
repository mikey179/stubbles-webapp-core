<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use stubbles\input\web\WebRequest;
use net\stubbles\webapp\response\Response;
/**
 * Contains logic to process the route.
 *
 * @since  2.0.0
 */
interface ProcessableRoute
{
    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function switchToHttps();

    /**
     * returns https uri of current route
     *
     * @return  HttpUri
     */
    public function getHttpsUri();

    /**
     * returns list of supported mime types
     *
     * @return  SupportedMimeTypes
     */
    public function getSupportedMimeTypes();

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function applyPreInterceptors(WebRequest $request, Response $response);

    /**
     * creates processor instance
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function process(WebRequest $request, Response $response);

    /**
     * apply post interceptors
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function applyPostInterceptors(WebRequest $request, Response $response);
}
