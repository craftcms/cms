<?php
/**
 * These are the default Craft requirements for [RequirementsChecker]] to use.
 */

$requirements = array(
	array(
		'name'      => 'PHP version',
		'mandatory' => true,
		'condition' => version_compare(PHP_VERSION, '5.4.0', '>='),
		'memo'      => 'PHP 5.4.0 or higher is required.',
	),
	array(
		'name'      => 'Reflection extension',
		'mandatory' => true,
		'condition' => class_exists('Reflection', false),
		'memo'      => 'The <a href="http://php.net/manual/en/class.reflectionextension.php">Reflection extension</a> is required.',
	),
	array(
		'name'      => 'PCRE extension with UTF-8 support',
		'mandatory' => true,
		'condition' => extension_loaded('pcre') && preg_match('/./u', 'Ü') === 1,
		'memo'      => 'The <a href="http://php.net/manual/en/book.pcre.php">PCRE extension</a> is required and it must be compiled to support UTF-8.',
	),
	array(
		'name'      => 'SPL extension',
		'mandatory' => true,
		'condition' => extension_loaded('SPL'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.spl.php">SPL extension</a> is required.'
	),
	array(
		'name'      => 'PDO extension',
		'mandatory' => true,
		'condition' => extension_loaded('pdo'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.pdo.php">PDO extension</a> is required.'
	),
	array(
		'name'      => 'PDO MySQL extension',
		'mandatory' => true,
		'condition' => extension_loaded('pdo_mysql'),
		'memo'      => 'The <http://php.net/manual/en/ref.pdo-mysql.php>PDO MySQL</a> extension is required.'
	),
	array(
		'name'      => 'MBString extension',
		'mandatory' => true,
		'condition' => (extension_loaded('mbstring') && ini_get('mbstring.func_overload') == 0),
		'memo'      => 'Craft requires the <a href="http://www.php.net/manual/en/book.mbstring.php">Multibyte String extension</a> with <a href="http://php.net/manual/en/mbstring.overload.php">Function Overloading</a> disabled in order to run.'
	),
	array(
		'name'      => 'Mcrypt extension',
		'mandatory' => true,
		'condition' => extension_loaded('mcrypt'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.mcrypt.php">Mcrypt extension</a> is required.',
	),
	array(
		'name'      => 'GD extension with FreeType support',
		'mandatory' => (!extension_loaded('imagick')), // Only required if ImageMagick isn't installed
		'condition' => extension_loaded('gd'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.image.php">GD</a> or <a href="http://php.net/manual/en/book.imagick.php">ImageMagick</a> extension is required, however ImageMagick is recommended as it adds animated GIF support, and preserves 8-bit and 24-bit PNGs during image transforms.'
	),
	array(
		'name'      => 'ImageMagick extension',
		'mandatory' => (!extension_loaded('gd')), // Only required if GD isn't installed
		'condition' => extension_loaded('imagick'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.image.php">GD</a> or <a href="http://php.net/manual/en/book.imagick.php">ImageMagick</a> extension is required, however ImageMagick is recommended as it adds animated GIF support, and preserves 8-bit and 24-bit PNGs during image transforms.'
	),
	array(
		'name'      => 'SSL support',
		'mandatory' => true,
		'condition' => extension_loaded('openssl'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.openssl.php">OpenSSL extension</a> is required.'
	),
	array(
		'name'      => 'cURL extension',
		'mandatory' => true,
		'condition' => extension_loaded('curl'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.curl.php">cURL extension</a> is required.',
	),
	array(
		'name'      => 'Intl extension',
		'mandatory' => false,
		'condition' => $this->checkPhpExtensionVersion('intl', '1.0.2', '>='),
		'memo'      => 'The <a href="http://www.php.net/manual/en/book.intl.php">Intl extension</a> version 1.0.2 is highly
		                recommended especially if you will be using any non-English locales for this Craft installation.'
	),
	array(
		'name'      => 'Fileinfo extension',
		'mandatory' => false,
		'condition' => extension_loaded('fileinfo'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.fileinfo.php">Fileinfo extension</a> is recommended for more accurate
                        mime-type detection for uploaded files. If it is not available a Craft will fall back to determining the mime-type
                        by the file extension.'
	),
	array(
		'name'      => 'DOM extension',
		'mandatory' => false,
		'condition' => extension_loaded('dom'),
		'memo'      => 'The <a href="http://php.net/manual/en/book.dom.php">DOM extension</a> is required for parsing XML feeds as well as <code>yii\web\XmlResponseFormatter</code>.',
	),
	array(
		'name'      => 'iconv support',
		'mandatory' => false,
		'condition' => $this->testIconvTruncateBug(),
		'memo'      => $this->iconvMessage,
	),
	array(
		'name'      => 'ini_set calls',
		'mandatory' => true,
		'condition' => $this->checkIniSet(),
		'memo'      => $this->iniSetMessage,
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
);

if ($this->checkDatabaseCreds() && extension_loaded('pdo') && extension_loaded('pdo_mysql'))
{
	$requirements[] = array(
		'name'      => 'MySQL InnoDB support',
		'mandatory' => true,
		'condition' => $this->isInnoDbSupported(),
		'memo'      => $this->dbConnectionError ? $this->dbConnectionError : 'Craft requires the MySQL InnoDB storage engine to run.',
	);

	$requirements[] = array(
		'name'      => 'MySQL version',
		'mandatory' => true,
		'condition' => $this->checkMySqlServerVersion(),
		'memo'      => $this->dbConnectionError ? $this->dbConnectionError : 'MySQL '.$this->requiredMySqlVersion.' or higher is required to run Craft.',
	);
}

return $requirements;
