<?php
/**
 * Copyright (c) 2009 Igor 'idle sign' Starikov
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/*
 * Heavily modified for Blocks.
 */

class BlocksFile extends CApplicationComponent
{
	/**
	 * @var array object instances array with key set to $_filepath
	 */
	private static $_instances = array();

	/**
	 * @var string filesystem object path submitted by user
	 */
	private $_filepath;

	/**
	 * @var string real filesystem object path figured by script on the basis of $_filepath
	 */
	private $_realpath;

	/**
	 * @var boolean 'true' if filesystem object described by $_realpath exists
	 */
	private $_exists;

	/**
	 * @var boolean 'true' if filesystem object described by $_realpath is a regular file
	 */
	private $_isFile = false;

	/**
	 * @var boolean 'true' if filesystem object described by $_realpath is a directory
	 */
	private $_isDir = false;

	/**
	 * @var boolean 'true' if file described by $_realpath is uploaded
	 */
	private $_isUploaded = false;

	/**
	 * @var boolean 'true' if filesystem object described by $_realpath is readable
	 */
	private $_readable;

	/**
	 * @var boolean 'true' if filesystem object described by $_realpath writeable
	 */
	private $_writeable;

	/**
	 * @var string basename of the file (eg. 'myfile.htm' for '/var/www/htdocs/files/myfile.htm')
	 */
	private $_basename;

	/**
	 * @var string name of the file (eg. 'myfile' for '/var/www/htdocs/files/myfile.htm')
	 */
	private $_filename;

	/**
	 * @var string directory name of the filesystem object (eg. '/var/www/htdocs/files' for '/var/www/htdocs/files/myfile.htm')
	 */
	private $_dirname;

	/**
	 * @var string file extension(eg. 'htm' for '/var/www/htdocs/files/myfile.htm')
	 */
	private $_extension;

	/**
	 * @var string file extension(eg. 'text/html' for '/var/www/htdocs/files/myfile.htm')
	 */
	private $_mimeType;

	/**
	 * @var integer the time the filesystem object was last modified (Unix timestamp eg. '1213760802')
	 */
	private $_timeModified;

	/**
	 * @var string filesystem object size formatted (eg. '70.4 KB') or in bytes (eg. '72081') see {@link getSize} parameters
	 */
	private $_size;

	/**
	 * @var boolean filesystem object has contents flag
	 */
	private $_isEmpty;

	/**
	 * @var mixed filesystem object owner name (eg. 'idle') or in ID (eg. '1000') see {@link getOwner} parameters
	 */
	private $_owner;

	/**
	 * @var mixed filesystem object group name (eg. 'apache') or in ID (eg. '127') see {@link getGroup} parameters
	 */
	private $_group;

	/**
	 * @var string filesystem object permissions (considered octal eg. '0755')
	 */
	private $_permissions;

	/**
	 * @var resource file pointer resource (for {@link open} & {@link close})
	 */
	private $_handle = null;

	/**
	 * @var CUploadedFile object instance
	 */
	private $_uploadedInstance = null;

	private $_contents = null;

	/**
	 * Returns the instance of BlocksFile for the specified file.
	 *
	 * @param string $filePath Path to file specified by user
	 * @return object BlocksFile instance
	 */
	public static function getInstance($filePath)
	{
		if (!array_key_exists($filePath, self::$_instances))
		{
			self::$_instances[$filePath] = new BlocksFile($filePath);
		}

		return self::$_instances[$filePath];
	}

	/**
	 * Logs a message.
	 *
	 * @param string $message Message to be logged
	 * @param string $level Level of the message (e.g. 'trace', 'warning', 'error', 'info', see CLogger constants definitions)
	 */
	private function addLog($message, $level = 'info')
	{
		Blocks::log($message.' (obj: '.$this->_realpath.')', $level, 'BlocksFile');
	}

	public function refresh()
	{
		return $this->set($this->_filepath);
	}

	/**
	 * Basic BlocksFile method. Sets BlocksFile object to work with specified filesystem object.
	 * Essentially path supplied by user is resolved into real path (see {@link getRealPath}), all the other property getting methods should use that real path.
	 * Uploaded files are supported through {@link CUploadedFile} Yii class. Path aliases are supported through {@link getPathOfAlias} Yii method.
	 *
	 * @param string $filePath Path to the file specified by user, if not set exception is raised
	 * @param boolean $greedy If true file properties (such as 'Size', 'Owner', 'Permission', etc.) would be autoloaded
	 * @return object BlocksFile instance for the specified filesystem object
	 */
	public function set($filePath, $greedy=false)
	{
		if (trim($filePath) != '')
		{
			$uploaded = null;

			if (strpos($filePath, '\\') === false && strpos($filePath, '/') === false)
			{
				$uploaded = CUploadedFile::getInstanceByName($filePath);

				if ($uploaded)
				{
					$filePath = $uploaded->getTempName();
					Blocks::trace('File "'.$filePath.'" is identified as uploaded', 'ext.file');

				}
				else
				{
					if ($pathOfAlias = Blocks::getPathOfAlias($filePath))
					{
						Blocks::trace('The string supplied to '.__METHOD__.' method - "'.$filePath.'" is identified as the alias to "'.$pathOfAlias.'"', 'ext.file');
						$filePath = $pathOfAlias;
					}
				}
			}

			clearstatcache();
			$realPath = self::realPath($filePath);
			$instance = self::getInstance($realPath);
			$instance->_filepath = $filePath;
			$instance->_realpath = $realPath;

			if ($instance->exists())
			{
				$instance->_uploadedInstance = $uploaded;

				$instance->pathInfo();
				$instance->readable;
				$instance->writeable;

				if ($greedy)
				{
					$instance->isempty;
					$instance->size;
					$instance->owner;
					$instance->group;
					$instance->permissions;
					$instance->timeModified;
					if ($instance->_isFile)
						$instance->mimeType;
				}
			}
			return $instance;
		}

		throw new BlocksException('Path to filesystem object is not specified within '.__METHOD__.' method');
	}

