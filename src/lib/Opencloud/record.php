<?php
/**
 * Defines a DNS record
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\DNS;

require_once(__DIR__.'/dnsobject.php');

/**
 * The Record class represents a single domain record
 *
 * This is also used for PTR records.
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class Record extends DnsObject {

	public
		$ttl,
		$updated,
		$created,
		$name,
		$id,
		$type,
		$data,
		$priority,
		$comment;

	protected static
		$json_name = FALSE,
		$json_collection_name = 'records',
		$url_resource = 'records';

	protected
		$_parent,
		$_update_keys = array('name','ttl','data','priority','comment'),
		$_create_keys = array('type','name','ttl','data','priority','comment');

	/**
	 * create a new record object
	 *
	 * @param mixed $parent either the domain object or the DNS object (for PTR)
	 * @param mixed $info ID or array/object of data for the object
	 * @return void
	 */
	public function __construct($parent, $info=NULL) {
		$this->_parent = $parent;
		if (get_class($parent) == 'OpenCloud\DNS')
			parent::__construct($parent, $info);
		else
			parent::__construct($parent->Service(), $info);
	}

	/**
	 * returns the parent domain
	 *
	 * @return Domain
	 */
	public function Parent() {
		return $this->_parent;
	}

} // class Record

