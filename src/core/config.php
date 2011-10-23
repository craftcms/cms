<?php

/**
 * Config Class
 */
/*class Config {

	private $defaults = array(
		'site_url'            => '/',
		'template_extensions' => array('html', 'php'),
		'template_index_file' => 'index',
		'trailing_slash'      => TRUE
	);

	private $config;

	/**
	 * Config Constructor
	 */
	/*function __construct()
	{
		// import the site's custom config file
		include CONFIG_PATH.'site.php';

		// merge the site's custom settings with the defaults
		$this->config = array_merge($this->defaults, $config);

		// force site_url to end with a slash
		if (substr($this->config['site_url'], -1) != '/') {
			$this->config['site_url'] .= '/';
		}
	}

	/**
	 * Get Config Item
	 */
	/*function item($item)
	{
		if (isset($this->config[$item])) {
			return $this->config[$item];
		}
	}

}*/