	/**
	 * Populates basic BlocksFile properties (i.e. 'Dirname', 'Basename', etc.)
	 * using values resolved by pathinfo() php function.
	 * Detects filesystem object type (file, directory).
	 */
	private function pathInfo()
	{
		if (is_file($this->_realpath))
		{
			$this->_isFile = true;
		}
		elseif (is_dir($this->_realpath))
		{
			$this->_isDir = true;
		}

		if ($this->_uploadedInstance)
			$this->_isUploaded = true;

		$pathinfo = pathinfo($this->_isUploaded ? $this->_uploadedInstance->getName() : $this->_realpath);

		$this->_dirname = $pathinfo['dirname'];
		$this->_basename = $pathinfo['basename'];

		// PHP version < 5.2 workaround
		if(!isset($pathinfo['filename']))
		{
			$this->_filename = substr($pathinfo['basename'], 0, strrpos($pathinfo['basename'], '.'));
		}
		else
		{
			$this->_filename = $pathinfo['filename'];
		}
		if (key_exists('extension', $pathinfo))
			$this->_extension = $pathinfo['extension'];
		else
			$this->_extension = null;
	}

	/**
	 * Returns real filesystem object path figured by script (see {@link realPath}) on the basis of user supplied $_filepath.
	 * If $_realpath property is set, returned value is read from that property.
	 *
	 * @param string $dir_separator Directory separator char (depends upon OS)
	 * @return string Real file path
	 */
	public function getRealPath($dir_separator = '/')
	{
		if (!isset($this->_realpath))
			$this->_realpath = $this->realPath($this->_filepath, $dir_separator);

		return $this->_realpath;
	}

	/**
	 * Base real filesystem object path resolving method.  Returns real path resolved from the supplied path.
	 *
	 * @param string $suppliedPath Path from which real filesystem object path should be resolved
	 * @param string $dir_separator Directory separator char (depends upon OS)
	 * @return string Real file path
	 */
	private function realPath($suppliedPath, $dir_separator = '/')
	{
		$currentPath = $suppliedPath;

		if (!strlen($currentPath))
			return $dir_separator;

		$winDrive = '';

		// Windows OS path type detection
		if (!strncasecmp(PHP_OS, 'win', 3))
		{
			$currentPath = preg_replace('/[\\\\\/]/', $dir_separator, $currentPath);
			if (preg_match('/([a-zA-Z]\:)(.*)/', $currentPath, $matches))
			{
				$winDrive = $matches[1];
				$currentPath = $matches[2];
			}
			else
			{
				$workingDir = getcwd();
				$winDrive = substr($workingDir, 0, 2);
				if ($currentPath{0} !== $dir_separator{0})
				{
					$currentPath = substr($workingDir, 3).$dir_separator.$currentPath;
				}
			}
		}
		elseif ($currentPath{0} !== $dir_separator)
		{
			$currentPath = getcwd().$dir_separator.$currentPath;
		}

		$pathsArr = array();
		foreach (explode($dir_separator, $currentPath) as $path)
		{
			if (strlen($path) && $path !== '.')
			{
				if ($path == '..')
				{
					array_pop($pathsArr);
				}
				else
				{
					$pathsArr[] = $path;
				}
			}
		}

		$realpath = $winDrive.$dir_separator.implode($dir_separator, $pathsArr);

		if ($currentPath != $suppliedPath)
			Blocks::trace('Path "'.$suppliedPath.'" resolved into "'.$realpath.'"', 'ext.file');

		return $realpath;
	}

	/**
	 * Tests current filesystem object existance and returns boolean (see {@link exists}).
	 * If $_exists property is set, returned value is read from that property.
	 *
	 * @return boolean 'True' if file exists, otherwise 'false'
	 */
	public function getExists()
	{
		if (!isset($this->_exists))
			$this->exists();

		return $this->_exists;
	}

	/**
	 * Returns filesystem object type for the current file (see {@link pathInfo}).
	 * Tells whether filesystem object is a regular file.
	 *
	 * @return boolean 'True' if filesystem object is a regular file,
	 * overwise 'false'
	 */
	public function getIsFile()
	{
		return $this->_isFile;
	}

	/**
	 * Returns filesystem object type for the current file (see {@link pathInfo}). Tells whether filesystem object is a directory.
	 *
	 * @return boolean 'True' if filesystem object is a directory,
	 * overwise 'false'
	 */
	public function getIsDir()
	{
		return $this->_isDir;
	}

	/**
	 * Tells whether file is uploaded through a web form.
	 *
	 * @return boolean 'True' if file is uploaded, otherwise 'false'
	 */
	public function getIsUploaded()
	{
		return $this->_isUploaded;
	}

	/**
	 * Returns filesystem object has-contents flag.
	 * Directory considered empty if it doesn't contain descendants.
	 * File considered empty if its size is 0 bytes.
	 *
	 * @return boolean 'True' if file is a directory, otherwise 'false'
	 */
	public function getIsEmpty()
	{
		if (!isset($this->_isEmpty))
		{
			if (($this->_isFile && $this->getSize(false)==0) || (!$this->_isFile && count($this->dirContents($this->_realpath)) == 0))
				$this->_isEmpty = true;
			else
				$this->_isEmpty = false;
		}

		return $this->_isEmpty;
	}

	/**
	 * Tests whether the current filesystem object is readable and returns boolean.
	 * If $_readable property is set, returned value is read from that property.
	 *
	 * @return boolean 'True' if filesystem object is readable, overwise 'false'
	 */
	public function getReadable()
	{
		if (!isset($this->_readable))
			$this->_readable = is_readable($this->_realpath);

		return $this->_readable;
	}

