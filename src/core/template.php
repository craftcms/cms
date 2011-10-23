<?php

/**
 * Template Class
 */
//class Template {

	/**
	 * Template Constructor
	 */
	/*function __construct($template = '')
	{
		// save a copy of the original
		$this->original_template = $template;

		// save the template for later editing
		$this->template = $template;
	}

	/**
	 * Parse Variables
	 */
	/*function parse_vars($vars)
	{
		// ignore if no vars
		if (! $vars) return;

		foreach ($vars as $var => $value) {
			$find[] = '{'.$var.'}';
			$replace[] = $value;
		}

		$this->template = str_replace($find, $replace, $this->template);
	}

	/**
	 * Parse Single Variable
	 */
	/*function parse_var($var, $value)
	{
		// forward this along to parse_vars()
		$this->parse_vars(array($var => $value));
	}

	/**
	 * Parse Global Variables
	 */
	/*function parse_globals()
	{
		// get the Blocks instance
		$B = get_instance();

		// parse all of the current global variables
		$this->parse_vars($B->globals);
	}

}

/**
 * TemplateFile Class
 */
/*class TemplateFile extends Template {

	/**
	 * TemplateFile Constructor
	 */
	/*function __construct($file)
	{
		// get the Blocks instance
		$B = get_instance();

		$segments = get_segments($file);

		// find the deepest matching directory
		$dir = TEMPLATES_PATH;
		$depth = 0;

		foreach ($segments as $segment) {
			$test_dir = $dir.$segment.'/';

			// is it a directory?
			if (file_exists($test_dir) && is_dir($test_dir)) {
				$dir = $test_dir;
				$depth++;
			} else {
				break;
			}
		}

		// see if the first segment that wasn't a directory is actually a file
		$files = array();
		if (isset($segments[$depth])) {
			$files[] = $segments[$depth];
		}

		// also looking for an index file
		$index_file = $B->config->item('template_index_file');
		if (! in_array($index_file, $files)) {
			$files[] = $index_file;
		}

		// get the list of file extensions to look for
		$extensions = $B->config->item('template_extensions');

		// try to find a matching file
		foreach ($files as $file) {
			foreach ($extensions as $ext) {
				$test_file = $dir.$file.'.'.$ext;

				if (file_exists($test_file)) {
					$this->file = $test_file;
					$contents = file_get_contents($this->file);
					break 2;
				}
			}
		}

		// couldn't find a template file?
		if (! isset($this->file)) {
			$this->file = '';
			$contents = '';
		}

		// call the Template class constructor
		parent::__construct($contents);

		// parse global variables
		$this->parse_globals();
	}

}
*/
