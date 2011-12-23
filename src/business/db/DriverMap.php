<?php

function getDbDriverMap()
{
	return array(
				'pgsql'=>'CPgsqlSchema',      // PostgreSQL
				'mysqli'=>'CMysqlSchema',     // MySQL
				'mysql'=>'BlocksMysqlSchema', // MySQL
				'sqlite'=>'CSqliteSchema',    // sqlite 3
				'sqlite2'=>'CSqliteSchema',   // sqlite 2
				'mssql'=>'CMssqlSchema',      // Mssql driver on windows hosts
				'dblib'=>'CMssqlSchema',      // dblib drivers on linux (and maybe others os) hosts
				'sqlsrv'=>'CMssqlSchema',     // Mssql
				'oci'=>'COciSchema',          // Oracle driver
	);
}
