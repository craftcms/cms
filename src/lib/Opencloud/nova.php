<?php
/**
 * An abstract class that defines shared components for products that use
 * OpenStack Nova
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud;

require_once(__DIR__.'/service.php');
require_once(__DIR__.'/flavor.php');
require_once(__DIR__.'/collection.php');

/**
 * Nova is an abstraction layer for the OpenStack compute service.
 *
 * Nova is used as a basis for several products, including Compute services
 * as well as Rackspace's Cloud Databases. This class is, in essence, a vehicle
 * for sharing common code between those other classes.
 */
abstract class Nova extends Service {
	private
		$_url;

	/**
	 * Called when creating a new Compute service object
	 *
	 * _NOTE_ that the order of parameters for this is *different* from the
	 * parent Service class. This is because the earlier parameters are the
	 * ones that most typically change, whereas the later ones are not
	 * modified as often.
	 *
	 * @param \OpenCloud\Identity $conn - a connection object
	 * @param string $serviceRegion - identifies the region of this Compute
	 *      service
	 * @param string $urltype - identifies the URL type ("publicURL",
	 *      "privateURL")
	 * @param string $serviceName - identifies the name of the service in the
	 *      catalog
	 */
	public function __construct(OpenStack $conn,
	        $serviceType, $serviceName, $serviceRegion, $urltype) {
		$this->debug(_('initializing Nova...'));
		parent::__construct(
			$conn,
			$serviceType,
			$serviceName,
			$serviceRegion,
			$urltype
		);
		$this->_url = noslash(parent::Url());
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
	    $baseurl = $this->_url;
	    if ($resource != '')
	        $baseurl = noslash($baseurl).'/'.$resource;
	    if (!empty($args))
	        $baseurl .= '?'.$this->MakeQueryString($args);
		return $baseurl;
	}

	/**
	 * Returns a flavor from the service
	 *
	 * This is a factory method and should generally be called instead of
	 * creating a Flavor object directly.
	 *
	 * @api
	 * @param string $id - if supplied, the Flavor identified by this is
	 *      retrieved
	 * @return Compute\Flavor object
	 */
	public function Flavor($id=NULL) {
	    return new Compute\Flavor($this, $id);
	} // flavor()

	/**
	 * Returns a list of Flavor objects
	 *
	 * This is a factory method and should generally be called instead of
	 * creating a FlavorList object directly.
	 *
	 * @api
	 * @param boolean $details - if TRUE (the default), returns full details.
	 *      Set to FALSE to retrieve minimal details and possibly improve
	 *      performance.
	 * @param array $filter - optional key/value pairs for creating query
	 *      strings
	 * @return Collection (or FALSE on an error)
	 */
	public function FlavorList($details=TRUE, $filter=array()) {
	    if ($details)
	        $url = $this->Url(Compute\Flavor::ResourceName().'/detail',
	                    $filter);
	    else
	        $url = $this->Url(Compute\Flavor::ResourceName(), $filter);
	    return $this->Collection('\OpenCloud\Compute\Flavor', $url);
	}

    /**
     * Gets a request from an HTTP source and ensures that the
     * content type is always "application/json"
     *
     * This is a simple subclass of the parent::Request() method that ensures
     * that all Compute requests use application/json as the Content-Type:
     *
     * @param string $url - the URL of the request
     * @param string $method - the HTTP method ("GET" by default)
     * @param array $headers - an associative array of headers to pass to
     *      the request
     * @param string $body - optional body for POST or PUT requests
     * @return \Rackspace\HttpResult object
     */
	public function Request($url, $method='GET', $headers=array(), $body=NULL) {
		$headers['Content-Type'] = RAXSDK_CONTENT_TYPE_JSON;
		return parent::Request($url, $method, $headers, $body);
	}

	/********** PRIVATE METHODS **********/

	/**
	 * Loads the available namespaces from the /extensions resource
	 */
	protected function load_namespaces() {
	    $ext = $this->Extensions();
	    foreach($ext as $obj)
	        $this->_namespaces[] = $obj->alias;
	}

} // class Compute
