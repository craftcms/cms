<?php
/**
 * Defines a load balancer object
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\LoadBalancerService;

require_once(__DIR__.'/persistentobject.php');	// handles persistence
require_once(__DIR__.'/metadata.php');			// metadata common
require_once(__DIR__.'/lbresources.php');		// child resources

/**
 * The LoadBalancer class represents a single load balancer
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class LoadBalancer extends \OpenCloud\PersistentObject {

	public
		$id,
		$name,
		$port,
		$protocol,
		$virtualIps=array(),
		$nodes=array(),
		$accessList,
		$algorithm,
		$connectionLogging,
		$connectionThrottle,
		$healthMonitor,
		$sessionPersistence,
		$metadata = array(),
		/* returned in response */
		$created,
		$updated,
		$sourceAddresses;

	protected static
		$json_name = 'loadBalancer',
		$url_resource = 'loadbalancers';

	private
	    $_create_keys = array(
	        'name',
	        'port',
	        'protocol',
	        'virtualIps',
	        'nodes',
	        'accessList',
	        'algorithm',
	        'connectionLogging',
	        'connectionThrottle',
	        'healthMonitor',
	        'sessionPersistence'
	    );

	/**
	 * adds a node to the load balancer
	 *
	 * This method creates a Node object and adds it to a list of Nodes
	 * to be added to the LoadBalancer. *Very important:* this method *NEVER*
	 * adds the nodes directly to the load balancer itself; it stores them
	 * on the object, and the nodes are added later, in one of two ways:
	 *
	 * * for a new LoadBalancer, the Nodes are added as part of the Create()
	 *   method call.
	 * * for an existing LoadBalancer, you must call the AddNodes() method
	 *
	 * @api
	 * @param string $addr the IP address of the node
	 * @param integer $port the port # of the node
	 * @param boolean $condition the initial condition of the node
	 * @param string $type either PRIMARY or SECONDARY
	 * @param integer $weight the node weight (for round-robin)
	 * @throws \OpenCloud\DomainError if value is not valid
	 * @return void
	 */
	public function AddNode($addr, $port, $condition='ENABLED',
			$type=NULL, $weight=NULL) {
	    $node = $this->Node();
	    $node->address = $addr;
	    $node->port = $port;
	    $cond = strtoupper($condition);
	    switch($cond) {
	    case 'ENABLED':
	    case 'DISABLED':
	    case 'DRAINING':
            $node->condition = $cond;
            break;
        default:
            throw new \OpenCloud\DomainError(sprintf(
                _('Value [%s] for Node::condition is not valid'), $condition));
	    }
	    if (isset($type)) {
	    	switch(strtoupper($type)) {
	    	case 'PRIMARY':
	    	case 'SECONDARY':
	    		$node->type = $type;
	    		break;
	    	default:
	    		throw new \OpenCloud\DomainError(sprintf(
	    			_('Value [%s] for Node::type is not valid'), $type));
	    	}
	    }
	    if (isset($weight)) {
	    	if (is_integer($weight))
	    		$node->weight = $weight;
	    	else
	    		throw new \OpenCloud\DomainError(sprintf(
	    			_('Value [%s] for Node::weight must be integer'), $weight));
	    }
	    $this->nodes[] = $node;
	}

	/**
	 * adds queued nodes to the load balancer
	 *
	 * In many cases, Nodes will be added to the Load Balancer when it is
	 * created (via the `Create()` method), but this method is provided when
	 * a set of Nodes needs to be added after the fact.
	 *
	 * @api
	 * @return HttpResponse
	 */
	public function AddNodes() {
		if (count($this->nodes) < 1)
			throw new MissingValueError(
				_('Cannot add nodes; no nodes are defined'));
		
		// iterate through all the nodes
		foreach($this->nodes as $node)
			$resp = $node->Create();
		return $resp;
	}

	/**
	 * adds a virtual IP to the load balancer
	 *
	 * You can use the strings `'PUBLIC'` or `'SERVICENET`' to indicate the
	 * public or internal networks, or you can pass the `Id` of an existing
	 * IP address.
	 *
	 * @api
	 * @param string $id either 'public' or 'servicenet' or an ID of an
	 *      existing IP address
	 * @param integer $ipVersion either null, 4, or 6 (both, IPv4, or IPv6)
	 * @return void
	 */
	public function AddVirtualIp($type='PUBLIC', $ipVersion=NULL) {
        $obj = new \stdClass();

        /**
         * check for PUBLIC or SERVICENET
         */
	    switch(strtoupper($type)) {
	    case 'PUBLIC':
	    case 'SERVICENET':
	        $obj->type = strtoupper($type);
	        break;
	    default:
	        $obj->id = $type;
	    }

	    if ($ipVersion) {
	        switch($ipVersion) {
	        case 4:
	            $obj->version = 'IPV4';
	            break;
	        case 6:
	            $obj->version = 'IPV6';
	            break;
	        default:
	            throw new \OpenCloud\DomainError(sprintf(
	                _('Value [%s] for ipVersion is not valid'), $ipVersion));
	        }
	    }

		/** 
		 * If the load balancer exists, we want to add it immediately. 
		 * If not, we add it to the virtualIps list and add it when the load
		 * balancer is created.
		 */
		if ($this->Id()) {
			$vip = $this->VirtualIp();
			$vip->type = $type;
			$vip->ipVersion = $obj->version;
			$http = $vip->Create();
			$this->Debug('AddVirtualIp:response [%s]', $http->HttpBody());
			return $http;
		}
		else // queue it
			$this->virtualIps[] = $obj;
		
		// done
		return TRUE;
	}

	/********** FACTORY METHODS **********
	 *
	 * These are used for the various sub-resources of LoadBalancer that are
	 * each managed by independent HTTP requests, such as the .../errorpage
	 * and the .../sessionpersistence objects.
	 */
	
	/**
	 * returns a Node object
	 */
	public function Node($id=NULL) {
		return new Node($this, $id);
	}

	/**
	 * returns a Collection of Nodes
	 */
	public function NodeList() {
		return $this->Parent()->Collection(
			'\OpenCloud\LoadBalancerService\Node', NULL, $this);
	}
	
	/**
	 * returns a NodeEvent object
	 */
	public function NodeEvent() {
		return new NodeEvent($this);
	}
	
	/**
	 * returns a Collection of NodeEvents
	 */
	public function NodeEventList() {
		return $this->Parent()->Collection(
			'\OpenCloud\LoadBalancerService\NodeEvent', NULL, $this);
	}

	/**
	 * returns a single Virtual IP (not called publicly)
	 */
	public function VirtualIp($data=NULL) {
		return new VirtualIp($this, $data);
	}

	/**
	 * returns  a Collection of Virtual Ips
	 */
	public function VirtualIpList() {
		return $this->Service()->Collection(
			'\OpenCloud\LoadBalancerService\VirtualIp', NULL, $this);
	}

	/**
	 */
	public function SessionPersistence() {
		return new SessionPersistence($this);
	}

	/**
	 * returns the load balancer's error page object
	 *
	 * @api
	 * @return ErrorPage
	 */
	public function ErrorPage() {
		return new ErrorPage($this);
	}

	/**
	 * returns statistics on the load balancer operation
	 *
	 * cannot be created, updated, or deleted
	 *
	 * @api
	 * @return Stats
	 */
	public function Stats() {
		return new Stats($this);
	}

	/**
	 */
	public function Usage() {
		return new Usage($this);
	}

	/**
	 */
	public function Access($data=NULL) {
		return new Access($this, $data);
	}

	/**
	 */
	public function AccessList() {
		return $this->Service()->Collection(
			'\OpenCloud\LoadBalancerService\Access', NULL, $this);
	}

	/**
	 */
	public function ConnectionThrottle() {
		return new ConnectionThrottle($this);
	}

	/**
	 */
	public function ConnectionLogging() {
		return new ConnectionLogging($this);
	}

	/**
	 */
	public function ContentCaching() {
		return new ContentCaching($this);
	}
	
	/**
	 */
	public function SSLTermination() {
		return new SSLTermination($this);
	}
	
	/**
	 */
	public function Metadata($data=NULL) {
		return new Metadata($this, $data);
	}

	/**
	 */
	public function MetadataList() {
		return $this->Service()->Collection(
			'\OpenCloud\LoadBalancerService\Metadata', NULL, $this);
	}

	/********** PROTECTED METHODS **********/

	/**
	 * returns the JSON object for Create()
	 *
	 * @return stdClass
	 */
	protected function CreateJson() {
	    $obj = new \stdClass();
	    $elem = $this->JsonName();
	    $obj->$elem = new \stdClass();

	    // set the properties
	    foreach($this->_create_keys as $key) {
	    	if ($key == 'nodes') {
	    		foreach($this->$key as $node) {
	    			$n = new \stdClass();
	    			foreach($node as $k => $v)
	    				if (isset($v))
	    					$n->$k = $v;
					$obj->$elem->nodes[] = $n;
	    		}
	    	}
	    	elseif (isset($this->$key)) {
	    		$obj->$elem->$key = $this->$key;
	    	}
	    }

	    return $obj;
	}

} // end LoadBalancer
