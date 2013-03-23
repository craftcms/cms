<?php
/**
 * Containers for OpenStack Object Storage (Swift) and Rackspace Cloud Files
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\ObjectStore;

require_once(__DIR__.'/dataobject.php');
require_once(__DIR__.'/objstorebase.php');

/**
 * A simple container for the CDN Service
 *
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class CDNContainer extends ObjStoreBase {
    public
        $name,
        $count=0,
        $bytes=0;
    private
        $service,
        $container_url,
        $_cdn;

    /**
     * Creates the container object
     *
     * Creates a new container object or, if the $cdata object is a string,
     * retrieves the named container from the object store. If $cdata is an
     * array or an object, then its values are used to set this object.
     *
     * @param OpenCloud\ObjectStore $service - the ObjectStore service
     * @param mixed $cdata - if supplied, the name of the object
     */
	public function __construct(\OpenCloud\Service $service, $cdata=NULL) {
		$this->debug(_('Initializing Container...'));
		parent::__construct();
		$this->service = $service;

		// set values from an object (via containerlist)
		if (is_object($cdata)) {
		    foreach($cdata as $name => $value)
		        if ($name == 'metadata')
		            $this->metadata->SetArray($value);
		        else
    		        $this->$name = $value;
		}
		// or, if it's a string, retrieve the object with that name
		else if ($cdata) {
			$this->debug(_('Getting container [%s]'), $cdata);
			$this->name = $cdata;
			$this->Refresh();
		}
	} // __construct()

    /**
     * Returns the URL of the container
     *
     * @return string
     * @throws NoNameError
     */
	public function Url() {
		if (!$this->name)
			throw new NoNameError(_('Container does not have an identifier'));
		return noslash($this->Service()->Url()).'/'.$this->name;
	}

	/**
	 * Creates a new container with the specified attributes
	 *
	 * @param array $params array of parameters
	 * @return boolean TRUE on success; FALSE on failure
	 * @throws ContainerCreateError
	 */
	public function Create($params=array()) {
		foreach($params as $name => $value) {
			switch($name) {
			case 'name':
				if ($this->is_valid_name($value))
					$this->name = $value;
				break;
			default:
				$this->$name = $value;
			}
		}
		$this->container_url = $this->Url();
		$headers = $this->MetadataHeaders();
		$response = $this->Service()->Request(
			$this->Url(),
			'PUT',
			$headers
		);

		// check return code
		if ($response->HttpStatus() > 202) {
			throw new ContainerCreateError(
				sprintf(_('Problem creating container [%s] status [%d] '.
				          'response [%s]'),
					$this->Url(),
					$response->HttpStatus(),
					$response->HttpBody()));
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Updates the metadata for a container
	 *
	 * @return boolean TRUE on success; FALSE on failure
	 * @throws ContainerCreateError
	 */
	public function Update() {
		$headers = $this->MetadataHeaders();
		$response = $this->Service()->Request(
			$this->Url(),
			'POST',
			$headers
		);

		// check return code
		if ($response->HttpStatus() > 204) {
			throw new ContainerCreateError(
				sprintf(_('Problem updating container [%s] status [%d] '.
				          'response [%s]'),
					$this->Url(),
					$response->HttpStatus(),
					$response->HttpBody()));
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Deletes the specified container
	 *
	 * @return boolean TRUE on success; FALSE on failure
	 * @throws ContainerDeleteError
	 */
	public function Delete() {
		$response = $this->Service()->Request(
			$this->Url(),
			'DELETE'
		);

		// validate the response code
		if ($response->HttpStatus() == 404)
			throw new ContainerNotFoundError(sprintf(
				_('Container [%s] not found'), $this->name));

		if ($response->HttpStatus() == 409)
			throw new ContainerNotEmptyError(sprintf(
				_('Container [%s] must be empty before deleting'),
				  $this->name));

		if ($response->HttpStatus() >= 300) {
			throw new ContainerDeleteError(
				sprintf(_('Problem deleting container [%s] status [%d] '.
				            'response [%s]'),
					$this->Url(),
					$response->HttpStatus(),
					$response->HttpBody()));
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Creates a Collection of objects in the container
	 *
	 * @param array $params associative array of parameter values.
     * * account/tenant	- The unique identifier of the account/tenant.
     * * container- The unique identifier of the container.
     * * limit (Optional) - The number limit of results.
     * * marker (Optional) - Value of the marker, that the object names
     *      greater in value than are returned.
     * * end_marker (Optional) - Value of the marker, that the object names
     *      less in value than are returned.
     * * prefix (Optional) - Value of the prefix, which the returned object
     *      names begin with.
     * * format (Optional) - Value of the serialized response format, either
     *      json or xml.
     * * delimiter (Optional) - Value of the delimiter, that all the object
     *      names nested in the container are returned.
	 * @link http://api.openstack.org for a list of possible parameter
	 *      names and values
	 * @return OpenCloud\Collection
	 * @throws ObjFetchError
	 */
	public function ObjectList($params=array()) {
		// construct a query string out of the parameters
		$params['format'] = 'json';
		$qstring = $this->MakeQueryString($params);

		// append the query string to the URL
		$url = $this->Url();
		if (strlen($qstring) > 0)
			$url .= '?' . $qstring;

		// fetch it
		return $this->Service()->Collection(
		    '\OpenCloud\ObjectStore\DataObject', $url, $this);
	} // object_list()

	/**
	 * Returns a new DataObject associated with this container
	 *
	 * @param string $name if supplied, the name of the object to return
	 * @return DataObject
	 */
	public function DataObject($name=NULL) {
		return new DataObject($this, $name);
	}

	/**
	 * Returns the Service associated with the Container
	 */
	public function Service() {
		return $this->service;
	}

	/********** PRIVATE METHODS **********/

	/**
	 * Loads the object from the service
	 *
	 * @return void
	 */
	protected function Refresh() {
        $response = $this->Service()->Request(
        	$this->Url(), 'HEAD', array('Accept'=>'*/*'));

        // validate the response code
        if ($response->HttpStatus() == 404)
            throw new ContainerNotFoundError(sprintf(
                _('Container [%s] not found'), $this->name));

        if ($response->HttpStatus() >= 300)
            throw new \OpenCloud\HttpError(
                sprintf(
                    _('Error retrieving Container, status [%d]'.
                    ' response [%s]'),
                    $response->HttpStatus(),
                    $response->HttpBody()));

        // parse the returned object
        $this->GetMetadata($response);
	}

	/**
	 * Validates that the container name is acceptable
	 *
	 * @param string $name the container name to validate
	 * @return boolean TRUE if ok; throws an exception if not
	 * @throws ContainerNameError
	 */
	private function is_valid_name($name) {
		if (($name == NULL) || ($name == ''))
			throw new ContainerNameError(
			    _('Container name cannot be blank'));
		if ($name == '0')
			throw new ContainerNameError(
			    _('"0" is not a valid container name'));
		if (strpos($name, '/') !== FALSE)
			throw new ContainerNameError(
			    _('Container name cannot contain "/"'));
		if (strlen($name) > \OpenCloud\ObjectStore::MAX_CONTAINER_NAME_LEN)
			throw new ContainerNameError(
			    _('Container name is too long'));
		return TRUE;
	}

} // class CDNContainer

/**
 * A regular container with a (potentially) CDN container
 *
 * This is the main entry point; CDN containers should only be used internally.
 */
class Container extends CDNContainer {

    private
        $_cdn;      // holds the related CDN container (if any)

	/**
	 * Makes the container public via the CDN
	 *
	 * @api
	 * @param integer $TTL the Time-To-Live for the CDN container; if NULL,
	 *      then the cloud's default value will be used for caching.
	 * @throws CDNNotAvailableError if CDN services are not available
	 * @return CDNContainer
	 */
	public function EnableCDN($TTL=NULL) {
	    $url = $this->Service()->CDN()->Url().'/'.$this->name;
	    $headers = $this->MetadataHeaders();
	    if ($TTL && !is_integer($TTL))
	        throw new CdnTtlError(sprintf(
	            _('TTL value [%s] must be an integer'), $TTL));
	    if ($TTL)
	        $headers['X-TTL'] = $TTL;
	    $headers['X-Log-Retention'] = 'True';
	    $headers['X-CDN-Enabled'] = 'True';

	    // PUT to the CDN container
	    $response = $this->Service()->Request($url, 'PUT', $headers);

	    // check the response status
	    if ($response->HttpStatus() > 202)
	        throw new CdnHttpError(sprintf(
	            _('HTTP error publishing to CDN, status [%d] response [%s]'),
	            $response->HttpStatus(), $response->HttpBody()));

	    // refresh the data
	    $this->Refresh();

	    // return the CDN container object
	    $this->_cdn = new CDNContainer($this->Service()->CDN(), $this->name);
	    return $this->CDN();
	}

	/**
	 * a synonym for PublishToCDN for backwards-compatibility
	 *
	 * @api
	 */
	public function PublishToCDN($TTL=NULL) {
		return $this->EnableCDN($TTL);
	}

	/**
	 * Disables the containers CDN function.
	 *
	 * Note that the container will still be available on the CDN until
	 * its TTL expires.
	 *
	 * @api
	 * @return void
	 */
	public function DisableCDN() {
	    $headers['X-Log-Retention'] = 'False';
	    $headers['X-CDN-Enabled'] = 'False';

	    // PUT it to the CDN service
	    $response = $this->Service()->Request($this->CDNURL(), 'PUT', $headers);

	    // check the response status
	    if ($response->HttpStatus() != 201)
	        throw new CdnHttpError(sprintf(
	            _('HTTP error disabling CDN, status [%d] response [%s]'),
	            $response->HttpStatus(), $response->HttpBody()));
	}

	/**
	 * Creates a static website from the container
	 *
	 * @api
	 * @link http://docs.rackspace.com/files/api/v1/cf-devguide/content/Create_Static_Website-dle4000.html
	 * @param string $index the index page (starting page) of the website
	 * @return \OpenCloud\HttpResponse
	 */
	public function CreateStaticSite($index) {
		$headers = array('X-Container-Meta-Web-Index'=>$index);
		$response = $this->Service()->Request($this->Url(), 'POST', $headers);

		// check return code
		if ($response->HttpStatus() > 204)
			throw new ContainerError(sprintf(
				_('Error creating static website for [%s], status [%d] response [%s]'),
				$this->name, $response->HttpStatus(), $response->HttpBody()));

		return $response;
	}

	/**
	 * Sets the error page(s) for the static website
	 *
	 * @api
	 * @link http://docs.rackspace.com/files/api/v1/cf-devguide/content/Set_Error_Pages_for_Static_Website-dle4005.html
	 * @param string $name the name of the error page
	 * @return \OpenCloud\HttpResponse
	 */
	public function StaticSiteErrorPage($name) {
		$headers = array('X-Container-Meta-Web-Error'=>$name);
		$response = $this->Service()->Request($this->Url(), 'POST', $headers);

		// check return code
		if ($response->HttpStatus() > 204)
			throw new ContainerError(sprintf(
				_('Error creating static site error page for [%s], '.
				  'status [%d] response [%s]'),
				$this->name, $response->HttpStatus(), $response->HttpBody()));

		return $response;
	}

	/**
	 * Returns the CDN service associated with this container.
	 */
	public function CDN() {
	    $cdn = $this->_cdn;
	    if (!$cdn)
	        throw new CdnNotAvailableError(_('CDN service is not available'));
	    return $cdn;
	}

	/**
	 * Returns the CDN URL of the container (if enabled)
	 *
	 * The CDNURL() is used to manage the container. Note that it is different
	 * from the PublicURL() of the container, which is the publicly-accessible
	 * URL on the network.
	 *
	 * @api
	 * @return string
	 */
	public function CDNURL() {
	    return $this->CDN()->Url();
	}

	/**
	 * Returns the Public URL of the container (on the CDN network)
	 *
	 */
	public function PublicURL() {
	    // return $this->CDNURI().'/'.$this->name;
	    return $this->CDNURI();
	}

	/**
	 * Returns the CDN info about the container
	 *
	 * @api
	 * @return stdClass
	 */
	public function CDNinfo($prop=NULL) {

	    // return NULL if the CDN container is not enabled
	    if (!isset($this->CDN()->metadata->Enabled))
	        return NULL;
	    if (!$this->CDN()->metadata->Enabled)
	        return NULL;

	    // check to see if it's set
	    if (isset($this->CDN()->metadata->$prop))
	        return trim($this->CDN()->metadata->$prop);
	    else if (isset($prop))
	        return NULL;

	    // otherwise, return the whole metadata object
	    return $this->CDN()->metadata;
	}

	/**
	 * Returns the CDN container URI prefix
	 *
	 * @api
	 * @return string
	 */
	public function CDNURI() {
	    return $this->CDNinfo('Uri');
	}

	/**
	 * Returns the SSL URI for the container
	 *
	 * @api
	 * @return string
	 */
	public function SSLURI() {
	    return $this->CDNinfo('Ssl-Uri');
	}

	/**
	 * Returns the streaming URI for the container
	 *
	 * @api
	 * @return string
	 */
	public function StreamingURI() {
	    return $this->CDNinfo('Streaming-Uri');
	}

    /**
     * Refreshes, then associates the CDN container
     */
    protected function Refresh() {
        parent::Refresh();

        // find the CDN object
        $cdn = $this->Service()->CDN();
        if (isset($cdn)) {
            try {
                $this->_cdn = new CDNContainer(
                    $this->Service()->CDN(),
                    $this->name
                );
            } catch (ContainerNotFoundError $e) {
                $this->_cdn = new CDNContainer($cdn);
                $this->_cdn->name = $this->name;
            }
        }
    }
} // class Container
