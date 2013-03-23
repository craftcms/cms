<?php
/**
 * Performs low-level HTTP operations via CURL
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud;

require_once(__DIR__.'/globals.php');
require_once(__DIR__.'/exceptions.php');

/**
 * The HttpRequest interface defines methods for wrapping CURL; this allows
 * those methods to be stubbed out for unit testing, thus allowing us to
 * test without actually making live calls.
 */
interface HttpRequest
{
    public function SetOption($name, $value);
    public function setheaders($arr);
    public function SetHeader($header, $value);
    public function Execute();
    public function close();
}

/**
 * The CurlRequest class is a simple wrapper to CURL functions. Not only does
 * this permit stubbing of the interface as described under the HttpRequest
 * interface, it could potentially allow us to replace the interface methods
 * with other function calls in the future.
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class CurlRequest implements HTTPRequest {

	private
		$url,
		$method,
		$handle,
		$retries=0,
		$headers=array(),		// headers sent with the request
		$returnheaders=array(); // headers returned from the request

    /**
     * initializes the CURL handle and HTTP method
     *
     * The constructor also sets a number of default values for options.
     *
     * @param string $url the URL to connect to
     * @param string $method the HTTP method (default "GET")
     * @param array $options optional hashed array of options => value pairs
     */
	public function __construct($url, $method='GET', $options=array()) {
		$this->url = $url;
		$this->method = $method;
		$this->handle = curl_init($url);

		// set our options
        $this->SetOption(CURLOPT_CUSTOMREQUEST, $method);
        foreach($options as $opt => $value) {
        	$this->debug(_('Setting option %s=%s'), $opt, $value);
        	$this->SetOptions($opt, $value);
        }

        // set security handling options
        if (RAXSDK_SSL_VERIFYHOST != 2)
            printf(
            	_('WARNING: RAXSDK_SSL_VERIFYHOST has reduced security, '.
            	  "value [%d]\n"),
                RAXSDK_SSL_VERIFYHOST);
        if (RAXSDK_SSL_VERIFYPEER !== TRUE)
            printf("WARNING: RAXSDK_SSL_VERIFYPEER has reduced security\n");
        $this->SetOption(CURLOPT_SSL_VERIFYHOST, RAXSDK_SSL_VERIFYHOST);
        $this->SetOption(CURLOPT_SSL_VERIFYPEER, RAXSDK_SSL_VERIFYPEER);
        if (defined('RAXSDK_CACERTPEM') && file_exists(RAXSDK_CACERTPEM)) {
            $this->setOption(CURLOPT_CAINFO, RAXSDK_CACERTPEM);
        }

        //  curl code [18]
        //	message [transfer closed with x bytes remaining to read]
        if ($method === 'HEAD')
        	$this->SetOption(CURLOPT_NOBODY, TRUE);

        // follow redirects
        $this->SetOption(CURLOPT_FOLLOWLOCATION, TRUE);

        // don't return the headers in the request
		$this->SetOption(CURLOPT_HEADER, FALSE);

        // retrieve headers via callback
        $this->SetOption(CURLOPT_HEADERFUNCTION,
        	array($this, '_get_header_cb'));

		// return the entire request on curl_exec()
        $this->SetOption(CURLOPT_RETURNTRANSFER, TRUE);

        // uncomment to turn on Verbose mode
        //$http->SetOption(CURLOPT_VERBOSE, TRUE);

        // set default timeouts
        $this->SetConnectTimeout(RAXSDK_CONNECTTIMEOUT);
        $this->SetHttpTimeout(RAXSDK_TIMEOUT);
	}

	/**
	 * Sets a CURL option
	 *
	 * @param const $name - a CURL named constant; e.g. CURLOPT_TIMEOUT
	 * @param mixed $value - the value for the option
	 */
	public function SetOption($name, $value) {
		return curl_setopt($this->handle, $name, $value);
	}

	/**
	 * Explicit method for setting the connect timeout
	 *
	 * The connect timeout is the time it takes for the initial connection
	 * request to be established. It is different than the HTTP timeout, which
	 * is the time for the entire request to be serviced.
	 *
	 * @param integer $value The connection timeout in seconds.
	 *      Use 0 to wait indefinitely (NOT recommended)
	 */
	public function SetConnectTimeout($value) {
        $this->SetOption(CURLOPT_CONNECTTIMEOUT, $value);
	}

	/**
	 * Explicit method for setting the HTTP timeout
	 *
	 * The HTTP timeout is the time it takes for the HTTP request to be
	 * serviced. This value is usually larger than the connect timeout
	 * value.
	 *
	 * @param integer $value - the number of seconds to wait before timing out
	 *      the HTTP request.
	 */
	public function SetHttpTimeout($value) {
        $this->SetOption(CURLOPT_TIMEOUT, $value);
	}

	/**
	 * Sets the number of retries
	 *
	 * If you set this to a non-zero value, then it will repeat the request
	 * up to that number.
	 */
	public function SetRetries($value) {
	    $this->retries = $value;
	}

	/**
	 * Simplified method for setting lots of headers at once
	 *
	 * This method takes an associative array of header/value pairs and calls
	 * the setheader() method on each of them.
	 *
	 * @param array $arr an associative array of headers
	 */
	public function setheaders($arr) {
		if (!is_array($arr))
			throw new HttpException(
				_('Value passed to CurlRequest::setheaders() must be array'));
		foreach ($arr as $name=>$value)
			$this->SetHeader($name, $value);
	}

	/**
	 * Sets a single header
	 *
	 * For example, to set the content type to JSON:
	 * `$request->SetHeader('Content-Type','application/json');`
	 *
	 * @param string $name The name of the header
	 * @param mixed $value The value of the header
	 */
	public function SetHeader($name, $value) {
		$this->headers[$name] = $value;
	}

	/**
	 * Executes the current request
	 *
	 * This method actually performs the request using the values set
	 * previously. It throws a OpenCloud\HttpError exception on
	 * any CURL error.
	 *
	 * @return OpenCloud\HttpResponse
	 * @throws OpenCloud\HttpError
	 */
	public function Execute() {
		// set all the headers
		$headarr = array();
		foreach ($this->headers as $name => $value)
			$headarr[] = $name.': '.$value;
        $this->SetOption(CURLOPT_HTTPHEADER, $headarr);

        // set up to retry if necessary
        $try_counter = 0;
        do {
		    $data = curl_exec($this->handle);
		    if (curl_errno($this->handle)&&($try_counter<$this->retries))
		        $this->debug(_('Curl error [%d]; retrying [%s]'),
		                curl_errno($this->handle), $this->url);
		} while((++$try_counter<=$this->retries) &&
		        (curl_errno($this->handle)!=0));

		// log retries error
		if ($this->retries && curl_errno($this->handle))
		    throw new HttpRetryError(
		        sprintf(_('No more retries available, last error [%d]'),
		            curl_errno($this->handle)));

		// check for CURL errors
        switch(curl_errno($this->handle)) {
        case 0:     // everything's ok
            break;
        case 3:
            throw new HttpUrlError(
                sprintf(_('Malformed URL [%s]'), $this->url));
            break;
        case 28:    // timeout
            throw new HttpTimeoutError(
                _('Operation timed out; check RAXSDK_TIMEOUT value'));
            break;
        default:
            throw new HttpError(
                sprintf(
                    _('HTTP error on [%s], curl code [%d] message [%s]'),
                    $this->url,
                    curl_errno($this->handle),
                    curl_error($this->handle)));
        }

		// otherwise, return the HttpResponse
		return new HttpResponse($this, $data);
	}

	/**
	 * returns an array of information about the request
	 */
	public function info() {
		return curl_getinfo($this->handle);
	}

	/**
	 * returns the most recent CURL error number
	 */
	public function errno() {
		return curl_errno($this->handle);
	}

	/**
	 * returns the most recent CURL error string
	 */
	public function error() {
		return curl_error($this->handle);
	}

	/**
	 * Closes the HTTP request
	 */
	public function close() {
		return curl_close($this->handle);
	}

	/**
	 * Returns the headers as an array
	 */
	public function ReturnHeaders() {
		return $this->returnheaders;
	}

	/**
	 * This is a callback method used to handle the returned HTTP headers
	 *
	 * @param mixed $ch a CURL handle
	 * @param string $header the header string in its entirety
	 */
	public function _get_header_cb($ch, $header) {
		$this->returnheaders[] = $header;
		return strlen($header);
	}

} // class CurlRequest

