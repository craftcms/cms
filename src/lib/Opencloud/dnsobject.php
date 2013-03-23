<?php
/**
 * The Rackspace Cloud DNS persistent object
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\DNS;

require_once(__DIR__.'/persistentobject.php');
require_once(__DIR__.'/asyncresponse.php');

/**
 * The DnsObject class is an extension of the PersistentObject class that
 * permits the asynchronous responses used by Cloud DNS
 *
 */
abstract class DnsObject extends \OpenCloud\PersistentObject {

	/**
	 * Create() returns an asynchronous response
	 *
	 * @param array $params array of key/value pairs
	 * @return AsyncResponse
	 */
	public function Create($params=array()) {
		$resp = parent::Create($params);
		return new AsyncResponse($this->Service(), $resp->HttpBody());
	}

	/**
	 * Update() returns an asynchronous response
	 *
	 * @param array $params array of key/value pairs
	 * @return AsyncResponse
	 */
	public function Update($params=array()) {
		$resp = parent::Update($params);
		return new AsyncResponse($this->Service(), $resp->HttpBody());
	}

	/**
	 * Delete() returns an asynchronous response
	 *
	 * @param array $params array of key/value pairs
	 * @return AsyncResponse
	 */
	public function Delete() {
		$resp = parent::Delete();
		return new AsyncResponse($this->Service(), $resp->HttpBody());
	}

	/**
	 * returns the create keys
	 */
	public function CreateKeys() {
		return $this->_create_keys;
	}
	
	/* ---------- PROTECTED METHODS ---------- */
	
	/**
	 * creates the JSON for create
	 *
	 * @return stdClass
	 */
	protected function CreateJson() {
		if (!isset($this->_create_keys))
			throw new \OpenCloud\CreateError(_('Missing [_create_keys]'));
		$top = self::JsonCollectionName();
		$obj->$top = array();
		$obj->{$top}[] = $this->GetJson($this->_create_keys);
		return $obj;
	}
	
	/**
	 * creates the JSON for update
	 *
	 * @return stdClass
	 */
	protected function UpdateJson() {
		if (!isset($this->_update_keys))
			throw new \OpenCloud\UpdateError(_('Missing [_update_keys]'));
		return $this->GetJson($this->_update_keys);
	}
	
	/* ---------- PRIVATE METHODS ---------- */
	
	/**
	 * returns JSON based on $keys
	 *
	 * @param array $keys list of items to include
	 * @return stdClass
	 */
	private function GetJson($keys) {
		$obj = new \stdClass;
		foreach($keys as $item)
			if ($this->$item)
				$obj->$item = $this->$item;
		return $obj;
	}
	
} // class DnsObject