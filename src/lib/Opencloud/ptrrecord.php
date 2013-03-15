<?php
/**
 * Defines a DNS PTR record
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\DNS;

require_once(__DIR__.'/record.php');

/**
 * PTR records are used for reverse DNS
 *
 * The PtrRecord object is nearly identical with the Record object. However,
 * the PtrRecord is a child of the service, and not a child of a Domain.
 *
 */
class PtrRecord extends Record {

	protected static
		$json_name = FALSE,
		$json_collection_name = 'records',
		$url_resource = 'rdns';

	private
		$link_rel,
		$link_href;

	/**
	 * constructur ensures that the record type is PTR
	 */
	public function __construct($parent, $info=NULL) {
		$this->type = 'PTR';
		parent::__construct($parent, $info);
		if ($this->type != 'PTR')
			throw new RecordTypeError(sprintf(
				_('Invalid record type [%s], must be PTR'), $this->type));
	}

	/**
	 * specialized DNS PTR URL requires server service name and href
	 */
	public function Url($subresource=NULL, $params=array()) {
		if (isset($subresource))
			$url = $this->Parent()->Url($subresource, $params);
		else
			$url = $this->Parent()->Url(self::$url_resource, $params);
		return $url;
	}

	/**
	 * DNS PTR Create() method requires a server
	 */
	public function Create(\OpenCloud\Compute\Server $srv, $param=array()) {
		$this->link_rel = $srv->Service()->Name();
		$this->link_href = $srv->Url();
		return parent::Create($param);
	}

	/**
	 * DNS PTR Update() method requires a server
	 */
	public function Update(\OpenCloud\Compute\Server $srv, $param=array()) {
		$this->link_rel = $srv->Service()->Name();
		$this->link_href = $srv->Url();
		return parent::Update($param);
	}

	/**
	 * DNS PTR Delete() method requires a server
	 *
	 * Note that delete will remove ALL PTR records associated with the device
	 * unless you pass in the parameter ip={ip address}
	 *
	 */
	public function Delete(\OpenCloud\Compute\Server $srv) {
		$this->link_rel = $srv->Service()->Name();
		$this->link_href = $srv->Url();
		$url = $this->Url('rdns/'.$this->link_rel,
			array('href'=>$this->link_href));
		if (isset($this->data))
			$url .= '&ip='.$this->data;

		// perform the request
		$resp = $this->Service()->Request($url, 'DELETE');

		// return the AsyncResponse object
		return new AsyncResponse($this->Service(), $resp->HttpBody());
	}

	/* ---------- PROTECTED METHODS ---------- */

	/**
	 * Specialized JSON for DNS PTR creates and updates
	 */
	protected function CreateJson() {
		$obj = new \stdClass;
		$obj->recordsList = parent::CreateJson();

		// add links from server
		$obj->link = new \stdClass;
		$obj->link->href = $this->link_href;
		$obj->link->rel  = $this->link_rel;
		return $obj;
	}

	/**
	 * The Update() JSON requires a record ID
	 */
	protected function UpdateJson() {
		$obj = $this->CreateJson();
		$obj->recordsList->records[0]->id = $this->id;
		return $obj;
	}

} // class PtrRecord