/**
 * The HttpResponse returns an object with status information, separated
 * headers, and any response body necessary.
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class HttpResponse {

	private
		$errno,
		$error,
		$info=array(),
		$body,
		$headers=array();

	/**
	 * The constructor parses everything necessary
	 */
	public function __construct($request, $data) {
		// save the raw data (who knows? we might need it)
		$this->body = $data;

        // and split each line into name: value pairs
        foreach($request->ReturnHeaders() as $line) {
        	if (preg_match('/^([^:]+):\s+(.+?)\s*$/', $line, $matches))
	        	$this->headers[$matches[1]] = $matches[2];
	        else
	        	$this->headers[$line] = trim($line);
        }

        // debug caching
        if (isset($this->headers['Cache-Control']))
        	$this->debug('Cache-Control: %s', $this->headers['Cache-Control']);
        if (isset($this->headers['Expires']))
        	$this->debug('Expires: %s', $this->headers['Expires']);

        // set some other data
        $this->info = $request->info();
        $this->errno = $request->errno();
        $this->error = $request->error();
	}

	/**
	 * Returns the full body of the request
	 *
	 * @return string
	 */
	public function HttpBody() {
		return $this->body;
	}

	/**
	 * Returns an array of headers
	 *
	 * @return associative array('header'=>value)
	 */
	public function Headers() {
		return $this->headers;
	}

	/**
	 * Returns a single header
	 *
	 * @return string with the value of the requested header, or NULL
	 */
	public function Header($name) {
		return $this->headers[$name];
	}

	/**
	 * Returns an array of information
	 *
	 * @return array
	 */
	public function info() {
		return $this->info;
	}

	/**
	 * Returns the most recent error number
	 *
	 * @return integer
	 */
	public function errno() {
		return $this->errno;
	}

	/**
	 * Returns the most recent error message
	 *
	 * @return string
	 */
	public function error() {
		return $this->error;
	}

	/**
	 * Returns the HTTP status code
	 *
	 * @return integer
	 */
	public function HttpStatus() {
		return $this->info['http_code'];
	}

} // class HttpResponse

/**
 * This is a stubbed-out variant of HttpResponse for unit testing
 */
class BlankResponse extends HttpResponse {
	public
		$errno,
		$error,
		$info,
		$body,
		$headers=array(),
		$status=200,
		$rawdata;
	public function __construct($values=array()) {
		foreach($values as $name => $value)
			$this->$name = $value;
	}
	public function HttpBody() { return $this->body; }
	public function HttpStatus() { return $this->status; }
}