	/**
	 * Tests whether the current filesystem object is readable and returns boolean.
	 * If $_writeable property is set, returned value is read from that property.
	 *
	 * @return boolean 'True' if filesystem object is writeable, otherwise 'false'
	 */
	public function getWriteable()
	{
		if (!isset($this->_writeable))
			$this->_writeable = $this->isReallyWritable($this->_filepath);

		return $this->_writeable;
	}

	/**
	 * PHP's is_writable has problems (especially on Windows).
	 * See: https://bugs.php.net/bug.php?id=27609 and https://bugs.php.net/bug.php?id=30931.
	 * This function tests writeability by creating a temp file on the filesystem.
	 *
	 * @param $path = the path to test.
	 * @return boolean 'True' if filesystem object is writeable, otherwise 'false'
	 */
	private function isReallyWritable($path)
	{
		$lastChar = $path{strlen($path) - 1};
		if ($lastChar == '/' || $lastChar == '\\')
			return $this->isReallyWritable($path.uniqid(mt_rand()).'.tmp');
		else if (is_dir($path))
			return $this->isReallyWritable($path.'/'.uniqid(mt_rand()).'.tmp');

		// check tmp file for read/write capabilities
		$rm = file_exists($path);
		$f = @fopen($path, 'a');

		if ($f === false)
			return false;

		fclose($f);
		if (!$rm)
			unlink($path);

		return true;
	}

	/**
	 * Base filesystem object existence resolving method.
	 * Tests current filesystem object existence and returns boolean.
	 *
	 * @return boolean 'True' if filesystem object exists, otherwise 'false'
	 */
	private function exists()
	{
		Blocks::trace('Filesystem object availability test: '.$this->_realpath, 'ext.file');

		if (file_exists($this->_realpath))
		{
			$this->_exists = true;
		}
		else
		{
			$this->_exists = false;
		}

		if ($this->_exists)
			return true;

		$this->addLog('Filesystem object not found');
		return false;
	}

	/**
	 * Creates empty file if the current file doesn't exist.
	 *
	 * @return mixed Updated the current BlocksFile object on success, 'false' on fail.
	 */
	public function create()
	{
		if (!$this->_exists)
		{
			if ($this->open('w'))
			{
				$this->close();
				return $this->set($this->_realpath);
			}

			$this->addLog('Unable to create empty file: '.$this->_realpath, 'warning');
			return false;
		}

		$this->addLog('File creation failed. File already exists: '.$this->_realpath, 'warning');
		return false;
	}

	/**
	 * Creates empty directory defined either through {@link set} or through the $directory parameter.
	 *
	 * @param int|string $permissions Access permissions for the directory
	 * @param string $directory Parameter used to create directory other than supplied by {@link set} method of the BlocksFile
	 * @return mixed Updated the current BlocksFile object on success, 'false' on fail.
	 */
	public function createDir($permissions = 0754, $directory = null)
	{
		if (is_null($directory))
			$dir = $this->_realpath;
		else
			$dir = $directory;

		$oldumask = umask(0);
		if (@mkdir($dir, $permissions, true))
		{
			@umask($oldumask);
			if (!$directory)
				return $this->set($dir);
			else
				return true;
		}

		$this->addLog('Unable to create empty directory: '.$dir, 'warning');
		return false;
	}

	/**
	 * Opens (if not already opened) the current file using certain mode. See fopen() php function for more info.
	 *
	 * For now used only internally.
	 *
	 * @param string $mode Type of access required to the stream
	 * @return mixed Current BlocksFile object on success, 'false' on fail.
	 */
	private function open($mode)
	{
		if (is_null($this->_handle))
		{
			if ($this->_handle = fopen($this->_realpath, $mode))
				return $this;

			$this->addLog('Unable to open file: '.$this->_realpath.' using mode "'.$mode.'"', 'warning');
			return false;
		}
	}

	/**
	 * Closes (if opened) the current file pointer.  See fclose() php function for more info.
	 *
	 * For now used only internally.
	 */
	private function close()
	{
		if (!is_null($this->_handle))
		{
			fclose($this->_handle);
			$this->_handle = null;
		}
	}

	/**
	 * Returns owner of current filesystem object (UNIX systems). Returned value depends upon $getName parameter value.
	 * If $_owner property is set, returned value is read from that property.
	 *
	 * @param boolean $getName Defaults to 'true', meaning that owner name instead of ID should be returned.
	 * @return mixed Owner name, or ID if $getName set to 'false'
	 */
	public function getOwner($getName = true)
	{
		if (!isset($this->_owner))
			$this->_owner = $this->_exists ? fileowner($this->_realpath) : null;

		if (is_int($this->_owner) && function_exists('posix_getpwuid') && $getName == true)
		{
			$this->_owner = posix_getpwuid($this->_owner);
			$this->_owner = $this->_owner['name'];
		}

		return $this->_owner;
	}

	/**
	 * Returns group of current filesystem object (UNIX systems). Returned value depends upon $getName parameter value.
	 * If $_group property is set, returned value is read from that property.
	 *
	 * @param boolean $getName Defaults to 'true', meaning that group name instead of ID should be returned.
	 * @return mixed Group name, or ID if $getName set to 'false'
	 */
	public function getGroup($getName = true)
	{
		if (!isset($this->_group))
			$this->_group = $this->_exists ? filegroup($this->_realpath) : null;

		if (is_int($this->_group) && function_exists('posix_getgrgid') && $getName == true)
		{
			$this->_group = posix_getgrgid($this->_group);
			$this->_group = $this->_group['name'];
		}

		return $this->_group;
	}

	/**
	 * Returns permissions of current filesystem object (UNIX systems). If $_permissions property is set, returned value is read from that property.
	 *
	 * @return string Filesystem object permissions in octal format (i.e. '0755')
	 */
	public function getPermissions()
	{
		if (!isset($this->_permissions))
			$this->_permissions = $this->_exists ? substr(sprintf('%o', fileperms($this->_realpath)), -4) : null;

		return $this->_permissions;
	}

