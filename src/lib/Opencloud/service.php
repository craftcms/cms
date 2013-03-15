<?php
/**
 * An abstraction that defines a cloud service
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud;

require_once(__DIR__.'/base.php');

/**
 * This class defines a "service"—a relationship between a specific OpenStack
 * and a provided service, represented by a URL in the service catalog.
 *
 * Because Service is an abstract class, it cannot be called directly. Provider
 * services such as Rackspace Cloud Servers or OpenStack Swift are each
 * subclassed from Service.
 *
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
abstract class Service extends Base {

	protected
		$_namespaces=array();

	private
		$conn,
		$service_type,
		$service_name,
		$service_region,
		$service_url;

	/**
	 * Creates a service on the specified connection
	 *
	 * Usage: `$x = new Service($conn, $type, $name, $region, $urltype);`
	 * The service's URL is defined in the OpenStack's serviceCatalog; it
	 * uses the $type, $name, $region, and $urltype to find the proper URL
	 * and set it. If it cannot find a URL in the service catalog that matches
	 * the criteria, then an exception is thrown.
	 *
	 * @param OpenStack $conn - a Connection object
	 * @param string $type - the service type (e.g., "compute")
	 * @param string $name - the service name (e.g., "cloudServersOpenStack")
	 * @param string $region - the region (e.g., "ORD")
	 * @param string $urltype - the specified URL from the catalog
	 *      (e.g., "publicURL")
	 */
	public function __construct(
		OpenStack $conn, $type, $name, $region, $urltype=RAXSDK_URL_PUBLIC) {
		$this->conn = $conn;
		$this->service_type = $type;
		$this->service_name = $name;
		$this->service_region = $region;
		$catalog = $conn->serviceCatalog();
		$this->service_url = $this->get_endpoint(
		        $type, $name, $region, $urltype);
	} // public function __construct()

	/**
	 * Returns the URL for the Service
	 *
	 * @param array $query optional k/v pairs for query strings
	 * @return string
	 */
	public function Url($param=array()) {
		return $this->service_url .
			(count($param) ? ('?'.$this->MakeQueryString($param)) : '');
	} // public function Url()

	/**
	 * Returns the /extensions for the service
	 *
	 * @api
	 * @return array of objects
	 */
	public function Extensions() {
		$ext = $this->GetMetaUrl('extensions');
		if (is_object($ext) && isset($ext->extensions))
			return $ext->extensions;
		else
			return array();
	} // public function Extensions()

	/**
	 * Returns the /limits for the service
	 *
	 * @api
	 * @return array of limits
	 */
	public function Limits() {
		$lim = $this->GetMetaUrl('limits');
		if (is_object($lim))
			return $lim->limits;
		else
			return array();
	} // public function Limits()

	/**
	 * Performs an authenticated request
	 *
	 * This method handles the addition of authentication headers to each
	 * request. It always adds the X-Auth-Token: header and will add the
	 * X-Auth-Project-Id: header if there is a tenant defined on the
	 * connection.
	 *
	 * @param string $url The URL of the request
	 * @param string $method The HTTP method (defaults to "GET")
	 * @param array $headers An associative array of headers
	 * @param string $body An optional body for POST/PUT requests
	 * @return \OpenCloud\HttpResult
	 */
	public function Request($url,$method='GET',$headers=array(),$body=NULL) {
		$headers['X-Auth-Token'] = $this->conn->Token();
		if ($tenant = $this->conn->Tenant())
			$headers['X-Auth-Project-Id'] = $tenant;
		return $this->conn->Request($url, $method, $headers, $body);
	} // function Request()

	/**
	 * returns a collection of objects
	 *
	 * @param string $class the class of objects to fetch
	 * @param string $url (optional) the URL to retrieve
	 * @param mixed $parent (optional) the parent service/object
	 * @param array $parm (optional) array of key/value pairs to use as
	 *		query strings
	 * @return \OpenCloud\Collection
	 */
	public function Collection($class, $url=NULL, $parent=NULL, $parm=array()) {
	    // set the element name
	    $collection = $class::JsonCollectionName();
	    $element = $class::JsonCollectionElement();
	    //$jsonname = $class::JsonName();

	    // save the parent
		if (!isset($parent))
			$parent = $this;

		// determine the URL
		if (!$url)
		    $url = $parent->Url($class::ResourceName());
		
		// add query string parameters
		if (count($parm))
			$url .= '?' . $this->MakeQueryString($parm);

		// save debug info
		$this->debug('%s:Collection(%s, %s, %s)',
			get_class($this), $url, $class, $collection);

		// fetch the list
		$response = $this->Request($url);
		$this->debug('response %d [%s]',
			$response->HttpStatus(), $response->HttpBody());

		// check return code
		if ($response->HttpStatus() > 204)
			throw new \OpenCloud\CollectionError(sprintf(
				_('Unable to retrieve [%s] list from [%s], '.
				  'status [%d] response [%s]'),
				$class,
				$url,
				$response->HttpStatus(),
				$response->HttpBody()));

		// handle empty response
		if (strlen($response->HttpBody()) == 0)
			return new Collection($parent, $class, array());

		// parse the return
		$obj = json_decode($response->HttpBody());
		if ($this->CheckJsonError())
			return FALSE;
			
		// see if there is a "next" link
		if (isset($obj->links)) {
			if (is_array($obj->links)) {
				foreach($obj->links as $link) {
					if (isset($link->rel) && ($link->rel=='next')) {
						if (isset($link->href))
							$next_page_url = $link->href;
						else
							throw new \OpenCloud\DomainError(
								_('unexpected [links] found with no [href]'));
					}
				}
			}
		}

		// and say goodbye
		if (!$collection)
			$coll_obj = new Collection($parent, $class, $obj);
		elseif (isset($obj->$collection)) {
			if (!$element)
				$coll_obj = new Collection($parent, $class, $obj->$collection);
			else { // handle element levels
				$arr = array();
				foreach($obj->$collection as $index => $item)
					$arr[] = $item->$element;
				$coll_obj = new Collection($parent, $class, $arr);
			}
		}
		else
			$coll_obj = new Collection($parent, $class, array());
		
		// if there's a $next_page_url, then we need to establish a
		// callback method
		if (isset($next_page_url)) {
			$coll_obj->SetNextPageCallback(
				array($this, 'Collection'), 
				$next_page_url);
		}
		
		return $coll_obj;
	}
	
	/**
	 * returns the Region associated with the service
	 *
	 * @api
	 * @return string
	 */
	public function Region() {
		return $this->service_region;
	}

	/**
	 * returns the serviceName associated with the service
	 *
	 * This is used by DNS for PTR record lookups
	 *
	 * @api
	 * @return string
	 */
	public function Name() {
		return $this->service_name;
	}

	/**
	 * Returns a list of supported namespaces
	 *
	 * @return array
	 */
	public function namespaces() {
		if (!isset($this->_namespaces))
			return array();
		if (is_array($this->_namespaces))
		    return $this->_namespaces;
		return array();
	}

	/********** PRIVATE METHODS **********/

    /**
     * Given a service type, name, and region, return the url
     *
     * This function ensures that services are represented by an entry in the
     * service catalog, and NOT by an arbitrarily-constructed URL.
     *
     * Note that it will always return the first match found in the
     * service catalog (there *should* be only one, but you never know...)
     *
     * @param string $type The OpenStack service type ("compute" or
     *      "object-store", for example
     * @param string $name The name of the service in the service catlog
     * @param string $region The region of the service
     * @param string $urltype The URL type; defaults to "publicURL"
     * @return string The URL of the service
     */
    private function get_endpoint($type, $name, $region, $urltype='publicURL') {
        $found = 0;
        $catalog = $this->conn->serviceCatalog();

        // search each service to find The One
        foreach($catalog as $service) {
        	// first, compare the type ("compute") and name ("openstack")
            if ((!strcasecmp($service->type, $type)) &&
                 (!strcasecmp($service->name, $name))) {
                // found the service, now we need to find the region
                foreach($service->endpoints as $endpoint) {
					// regionless service
                    if (!isset($endpoint->region) &&
                    	 isset($endpoint->$urltype)) {
                    	++$found;
                    	return $endpoint->$urltype;
                    }
                    // compare the regions
                    elseif ((!strcasecmp($endpoint->region, $region)) &&
                         isset($endpoint->$urltype)) {
                        // now we have a match! Yay!
                        ++$found;
                        return $endpoint->$urltype;
                    }
                }
            }
        }

        // error if not found
        if (!$found)
            throw new EndpointError(
                sprintf(_('No endpoints for service type [%s], name [%s], '.
                  'region [%s] and urlType [%s]'),
                $type,
                $name,
                $region,
                $urltype)
           );
    } // function get_endpoint()

	/**
	 * Constructs a specified URL from the subresource
	 *
	 * Given a subresource (e.g., "extensions"), this constructs the proper
	 * URL and retrieves the resource.
	 *
	 * @param string $resource The resource requested; should NOT have slashes
	 *      at the beginning or end
	 * @return \stdClass object
	 */
	private function GetMetaUrl($resource) {
		$urlbase = $this->get_endpoint(
			$this->service_type,
			$this->service_name,
			$this->service_region,
			RAXSDK_URL_PUBLIC
		);
		if ($urlbase == '')
			return array();
		$ext_url = noslash($urlbase) .
						'/' .
						$resource;
		$response = $this->Request($ext_url);

		// check for NOT FOUND response
		if ($response->HttpStatus() == 404)
		    return array();

		// check for error status
		if ($response->HttpStatus() >= 300)
		    throw new HttpError(sprintf(
		        _('Error accessing [%s] - status [%d], response [%s]'),
		        $urlbase, $response->HttpStatus(), $response->HttpBody()));

		// we're good; proceed
		$obj = json_decode($response->HttpBody());
		if ($this->CheckJsonError())
			return FALSE;
		return $obj;
	}

} // class Service
