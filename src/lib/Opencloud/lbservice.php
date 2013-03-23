<?php
/**
 * Rackspace's Cloud Load Balancers
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud;

require_once(__DIR__.'/nova.php');
require_once(__DIR__.'/loadbalancer.php');

/**
 * The Rackspace Cloud Load Balancers
 *
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class LoadBalancerService extends Nova {

    const
        SERVICE_TYPE = 'rax:load-balancer',
        SERVICE_OBJECT_CLASS = 'LoadBalancer',
        URL_RESOURCE = 'loadbalancers',
        JSON_ELEMENT = 'loadBalancers';

	/**
	 * Creates a new LoadBalancerService connection
	 *
	 * This is not normally called directly, but via the factory method on the
	 * OpenStack or Rackspace connection object.
	 *
	 * @param OpenStack $conn the connection on which to create the service
	 * @param string $name the name of the service (e.g., "cloudDatabases")
	 * @param string $region the region of the service (e.g., "DFW" or "LON")
	 * @param string $urltype the type of URL (normally "publicURL")
	 */
	public function __construct(OpenStack $conn, $name, $region, $urltype) {
		parent::__construct($conn, self::SERVICE_TYPE,
		    $name, $region, $urltype);
	}

	/**
	 * Returns the URL of this service, or optionally that of
	 * an instance
	 *
	 * @param string $resource the resource required
	 * @param array $args extra arguments to pass to the URL as query strings
	 */
	public function Url($resource=self::URL_RESOURCE, $args=array()) {
		return parent::Url($resource, $args);
	}

	/**
	 * creates a new LoadBalancer object
	 *
	 * @api
	 * @param string $id the identifier of the load balancer
	 * @return LoadBalancerService\LoadBalancer
	 */
	public function LoadBalancer($id=NULL) {
		return new LoadBalancerService\LoadBalancer($this, $id);
	}

	/**
	 * returns a Collection of LoadBalancer objects
	 *
	 * @api
	 * @param boolean $detail if TRUE (the default), then all details are
	 *      returned; otherwise, the minimal set (ID, name) are retrieved
	 * @param array $filter if provided, a set of key/value pairs that are
	 * 		set as query string parameters to the query
	 * @return \OpenCloud\Collection
	 */
	public function LoadBalancerList($detail=TRUE, $filter=array()) {
		return $this->Collection('\OpenCloud\LoadBalancerService\LoadBalancer');
	}

	/**
	 * creates a new BillableLoadBalancer object (read-only)
	 *
	 * @api
	 * @param string $id the identifier of the load balancer
	 * @return LoadBalancerService\LoadBalancer
	 */
	public function BillableLoadBalancer($id=NULL) {
		return new LoadBalancerService\BillableLoadBalancer($this, $id);
	}

	/**
	 * returns a Collection of BillableLoadBalancer objects
	 *
	 * @api
	 * @param boolean $detail if TRUE (the default), then all details are
	 *      returned; otherwise, the minimal set (ID, name) are retrieved
	 * @param array $filter if provided, a set of key/value pairs that are
	 * 		set as query string parameters to the query
	 * @return \OpenCloud\Collection
	 */
	public function BillableLoadBalancerList($detail=TRUE, $filter=array()) {
		return $this->Collection(
			'\OpenCloud\LoadBalancerService\BillableLoadBalancer',
			NULL,
			NULL,
			$filter);
	}

	/**
	 * returns allowed domain
	 *
	 * @api
	 * @param mixed $data either an array of values or NULL
	 * @return LoadBalancerService\AllowedDomain
	 */
	public function AllowedDomain($data=NULL) {
		return new LoadBalancerService\AllowedDomain($this, $data);
	}

	/**
	 * returns Collection of AllowedDomain object
	 * 
	 * @api
	 * @return Collection
	 */
	public function AllowedDomainList() {
		return $this->Collection(
			'\OpenCloud\LoadBalancerService\AllowedDomain', NULL, $this);
	}
	
	/**
	 * single protocol (should never be called directly)
	 *
	 * Convenience method to be used by the ProtocolList Collection. 
	 *
	 * @return LoadBalancerService\Protocol
	 */
	public function Protocol($data=NULL) {
		return new LoadBalancerService\Protocol($this, $data);
	}
	
	/**
	 * a list of Protocol objects
	 *
	 * @api
	 * @return Collection
	 */
	public function ProtocolList() {
		return $this->Collection(
			'\OpenCloud\LoadBalancerService\Protocol', NULL, $this);
	}

	/**
	 * single algorithm (should never be called directly)
	 *
	 * convenience method used by the Collection factory
	 *
	 * @return LoadBalancerService\Algorithm
	 */
	public function Algorithm($data=NULL) {
		return new LoadBalancerService\Algorithm($this, $data);
	}
	
	/**
	 * a list of Algorithm objects
	 *
	 * @api
	 * @return Collection
	 */
	public function AlgorithmList() {
		return $this->Collection(
			'\OpenCloud\LoadBalancerService\Algorithm', NULL, $this);
	}

}
