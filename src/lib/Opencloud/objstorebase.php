<?php
/**
 * An abstract class that defines shared code among all Object Storage
 * components
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\ObjectStore;

require_once(__DIR__.'/base.php');
require_once(__DIR__.'/metadata.php');

/**
 * Intermediate (abstract) class to implement shared
 * features of all object-storage classes
 *
 * This class implements metadata-handling and other features that are common to
 * the object storage components.
 *
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
abstract class ObjStoreBase extends \OpenCloud\Base {

	const
		ACCOUNT_META_PREFIX = 'X-Account-',
		CONTAINER_META_PREFIX = 'X-Container-Meta-',
		OBJECT_META_PREFIX = 'X-Object-Meta-',
		CDNCONTAINER_META_PREFIX = 'X-Cdn-';

	public
		$metadata;

	/**
	 * Initializes the metadata component
	 */
	public function __construct() {
	    $this->metadata = new \OpenCloud\Metadata();
	}

	/**
	 * Given an HttpResponse object, converts the appropriate headers
	 * to metadata
	 *
	 * @param \OpenCloud\HttpResponse
	 * @return void
	 */
	public function GetMetadata(\OpenCloud\HttpResponse $response) {
	    $this->metadata = new \OpenCloud\Metadata();
	    $prefix = $this->Prefix();
	    $this->metadata->SetArray($response->Headers(), $prefix);
	}

	/**
	 * If object has metadata, returns an associative array of headers
	 *
	 * For example, if a DataObject has a metadata item named 'FOO',
	 * then this would return array('X-Object-Meta-FOO'=>$value);
	 *
	 * @return array;
	 */
	public function MetadataHeaders() {
	    $headers = array();
	    $prefix = $this->Prefix();

	    // only build if we have metadata
	    if (is_object($this->metadata)) {
	        foreach($this->metadata as $key => $value)
	            $headers[$prefix.$key] = $value;
	    }

	    return $headers;
	}

	/**
	 * Returns the displayable name of the object
	 *
	 * Can be overridden by child objects; *must* be overridden by child
	 * objects if the object does not have a `name` attribute defined.
	 *
	 * @api
	 * @throws NameError if attribute 'name' is not defined
	 */
	public function Name() {
		if (property_exists($this, 'name'))
			return $this->name;
		else
			throw new \OpenCloud\NameError(sprintf(
				_('name attribute does not exist for [%s]'),
				get_class($this)));
	}

	public static function JsonName() {
	    return NULL;
	}

	public static function JsonCollectionName() {
	    return NULL;
	}

	public static function JsonCollectionElement() {
	    return NULL;
	}

	/********** PRIVATE METHODS **********/

	/**
	 * Returns the proper prefix for the specified type of object
	 *
	 * @param string $type The type of object; derived from `get_class()` if not
	 *		specified.
	 */
	private function Prefix($type=NULL) {
		if (!isset($type)) {
			$parts = preg_split('/\\\/', get_class($this));
			$type = $parts[count($parts)-1];
		}
		switch($type) {
		case 'Account':
			return self::ACCOUNT_META_PREFIX;
		case 'CDNContainer':
		    return self::CDNCONTAINER_META_PREFIX;
		case 'Container':
			return self::CONTAINER_META_PREFIX;
		case 'DataObject':
			return self::OBJECT_META_PREFIX;
		default:
			throw new MetadataPrefixError(sprintf(
				_('Unrecognized metadata type [%s]'), $type));
		}
	}
}
