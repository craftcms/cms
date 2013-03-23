<?php
/**
 * The root class for all other classes in this library
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
require_once(__DIR__.'/http.php');

/**
 * The Base class is the root class for all other objects used or defined by
 * this SDK.
 *
 * It contains common code for error handling as well as service functions that
 * are useful. Because it is an abstract class, it cannot be called directly,
 * and it has no publicly-visible properties.
 *
 * @since 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
abstract class Base {
	private
		$http_headers=array(),
		$_errors=array();

	// debug() - display a debug message
	/**
	 * Displays a debug message if $RAXSDK_DEBUG is TRUE
	 *
	 * The primary parameter is a string in sprintf() format, and it can accept
	 * up to five optional parameters. It prints the debug message, prefixed
	 * with "Debug:" and the class name, to the standard output device.
	 *
	 * Example:
	 *   `$this->debug('Starting execution of %s', get_class($this))`
	 *
	 * @param string $msg The message string (required); can be in
	 *      sprintf() format.
	 * @param mixed $p1 Optional argument to be passed to sprintf()
	 * @param mixed $p2 Optional argument to be passed to sprintf()
	 * @param mixed $p3 Optional argument to be passed to sprintf()
	 * @param mixed $p4 Optional argument to be passed to sprintf()
	 * @param mixed $p5 Optional argument to be passed to sprintf()
	 * @return void
	 */
	public function debug($msg,$p1=NULL,$p2=NULL,$p3=NULL,$p4=NULL,$p5=NULL) {
	    global $RAXSDK_DEBUG;
		// don't display the message unless the debug flag is set
		if (!$RAXSDK_DEBUG)
			return;
		printf("Debug:(%s)$msg\n", get_class($this), $p1, $p2, $p3, $p4, $p5);
	}

    /**
     * Returns the URL of the service/object
     *
     * The assumption is that nearly all objects will have a URL; at this
     * base level, it simply throws an exception to enforce the idea that
     * subclasses need to define this method.
     *
     * @throws UrlError
     */
    public function Url() {
    	throw new UrlError(
    	    _('URL method must be overridden in class definition'));
    }

	/**
	 * Sets extended attributes on an object and validates them
	 *
	 * This function is provided to ensure that attributes cannot
	 * arbitrarily added to an object. If this function is called, it
	 * means that the attribute is not defined on the object, and thus
	 * an exception is thrown.
	 *
	 * @param string $property the name of the attribute
	 * @param mixed $value the value of the attribute
	 * @return void
	 */
	public function __set($property, $value) {
	    $this->SetProperty($property, $value);
	}

	/**
	 * Sets an extended (unrecognized) property on the current object
	 *
	 * If RAXSDK_STRICT_PROPERTY_CHECKS is TRUE, then the prefix of the
	 * property name must appear in the $prefixes array, or else an
	 * exception is thrown.
	 *
	 * @param string $property the property name
	 * @param mixed $value the value of the property
	 * @param array $prefixes optional list of supported prefixes
	 * @throws \OpenCloud\AttributeError if strict checks are on and
	 *      the property prefix is not in the list of prefixes.
	 */
	public function SetProperty($property, $value, $prefixes=array()) {
	    // if strict checks are off, go ahead and set it
        if (!RAXSDK_STRICT_PROPERTY_CHECKS)
            $this->$property = $value;
        // otherwise, check the prefix
        elseif ($this->CheckAttributePrefix($property, $prefixes))
            $this->$property = $value;
        // if that fails, then throw the exception
        else
	        throw new \OpenCloud\AttributeError(sprintf(
	            _('Unrecognized attribute [%s] for [%s]'),
	            $property,
	            get_class($this)));
	}

	/**
	 * Converts an array of key/value pairs into a single query string
	 *
	 * For example, array('A'=>1,'B'=>2) would become 'A=1&B=2'.
	 *
	 * @param array $arr array of key/value pairs
	 * @return string
	 */
	public function MakeQueryString($arr) {
	    $qstring = '';
	    foreach($arr as $key => $value) {
	        if ($qstring != '')
	            $qstring .= '&';
	        $qstring .= urlencode($key) . '=' .
	                urlencode($this->to_string($value));
	    }
        return $qstring;
	}

	/**
	 * Checks the most recent JSON operation for errors
	 *
	 * This function should be called after any `json_*()` function call.
	 * This ensures that nasty JSON errors are detected and the proper
	 * exception thrown.
	 *
	 * Example:
	 *   `$obj = json_decode($string);`
	 *   `if (check_json_error()) do something ...`
	 *
	 * @return boolean TRUE if an error occurred, FALSE if none
	 * @throws JsonError
	 */
	public function CheckJsonError() {
		switch(json_last_error()) {
		case JSON_ERROR_NONE:
			return FALSE;
		case JSON_ERROR_DEPTH:
			throw new JsonError(
			    _('JSON error: The maximum stack depth has been exceeded'));
			break;
		case JSON_ERROR_STATE_MISMATCH:
			throw new JsonError(
			    _('JSON error: Invalid or malformed JSON'));
			break;
		case JSON_ERROR_CTRL_CHAR:
			throw new JsonError(
			    _('JSON error: Control character error, possibly '.
			        'incorrectly encoded'));
			break;
		case JSON_ERROR_SYNTAX:
			throw new JsonError(_('JSON error: Syntax error'));
			break;
		case JSON_ERROR_UTF8:
			throw new JsonError(
			    _('JSON error: Malformed UTF-8 characters, possibly '.
			        'incorrectly encoded'));
			break;
		default:
			throw new JsonError(_('Unexpected JSON error'));
		}
		return TRUE;
	}

	/********** PRIVATE METHODS **********/

	/**
	 * Returns a class that implements the HttpRequest interface.
	 *
	 * This can be stubbed out for unit testing and avoid making live calls.
	 */
	protected function GetHttpRequestObject($url, $method='GET') {
		return new CurlRequest($url, $method);
	}

	/**
	 * Checks the attribute $property and only permits it if the prefix is
	 * in the specified $prefixes array
	 *
	 * This is to support extension namespaces in some services.
	 *
	 * @param string $property the name of the attribute
	 * @param array $prefixes a list of prefixes
	 * @return boolean TRUE if valid; FALSE if not
	 */
	private function CheckAttributePrefix($property, $prefixes=array()) {
        $prefix = strstr($property, ':', TRUE);
        if (in_array($prefix, $prefixes))
            return TRUE;
        else
            return FALSE;
	}

    /**
     * Converts a value to an HTTP-displayable string form
     *
     * @param mixed $x a value to convert
     * @return string
     */
    private function to_string($x) {
        if (is_bool($x) && $x)
            return 'True';
        else if (is_bool($x))
            return 'False';
        else
            return (string)$x;
    }

} // class Base