	/**
	 * Returns size of current filesystem object. Returned value depends upon $format parameter value.
	 * If $_size property is set, returned value is read from that property. Uses {@link dirSize} method for directory size calculation.
	 *
	 * @param mixed $format Number format (see {@link CNumberFormatter}) or 'false'
	 * @return mixed Filesystem object size formatted (eg. '70.4 KB') or in bytes (eg. '72081') if $format set to 'false'
	 */
	public function getSize($format = '0.00')
	{
		if (!isset($this->_size))
		{
			if ($this->_isFile)
				$this->_size = $this->_exists ? sprintf("%u", filesize($this->_realpath)) : null;
			else
				$this->_size = $this->_exists ? sprintf("%u", $this->dirSize()) : null;
		}

		$size = $this->_size;

		if ($format !== false)
			$size = $this->formatFileSize($this->_size, $format);

		return $size;
	}

	/**
	 * Calculates the current directory size recursively fetching sizes of all descendant files.
	 *
	 * This method is used internally and only for folders. See {@link getSize} method params for detailed information.
	 * @return integer $size
	 */
	private function dirSize()
	{
		$size = 0;
		foreach ($this->dirContents($this->_realpath, true) as $item)
		{
			if (is_file($item))
				$size += sprintf("%u", filesize($item));
		}

		return $size;
	}

	/**
	 * Base filesystem object size format method. Converts file size in bytes into human readable format (i.e. '70.4 KB')
	 *
	 * @param integer $bytes Filesystem object size in bytes
	 * @param integer $format Number format (see {@link CNumberFormatter})
	 * @return string Filesystem object size in human readable format
	 */
	private function formatFileSize($bytes, $format)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

		$bytes = max($bytes, 0);
		$expo = floor(($bytes ? log($bytes) : 0) / log(1024));
		$expo = min($expo, count($units)-1);

		$bytes /= pow(1024, $expo);

