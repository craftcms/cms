<?php
/**
 * Specialized metadata for OpenStack Server objects (metadata items
 * can be managed individually or in aggregate)
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\Compute;

require_once(__DIR__.'/metadata.php');

/**
 * This class handles server metadata
 *
 * Server metadata is a weird beast in that it has resource representations
 * and HTTP calls to set the entire server metadata as well as individual
 * items.
 *
 * @author Glen Campbell <glen.campbell@rackspace.com>s
 */
class ServerMetadata extends \OpenCloud\Metadata {
    private
		$_parent,			// the parent object
		$_key,				// the metadata item (if supplied)
		$_url;				// the URL of this particular metadata item or block

	/**
	 * Contructs a Metadata object associated with a Server or Image object
	 *
	 * @param object $parent either a Server or an Image object
	 * @param string $key the (optional) key for the metadata item
	 * @throws MetadataError
	 */
	public function __construct(Server $parent, $key=NULL) {
		// construct defaults
		$this->_parent = $parent;

		// set the URL according to whether or not we have a key
		if ($this->Parent()->id) {
            $this->_url = $this->Parent()->Url().'/metadata';
            $this->_key = $key;

            // in either case, retrieve the data
            $response = $this->Parent()->Service()->Request($this->Url());

            // check the response
            if ($response->HttpStatus() >= 300)
                throw new MetadataError(
                    sprintf(
                        _('Unable to retrieve metadata [%s], response [%s]'),
                        $this->Url(), $response->HttpBody()));

            $this->debug(_('Metadata for [%s] is [%s]'),
                $this->Url(), $response->HttpBody());

            // parse and assign the server metadata
            $obj = json_decode($response->HttpBody());
            if ((!$this->CheckJsonError()) && isset($obj->metadata)) {
            	foreach($obj->metadata as $k => $v)
            		$this->$k = $v;
            }
		}
	}

	/**
	 * Returns the URL of the metadata (key or block)
	 *
	 * @return string
	 * @throws ServerUrlerror
	 */
	public function Url() {
	    if (!isset($this->_url))
	        throw new ServerUrlError(_('Metadata has no URL (new object)'));
		if ($this->_key)
			return $this->_url . '/' . $this->_key;
		else
			return $this->_url;
	}

	/**
	 * Sets a new metadata value or block
	 *
	 * Note that, if you're setting a block, the block specified will
	 * *entirely replace* the existing block.
	 *
	 * @api
	 * @return void
	 * @throws MetadataCreateError
	 */
	public function Create() {
		// perform the request
		$response = $this->parent()->Service()->Request(
			$this->Url(),
			'PUT',
			array(),
			$this->GetMetadataJson()
		);

		// check the response
		if ($response->HttpStatus() >= 300)
			throw new MetadataCreateError(
				sprintf(_('Error setting metadata on [%s], response [%s]'),
					$this->Url(), $response->HttpBody()));
	}

	/**
	 * Updates a metadata key or block
	 *
	 * @api
	 * @return void
	 * @throws MetadataUpdateError
	 */
	public function Update() {
		// perform the request
		$response = $this->parent()->Service()->Request(
			$this->Url(),
			'POST',
			array(),
			$this->GetMetadataJson()
		);

		// check the response
		if ($response->HttpStatus() >= 300)
			throw new MetadataUpdateError(
				sprintf(_('Error updating metadata on [%s], response [%s]'),
					$this->Url(), $response->HttpBody()));
	}

	/**
	 * Deletes a metadata key or block
	 *
	 * @api
	 * @return void
	 * @throws MetadataDeleteError
	 */
	public function Delete() {
		// perform the request
		$response = $this->parent()->Service()->Request(
			$this->Url(),
			'DELETE',
			array()
		);

		// check the response
		if ($response->HttpStatus() >= 300)
			throw new MetadataDeleteError(
				sprintf(_('Error deleting metadata on [%s], response [%s]'),
					$this->Url(), $response->HttpBody()));
	}

	/**
	 * Returns the parent Server object
	 *
	 * @return Server
	 */
	public function Parent() {
		return $this->_parent;
	}

	/**
	 * Overrides the base setter method, since the metadata key can be
	 * anything (no name-checking is required)
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 * @throws MetadataKeyError
	 */
	public function __set($key, $value) {
		// if a key was supplied when creating the object, then we can't set
		// any other values
		if ($this->_key and ($key != $this->_key))
			throw new MetadataKeyError(
				sprintf(_('You cannot set extra values on [%s]'),
				    $this->Url()));

		// otherwise, just set it;
		parent::__set($key, $value);
	}

	/********** PRIVATE METHODS **********/

	/**
	 * Builds a metadata JSON string
	 *
	 * @return string
	 * @throws MetadataJsonError
	 */
	private function GetMetadataJson() {
		$obj = new \stdClass();

		// different element if only a key is set
		if ($this->_key) {
			$name = $this->_key;
			$obj->meta->$name = $this->$name;
		}
		else {
			$obj->metadata = new \stdClass();
			foreach($this->Keylist() as $key)
				$obj->metadata->$key = (string)$this->$key;
		}
		$json = json_encode($obj);
		if ($this->CheckJsonError())
			throw new MetadataJsonError(
			    _('Unable to encode JSON for metadata'));
		else
			return $json;
	}

}
