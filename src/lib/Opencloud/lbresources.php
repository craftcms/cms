<?php
/**
 * Defines a a number of classes that are child resources of LoadBalancer
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\LoadBalancerService;

require_once(__DIR__.'/persistentobject.php');

/**
 * SubResource is an abstract class that handles subresources of a
 * LoadBalancer object; for example, the
 * `/loadbalancers/{id}/errorpage`. Since most of the subresources are
 * handled in a similar manner, this consolidates the functions.
 *
 * There are really four pieces of data that define a subresource:
 * * `$url_resource` - the actual name of the url component
 * * `$json_name` - the name of the JSON object holding the data
 * * `$json_collection_name` - if the collection is not simply
 *   `$json_name . 's'`, this defines the collectio name.
 * * `$json_collection_element` - if the object in a collection is not
 *   anonymous, this defines the name of the element holding the object.
 * Of these, only the `$json_name` and `$url_resource` are required.
 */
abstract class SubResource extends \OpenCloud\PersistentObject {
	private
		$parent;	// holds the parent load balancer

	/**
	 * constructs the SubResource's object
	 *
	 * @param mixed $obj the parent object
	 * @param mixed $value the ID or array of values for the object
	 */
	public function __construct($obj, $value=NULL) {
		$this->parent = $obj;
		parent::__construct($obj->Service(), $value);
		/**
		 * Note that, since these sub-resources do not have an ID, we must
		 * fake out the `Refresh` method.
		 */
		 if (isset($this->id))
			$this->Refresh();
		 else
			$this->Refresh('<no-id>');
	}

	/**
	 * returns the URL of the SubResource
	 *
	 * @api
	 * @param string $subresource the subresource of the parent
	 * @param array $qstr an array of key/value pairs to be converted to
	 *	query string parameters for the subresource
	 * @return string
	 */
	public function Url($subresource=NULL, $qstr=array()) {
		return $this->Parent()->Url($this->ResourceName());
	}

	/**
	 * returns the JSON document's object for creating the subresource
	 *
	 * The value `$_create_keys` should be an array of names of data items
	 * that can be used in the creation of the object.
	 *
	 * @return \stdClass;
	 */
	protected function CreateJson() {
    	$obj = new \stdClass();
    	$top = $this->JsonName();
    	if ($top) {
			$obj->$top = new \stdClass();
			foreach($this->_create_keys as $item)
				$obj->$top->$item = $this->$item;
		}
		else {
			foreach($this->_create_keys as $item)
				$obj->$item = $this->$item;
		}
    	return $obj;
	}
	
	/**
	 * returns the JSON for the update (same as create)
	 *
	 * For these subresources, the update JSON is the same as the Create JSON
	 * @return \stdClass
	 */
	protected function UpdateJson($params = array()) {
		return $this->CreateJson();
	}
	
	/**
	 * returns the Parent object (usually a LoadBalancer, but sometimes another
	 * SubResource)
	 *
	 * @return mixed
	 */
	public function Parent() {
		return $this->parent;
	}
	
	/**
	 * returns a (default) name of the object
	 *
	 * The name is constructed by the object class and the object's ID.
	 *
	 * @api
	 * @return string
	 */
	public function Name() {
		return sprintf(_('%s-%s'),
			get_class($this), $this->Parent()->Id());
	}
} // end SubResource

/**
 * This defines a read-only SubResource - one that cannot be created, updated,
 * or deleted. Many subresources are like this, and this simplifies their
 * class definitions.
 */
abstract class ReadonlySubResource extends SubResource {
	/**
	 * no Create
	 */
	public function Create($params=array()) { $this->NoCreate(); }
	/**
	 * no Update
	 */
	public function Update($params=array()) { $this->NoUpdate(); }
	/**
	 * no Delete
	 */
	public function Delete() { $this->NoDelete(); }
} // end class ReadonlySubResource

/**
 * The /loadbalancer/{id}/errorpage manages the error page for the load
 * balancer.
 */
class ErrorPage extends SubResource {
	public
		$content;
	protected static
		$json_name = 'errorpage',
		$url_resource = 'errorpage';
	protected
		$_create_keys = array('content');
	/**
	 * creates a new error page
	 *
	 * This calls the Update() method, since it requires a PUT to create
	 * a new error page. A POST request is not supported, since the URL
	 * resource is already defined.
	 *
	 * @param array $parm array of parameters
	 */
	public function Create($parm=array()) { $this->Update($parm); }
} // end ErrorPage

/**
 * Stats returns statistics about the load balancer
 */
