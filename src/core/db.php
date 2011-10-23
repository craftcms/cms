<?php

/*class DB {

	var $defaults = array(
		'server' => 'localhost',
		'prefix' => 'blx_'
	);

	function __construct() {
		// import the site's database config file
		include CONFIG_PATH.'db.php';

		$this->conn = mysql_connect($db['server'], $db['user'], $db['password']) or $this->_show_error();
		$this->conn = mysql_select_db($db['database']) or $this->_show_error();
	}

	private function _show_error() {
		die('MySQL error: ' . mysql_error());
	}

}
*/
