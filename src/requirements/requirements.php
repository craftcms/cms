<?php
/**
 * These are the default Craft requirements for [RequirementsChecker]] to use.
 */

$requirements = [
    [
        'name' => 'PHP 5.6+',
        'mandatory' => true,
        'condition' => version_compare(PHP_VERSION, '5.6.0', '>='),
        'memo' => 'PHP 5.6.0 or higher is required.',
    ],
];

if ($this->checkDatabaseCreds()) {
    switch ($this->dbCreds['driver']) {
        case 'mysql':
            if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
                /** @noinspection ElvisOperatorCanBeUsedInspection */
                $requirements[] = [
                    'name' => "MySQL {$this->requiredMySqlVersion}+",
                    'mandatory' => true,
                    'condition' => $this->checkDatabaseServerVersion(),
                    'memo' => $this->dbConnectionError ? $this->dbConnectionError : 'MySQL '.$this->requiredMySqlVersion.' or higher is required to run Craft CMS.',
                ];

                // If we know we already can't connect to the database, don't both running this one so we don't get double error messages.
                if (!$this->dbConnectionError) {
                    /** @noinspection ElvisOperatorCanBeUsedInspection */
                    $requirements[] = [
                        'name' => 'MySQL InnoDB support',
                        'mandatory' => true,
                        'condition' => $this->isInnoDbSupported(),
                        'memo' => $this->dbConnectionError ? $this->dbConnectionError : 'Craft CMS requires the MySQL InnoDB storage engine to run.',
                    ];
                }
            }
            break;
        case 'pgsql':
            if (extension_loaded('pdo') && extension_loaded('pdo_pgsql')) {
                /** @noinspection ElvisOperatorCanBeUsedInspection */
                $requirements[] = [
                    'name' => "PostgreSQL {$this->requiredPgSqlVersion}+",
                    'mandatory' => true,
                    'condition' => $this->checkDatabaseServerVersion(),
                    'memo' => $this->dbConnectionError ? $this->dbConnectionError : 'PostgresSQL '.$this->requiredPgSqlVersion.' or higher is required to run Craft CMS.',
                ];
            }
            break;
        default:
            /** @noinspection ThrowRawExceptionInspection */
            throw new Exception('Unsupported connection type: '.$this->dbCreds['driver']);
    }
}

// Only run this requirement check if we're running in the context of Craft.
if ($this->isCraftRunning()) {
    $requirements[] = [
        'name' => 'Sensitive Craft folders should not be publicly accessible',
        'mandatory' => false,
        'condition' => $this->checkWebRoot(),
        'memo' => $this->webRootFolderMessage,
    ];
}

switch ($this->dbCreds['driver']) {
    case 'mysql':
        $requirements[] = [
            'name' => 'PDO MySQL extension',
            'mandatory' => true,
            'condition' => extension_loaded('pdo_mysql'),
            'memo' => 'The <http://php.net/manual/en/ref.pdo-mysql.php>PDO MySQL</a> extension is required.'
        ];
        break;
    case 'pgsql':
        $requirements[] = [
            'name' => 'PDO PostgreSQL extension',
            'mandatory' => true,
            'condition' => extension_loaded('pdo_pgsql'),
            'memo' => 'The <https://secure.php.net/manual/en/ref.pdo-pgsql.php>PDO PostgreSQL</a> extension is required.'
        ];
        break;
    default:
        /** @noinspection ThrowRawExceptionInspection */
        throw new Exception('Unsupported connection type: '.$this->dbCreds['driver']);
}