class Stats extends ReadonlySubResource {
	public
		$connectTimeOut,
		$connectError,
		$connectFailure,
		$dataTimedOut,
		$keepAliveTimedOut,
		$maxConn;
	protected static
		$json_name = FALSE,
		$url_resource = 'stats';
}

/**
 * information on a single node in the load balancer
 *
 * This extends `PersistentObject` because it has an ID, unlike most other
 * sub-resources.
 */
class Node extends \OpenCloud\PersistentObject {
	public
		$id,
		$address,
		$port,
		$condition,
		$status,
		$weight,
		$type;
	protected static
		$json_name = FALSE,
		$json_collection_name = 'nodes',
		$url_resource = 'nodes';
	private
		$_create_keys = array(
			'address',
			'port',
			'condition',
			'type',
			'weight'
		),
		$_lb;
	/**
	 * builds a new Node object
	 *
	 * @param LoadBalancer $lb the parent LB object
	 * @param mixed $info either an ID or an array of values
	 * @returns void
	 */
	public function __construct(LoadBalancer $lb, $info=NULL) {
		$this->_lb = $lb;
		parent::__construct($lb->Service(), $info);
	}
	/**
	 * returns the parent LoadBalancer object
	 *
	 * @return LoadBalancer
	 */
	public function Parent() {
		return $this->_lb;
	}
	/**
	 * returns the Node name
	 *
	 * @return string
	 */
	public function Name() {
		return get_class().'['.$this->Id().']';
	}
	/**
	 * returns the object for the Create JSON
	 *
	 * @return \stdClass
	 */
	protected function CreateJson() {
		$obj = new \stdClass();
		$obj->nodes = array();
		$node = new \stdClass();
		$node->node = new \stdClass();
		foreach($this->_create_keys as $key) {
			$node->node->$key = $this->$key;
		}
		$obj->nodes[] = $node;
		return $obj;
	}

	/**
	 * factory method to create a new Metadata child of the Node
	 *
	 * @api
	 * @return Metadata
	 */
	public function Metadata($data=NULL) {
		return new Metadata($this, $data);
	}

	/**
	 * factory method to create a Collection of Metadata object
	 *
	 * Note that these are metadata children of the Node, not of the
	 * LoadBalancer.
	 *
	 * @api
	 * @return Collection of Metadata
	 */
	public function MetadataList() {
		return $this->Service()->Collection(
			'\OpenCloud\LoadBalancerService\Metadata', NULL, $this);
	}
}

/**
 * a single node event, usually called as part of a Collection
 *
 * This is a read-only subresource. 
 */
class NodeEvent extends ReadonlySubResource {
	public
		$detailedMessage,
		$nodeId,
		$id,
		$type,
		$description,
		$category,
		$severity,
		$relativeUri,
		$accountId,
		$loadbalancerId,
		$title,
		$author,
		$created;
	protected static
		$json_name = 'nodeServiceEvent',
		$url_resource = 'nodes/events';
}

/**
 * sub-resource to manage allowed domains
 *
 * Note that this is actually a sub-resource of the load balancers service,
 * and not of the load balancer object. It's included here for convenience,
 * since it matches the pattern of the other LB subresources.
 *
 * @api
 */
class AllowedDomain extends \OpenCloud\PersistentObject {
	public
		$name;
	protected static
		$json_name = 'allowedDomain',
		$json_collection_name = 'allowedDomains',
		$json_collection_element = 'allowedDomain',
		$url_resource = 'loadbalancers/alloweddomains';
	public function Create($params=array()) { $this->NoCreate(); }
	public function Update($params=array()) { $this->NoUpdate(); }
	public function Delete() { $this->NoDelete(); }
}

/**
 * VirtualIp represents a single virtual IP (usually returned in a Collection)
 *
 * Virtual IPs can be added to a load balancer when it is created; however,
 * this subresource allows the user to add or update them one at a time.
 *
 * @api
 */
class VirtualIp extends SubResource {
	public
		$id,
		$address,
		$type,
		$ipVersion;
	protected static
		$json_collection_name = 'virtualIps',
		$json_name = FALSE,
		$url_resource = 'virtualips';
	protected
		$_create_keys = array('type', 'ipVersion');
	public function Update($params=array()) { $this->NoUpdate(); }
}

/**
 * used to get a list of billable load balancers for a specific date range
 */
class BillableLoadBalancer extends LoadBalancer {
	protected static
		$url_resource = 'loadbalancers/billable';
	public function Create($params=array()) { $this->NoCreate(); }
	public function Update($params=array()) { $this->NoUpdate(); }
	public function Delete() { $this->NoDelete(); }
} // end BillableLoadBalancer

/**
 * used to get usage data for a load balancer
 */
