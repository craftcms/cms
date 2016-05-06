<?php
namespace Craft;

/**
 * Class IOHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class IOHelper
{
	// Public Methods
	// =========================================================================

	private static $_fileKinds;

	// Public Methods
	// =========================================================================

	/**
	 * Tests whether the given file path exists on the file system.
	 *
	 * @param string $path            The path to test.
	 * @param bool   $caseInsensitive Whether to perform a case insensitive check or not.
	 * @param bool   $suppressErrors  Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return string The resolved path of the file if it exists.
	 */
	public static function fileExists($path, $caseInsensitive = false, $suppressErrors = false)
	{
		$resolvedPath = static::getRealPath($path, $suppressErrors);

		if ($resolvedPath)
		{
			if ($suppressErrors ? @is_file($resolvedPath) : is_file($resolvedPath))
			{
				return $resolvedPath;
			}
		}
		else if ($caseInsensitive)
		{
			$folder = static::getFolderName($path, true, $suppressErrors);
			$files = static::getFolderContents($folder, false, null, false, $suppressErrors);
			$lcaseFileName = StringHelper::toLowerCase($path);

			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					$file = static::normalizePathSeparators($file);

					if ($suppressErrors ? @is_file($file) : is_file($file))
					{
						if (StringHelper::toLowerCase($file) === $lcaseFileName)
						{
							return $file;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Tests whether the given folder path exists on the file system.
	 *
	 * @param string $path            The path to test.
	 * @param bool   $caseInsensitive Whether to perform a case insensitive check or not.
	 * @param bool   $suppressErrors  Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if the folder exists, otherwise 'false'.
	 */
	public static function folderExists($path, $caseInsensitive = false, $suppressErrors = false)
	{
		$path = static::getRealPath($path, $suppressErrors);

		if ($path)
		{
			if ($suppressErrors ? @is_dir($path) : is_dir($path))
			{
				return $path;
			}

			if ($caseInsensitive)
			{
				return StringHelper::toLowerCase(static::getFolderName($path, true, $suppressErrors)) === StringHelper::toLowerCase($path);
			}
		}

		return false;
	}

	/**
	 * If the file exists on the file system will return a new File instance, otherwise, false.
	 *
	 * @param string $path           The path to the file.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return File|bool
	 */
	public static function getFile($path, $suppressErrors = false)
	{
		if (static::fileExists($path, false, $suppressErrors))
		{
			return new File($path);
		}

		return false;
	}

	/**
	 * If the folder exists on the file system, will return a new Folder instance, otherwise, false.
	 *
	 * @param string $path           The path to the folder.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return Folder|bool
	 */
	public static function getFolder($path, $suppressErrors = false)
	{
		if (static::folderExists($path, false, $suppressErrors))
		{
			return new Folder($path);
		}

		return false;
	}

	/**
	 * If the path exists on the file system, will return the paths of any folders that are contained within it.
	 *
	 * @param string $path           The folder path to check
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return array|bool
	 */
	public static function getFolders($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path, $suppressErrors);

		if (static::folderExists($path, false, $suppressErrors))
		{
			$folders = $suppressErrors ? @glob($path.'*', GLOB_ONLYDIR) : glob($path.'*', GLOB_ONLYDIR);

			if ($folders)
			{
				foreach ($folders as $key => $folder)
				{
					$folders[$key] = static::normalizePathSeparators($folder, $suppressErrors);
				}

				return $folders;
			}
		}

		return false;
	}

	/**
	 * If the path exists on the file system, will return the paths of any files that are contained within it.
	 *
	 * @param string $path           The folder path to check
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return array|bool
	 */
	public static function getFiles($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path, $suppressErrors);

		if (static::folderExists($path, false, $suppressErrors))
		{
			return $suppressErrors ? @glob($path.'*.*') : glob($path.'*');
		}

		return false;
	}

	/**
	 * Returns the real filesystem path of the given path.
	 *
	 * @param string $path           The path to test.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return string|false The real file or folder path, or `false `if the file doesn’t exist.
	 */
	public static function getRealPath($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);
		$path = $suppressErrors ? @realpath($path) : realpath($path);

		// realpath() should just return false if the file doesn't exist, but seeing one case where
		// it's returning an empty string instead
		if (!$path)
		{
			return false;
		}

		if ($suppressErrors ? @is_dir($path) : is_dir($path))
		{
			$path = $path.'/';
		}

		// Normalize again, because realpath probably screwed things up again.
		return static::normalizePathSeparators($path);
	}

	/**
	 * Tests whether the give filesystem path is readable.
	 *
	 * @param string $path           The path to test.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if filesystem path is readable, otherwise 'false'.
	 */
	public static function isReadable($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);
		return $suppressErrors ? @is_readable($path) : is_readable($path);
	}

	/**
	 * Tests file and folder write-ability by attempting to create a temp file on the filesystem. PHP's is_writable has
	 * problems (especially on Windows). {@see https://bugs.php.net/bug.php?id=27609} and
	 * {@see https://bugs.php.net/bug.php?id=30931}.
	 *
	 * @param string $path           The path to test.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if filesystem object is writable, otherwise 'false'.
	 */
	public static function isWritable($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path, false, $suppressErrors))
		{
			return static::isWritable($path.uniqid(mt_rand()).'.tmp', $suppressErrors);
		}

		// Check tmp file for read/write capabilities
		$rm = static::fileExists($path, false, $suppressErrors);
		$f = @fopen($path, 'a');

		if ($f === false)
		{
			return false;
		}

		@fclose($f);

		if (!$rm)
		{
			@unlink($path);
		}

		return true;
	}

	/**
	 * Will return the file name of the given path with or without the extension.
	 *
	 * @param string $path             The path to test.
	 * @param bool   $includeExtension Whether to include the extension in the file name.
	 * @param bool   $suppressErrors   Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return string The file name with or without the extension.
	 */
	public static function getFileName($path, $includeExtension = true, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if ($includeExtension)
		{
			return $suppressErrors ? @pathinfo($path, PATHINFO_BASENAME) : pathinfo($path, PATHINFO_BASENAME);
		}
		else
		{
			return $suppressErrors ? @pathinfo($path, PATHINFO_FILENAME) : pathinfo($path, PATHINFO_FILENAME);
		}
	}

	/**
	 * Will return the folder name of the given path either as the full path or
	 * only the single top level folder.
	 *
	 * @param string $path           The path to test.
	 * @param bool   $fullPath       Whether to include the full path in the return results or the top level folder only.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return string The folder name.
	 */
	public static function getFolderName($path, $fullPath = true, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if ($fullPath)
		{
			$folder = static::normalizePathSeparators($suppressErrors ? @pathinfo($path, PATHINFO_DIRNAME) : pathinfo($path, PATHINFO_DIRNAME));

			// normalizePathSeparators() only enforces the trailing slash for known directories so let's be sure
			// that it'll be there.
			return rtrim($folder, '/').'/';
		}
		else
		{
			if ($suppressErrors ? !@is_dir($path) : !is_dir($path))
			{
				// Chop off the file
				$path = $suppressErrors ? @pathinfo($path, PATHINFO_DIRNAME) : pathinfo($path, PATHINFO_DIRNAME);
			}

			return $suppressErrors ? @pathinfo($path, PATHINFO_BASENAME) : pathinfo($path, PATHINFO_BASENAME);
		}
	}

	/**
	 * Returns the file extension for the given path.  If there is not one, then $default is returned instead.
	 *
	 * @param string      $path           The path to test.
	 * @param null|string $default        If the file has no extension, this one will be returned by default.
	 * @param bool        $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return string The file extension.
	 */
	public static function getExtension($path, $default = null, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);
		$extension = $suppressErrors ? @pathinfo($path, PATHINFO_EXTENSION) : pathinfo($path, PATHINFO_EXTENSION);

		if ($extension)
		{
			return $extension;
		}
		else
		{
			return $default;
		}
	}

	/**
	 * If the path points to a real file, we call {@link FileHelper::getMimeType()}, otherwise
	 * {@link FileHelper::getMimeTypeByExtension()}
	 *
	 * @param string $path The path to test.
	 *
	 * @return string The mime type.
	 */
	public static function getMimeType($path)
	{
		if (@file_exists($path))
		{
			return FileHelper::getMimeType($path);
		}
		else
		{
			return FileHelper::getMimeTypeByExtension($path);
		}
	}

	/**
	 * A wrapper for {@link FileHelper::getMimeTypeByExtension()}.
	 *
	 * @param  string $path The path to test.
	 *
	 * @return string       The mime type.
	 */
	public static function getMimeTypeByExtension($path)
	{
		return FileHelper::getMimeTypeByExtension($path);
	}

	/**
	 * Returns the last modified time for the given path in DateTime format or false if the file or folder does not
	 * exist.
	 *
	 * @param string $path           The path to test.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return int|bool The last modified timestamp or false if the file or folder does not exist.
	 */
	public static function getLastTimeModified($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors) || static::folderExists($path, false, $suppressErrors))
		{
			$timeStamp = $suppressErrors ? @filemtime($path) : filemtime($path);
			return new DateTime('@'.$timeStamp);
		}

		return false;
	}

	/**
	 * Returns the file size in bytes for the given path or false if the file does not exist.
	 *
	 * @param string $path           The path to test.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool|string The file size in bytes or false if the file does not exist.
	 */
	public static function getFileSize($path, $suppressErrors = false)
	{
		clearstatcache();

		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors))
		{
			return sprintf("%u", $suppressErrors ? @filesize($path) : filesize($path));
		}

		return false;
	}

	/**
	 * Returns the folder size in bytes for the given path or false if the folder does not exist.
	 *
	 * @param string $path           The path to test.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool|string The folder size in bytes or false if the folder does not exist.
	 */
	public static function getFolderSize($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path, false, $suppressErrors))
		{
			return sprintf("%u", static::_folderSize($path, $suppressErrors));
		}

		return false;
	}

	/**
	 * Will take a given path and normalize it to use single forward slashes for path separators.  If it is a folder, it
	 * will append a trailing forward slash to the end of the path.
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string The normalized path.
	 */
	public static function normalizePathSeparators($path)
	{
		// Special case for normalizing UNC network share paths.
		if (isset($path[0]) && isset($path[1]))
		{
			if (($path[0] == '\\' && $path[1] == '\\') ||($path[0] == '/' && $path[1] == '/') )
			{
				$path = mb_substr($path, 2);
				$path = str_replace('\\', '/', $path);

				// Add the share back in
				$path = '\\\\'.$path;
			}
		}
		else
		{
			$path = str_replace('\\', '/', $path);
		}

		$path = str_replace('//', '/', $path);

		// Check if the path is just a slash.  If the server has openbase_dir restrictions in place calling is_dir on it
		// will complain.
		if ($path !== '/')
		{
			// Use is_dir here to prevent an endless recursive loop.
			// Always suppress errors here because of openbase_dir, too.
			if (@is_dir($path))
			{
				$path = rtrim($path, '/').'/';
			}
		}

		return $path;
	}

	/**
	 * Will take a path, make sure the file exists and if the size of the file is 0 bytes, return true.  Otherwise false.
	 *
	 * @param string $path         The path to test.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool Whether the file is empty or not.
	 */
	public static function isFileEmpty($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if ((static::fileExists($path, false, $suppressErrors) && static::getFileSize($path, $suppressErrors) == 0))
		{
			return true;
		}

		return false;
	}

	/**
	 * Will take a path, make sure the folder exists and if the size of the folder is 0 bytes, return true.
	 * Otherwise false.
	 *
	 * @param string $path           The path to test.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool Whether the folder is empty or not.
	 */
	public static function isFolderEmpty($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if ((static::folderExists($path, false, $suppressErrors) && static::getFolderSize($path, $suppressErrors) == 0))
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns owner of current filesystem object (UNIX systems). Returned value depends upon $getName parameter value.
	 *
	 * @param string $path           The path to check.
	 * @param bool   $getName        Defaults to 'true', meaning that owner name instead of ID should be returned.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return mixed Owner name, or ID if $getName set to 'false' or false if the file or folder does not exist.
	 */
	public static function getOwner($path, $getName = true, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors) || static::folderExists($path, false, $suppressErrors))
		{
			$owner = $suppressErrors ? @fileowner($path) : fileowner($path);
		}
		else
		{
			$owner =  false;
		}

		if (is_int($owner) && function_exists('posix_getpwuid') && $getName == true)
		{
			$owner = posix_getpwuid($owner);
			$owner = $owner['name'];
		}

		return $owner;
	}

	/**
	 * Returns group of current filesystem object (UNIX systems). Returned value
	 * depends upon $getName parameter value.
	 *
	 * @param string $path           The path to check.
	 * @param bool   $getName        Defaults to 'true', meaning that group name instead of ID should be returned.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return mixed Group name, or ID if $getName set to 'false' or false if the file or folder does not exist.
	 */
	public static function getGroup($path, $getName = true, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors) || static::folderExists($path, false, $suppressErrors))
		{
			$group = $suppressErrors ? @filegroup($path) : filegroup($path);
		}
		else
		{
			$group =  false;
		}

		if (is_int($group) && function_exists('posix_getgrgid') && $getName == true)
		{
			$group = posix_getgrgid($group);
			$group = $group['name'];
		}

		return $group;
	}

	/**
	 * Returns permissions of current filesystem object (UNIX systems).
	 *
	 * @param string $path           The path to check
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return string Filesystem object permissions in octal format (i.e. '0755'), false if the file or folder doesn't
	 *                exist
	 */
	public static function getPermissions($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors) || static::folderExists($path, false, $suppressErrors))
		{
			return mb_substr(sprintf('%o', $suppressErrors ? @fileperms($path) : fileperms($path)), -4);
		}

		return false;
	}

	/**
	 * Returns the contents of a folder as an array of file and folder paths, or false if the folder does not exist or
	 * is not readable.
	 *
	 * @param string $path               The path to test.
	 * @param bool   $recursive          Whether to do a recursive folder search.
	 * @param bool   $filter             The filter to use when performing the search.
	 * @param bool   $includeHiddenFiles Whether to include hidden files (that start with a .) in the results.
	 * @param bool   $suppressErrors     Whether to suppress any PHP Notices/Warnings/Errors (usually permissions
	 *                                   related).
	 *
	 * @return array|bool An array of file and folder paths, or false if the folder does not exist or is not readable.
	 */
	public static function getFolderContents($path, $recursive = true, $filter = null, $includeHiddenFiles = false, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path, false, $suppressErrors) && static::isReadable($path, $suppressErrors))
		{
			if (($contents = static::_folderContents($path, $recursive, $filter, $includeHiddenFiles, $suppressErrors)) !== false)
			{
				return $contents;
			}

			Craft::log('Tried to read the file contents at '.$path.' and could not.');
			return false;
		}

		return false;
	}

	/**
	 * Will return the contents of the file as a string or an array if it exists and is readable, otherwise false.
	 *
	 * @param string $path           The path of the file.
	 * @param bool   $array          Whether to return the contents of the file as an array or not.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool|string|array The contents of the file as a string, an array, or false if the file does not exist or
	 *                           is not readable.
	 */
	public static function getFileContents($path, $array = false, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors) && static::isReadable($path, $suppressErrors))
		{
			if ($array)
			{
				if (($contents = $suppressErrors ? @file($path) : file($path)) !== false)
				{
					return $contents;
				}
			}
			else
			{
				if (($contents = $suppressErrors ? @file_get_contents($path) : file_get_contents($path)) !== false)
				{
					return $contents;
				}
			}

			Craft::log('Tried to read the file contents at '.$path.' and could not.', LogLevel::Error);
			return false;
		}

		Craft::log('Tried to read the file contents at '.$path.', but either the file does not exist or is it not readable.', LogLevel::Error);
		return false;
	}

	/**
	 * Will create a file on the file system at the given path and return a {@link File} object or false if we don't
	 * have write permissions.
	 *
	 * @param string $path           The path of the file to create.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return File|bool The newly created file as a {@link File} object or false if we don't have write permissions.
	 */
	public static function createFile($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (!static::fileExists($path, false, $suppressErrors))
		{
			if (($handle = $suppressErrors ? @fopen($path, 'w') : fopen($path, 'w')) === false)
			{
				Craft::log('Tried to create a file at '.$path.', but could not.', LogLevel::Error);
				return false;
			}

			@fclose($handle);
			return new File($path);
		}

		return false;
	}

	/**
	 * Will create a folder on the file system at the given path and return a {@link Folder} object or false if we don't
	 * have write permissions.
	 *
	 * @param string $path           The path of the file to create.
	 * @param int    $permissions    The permissions to set the folder to.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return Folder|bool The newly created folder as a {@link Folder} object or false if we don't have write
	 *                     permissions.
	 */
	public static function createFolder($path, $permissions = null, $suppressErrors = false)
	{
		if ($permissions == null)
		{
			$permissions = craft()->config->get('defaultFolderPermissions');
		}

		$path = static::normalizePathSeparators($path);

		if (!static::folderExists($path, false, $suppressErrors))
		{
			$oldumask = $suppressErrors ? @umask(0) : umask(0);

			if ($suppressErrors ? !@mkdir($path, $permissions, true) : !mkdir($path, $permissions, true))
			{
				Craft::log('Tried to create a folder at '.$path.', but could not.', LogLevel::Error);
				return false;
			}

			// Because setting permission with mkdir is a crapshoot.
			$suppressErrors ? @chmod($path, $permissions) : chmod($path, $permissions);
			$suppressErrors ? @umask($oldumask) : umask($oldumask);
			return new Folder($path);
		}

		Craft::log('Tried to create a folder at '.$path.', but the folder already exists.', LogLevel::Error);
		return false;
	}

	/**
	 * Will write $contents to a file.
	 *
	 * @param string    $path           The path of the file to write to.
	 * @param string    $contents       The contents to be written to the file.
	 * @param bool      $autoCreate     Whether or not to auto-create the file if it does not exist.
	 * @param bool      $append         If true, will append the data to the contents of the file, otherwise it will
	 *                                  overwrite the contents.
	 * @param bool|null $noFileLock     Whether to use file locking when writing to the file.
	 * @param bool      $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' upon successful writing to the file, otherwise false.
	 */
	public static function writeToFile($path, $contents, $autoCreate = true, $append = false, $noFileLock = null, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (!static::fileExists($path, false, $suppressErrors) && $autoCreate)
		{
			$folderName = static::getFolderName($path, true, $suppressErrors);

			if (!static::folderExists($folderName, false, $suppressErrors))
			{
				if (!static::createFolder($folderName, $suppressErrors))
				{
					return false;
				}
			}

			if ((!static::createFile($path, $suppressErrors)) !== false)
			{
				return false;
			}
		}

		if (static::isWritable($path, $suppressErrors))
		{
			// Let's try to use our auto-magic detection.
			if (craft()->config->get('useWriteFileLock') === 'auto')
			{
				// We haven't cached file lock information yet and this is not a noFileLock request.
				if (($useFileLock = craft()->cache->get('useWriteFileLock')) === false && !$noFileLock)
				{
					// For file systems that don't support file locking... LOOKING AT YOU NFS!!!
					set_error_handler(array(new IOHelper(), 'handleError'));

					try
					{
						Craft::log('Trying to write to file at '.$path.' using LOCK_EX.', LogLevel::Info);
						if (static::_writeToFile($path, $contents, true, $append, $suppressErrors))
						{
							// Restore quickly.
							restore_error_handler();

							// Cache the file lock info to use LOCK_EX for 2 months.
							Craft::log('Successfully wrote to file at '.$path.' using LOCK_EX. Saving in cache.', LogLevel::Info);
							craft()->cache->set('useWriteFileLock', 'yes', 5184000);
							return true;
						}
						else
						{
							// Try again without the lock flag.
							Craft::log('Trying to write to file at '.$path.' without LOCK_EX.', LogLevel::Info);
							if (static::_writeToFile($path, $contents, false, $append, $suppressErrors))
							{
								// Cache the file lock info to not use LOCK_EX for 2 months.
								Craft::log('Successfully wrote to file at '.$path.' without LOCK_EX. Saving in cache.', LogLevel::Info);
								craft()->cache->set('useWriteFileLock', 'no', 5184000);
								return true;
							}
						}
					}
					catch (ErrorException $e)
					{
						// Restore here before we attempt to write again.
						restore_error_handler();

						// Try again without the lock flag.
						Craft::log('Trying to write to file at '.$path.' without LOCK_EX.', LogLevel::Info);
						if (static::_writeToFile($path, $contents, false, $append, $suppressErrors))
						{
							// Cache the file lock info to not use LOCK_EX for 2 months.
							Craft::log('Successfully wrote to file at '.$path.' without LOCK_EX. Saving in cache.', LogLevel::Info);
							craft()->cache->set('useWriteFileLock', 'no', 5184000);
							return true;
						}
					}

					// Make sure we're really restored
					restore_error_handler();
				}
				else
				{
					// If cache says use LOCK_X and this is not a noFileLock request.
					if ($useFileLock == 'yes' && !$noFileLock)
					{
						// Write with LOCK_EX
						if (static::_writeToFile($path, $contents, true, $append, $suppressErrors))
						{
							return true;
						}
					}
					else
					{
						// Write without LOCK_EX
						if (static::_writeToFile($path, $contents, false, $append, $suppressErrors))
						{
							return true;
						}
						else
						{
							Craft::log('Tried to write to file at '.$path.' and could not.', LogLevel::Error);
							return false;
						}
					}

				}
			}
			// We were explicitly told not to use LOCK_EX
			else if (craft()->config->get('useWriteFileLock') === false)
			{
				if (static::_writeToFile($path, $contents, false, $append, $suppressErrors))
				{
					return true;
				}
				else
				{
					Craft::log('Tried to write to file at '.$path.' with no LOCK_EX and could not.', LogLevel::Error);
					return false;
				}
			}
			// Not 'auto', not false, so default to using LOCK_EX
			else
			{
				if (static::_writeToFile($path, $contents, true, $append, $suppressErrors))
				{
					return true;
				}
				else
				{
					Craft::log('Tried to write to file at '.$path.' with LOCK_EX and could not.', LogLevel::Error);
					return false;
				}
			}
		}
		else
		{
			Craft::log('Tried to write to file at '.$path.', but the file is not writable.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Will attempt to change the owner of the given file system path (*nix only)
	 *
	 * @param string $path           The path to change the owner of.
	 * @param string $owner          The new owner's name.
	 * @param bool   $recursive      If the path is a folder, whether to change the owner of all of the folder's children.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if successful, 'false' if not or the given path does
	 *              not exist.
	 */
	public static function changeOwner($path, $owner, $recursive = false, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (posix_getpwnam($owner) == false xor (is_numeric($owner) && posix_getpwuid($owner)== false))
		{
			Craft::log('Tried to change the owner of '.$path.', but the owner name "'.$owner.'" does not exist.', LogLevel::Error);
			return false;
		}

		if (static::fileExists($path, false, $suppressErrors) || static::folderExists($path, false, $suppressErrors))
		{
			$success = $suppressErrors ? @chown($path, $owner) : chown($path, $owner);

			if ($success && static::folderExists($path, false, $suppressErrors) && $recursive)
			{
				$contents = static::getFolderContents($path, true, null, false, $suppressErrors);

				foreach ($contents as $path)
				{
					$path = static::normalizePathSeparators($path);

					if ($suppressErrors ? !@chown($path, $owner) : chown($path, $owner))
					{
						$success = false;
					}
				}
			}

			if (!$success)
			{
				Craft::log('Tried to change the own of '.$path.', but could not.', LogLevel::Error);
				return false;
			}

			return true;
		}
		else
		{
			Craft::log('Tried to change owner of '.$path.', but that path does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Will attempt to change the group of the given file system path (*nix only)
	 *
	 * @param string $path           The path to change the group of.
	 * @param string $group          The new group name.
	 * @param bool   $recursive      If the path is a directory, whether to recursively change the group of the child
	 *                               files and folders.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if successful, 'false' if not, or the given path does not exist.
	 */
	public static function changeGroup($path, $group, $recursive = false, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (posix_getgrnam($group) == false xor (is_numeric($group) && posix_getgrgid($group) == false))
		{
			Craft::log('Tried to change the group of '.$path.', but the group name "'.$group.'" does not exist.', LogLevel::Error);
			return false;
		}

		if (static::fileExists($path, false, $suppressErrors) || static::folderExists($path, false, $suppressErrors))
		{
			$success = $suppressErrors ? @chgrp($path, $group) : chgrp($path, $group);

			if ($success && static::folderExists($path, false, $suppressErrors) && $recursive)
			{
				$contents = static::getFolderContents($path, true, null, false, $suppressErrors);

				foreach ($contents as $path)
				{
					$path = static::normalizePathSeparators($path);

					if ($suppressErrors ? !@chgrp($path, $group) : chgrp($path, $group))
					{
						$success = false;
					}
				}
			}

			if (!$success)
			{
				Craft::log('Tried to change the group of '.$path.', but could not.', LogLevel::Error);
				return false;
			}

			return true;
		}
		else
		{
			Craft::log('Tried to change group of '.$path.', but that path does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Will attempt to change the permission of the given file system path (*nix only).
	 *
	 * @param string $path           The path to change the permissions of.
	 * @param int    $permissions    The new permissions.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if successful, 'false' if not or the path does not exist.
	 */
	public static function changePermissions($path, $permissions, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors) || static::folderExists($path, false, $suppressErrors))
		{
			if ($suppressErrors ? @chmod($path, $permissions) : chmod($path, $permissions))
			{
				return true;
			}

			Craft::log('Tried to change the permissions of '.$path.', but could not.', LogLevel::Error);
		}
		else
		{
			Craft::log('Tried to change permissions of '.$path.', but that path does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Will copy a file from one path to another and create folders if necessary.
	 *
	 * @param string $path           The source path of the file.
	 * @param string $destination    The destination path to copy the file to.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if the copy was successful, 'false' if it was not, the source file is not readable or does
	 *              not exist.
	 */
	public static function copyFile($path, $destination, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors))
		{
			$destFolder = static::getFolderName($destination, true, $suppressErrors);

			if (!static::folderExists($destFolder, false, $suppressErrors))
			{
				static::createFolder($destFolder, craft()->config->get('defaultFolderPermissions'), $suppressErrors);
			}

			if (static::isReadable($path, $suppressErrors))
			{
				if ($suppressErrors ? @copy($path, $destination) : copy($path, $destination))
				{
					return true;
				}

				Craft::log('Tried to copy '.$path.' to '.$destination.', but could not.', LogLevel::Error);
			}
			else
			{
				Craft::log('Tried to copy '.$path.' to '.$destination.', but could not read the source file.', LogLevel::Error);
			}
		}
		else
		{
			Craft::log('Tried to copy '.$path.' to '.$destination.', but the source file does not exist.', LogLevel::Error);
		}

		return false;

	}

	/**
	 * Will copy the contents of one folder to another.
	 *
	 * @param string $path           The source path to copy.
	 * @param string $destination    The destination path to copy to.
	 * @param bool   $validate       Whether to compare the size of the folders after the copy is complete.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if the copy was successful, 'false' if it was not, or $validate is true and the size of the
	 *              folders do not match after the copy.
	 */
	public static function copyFolder($path, $destination, $validate = false, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path, false, $suppressErrors))
		{
			$folderContents = static::getFolderContents($path, true, null, true, $suppressErrors);

			foreach ($folderContents as $item)
			{
				$itemDest = $destination.str_replace($path, '', $item);

				$destFolder = static::getFolderName($itemDest, true, $suppressErrors);

				if (!static::folderExists($destFolder, false, $suppressErrors))
				{
					static::createFolder($destFolder, craft()->config->get('defaultFolderPermissions'), $suppressErrors);
				}

				if (static::fileExists($item, false, $suppressErrors))
				{
					if ($suppressErrors ? @copy($item, $itemDest) : copy($item, $itemDest))
					{
						Craft::log('Could not copy file from '.$item.' to '.$itemDest.'.', LogLevel::Error);
					}
				}
				elseif (static::folderExists($item, false, $suppressErrors))
				{
					if (!static::createFolder($itemDest, $suppressErrors))
					{
						Craft::log('Could not create destination folder '.$itemDest, LogLevel::Error);
					}
				}
			}

			if ($validate)
			{
				if (static::getFolderSize($path, $suppressErrors) !== static::getFolderSize($destination, $suppressErrors))
				{
					return false;
				}
			}

			return true;
		}
		else
		{
			Craft::log('Cannot copy folder '.$path.' to '.$destination.' because the source path does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Renames a given file or folder to a new name.
	 *
	 * @param string $path           The original path of the file or folder.
	 * @param string $newName        The new name of the file or folder.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if successful, 'false' if not or the source file or folder does not exist.
	 */
	public static function rename($path, $newName, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors) || static::folderExists($path, false, $suppressErrors))
		{
			// If we're renaming a file and there is no extension on the new name, default to the old extension
			if (static::fileExists($path, false, $suppressErrors) && !static::getExtension($newName, null, $suppressErrors))
			{
				$newName .= '.'.static::getExtension($path, null, $suppressErrors);
			}

			if (static::isWritable($path, $suppressErrors))
			{
				if ($suppressErrors ? @rename($path, $newName) : rename($path, $newName))
				{
					return true;
				}
				else
				{
					Craft::log('Could not rename '.$path.' to '.$newName.'.', LogLevel::Error);
				}
			}
			else
			{
				Craft::log('Could not rename '.$path.' to '.$newName.' because the source file or folder is not writable.', LogLevel::Error);
			}
		}
		else
		{
			Craft::log('Could not rename '.$path.' to '.$newName.' because the source file or folder does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Moves a file from one location on disk to another.
	 *
	 * @param string $path           The original path of the file/folder to move.
	 * @param string $newPath        The new path the file/folder should be moved to.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if the file was successfully moved, 'false', otherwise.
	 */
	public static function move($path, $newPath, $suppressErrors = false)
	{
		return static::rename($path, $newPath, $suppressErrors);
	}

	/**
	 * Purges the contents of a file.
	 *
	 * @param string $path           The path of the file to clear.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if the file was successfully cleared, 'false' if it wasn't, if the file is not writable or the file does not exist.
	 */
	public static function clearFile($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors))
		{
			if (static::isWritable($path, $suppressErrors))
			{
				static::writeToFile($path, '', false, $suppressErrors);
				return true;
			}
			else
			{
				Craft::log('Could not clear the contents of '.$path.' because the source file is not writable.', LogLevel::Error);
			}
		}
		else
		{
			Craft::log('Could not clear the contents of '.$path.' because the source file does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Purges the contents of a folder while leaving the folder itself.
	 *
	 * @param string $path           The path of the folder to clear.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if is successfully purges the folder, 'false' if the folder does not exist.
	 */
	public static function clearFolder($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path, false, $suppressErrors))
		{
			$folderContents = static::getFolderContents($path, true, null, true, $suppressErrors);

			if ($folderContents)
			{
				foreach ($folderContents as $item)
				{
					$item = static::normalizePathSeparators($item);

					if (static::fileExists($item, false, $suppressErrors))
					{
						static::deleteFile($item, $suppressErrors);
					}
					elseif (static::folderExists($item, false, $suppressErrors))
					{
						static::deleteFolder($item, $suppressErrors);
					}
				}

				return true;
			}
			else
			{
				Craft::log('Tried to read the folder contents of '.$path.', but could not.', LogLevel::Error);
			}
		}
		else
		{
			Craft::log('Could not clear the contents of '.$path.' because the source folder does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Deletes a file from the file system.
	 *
	 * @param string $path           The path of the file to delete.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if successful, 'false' if it cannot be deleted, it does not exist or it is not writable.
	 */
	public static function deleteFile($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors))
		{
			if (static::isWritable($path, $suppressErrors))
			{
				if ($suppressErrors ? @unlink($path) : unlink($path))
				{
					return true;
				}
				else
				{
					Craft::log('Could not delete the file '.$path.'.', LogLevel::Error);
				}
			}
			else
			{
				Craft::log('Could not delete the file '.$path.' because it is not writable.', LogLevel::Error);
			}
		}
		else
		{
			Craft::log('Could not delete the file '.$path.' because the file does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Deletes a folder from the file system.
	 *
	 * @param string $path           The path of the folder to delete.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool 'true' if successful, 'false' if it cannot be deleted, it does not exist or it is not writable.
	 */
	public static function deleteFolder($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path, false, $suppressErrors))
		{
			if (static::isWritable($path, $suppressErrors))
			{
				// Empty the folder contents first.
				static::clearFolder($path, $suppressErrors);

				// Delete the folder.
				if ($suppressErrors ? @rmdir($path) : rmdir($path))
				{
					return true;
				}
				else
				{
					Craft::log('Could not delete the folder '.$path.'.', LogLevel::Error);
				}
			}
			else
			{
				Craft::log('Could not delete the folder '.$path.' because it is not writable.', LogLevel::Error);
			}
		}
		else
		{
			Craft::log('Could not delete the folder '.$path.' because the folder does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Calculates the MD5 hash for a given file path or false if one could not be calculated or the file does not exist.
	 *
	 * @param string $path           The path of the file to calculate.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool|string The MD5 hash or false if it does not exist, isn't readable or could not be calculated.
	 */
	public static function getFileMD5($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path, false, $suppressErrors) && static::isReadable($path, $suppressErrors))
		{
			return $suppressErrors ? @md5_file($path) : md5_file($path);
		}
		else
		{
			Craft::log('Could not calculate the MD5 for the file '.$path.' because the file does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Get a list of allowed file extensions.
	 *
	 * @return array
	 */
	public static function getAllowedFileExtensions()
	{
		$allowedFileExtensions = ArrayHelper::stringToArray(craft()->config->get('allowedFileExtensions'));

		if (($extraExtensions = craft()->config->get('extraAllowedFileExtensions')) !== '')
		{
			$extraExtensions = ArrayHelper::stringToArray($extraExtensions);
			$allowedFileExtensions = array_merge($allowedFileExtensions, $extraExtensions);
		}

		return  $allowedFileExtensions;
	}

	/**
	 * Returns whether the extension is allowed.
	 *
	 * @param $extension
	 *
	 * @return bool
	 */
	public static function isExtensionAllowed($extension)
	{
		static $extensions = null;

		if (is_null($extensions))
		{
			$extensions = array_map('mb_strtolower', static::getAllowedFileExtensions());
		}

		return in_array(mb_strtolower($extension), $extensions);
	}

	/**
	 * Returns a list of file kinds.
	 *
	 * @return array
	 */
	public static function getFileKinds()
	{
		self::_buildFileKinds();

		return self::$_fileKinds;
	}

	/**
	 * Returns the label of a given file kind.
	 *
	 * @param string $kind
	 *
	 * @return array
	 */
	public static function getFileKindLabel($kind)
	{
		self::_buildFileKinds();

		if (isset(self::$_fileKinds[$kind]['label']))
		{
			return self::$_fileKinds[$kind]['label'];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Return a file's kind by extension.
	 *
	 * @param string $extension
	 *
	 * @return int|string
	 */
	public static function getFileKind($extension)
	{
		$extension = StringHelper::toLowerCase($extension);
		$fileKinds = static::getFileKinds();

		foreach ($fileKinds as $kind => $info)
		{
			if (in_array($extension, $info['extensions']))
			{
				return $kind;
			}
		}

		return 'unknown';
	}

	/**
	 * Makes sure a folder exists. If it does not - creates one with write permissions
	 *
	 * @param string $folderPath     The path to the folder.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return null
	 */
	public static function ensureFolderExists($folderPath, $suppressErrors = false)
	{
		if (!IOHelper::folderExists($folderPath, false, $suppressErrors))
		{
			IOHelper::createFolder($folderPath, craft()->config->get('defaultFolderPermissions'), $suppressErrors);
		}
	}

	/**
	 * Cleans a filename.
	 *
	 * @param string $fileName  The filename to clean.
	 * @param bool   $onlyAscii Whether to only allow ASCII characters in the filename.
	 * @param string $separator The separator to use for any whitespace. Defaults to '-'.
	 *
	 * @return string The cleansed filename.
	 */
	public static function cleanFilename($fileName, $onlyAscii = false, $separator = '-')
	{
		$disallowedChars = array('â€”', 'â€“', '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8211;', '&#8212;', '+', '%', '^', '~', '?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', '\'', '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}');

		// Replace any control characters in the name with a space.
		$fileName = preg_replace( "#\x{00a0}#siu", ' ', $fileName );

		// Strip any characters not allowed.
		$fileName = str_replace($disallowedChars, '', strip_tags($fileName));

		if (!is_null($separator))
		{
			$fileName = preg_replace('/(\s|'.preg_quote($separator).')+/u', $separator, $fileName);
		}

		// Nuke any trailing or leading .-_
		$fileName = trim($fileName, '.-_');

		$fileName = ($onlyAscii) ? StringHelper::asciiString($fileName) : $fileName;

		return $fileName;
	}

	/**
	 * Cleans a path.
	 *
	 * @param string $path      The path to clean.
	 * @param bool   $onlyAscii Whether to only allow ASCII characters in the path.
	 * @param string $separator The separator to use for any whitespace. Defaults to '-'.
	 *
	 * @return string The cleansed path.
	 */
	public static function cleanPath($path, $onlyAscii = false, $separator = '-')
	{
		$segments = explode('/', $path);

		foreach ($segments as &$segment)
		{
			$segment = self::cleanFilename($segment, $onlyAscii, $separator);
		}

		return implode('/', $segments);
	}

	/**
	 * Will set the access and modification times of the given file to the given
	 * time, or the current time if it is not supplied.
	 *
	 * @param string $fileName       The path to the file/folder to touch.
	 * @param null   $time           The time to set on the file/folder. If none
	 *                               is provided, will default to the current time.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool
	 */
	public static function touch($fileName, $time = null, $suppressErrors = false)
	{
		if (!$time)
		{
			$time = time();
		}

		if ($suppressErrors ? @touch($fileName, $time) : touch($fileName, $time))
		{
			return true;
		}

		return false;
	}

	/**
	 * Gets the default folder permissions from the config service.
	 *
	 * @deprecated Deprecated in 2.2. Use
	 *             {@link ConfigService::get() `craft()->config->get('defaultFolderPermissions')`} instead.
	 * @return mixed
	 */
	public static function getDefaultFolderPermissions()
	{
		return craft()->config->get('defaultFolderPermissions');
	}

	/**
	 * Gets the writable file permissions from the config service.
	 *
	 * @deprecated Deprecated in 2.2. Use
	 *             {@link ConfigService::get() `craft()->config->get('defaultFilePermissions')`} instead.
	 * @return mixed
	 */
	public static function getWritableFilePermissions()
	{
		return craft()->config->get('defaultFilePermissions');
	}

	/**
	 * Gets the writable folder permissions from the config service.
	 *
	 * @deprecated Deprecated in 2.2. Use
	 *             {@link ConfigService::get() `craft()->config->get('defaultFolderPermissions')`} instead.
	 * @return mixed
	 */
	public static function getWritableFolderPermissions()
	{
		return craft()->config->get('defaultFolderPermissions');
	}

	/**
	 * Returns the last $number of modified files from a given folder ordered by
	 * the last modified date descending.
	 *
	 * @param string $folder         The folder to get the files from.
	 * @param int    $number         The number of files to return.  If null is
	 *                               given, all files will be returned.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return array
	 */
	public static function getLastModifiedFiles($folder, $number = null, $suppressErrors = false)
	{
		$fileResults = array();

		$files = static::getFiles($folder, $suppressErrors);

		foreach ($files as $file)
		{
			$lastModifiedTime = IOHelper::getLastTimeModified($file, $suppressErrors);
			$fileResults[$lastModifiedTime->getTimestamp()] = $file;
		}

		krsort($fileResults);

		if ($number !== null)
		{
			$fileResults = array_slice($fileResults, 0, $number, true);
		}

		return $fileResults;
	}

	/**
	 * Returns a parent folder's path for a given path.
	 *
	 * @param string $fullPath The path to get the parent folder path for.
	 *
	 * @return string
	 */
	public static function getParentFolderPath($fullPath)
	{
		$fullPath = static::normalizePathSeparators($fullPath);

		// Drop the trailing slash and split it by slash
		$parts = explode("/", rtrim($fullPath, "/"));

		// Drop the last part and return the part leading up to it
		array_pop($parts);

		if (empty($parts))
		{
			return '';
		}

		return join("/", $parts).'/';
	}

	/**
	 * Custom error handler used in IOHelper used for detecting if the file system
	 * supports exclusive locks when writing.
	 *
	 * @param       $errNo
	 * @param       $errStr
	 * @param       $errFile
	 * @param       $errLine
	 * @param array $errContext
	 *
	 * @throws ErrorException
	 * @return bool
	 */
	public function handleError($errNo, $errStr, $errFile, $errLine, array $errContext)
	{
		// The error was suppressed with the @-operator
		if (0 === error_reporting())
		{
			return false;
		}

		$message = 'ErrNo: '.$errNo.': '.$errStr.' in file: '.$errFile.' on line: '.$errLine.'.';

		throw new ErrorException($message, 0);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param string $path
	 * @param string $contents
	 * @param bool   $lock
	 * @param bool   $append
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return bool
	 */
	private static function _writeToFile($path, $contents, $lock = true, $append = true, $suppressErrors = false)
	{
		$flags = 0;

		if ($lock)
		{
			$flags |= LOCK_EX;
		}

		if ($append)
		{
			$flags |= FILE_APPEND;
		}

		if (($suppressErrors ? @file_put_contents($path, $contents, $flags) : file_put_contents($path, $contents, $flags)) !== false)
		{
			return true;
		}

		return false;
	}

	/**
	 * Used by {@link getFolderSize} to calculate the size of a folder.
	 *
	 * @param string $path           The path of the folder.
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return int The size of the folder in bytes.
	 */
	private static function _folderSize($path, $suppressErrors = false)
	{
		$size = 0;

		foreach (static::getFolderContents($path, true, null, true, $suppressErrors) as $item)
		{
			$item = static::normalizePathSeparators($item);

			if (static::fileExists($item, false, $suppressErrors))
			{
				$size += sprintf("%u", $suppressErrors ? @filesize($item) : filesize($item));
			}
		}

		return $size;
	}

	/**
	 * @param string $path
	 * @param bool   $recursive
	 * @param null   $filter
	 * @param bool   $includeHiddenFiles
	 * @param bool   $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
	 *
	 * @return array
	 */
	private static function _folderContents($path, $recursive = false, $filter = null, $includeHiddenFiles = false, $suppressErrors = false)
	{
		$descendants = array();

		$path = static::normalizePathSeparators(static::getRealPath($path, $suppressErrors));

		if ($filter !== null)
		{
			if (is_string($filter))
			{
				$filter = array($filter);
			}
		}

		if (($contents = $suppressErrors ? @scandir($path) : scandir($path)) !== false)
		{
			foreach ($contents as $key => $item)
			{
				$fullItem = $path.$item;
				$contents[$key] = $fullItem;

				if ($item == '.' || $item == '..')
				{
					continue;
				}

				if (!$includeHiddenFiles)
				{
					// If it's hidden, skip it.
					if (isset($item[0]) && $item[0] == '.')
					{
						continue;
					}
				}

				if (static::_filterPassed($contents[$key], $filter))
				{
					if (static::fileExists($contents[$key], false, $suppressErrors))
					{
						$descendants[] = static::normalizePathSeparators($contents[$key]);
					}
					elseif (static::folderExists($contents[$key], false, $suppressErrors))
					{
						$descendants[] = static::normalizePathSeparators($contents[$key]);
					}
				}

				if (static::folderExists($contents[$key], false, $suppressErrors) && $recursive)
				{
					$descendants = array_merge($descendants, static::_folderContents($contents[$key], $recursive, $filter, $includeHiddenFiles, $suppressErrors));
				}
			}
		}
		else
		{
			Craft::log(Craft::t('Unable to get folder contents for “{path}”.', array('path' => $path), LogLevel::Error));
		}

		return $descendants;
	}

	/**
	 * Applies an array of filter rules to the string representing the file path. Used internally by {@link dirContents}
	 * method.
	 *
	 * @param string $str   String representing file path to be filtered
	 * @param array $filter An array of filter rules, where each rule is a string, supposing that the string starting
	 *                      with '/' is a regular expression. Any other string treated as an extension part of the given
	 *                      filepath (eg. file extension)
	 *
	 * @return bool Returns 'true' if the supplied string matched one of the filter rules.
	 */
	private static function _filterPassed($str, $filter)
	{
		$passed = false;

		if ($filter !== null)
		{
			foreach ($filter as $rule)
			{
				$passed = (bool)preg_match('/'.$rule.'/', $str);

				if ($passed)
				{
					break;
				}
			}
		}
		else
		{
			$passed = true;
		}

		return $passed;
	}

	/**
	 * Builds the internal file kinds array, if it hasn't been built already.
	 *
	 * @return void
	 */
	private static function _buildFileKinds()
	{
		if (!isset(self::$_fileKinds))
		{
			self::$_fileKinds = array(
				'access'      => array('label' => Craft::t('Access'),      'extensions' => array('adp','accdb','mdb','accde','accdt','accdr')),
				'audio'       => array('label' => Craft::t('Audio'),       'extensions' => array('3gp','aac','act','aif','aiff','aifc','alac','amr','au','dct','dss','dvf','flac','gsm','iklax','ivs','m4a','m4p','mmf','mp3','mpc','msv','oga','ogg','opus','ra','tta','vox','wav','wma','wv')),
				'compressed'  => array('label' => Craft::t('Compressed'),  'extensions' => array('bz2', 'tar', 'gz', '7z', 's7z', 'dmg', 'rar', 'zip', 'tgz', 'zipx')),
				'excel'       => array('label' => Craft::t('Excel'),       'extensions' => array('xls', 'xlsx','xlsm','xltx','xltm')),
				'flash'       => array('label' => Craft::t('Flash'),       'extensions' => array('fla','flv','swf','swt','swc')),
				'html'        => array('label' => Craft::t('HTML'),        'extensions' => array('html','htm')),
				'illustrator' => array('label' => Craft::t('Illustrator'), 'extensions' => array('ai')),
				'image'       => array('label' => Craft::t('Image'),       'extensions' => array('jfif','jp2','jpx','jpg','jpeg','jpe','tiff','tif','png','gif','bmp','webp','ppm','pgm','pnm','pfm','pam','svg')),
				'javascript'  => array('label' => Craft::t('Javascript'),  'extensions' => array('js')),
				'json'        => array('label' => Craft::t('JSON'),        'extensions' => array('json')),
				'pdf'         => array('label' => Craft::t('PDF'),         'extensions' => array('pdf')),
				'photoshop'   => array('label' => Craft::t('Photoshop'),   'extensions' => array('psd','psb')),
				'php'         => array('label' => Craft::t('PHP'),         'extensions' => array('php')),
				'powerpoint'  => array('label' => Craft::t('PowerPoint'),  'extensions' => array('pps','ppsm','ppsx','ppt','pptm','pptx','potx')),
				'text'        => array('label' => Craft::t('Text'),        'extensions' => array('txt','text')),
				'video'       => array('label' => Craft::t('Video'),       'extensions' => array('avchd','asf','asx','avi','flv','fla','mov','m4v','mng','mpeg','mpg','m1s','mp2v','m2v','m2s','mp4','mkv','qt','flv','mp4','ogg','ogv','rm','wmv','webm','vob')),
				'word'        => array('label' => Craft::t('Word'),        'extensions' => array('doc','docx','dot','docm','dotm')),
				'xml'         => array('label' => Craft::t('XML'),         'extensions' => array('xml')),
			);
		}
	}
}
