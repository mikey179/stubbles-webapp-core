<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\format;
use stubbles\webapp\response\Headers;
/**
 * Interface for resource formatters.
 *
 * @since  1.1.0
 */
interface Formatter
{
    /**
     * formats resource for response
     *
     * @param   mixed                              $resource  resource data to create a representation of
     * @param   \stubbles\webapp\response\Headers  $headers   list of headers for the response
     * @return  string
     */
    public function format($resource, Headers $headers);

    /**
     * write error message about 403 Forbidden error
     *
     * @return  string
     */
    public function formatForbiddenError();

    /**
     * write error message about 404 Not Found error
     *
     * @return  string
     */
    public function formatNotFoundError();

    /**
     * write error message about 405 Method Not Allowed error
     *
     * @param   string    $requestMethod   original request method
     * @param   string[]  $allowedMethods  list of allowed methods
     * @return  string
     */
    public function formatMethodNotAllowedError($requestMethod, array $allowedMethods);

    /**
     * write error message about 500 Internal Server error
     *
     * @param   string  $message  error messsage to display
     * @return  string
     */
    public function formatInternalServerError($message);
}