class Usage extends ReadonlySubResource {
	public
		$id,
		$averageNumConnections,
		$incomingTransfer,
		$outgoingTransfer,
		$averageNumConnectionsSsl,
		$incomingTransferSsl,
		$outgoingTransferSsl,
		$numVips,
		$numPolls,
		$startTime,
		$endTime,
		$vipType,
		$sslMode,
		$eventType;
	protected static
		$json_name = 'loadBalancerUsageRecord',
		$url_resource = 'usage';
} // end Usage

/**
 * sub-resource to manage access lists
 *
 * @api
 */
class Access extends SubResource {
	public
		$id,
		$type,
		$address;
    protected static
    	$json_name = "accessList",
    	$url_resource = "accesslist";
    protected
    	$_create_keys = array('type', 'address');
	public function Update($params=array()) { $this->NoUpdate(); }
}

/**
 * sub-resource to read health monitor info
 */
class HealthMonitor extends ReadonlySubResource {
	public
		$type;
	protected static
		$json_name = 'healthMonitor',
		$url_resource = 'healthmonitor';
} // end HealthMonitor

/**
 * sub-resource to manage connection throttling
 *
 * @api
 */
class ConnectionThrottle extends SubResource {
	public
		$minConnections,
		$maxConnections,
		$maxConnectionRate,
		$rateInterval;
    protected static
    	$json_name = "connectionThrottle",
    	$url_resource = "connectionthrottle";
    protected
    	$_create_keys = array(
    		'minConnections',
    		'maxConnections',
    		'maxConnectionRate',
    		'rateInterval'
    	);
    /**
     * create uses PUT like Update
     */
	public function Create($parm=array()) { $this->Update($parm); }
}

/**
 * sub-resource to manage connection logging
 *
 * @api
 */
class ConnectionLogging extends SubResource {
	public
		$enabled;
    protected static
    	$json_name = "connectionLogging",
    	$url_resource = "connectionlogging";
    protected
    	$_create_keys = array( 'enabled' );
	public function Create($params=array()) { $this->Update($params); }
	public function Delete() { $this->NoDelete(); }
}

/**
 * sub-resource to manage content caching
 *
 * @api
 */
class ContentCaching extends SubResource {
	public
		$enabled;
    protected static
    	$json_name = "contentCaching",
    	$url_resource = "contentcaching";
    protected
    	$_create_keys = array( 'enabled' );
	public function Create($parm=array()) { $this->Update($parm); }
	public function Delete() { $this->NoDelete(); }
}

/**
 * sub-resource to manage session persistence setting
 */
class SessionPersistence extends SubResource {
	public
		$persistenceType;
	protected static
		$json_name = 'sessionPersistence',
		$url_resource = 'sessionpersistence';
	private
		$_create_keys = array('persistenceType');
}

/**
 * sub-resource to manage protocols (read-only)
 */
class Protocol extends \OpenCloud\PersistentObject {
	public
		$name,
		$port;
	protected static
		$json_name = 'protocol',
		$url_resource = 'loadbalancers/protocols';
	public function Create($params=array()) { $this->NoCreate(); }
	public function Update($params=array()) { $this->NoUpdate(); }
	public function Delete() { $this->NoDelete(); }
}

/**
 * sub-resource to manage algorithms (read-only)
 */
class Algorithm extends \OpenCloud\PersistentObject {
	public
		$name;
	protected static
		$json_name = 'algorithm',
		$url_resource = 'loadbalancers/algorithms';
	public function Create($params=array()) { $this->NoCreate(); }
	public function Update($params=array()) { $this->NoUpdate(); }
	public function Delete() { $this->NoDelete(); }
}

/**
 * sub-resource to manage SSL termination
 */
class SSLTermination extends SubResource {
	public
		$certificate,
		$enabled,
		$secureTrafficOnly,
		$privatekey,
		$intermediateCertificate,
		$securePort;
    protected static
    	$json_name = "sslTermination",
    	$url_resource = "ssltermination";
    protected
    	$_create_keys = array(
    		'certificate',
    		'enabled',
    		'secureTrafficOnly',
    		'privatekey',
    		'intermediateCertificate',
    		'securePort'
    	);
	public function Create($params=array()) { $this->Update($params); }
}

/**
 * sub-resource to manage Metadata
 */
class Metadata extends SubResource {
	public
		$id,
		$key,
		$value;
	protected static
		$json_name = 'meta',
		$json_collection_name = 'metadata',
		$url_resource = 'metadata';
	protected
		$_create_keys = array('key', 'value');
	public function Name() {
		return $this->key;
	}
}