$requirements = array_merge($requirements, [
    [
        'name' => 'Reflection extension',
        'mandatory' => true,
        'condition' => class_exists('Reflection', false),
        'memo' => 'The <a href="http://php.net/manual/en/class.reflectionextension.php">Reflection</a> extension is required.',
    ],
    [
        'name' => 'PCRE extension (with UTF-8 support)',
        'mandatory' => true,
        'condition' => extension_loaded('pcre') && preg_match('/./u', 'Ü') === 1,
        'memo' => 'The <a href="http://php.net/manual/en/book.pcre.php">PCRE</a> extension is required and it must be compiled to support UTF-8.',
    ],
    [
        'name' => 'SPL extension',
        'mandatory' => true,
        'condition' => extension_loaded('SPL'),
        'memo' => 'The <a href="http://php.net/manual/en/book.spl.php">SPL</a> extension is required.'
    ],
    [
        'name' => 'PDO extension',
        'mandatory' => true,
        'condition' => extension_loaded('pdo'),
        'memo' => 'The <a href="http://php.net/manual/en/book.pdo.php">PDO</a> extension is required.'
    ],
    [
        'name' => 'Multibyte String extension (with Function Overloading disabled)',
        'mandatory' => true,
        'condition' => extension_loaded('mbstring') && ini_get('mbstring.func_overload') == 0,
        'memo' => 'Craft CMS requires the <a href="http://www.php.net/manual/en/book.mbstring.php">Multibyte String</a> extension with <a href="http://php.net/manual/en/mbstring.overload.php">Function Overloading</a> disabled in order to run.'
    ],
    [
        'name' => 'GD extension or ImageMagick extension',
        'mandatory' => true,
        'condition' => extension_loaded('gd') || extension_loaded('imagick'),
        'memo' => 'The <a href="http://php.net/manual/en/book.image.php">GD</a> or <a href="http://php.net/manual/en/book.imagick.php">ImageMagick</a> extension is required, however ImageMagick is recommended as it adds animated GIF support, and preserves 8-bit and 24-bit PNGs during image transforms.'
    ],
    [
        'name' => 'OpenSSL extension',
        'mandatory' => true,
        'condition' => extension_loaded('openssl'),
        'memo' => 'The <a href="http://php.net/manual/en/book.openssl.php">OpenSSL</a> extension is required.'
    ],
    [
        'name' => 'cURL extension',
        'mandatory' => true,
        'condition' => extension_loaded('curl'),
        'memo' => 'The <a href="http://php.net/manual/en/book.curl.php">cURL</a> extension is required.',
    ],
    [
        'name' => 'ini_set calls',
        'mandatory' => true,
        'condition' => $this->checkIniSet(),
        'memo' => $this->iniSetMessage,
    ],
    [
        'name' => 'Intl extension',
        'mandatory' => false,
        'condition' => $this->checkPhpExtensionVersion('intl', '1.0.2', '>='),
        'memo' => 'The <a href="http://www.php.net/manual/en/book.intl.php">Intl</a> extension version 1.0.2 is highly '.
            'recommended especially if you will be using any non-English languages for this Craft CMS installation.'
    ],
    [
        'name' => 'Fileinfo extension',
        'mandatory' => false,
        'condition' => extension_loaded('fileinfo'),
        'memo' => 'The <a href="http://php.net/manual/en/book.fileinfo.php">Fileinfo</a> extension is recommended for more accurate '.
            'mime-type detection for uploaded files. If it is not available a Craft CMS will fall back to determining the mime-type '.
            'by the file extension.'
    ],
    [
        'name' => 'DOM extension',
        'mandatory' => false,
        'condition' => extension_loaded('dom'),
        'memo' => 'The <a href="http://php.net/manual/en/book.dom.php">DOM</a> extension is required for parsing XML feeds as well as <code>yii\web\XmlResponseFormatter</code>.',
    ],
    [
        'name' => 'iconv extension',
        'mandatory' => false,
        'condition' => $this->testIconvTruncateBug(),
        'memo' => $this->iconvMessage,
    ],
    [
        'name' => 'Max Upload File Size',
        'mandatory' => false,
        'condition' => true,
        'memo' => 'upload_max_filesize is set to '.ini_get('upload_max_filesize').'.',
    ],
    [
        'name' => 'Max POST Size',
        'mandatory' => false,
        'condition' => true,
        'memo' => 'post_max_size is set to '.ini_get('post_max_size').'.',
    ],
    [
        'name' => 'Memory Limit',
        'mandatory' => false,
        'condition' => $this->checkMemory(),
        'memo' => $this->memoryMessage,
    ],
]);

return $requirements;
