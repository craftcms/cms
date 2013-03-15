<?php
/**
 * The Rackspace Cloud DNS service
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud;

require_once(__DIR__.'/asyncresponse.php');
require_once(__DIR__.'/domain.php');
require_once(__DIR__.'/record.php');
require_once(__DIR__.'/ptrrecord.php');
require_once(__DIR__.'/service.php');

class DNS extends Service {

	/**
	 * creates a new DNS object
	 *
	 * @param \OpenCloud\OpenStack $conn connection object
	 * @param string $serviceName the name of the service
	 * @param string $serviceRegion (not currently used; DNS is regionless)
	 * @param string $urltype the type of URL
	 */
	public function __construct(OpenStack $conn,
	        $serviceName, $serviceRegion, $urltype) {
		$this->debug(_('initializing DNS...'));
		parent::__construct(
			$conn,
			'rax:dns',
			$serviceName,
			$serviceRegion,
			$urltype
		);
	} // function __construct()

	/**
	 * Returns the selected endpoint URL of this Service
	 *
	 * @param string $resource - a child resource. For example,
	 *      passing 'servers' would return .../servers. Should *not* be
	 *    prefixed with a slash (/).
	 * @param array $args (optional) an array of key-value pairs for query
	 *      strings to append to the URL
	 * @returns string - the requested URL
	 */
	public function Url($resource='', $args=array()) {
	    $baseurl = parent::Url();
	    if ($resource != '')
	        $baseurl = noslash($baseurl).'/'.$resource;
	    if (!empty($args))
	        $baseurl .= '?'.$this->MakeQueryString($args);
		return $baseurl;
	}

	/**
	 * returns a DNS::Domain object
	 *
	 * @api
	 * @param mixed $info either the ID, an object, or array of parameters
	 * @return DNS\Domain
	 */
	public function Domain($info=NULL) {
		return new DNS\Domain($this, $info);
	}

	/**
	 * returns a Collection of DNS::Domain objects
	 *
	 * @api
	 * @param array $filter key/value pairs to use as query strings
	 * @return \OpenCloud\Collection
	 */
	public function DomainList($filter=array()) {
		$url = $this->Url(DNS\Domain::ResourceName(), $filter);
		return $this->Collection('\OpenCloud\DNS\Domain', $url);
	}

	/**
	 * returns a PtrRecord object for a server
	 *
	 * @param mixed $info ID, array, or object containing record data
	 * @return Record
	 */
	public function PtrRecord($info=NULL) {
		return new DNS\PtrRecord($this, $info);
	}

	/**
	 * returns a Collection of PTR records for a given Server
	 *
	 * @param \OpenCloud\Compute\Server $server the server for which to
	 *		retrieve the PTR records
	 * @return Collection
	 */
	public function PtrRecordList(\OpenCloud\Compute\Server $server)  {
		$service_name = $server->Service()->Name();
		$url = $this->Url('rdns/'.$service_name);
		$url .= '?' . $this->MakeQueryString(array('href'=>$server->Url()));
		return $this->Collection('\OpenCloud\DNS\PtrRecord', $url);
	}

	/**
	 * performs a HTTP request
	 *
	 * This method overrides the request with JSON content type
	 *
	 * @param string $url the URL to target
	 * @param string $method the HTTP method to use
	 * @param array $headers key/value pairs for headers to include
	 * @param string $body the body of the request (for PUT and POST)
	 * @return \OpenCloud\HttpResponse
	 */
	public function Request($url,$method='GET',$headers=array(),$body=NULL) {
		$headers['Accept'] = RAXSDK_CONTENT_TYPE_JSON;
		$headers['Content-Type'] = RAXSDK_CONTENT_TYPE_JSON;
		return parent::Request($url, $method, $headers, $body);
	}

	/**
	 * retrieves an asynchronous response
	 *
	 * This method calls the provided `$url` and expects an asynchronous
	 * response. It checks for various HTTP error codes and returns
	 * an `AsyncResponse` object. This object can then be used to poll
	 * for the status or to retrieve the final data as needed.
	 *
	 * @param string $url the URL of the request
	 * @param string $method the HTTP method to use
	 * @param array $headers key/value pairs for headers to include
	 * @param string $body the body of the request (for PUT and POST)
	 * @return DNS\AsyncResponse
	 */
	public function AsyncRequest(
			$url, $method='GET', $headers=array(), $body=NULL) {

		// perform the initial request
		$resp = $this->Request($url, $method, $headers, $body);

		// check response status
		if ($resp->HttpStatus() > 204)
			throw new DNS\AsyncHttpError(sprintf(
				_('Unexpected HTTP status for async request: '.
				  'URL [%s] method [%s] status [%s] response [%s]'),
				$url, $method, $resp->HttpStatus(), $resp->HttpBody()));

		// debug
		$this->debug('AsyncResponse [%s]', $resp->HttpBody());

		// return an AsyncResponse object
		return new DNS\AsyncResponse($this, $resp->HttpBody());
	}

	/**
	 * imports domain records
	 *
	 * Note that this function is called from the service (DNS) level, and
	 * not (as you might suspect) from the Domain object. Because the function
	 * return an AsyncResponse, the domain object will not actually exist
	 * until some point after the import has occurred.
	 *
	 * @api
	 * @param string $data the BIND_9 formatted data to import
	 * @return DNS\AsyncResponse
	 */
	public function Import($data) {
		// determine the URL
		$url = $this->Url('domains/import');

		// create the JSON object
		$obj = new \stdClass;
		$obj->domains = array();
		$dom = new \stdClass;
		$dom->contents = $data;
		$dom->contentType = 'BIND_9';
		$obj->domains[] = $dom;

		// encode it
		$json = json_encode($obj);

		// debug it
		$this->debug('Importing [%s]', $json);

		// perform the request
		return $this->AsyncRequest($url, 'POST', array(), $json);
	}

	/**
	 * returns a list of limits
	 *
	 */
	public function Limits($type=NULL) {
		$url = $this->url('limits');
		if (isset($type))
			$url .= '/'.$type;

		// perform the request
		$obj = $this->SimpleRequest($url);

		if (isset($type))
			return $obj;
		else
			return $obj->limits;
	}

	/**
	 * returns an array of limit types
	 *
	 * @return array
	 */
	public function LimitTypes() {
		$url = $this->Url('limits/types');
		$obj = $this->SimpleRequest($url);
		return $obj->limitTypes;
	}

	/**
	 * performs a simple request and returns the JSON as an object
	 *
	 * @param string $url the URL to GET
	 */
	public function SimpleRequest($url) {
		// perform the request
		$resp = $this->Request($url);

		// check for errors
		if ($resp->HttpStatus() > 202)
			throw new \OpenCloud\HttpError(sprintf(
				_('Unexpected status [%s] for URL [%s], body [%s]'),
				$resp->HttpStatus(),
				$url,
				$resp->HttpBody()));

		// decode the JSON
		$json = $resp->HttpBody();
		$this->debug(_('Limit Types JSON [%s]'), $json);
		$obj = json_decode($json);
		if ($this->CheckJsonError())
			return FALSE;

		return $obj;
	}

} // end class DNS
