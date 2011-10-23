<?php

/**
 * URI Class
 */
//class URI {

	/**
	 * URI Constructor
	 */
	/*function __construct()
	{
		// get the Blocks instance
		$B = get_instance();

		// get the URI segments
		$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
		$this->segments = get_segments($path);

		// figure out what the URI should look like
		$this->uri = $test_uri = implode('/', $this->segments);

		// add a trailing slash to the official URI
		$this->uri .= '/';

		if ($B->config->item('trailing_slash')) {
			$test_uri .= '/';
		}

		$proper_url = $B->config->item('site_url') . $test_uri;
		$actual_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		// is the REQUEST_URI formatted correctly?
		if ($actual_url != $proper_url) {
			// redirect to the correct URL
			header("Location: {$proper_url}");
		}
	}*/

//}