		return Blocks::app()->numberFormatter->format($format, $bytes).' '.$units[$expo];
	}

	/**
	 * Returns the current file last modified time.
	 * Returned Unix timestamp could be passed to php date() function.
	 *
	 * @return integer Last modified time Unix timestamp (eg. '1213760802')
	 */
	public function getTimeModified()
	{
		if (empty($this->_timeModified))
			$this->_timeModified = $this->_exists ? filemtime($this->_realpath) : null;

		return $this->_timeModified;
	}

	/**
	 * Returns the current file extension from $_extension property set by {@link pathInfo} (eg. 'htm' for '/var/www/htdocs/files/myfile.htm').
	 *
	 * @return string Current file extension without the leading dot
	 */
	public function getExtension()
	{
		return $this->_extension;
	}

	/**
	 * Returns the current file basename (file name plus extension) from $_basename property set by {@link pathInfo} (eg. 'myfile.htm' for '/var/www/htdocs/files/myfile.htm').
	 *
	 * @return string Current file basename
	 */
	public function getBaseName()
	{
		return $this->_basename;
	}

	/**
	 * Returns the current file name (without extension) from $_filename property set by {@link pathInfo} (eg. 'myfile' for '/var/www/htdocs/files/myfile.htm')
	 *
	 * @return string Current file name
	 */
	public function getFileName()
	{
		return $this->_filename;
	}

	/**
	 * Returns the current file directory name (without final slash) from $_dirname property set by {@link pathInfo} (eg. '/var/www/htdocs/files' for '/var/www/htdocs/files/myfile.htm')
	 *
	 * @return string Current file directory name
	 */
	public function getDirName()
	{
		return $this->_dirname;
	}

	/**
	 * Returns the current filesystem object contents. Reads data from filesystem object if it is a regular file.
	 * List files and directories inside the specified path if filesystem object is a directory.
	 *
	 * @param boolean $recursive If 'true' method would return all directory descendants
	 * @param string $filter Filter to be applied to all directory descendants.
	 * Could be a string, or an array of strings (perl regexp supported).
	 * @return mixed The read data or 'false' on fail.
	 */
	public function getContents($recursive = false, $filter = null)
	{
		if ($this->_readable)
		{
			if ($this->_isFile)
			{
				if ($contents = file_get_contents($this->_realpath))
					return $contents;
			}
			else
			{
				if ($contents = $this->dirContents($this->_realpath, $recursive, $filter))
					return $contents;

			}
		}

		$this->addLog('Unable to get filesystem object contents for '.$this->_realpath.' '.($filter !== null?' *using supplied filter*':''), 'warning');
		return false;
	}

	/**
	 * Gets directory contents (descendant files and folders).
	 *
	 * @param bool|string $directory Initial directory to get descendants for
	 * @param boolean $recursive If 'true' method would return all descendants recursively, otherwise just immediate descendants
	 * @param string $filter Filter to be applied to all directory descendants.
	 * Could be a string, or an array of strings (perl regexp supported). See {@link filterPassed} method for further information on filters.
	 * @return array Array of descendants filepaths
	 */
	private function dirContents($directory = false, $recursive = false, $filter = null)
	{
		$descendants = array();
		if (!$directory)
			$directory = $this->_realpath;

		if ($filter !== null)
		{
			if (is_string($filter))
				$filter = array($filter);

			foreach ($filter as $key=>$rule)
			{
				if ($rule[0]!='/')
					$filter[$key] = ltrim($rule, '.');
			}
		}

		if ($contents = @scandir($directory.'/'))
		{
			foreach ($contents as $key=>$item)
			{
				$contents[$key] = $directory.'/'.$item;
				if (!in_array($item, array(".", "..")))
				{
					if ($this->filterPassed($contents[$key], $filter))
						$descendants[] = $contents[$key];

					if (is_dir($contents[$key]) && $recursive)
						$descendants = array_merge($descendants, $this->dirContents($contents[$key], $recursive, $filter));
				}
			}
		}
		else
		{
			throw new BlocksException('Unable to get directory contents for "'.$directory.'/'.'"');
		}

		return $descendants;
	}

	/**
	 * Applies an array of filter rules to the string representing filepath. Used internally by {@link dirContents} method.
	 *
	 * @param string $str String representing filepath to be filtered
	 * @param array $filter An array of filter rules, where each rule is a string, supposing that the string starting with '/' is a regular
	 * expression. Any other string reated as an extension part of the given filepath (eg. file extension)
	 * @return boolean Returns 'true' if the supplied string matched one of the filter rules.
	 */
	private function filterPassed($str, $filter)
	{
		$passed = false;

		if ($filter !== null)
		{
			foreach ($filter as $rule)
			{
				if ($rule[0]!='/')
				{
					$rule = '.'.$rule;
					$passed = (bool)substr_count($str, $rule, strlen($str) - strlen($rule));
				}
				else
					$passed = (bool)preg_match($rule, $str);

				if ($passed)
					break;
			}
		}
		else
			$passed = true;

		return $passed;
	}

	/**
	 * Writes contents (data) into the current file. This method works only for files.
	 *
	 * @param string $destination Alternative file to write to (not $this).
	 * @param string $contents Contents to be written
	 * @param boolean $autoCreate If 'true' file will be created automatically
	 * @param integer $flags Flags for file_put_contents(). E.g.: FILE_APPEND to append data to file instead of overwriting.
	 * @return mixed Current BlocksFile object on success, 'false' on fail.
	 */
	public function setContents($destination = null, $contents = null, $autoCreate = true, $flags = 0)
	{
		if ($destination != null)
		{
			$newFile = Blocks::app()->file->set($destination);

			if ($autoCreate && !$newFile->_exists)
			{
				$destDir = dirname($newFile->getRealPath());
				if (!is_dir($destDir))
					mkdir($destDir, 0754, true);

				$newFile->create();
			}

			if ($newFile->_writeable && file_put_contents($newFile->_realpath, $contents, $flags) !== false)
				return $this;

			$this->addLog('Unable to set file contents of '.$newFile->_realPath, 'warning');
			return false;

		}
		else
		{
			if ($this->_isFile)
			{
				if ($autoCreate && !$this->_exists)
					$this->create();

				if ($this->writeable && file_put_contents($this->_realpath, $contents, $flags) !== false)
					return $this;

				$this->addLog('Unable to set file contents of '.$this->_realpath, 'warning');
				return false;
			}
			else
			{
				$this->addLog(__METHOD__.' method is available only for files', 'warning');
				return false;
			}
		}
	}

	/**
	 * Sets basename for the current file. Lazy wrapper for {@link rename}. This method works only for files.
	 *
	 * @param bool|string $basename New file basename (eg. 'mynewfile.txt')
	 * @return mixed Current BlocksFile object on success, 'false' on fail.
	 */
	public function setBaseName($basename = false)
	{
		if ($this->_isFile)
		{
			if ($this->_isUploaded)
			{
				$this->addLog(__METHOD__.' method is unavailable for uploaded files. Please copy/move uploaded file from temporary directory', 'warning');
				return false;
			}

			if($this->_writeable && $basename !== false && $this->rename($basename))
				return $this;

			$this->addLog('Unable to set file basename "'.$basename.'" for file: '.$this->_realpath, 'warning');
			return false;
		}

		$this->addLog(__METHOD__.' method is available only for files.', 'warning');
		return false;
	}

	/**
	 * Sets the current file name. Lazy wrapper for {@link rename}. This method works only for files.
	 *
	 * @param bool|string $filename New file name (eg. 'mynewfile')
	 * @return mixed Current BlocksFile object on success, 'false' on fail.
	 */
	public function setFileName($filename = false)
	{
		if ($this->_isFile)
		{
			if ($this->_isUploaded)
			{
				$this->addLog(__METHOD__.' method is unavailable for uploaded files. Please copy/move uploaded file from temporary directory', 'warning');
				return false;
			}

			if ($this->_writeable && $filename!==false && $this->rename(str_replace($this->_filename, $filename, $this->_basename)))
				return $this;

			$this->addLog('Unable to set file name "'.$filename.'" for file: '.$this->_realpath, 'warning');
			return false;
		}

		$this->addLog(__METHOD__.' method is available only for files.', 'warning');
		return false;
	}

	/**
	 * Sets the current file extension. If new extension is 'null' or 'false' current file extension is dropped.
	 * Lazy wrapper for {@link rename}. This method works only for files.
	 *
	 * @param bool|string $extension New file extension (eg. 'txt')
	 * @return mixed Current BlocksFile object on success, 'false' on fail.
	 */
	public function setExtension($extension = false)
	{
		if ($this->_isFile)
		{
			if ($this->_isUploaded)
			{
				$this->addLog(__METHOD__.' method is unavailable for uploaded files. Please copy/move uploaded file from temporary directory', 'warning');
				return false;
			}

			if($this->_writeable && $extension !== false)
			{
				$extension = trim($extension);

				// drop current extension
				if (is_null($extension) || $extension == '')
				{
					$newBaseName = $this->_filename;
				}
				// apply new extension
				else
				{
					$extension = ltrim($extension, '.');

					if (is_null($this->_extension))
						$newBaseName = $this->_filename.'.'.$extension;
					else
						$newBaseName = str_replace($this->_extension, $extension, $this->_basename);
				}

				if ($this->rename($newBaseName))
					return $this;
			}

			$this->addLog('Unable to set file extension "'.$extension.'" for file: '.$this->_realpath, 'warning');
			return false;
		}

		$this->addLog(__METHOD__.' method is available only for files.', 'warning');
		return false;
	}

	/**
	 * Sets the current filesystem object owner, updates $_owner property on success. For UNIX systems.
	 *
	 * @param mixed $owner New owner name or ID
	 * @return mixed Current BlocksFile object on success, 'false' on fail.
	 */
	public function setOwner($owner)
	{
		if($this->_exists && chown($this->_realpath, $owner))
		{
			$this->_owner = $owner;
			return $this;
		}

		$this->addLog('Unable to set owner for filesystem object to "'.$owner.'" for file: '.$this->_realpath, 'warning');
		return false;
	}

	/**
	 * Sets the current filesystem object group, updates $_group property on success. For UNIX systems.
	 *
	 * @param mixed $group New group name or ID
	 * @return mixed Current BlocksFile object on success, 'false' on fail.
	 */
	public function setGroup($group)
	{
		if ($this->_exists && chgrp($this->_realpath, $group))
		{
			$this->_group = $group;
			return $this;
		}

		$this->addLog('Unable to set group for filesystem object to "'.$group.'" for file: '.$this->_realpath, 'warning');
		return false;
	}

	/**
	 * Sets the current filesystem object permissions, updates $_permissions property on success. For UNIX systems.
	 *
	 * @param string $permissions New filesystem object permissions in numeric (octal, i.e. '0755') format
	 * @return mixed Current BlocksFile object on success, 'false' on fail.
	 */
	public function setPermissions($permissions)
	{
		if ($this->_exists && is_numeric($permissions))
		{
			// '755' normalize to octal '0755'
			$permissions = octdec(str_pad($permissions, 4, "0", STR_PAD_LEFT));

			if (@chmod($this->_realpath, $permissions))
			{
				$this->_group = $permissions;
				return $this;
			}
		}

		$this->addLog('Unable to change permissions for filesystem object to "'.$permissions.'" for file: '.$this->_realpath, 'warning');
		return false;
	}

	/**
	 * Resolves destination path for the current filesystem object. This method enables short calls for {@link copy} & {@link rename} methods
	 * (i.e. copy('mynewfile.htm') makes a copy of the current filesystem object in the same directory, named 'mynewfile.htm')
	 *
	 * @param string $fileDest Destination filesystem object name (with or w/o path) submitted by user
	 * @return string Resolved real destination path for the current filesystem object
	 */
	private function resolveDestPath($fileDest)
	{
		if (strpos($fileDest, '/') === false)
			return $this->_dirname.'/'.$fileDest;

		return $this->realPath($fileDest);
	}

	/**
	 * Copies the current filesystem object to specified destination. Destination path supplied by user resolved to real destination path with {@link resolveDestPath}
	 *
	 * @param string $fileDest Destination path for the current filesystem object to be copied to
	 * @param bool $recursive If set to true, if the current filesystem object is a file, will recursively create the subdirectories needed to copy the file.
	 * @return mixed New BlocksFile object for newly created filesystem object on success, 'false' on fail.
	 */
	public function copy($fileDest, $recursive = false)
	{
		$destRealPath = $this->resolveDestPath($fileDest);

		if ($this->_isFile)
		{
			if ($recursive)
			{
				$destDir = dirname($fileDest);
				if (!is_dir($destDir))
					mkdir($destDir, 0754, true);
			}

			if ($this->_readable && @copy($this->_realpath, $destRealPath))
				return $this->set($destRealPath);
		}
		else
		{
			Blocks::trace('Copying directory "'.$this->_realpath.'" to "'.$destRealPath.'"', 'BlocksFile');

			$dirContents = $this->dirContents($this->_realpath, true);
			foreach ($dirContents as $item)
			{
				$itemDest = $destRealPath.str_replace($this->_realpath, '', $item);
				if (is_file($item))
				{
					@copy($item, $itemDest);
				}
				elseif (is_dir($item))
				{
					$this->createDir(0754, $itemDest);
				}
			}

			return $this->set($destRealPath);
		}

		$this->addLog('Unable to copy filesystem object into "'.$destRealPath.'".', 'warning');
		return false;
	}

	/**
	 * Renames/moves the current filesystem object to specified destination. Destination path supplied by user resolved to real destination path with {@link resolveDestPath}
	 *
	 * @param string $fileDest Destination path for the current filesystem object to be renamed/moved to
	 * @return mixed Updated current BlocksFile object on success, 'false' on fail.
	 */
	public function rename($fileDest)
	{
		$destRealPath = $this->resolveDestPath($fileDest);

		if ($this->_writeable && @rename($this->_realpath, $destRealPath))
		{
			$this->_filepath = $fileDest;
			$this->_realpath = $destRealPath;

			// update pathinfo properties
			$this->pathInfo();
			return $this;
		}

		$this->addLog('Unable to rename/move filesystem object into "'.$destRealPath.'"', 'warning');
		return false;
	}

	/**
	 * Alias for {@link rename}
	 * @param $fileDest
	 * @return mixed
	 */
	public function move($fileDest)
	{
		return $this->rename($fileDest);
	}

	/**
	 * Purges (makes empty) the current filesystem object. If the current filesystem object is a file its contents set to ''.
	 * If the current filesystem object is a directory all its descendants are deleted.
	 *
	 * @param bool $path
	 * @return mixed Current BlocksFile object on success, 'false' on fail.
	 */
	public function purge($path = false)
	{
		if (!$path) $path = $this->_realpath;

		if ($this->_isFile)
		{
			if ($this->_writeable)
				return $this->_contents = '';
		}
		else
		{
			Blocks::trace('Purging directory "'.$path.'"', 'BlocksFile');

			if (!realpath($path))
				return true;

			$dirContents = $this->dirContents($path, true);

			foreach ($dirContents as $item)
			{
				if (is_file($item))
				{
					@unlink($item);
				}
				elseif (is_dir($item))
				{
					$this->purge($item);
					@rmdir($item);
				}
			}

			// TODO: Still need a valid check here
			return true;
		}
	}

	/**
	 * Deletes the current filesystem object. For folders purge parameter can be supplied.
	 *
	 * @param boolean $purge If 'true' folder would be deleted with all the descendants
	 * @return boolean 'True' if sucessfully deleted, 'false' on fail
	 */
	public function delete($purge = true)
	{
		if ($this->_writeable)
		{
			if (($this->_isFile && @unlink($this->_realpath) ) || (!$this->_isFile && ($purge ? $this->purge() : true) && rmdir($this->_realpath)))
			{
				$this->_exists = $this->_readable = $this->_writeable = false;
				return true;
			}
		}

		$this->addLog('Unable to delete filesystem object: '.$this->_realpath, 'warning');
		return false;
	}

	/**
	 * Sends the current file to browser as a download with real or faked file name.
	 * Browser caching is prevented.  This method works only for files.
	 *
	 * @param bool|string $fakeName New filename (eg. 'myfileFakedName.htm')
	 * @param boolean $serverHandled Whether file contents delivery is handled by server internals (cf. when file contents is read and sent by php).
	 * E.g.: lighttpd and Apache with mod-sendfile can use X-Senfile header to speed up file delivery blazingly.
	 * Note: If you want to serve big or even huge files you are definitely advised to turn this option on and setup your server software appropriately, if not to say that it is your only alternative :).
	 * @param null $contents Alternative contents to send other than what's on the file system.
	 * @return file File download
	 */
	public function send($fakeName = false, $serverHandled = false, $contents = null)
	{
		if ($this->_isFile)
		{
			if ($this->_readable && !headers_sent())
			{
				$contentType = $this->_mimeType;

				if (!$contentType)
					$contentType = "application/octet-stream";

				if ($fakeName)
					$filename = $fakeName;
				else
					$filename = $this->_basename;

				// disable browser caching
				header('Cache-control: private');
				header('Pragma: private');
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

				header('Content-Type: '.$contentType);
				header('Content-Transfer-Encoding: binary');
				header('Content-Length: '.$this->getSize(false));
				header('Content-Disposition: attachment;filename="'.$filename.'"');

				if ($serverHandled)
				{
					header('X-Sendfile: '.$this->_realpath);
				}
				else
				{
					if ($contents == null)
					{
						$contents = $this->_contents;
					}

					echo $contents;
				}

				exit(0);
			}

			$this->addLog('Unable to prepare file for download. Headers already sent or file doesn\'t not exist: '.$this->_realpath, 'warning');
			return false;
		}
		else
		{
			$this->addLog('send() and download() methods are available only for files: '.$this->_realpath, 'warning');
			return false;
		}
	}

	/**
	 * Alias for {@link send}
	 * @param bool $fakeName
	 * @param bool $serverHandled
	 * @return \file
	 */
	function download($fakeName = false, $serverHandled = false)
	{
		return $this->send($fakeName, $serverHandled);
	}

	/**
	 * Returns the MIME type of the current file. If $_mimeType property is set, returned value is read from that property.
	 *
	 * This method will attempt the following approaches in order: 1) finfo 2) {@link getMimeTypeByExtension}
	 * This method works only for files.
	 * @return mixed the MIME type on success, 'false' on fail.
	 */
	public function getMimeType()
	{
		if ($this->_mimeType)
			return $this->_mimeType;

		if ($this->_isFile)
		{
			if ($this->_readable)
			{
				if ($this->_isUploaded)
					return $this->_mimeType = $this->_uploadedInstance->getType();

				if (function_exists('finfo_open'))
				{
					if (($info = @finfo_open(FILEINFO_MIME)) && ($result = finfo_file($info, $this->_realpath)) !== false)
						return $this->_mimeType = $result;
				}

				return $this->_mimeType = $this->getMimeTypeByExtension($this->_realpath);

			}

			$this->addLog('Unable to get mime type for file: '.$this->_realpath, 'warning');
			return false;
		}
		else
		{
			$this->addLog('getMimeType() method is available only for files: '.$this->_realpath, 'warning');
			return false;
		}
	}

	/**
	 * Determines the MIME type based on the extension of the current file. This method will use a local map between extension name and MIME type. This method works only for files.
	 *
	 * @return string the MIME type. False is returned if the MIME type cannot be determined.
	 */
	public function getMimeTypeByExtension()
	{
		if ($this->_isFile)
		{
			Blocks::trace('Trying to get MIME type for "'.$this->_realpath.'" from extension "'.$this->_extension.'"', 'BlocksFile');
			static $extensions;

			if ($extensions === null)
				$extensions = require(Blocks::getPathOfAlias('system.utils.mimeTypes').'.php');

			$ext = strtolower($this->_extension);

			if (!empty($ext) && isset($extensions[$ext]))
					return $extensions[$ext];

			return false;
		}
		else
		{
			$this->addLog(__METHOD__.' method is available only for files.', 'warning');
			return false;
		}
	}

	public function generateMD5()
	{
		if ($this->_isFile)
		{
			return md5_file($this->getRealPath());
		}
		else
		{
			$this->addLog(__METHOD__.' method is available only for files.', 'warning');
			return false;
		}
	}

	public function zipDir($srcDir)
	{
		if ($this->_exists)
		{
			$this->purge();
		}
		else
		{
			$this->create();
		}

		if (@class_exists('ZipArchive', false))
		{
			return $this->zipZipArchive($srcDir);
		}

		return $this->zipPclZip($srcDir);
	}

	private function zipPclZip($srcDir)
	{
		$zip = new PclZip($this->getRealPath());

		$result = $zip->create($srcDir, PCLZIP_OPT_REMOVE_PATH, $srcDir);

		if ($result == 0)
		{
			$this->addLog('Unable to create zip file: '.$this->_realpath, 'error');
			return false;
		}

		return true;
	}

	private function zipZipArchive($srcDir)
	{
		$zip = new ZipArchive;
		$zipContents = $zip->open($this->getRealPath(), ZipArchive::CREATE);

		if ($zipContents !== TRUE)
		{
			$this->addLog('Unable to create zip file: '.$this->_realpath, 'error');
			return false;
		}

		$srcDir = Blocks::app()->file->set($srcDir);
		$dirContents = $srcDir->getContents(true);

		foreach ($dirContents as $itemToZip)
		{
			if ((file_exists($itemToZip) || is_readable($itemToZip)) && !is_dir($itemToZip))
			{
				// We can't use $zip->addFile() here but it's a terrible, horrible method that's buggy on Windows.
				$fileContents = file_get_contents($itemToZip);
				$relFilePath = substr($itemToZip, strlen($srcDir->getRealPath()) + 1);
				if (!$zip->addFromString($relFilePath, $fileContents))
					$this->addLog('There was an error adding the file at this path to the zip: '.$itemToZip, 'error');
			}

		}

		$zip->close();
		return true;
	}

	public function unzip($destination)
	{
		if ($this->_isFile)
		{
			if ($this->getExtension() == 'zip')
			{
				@ini_set('memory_limit', '256M');

				if (@class_exists('ZipArchive', false))
				{
					$result = $this->unzipZipArchive($destination);

					if ($result === true)
					{
						return $result;
					}
					else
					{
						$this->addLog('There was an error unzipping the file: '.$this->_realpath, 'error');
					}
				}
			}
			else
			{
				$this->addLog(__METHOD__.' method is available only for zip files.', 'warning');
			}
		}
		else
		{
			$this->addLog(__METHOD__.' method is available only for files.', 'warning');
		}

		// last chance, try pclzip
		return $this->unzipPclZip($destination);
	}

	private function unzipPclZip($destination)
	{
		$zip = new PclZip($this->getRealPath());
		$destDirectories = null;

		// check to see if it's a valid archive.
		if (($zipFiles = $zip->extract(PCLZIP_OPT_EXTRACT_AS_STRING)) == false)
		{
			$this->addLog('Not a valid zip archive: '.$this->_realpath, 'error');
			return false;
		}

		if (count($zipFiles) == 0)
		{
			$this->addLog('Empty zip archive: '.$this->_realpath, 'error');
			return false;
		}

		// find out which directories we need to create in the destination.
		foreach ($zipFiles as $zipFile)
		{
			if (substr($zipFile['filename'], 0, 9) === '__MACOSX/')
				continue;

			$destDirectories[] = $destination.'/'.rtrim($zipFile['folder'] ? $zipFile['filename'] : dirname($zipFile['filename']), '/');
		}

		$destDirectories = array_unique($destDirectories);

		foreach ($destDirectories as $destDirectory)
		{
			// Skip over the working directory
			if (rtrim($destination, '/') == $destDirectory)
				continue;

			// Make sure the current directory is within the working directory
			if (strpos($destDirectory, $destination) === false)
				continue;

			$parentDirectory = dirname($destDirectory);

			while (!empty($parentDirectory) && rtrim($destination, '/') != $parentDirectory && !in_array($parentDirectory, $destDirectories))
			{
				$destDirectories[] = $parentDirectory;
				$parentDirectory = dirname($parentDirectory);
			}
		}

		asort($destDirectories);

		// Create the destination directories.
		foreach ($destDirectories as $destDirectory)
		{
			$newDir = Blocks::app()->file->set($destDirectory, false);

			if (!$newDir->createDir(0754) && !$newDir->getIsDir())
			{
				$this->addLog('Could not create directory during unzip: '.$newDir->getRealPath(), 'error');
				return false;
			}
		}

		unset($destDirectories);

		// Extract the files from the zip
		foreach ($zipFiles as $zipFile)
		{
			// folders have already been created.
			if ($zipFile['folder'])
				continue;

			if (substr($zipFile['filename'], 0, 9) === '__MACOSX/')
				continue;

			$destFile = Blocks::app()->file->set($destination.'/'.$zipFile['filename']);
			if (!$destFile->setContents($destFile->getRealPath(), $zipFile['content'], true, FILE_APPEND))
			{
				$this->addLog('Could not copy file during unzip: '.$destFile->getRealPath(), 'error');
				return false;
			}
		}

		return true;
	}

	private function unzipZipArchive($destination)
	{
		$zipArchive = new ZipArchive();

		$zipContents = $zipArchive->open($this->getRealPath(), ZipArchive::CHECKCONS);

		if ($zipContents !== true)
		{
			$this->addLog('Could not open the zip file: '.$this->getRealPath(), 'error');
			return false;
		}

		for ($i = 0; $i < $zipArchive->numFiles; $i++)
		{
			if (!$info = $zipArchive->statIndex($i))
			{
				$this->addLog('Could not retrieve file from archive.', 'error');
				return false;
			}

			// normalize directory separators
			$info = Blocks::app()->path->normalizeDirectorySeparators($info);

			// found a directory
			if (substr($info['name'], -1) === '/')
			{
				$dir = Blocks::app()->file->set($destination.'/');
				$dir->createDir(0754, $destination.'/'.$info['name']);
				continue;
			}

			 // Don't extract the OSX __MACOSX directory
			if (substr($info['name'], 0, 9) === '__MACOSX/')
				continue;

			$contents = $zipArchive->getFromIndex($i);

			if ($contents === false)
			{
				$this->addLog('Could not extract file from archive.', 'error');
				return false;
			}

			if (!$this->setContents($destination.'/'.$info['name'], $contents, true, FILE_APPEND))
			{
				$this->addLog('Could not copy file: '.$info['filename'], 'error');
				return false;
			}
		}

		$zipArchive->close();
		return true;
	}
}
