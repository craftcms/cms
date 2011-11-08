<?php

class SecurityTag extends Tag
{
	private $_siteId;

	function __construct($siteId)
	{
		$this->_siteId = $siteId;
	}

	// TODO
	// Permissions for user
	// Permissions for group
}
