<?php
/**
 * The Object Storage service
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
require_once(__DIR__.'/container.php');

define('SWIFT_MAX_OBJECT_SIZE', 5*1024*1024*1024+1);

/**
 * A base class for common code shared between the ObjectStore and ObjectStoreCDN
 * objects
 *
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class ObjectStoreBase extends Service {

    const
        MAX_CONTAINER_NAME_LEN = 256,
        MAX_OBJECT_NAME_LEN = 1024,
        MAX_OBJECT_SIZE = SWIFT_MAX_OBJECT_SIZE;

    /**
     * Returns the URL of the service, selected from the connection's
     * serviceCatalog
     */
	public function Url($param=array()) {
		return noslash(parent::Url($param));
	}

    /**
     * Creates a Container object associated with the ObjectStore
     *
     * This is a factory method and should generally be used instead of
     * calling the Container class directly.
     *
     * @param mixed $cdata (optional) the name of the container (if string)
     *      or an object from which to set values
     * @return ObjectStore\Container
     */
	public function Container($cdata=NULL) {
		return new ObjectStore\Container($this, $cdata);
	}

    /**
     * Returns a Collection of Container objects
     *
     * This is a factory method and should generally be used instead of
     * calling the ContainerList class directly.
     *
     * @param array $filter a list of key-value pairs to pass to the
     *      service to filter the results
     * @return ObjectStore\ContainerList
     */
	public function ContainerList($filter=array()) {
		$filter['format'] = 'json';
		return $this->Collection(
		    '\OpenCloud\ObjectStore\Container',
			$this->Url().'?'.$this->MakeQueryString($filter)
		);
	}

} // class ObjectStoreBase

/**
 * ObjectStore - this defines the object-store (Cloud Files) service.
 *
 * Usage:
 * <code>
 *      $conn = new OpenStack('{URL}', '{SECRET}');
 *      $ostore = new OpenCloud\ObjectStore(
 *          $conn,
 *          'service name',
 *          'service region',
 *          'URL type'
 *      );
 * </code>
 *
 * Default values for service name, service region, and urltype can be
 * provided via the global constants RAXSDK_OBJSTORE_NAME,
 * RAXSDK_OBJSTORE_REGION, and RAXSDK_OBJSTORE_URLTYPE.
 *
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class ObjectStore extends ObjectStoreBase {
	private
	    /**
	     * This holds the associated CDN object (for Rackspace public cloud)
	     * or is NULL otherwise. The existence of an object here is
	     * indicative that the CDN service is available.
	     */
		$cdn;

    /**
     * creates a new ObjectStore object
     *
     * @param OpenCloud\OpenStack $conn a connection object
     * @param string $serviceName the name of the service to use
     * @param string $serviceRegion the name of the service region to use
     * @param string $urltype the type of URL to use (usually "publicURL")
     */
	public function __construct(
		OpenStack $conn,
		$serviceName=RAXSDK_OBJSTORE_NAME,
		$serviceRegion=RAXSDK_OBJSTORE_REGION,
		$urltype=RAXSDK_OBJSTORE_URLTYPE) {
		$this->debug(_('initializing ObjectStore...'));

		// call the parent contructor
		parent::__construct(
			$conn,
			'object-store',
			$serviceName,
			$serviceRegion,
			$urltype
		);

		// establish the CDN container, if available
		try {
            $this->cdn = new ObjectStoreCDN(
                $conn,
                $serviceName.'CDN', // will work for Rackspace
                $serviceRegion,
                $urltype
            );
		} catch (\OpenCloud\EndpointError $e) {
		    /**
		     * if we have an endpoint error, then
		     * the CDN functionality is not available
		     * In this case, we silently ignore  it.
		     */
		    $this->cdn = NULL;
		}
	} // function __construct()

    /**
     * returns the CDN object
     */
    public function CDN() {
        return $this->cdn;
    }
}

/**
 * This is the CDN related to the ObjectStore
 *
 * This is intended for Rackspace customers, so it almost certainly will
 * not work for other public clouds.
 *
 * @param OpenCloud\OpenStack $conn a connection object
 * @param string $serviceName the name of the service to use
 * @param string $serviceRegion the name of the service region to use
 * @param string $urltype the type of URL to use (usually "publicURL")
 */
class ObjectStoreCDN extends ObjectStoreBase {

    /**
     * Creates a new ObjectStoreCDN object
     *
     * This is a simple wrapper function around the parent Service construct,
     * but supplies defaults for the service type.
     *
     * @param OpenStack $conn the connection object
     * @param string $serviceName the name of the service
     * @param string $serviceRegion the service's region
     * @param string $urlType the type of URL (normally 'publicURL')
     */
	public function __construct(
		OpenStack $conn,
		$serviceName=RAXSDK_OBJSTORE_NAME,
		$serviceRegion=RAXSDK_OBJSTORE_REGION,
		$urltype=RAXSDK_OBJSTORE_URLTYPE) {

		// call the parent contructor
		parent::__construct(
			$conn,
			'rax:object-cdn',
			$serviceName,
			$serviceRegion,
			$urltype
		);
	}

	/**
	 * Helps catch errors if someone calls the method on the
	 * wrong object
	 */
	public function CDN() {
	    throw new ObjectStore\CdnError(
	        _('Invalid method call; no CDN() on the CDN object'));
	}
}
