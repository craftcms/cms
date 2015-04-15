<?php
/**
 * These are the default Craft requirements for [RequirementsChecker]] to use.
 */

$requirements = array(
	array(
		'name'      => 'PHP 5.4+',
		'mandatory' => true,
		'condition' => version_compare(PHP_VERSION, '5.4.0', '>='),
		'memo'      => 'PHP 5.4.0 or higher is required.',
	),
);


if ($this->checkDatabaseCreds() && extension_loaded('pdo') && extension_loaded('pdo_mysql'))
{
	$requirements[] = array(
		'name'      => "MySQL {$this->requiredMySqlVersion}+",
		'mandatory' => true,
		'condition' => $this->checkMySqlServerVersion(),
		'memo'      => $this->dbConnectionError ? $this->dbConnectionError : 'MySQL '.$this->requiredMySqlVersion.' or higher is required to run Craft.',
	);

	$requirements[] = array(
		'name'      => 'MySQL InnoDB support',
		'mandatory' => true,
		'condition' => $this->isInnoDbSupported(),
		'memo'      => $this->dbConnectionError ? $this->dbConnectionError : 'Craft requires the MySQL InnoDB storage engine to run.',
	);
}

$requirements = array_merge($requirements, array(
	array(
		'name'      => 'Reflection extension',
		'mandatory' => true,
		'condition' => class_exists('Reflection', false),
		'memo'      => 'The <a href="http://php.net/manual/en/class.reflectionextension.php">Reflection</a> extension is required.',
	),
	array(
		'name'      => 'PCRE extension (with UTF-8 support)',
		'mandatory' => true,
		'condition' => extension_loaded('pcre') && preg_match('/./u', 'Ü') === 1,
		'memo'      => 'The <a href="http://php.net/manual/en/book.pcre.php">PCRE</a> extension is required and it must be compiled to support UTF-8.',
	),
	array(
		'name'      => 'SPL extension',
		'mandatory' => true,
		'condition' => extension_loaded('SPL'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.spl.php">SPL</a> extension is required.'
	),
	array(
		'name'      => 'PDO extension',
		'mandatory' => true,
		'condition' => extension_loaded('pdo'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.pdo.php">PDO</a> extension is required.'
	),
	array(
		'name'      => 'PDO MySQL extension',
		'mandatory' => true,
		'condition' => extension_loaded('pdo_mysql'),
		'memo'      => 'The <http://php.net/manual/en/ref.pdo-mysql.php>PDO MySQL</a> extension is required.'
	),
	array(
		'name'      => 'Multibyte String extension (with Function Overloading disabled)',
		'mandatory' => true,
		'condition' => (extension_loaded('mbstring') && ini_get('mbstring.func_overload') == 0),
		'memo'      => 'Craft requires the <a href="http://www.php.net/manual/en/book.mbstring.php">Multibyte String</a> extension with <a href="http://php.net/manual/en/mbstring.overload.php">Function Overloading</a> disabled in order to run.'
	),
	array(
		'name'      => 'Mcrypt extension',
		'mandatory' => true,
		'condition' => extension_loaded('mcrypt'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.mcrypt.php">Mcrypt</a> extension is required.',
	),
	array(
		'name'      => 'GD extension or ImageMagick extension',
		'mandatory' => true,
		'condition' => (extension_loaded('gd') || extension_loaded('imagick')),
		'memo'      => 'The <a href="http://php.net/manual/en/book.image.php">GD</a> or <a href="http://php.net/manual/en/book.imagick.php">ImageMagick</a> extension is required, however ImageMagick is recommended as it adds animated GIF support, and preserves 8-bit and 24-bit PNGs during image transforms.'
	),
	array(
		'name'      => 'OpenSSL extension',
		'mandatory' => true,
		'condition' => extension_loaded('openssl'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.openssl.php">OpenSSL</a> extension is required.'
	),
	array(
		'name'      => 'cURL extension',
		'mandatory' => true,
		'condition' => extension_loaded('curl'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.curl.php">cURL</a> extension is required.',
	),
	array(
		'name'      => 'ini_set calls',
		'mandatory' => true,
		'condition' => $this->checkIniSet(),
		'memo'      => $this->iniSetMessage,
	),
	array(
		'name'      => 'Intl extension',
		'mandatory' => false,
		'condition' => $this->checkPhpExtensionVersion('intl', '1.0.2', '>='),
		'memo'      => 'The <a href="http://www.php.net/manual/en/book.intl.php">Intl</a> extension version 1.0.2 is highly ' .
			'recommended especially if you will be using any non-English locales for this Craft installation.'
	),
	array(
		'name'      => 'Fileinfo extension',
		'mandatory' => false,
		'condition' => extension_loaded('fileinfo'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.fileinfo.php">Fileinfo</a> extension is recommended for more accurate ' .
			'mime-type detection for uploaded files. If it is not available a Craft will fall back to determining the mime-type ' .
			'by the file extension.'
	),
	array(
		'name'      => 'DOM extension',
		'mandatory' => false,
		'condition' => extension_loaded('dom'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.dom.php">DOM</a> extension is required for parsing XML feeds as well as <code>yii\web\XmlResponseFormatter</code>.',
	),
	array(
		'name'      => 'iconv extension',
		'mandatory' => false,
		'condition' => $this->testIconvTruncateBug(),
		'memo'      => $this->iconvMessage,
	),
	array(
		'name'      => 'Max Upload File Size',
		'mandatory' => false,
		'condition' => true,
		'memo'      => 'upload_max_filesize is set to '.ini_get('upload_max_filesize').'.',
	),
	array(
		'name'      => 'Max POST Size',
		'mandatory' => false,
		'condition' => true,
		'memo'      => 'post_max_size is set to '.ini_get('post_max_size').'.',
	),
	array(
		'name'      => 'Memory Limit',
		'mandatory' => false,
		'condition' => $this->checkMemory(),
		'memo'      => $this->memoryMessage,
	),
));

return $requirements;
