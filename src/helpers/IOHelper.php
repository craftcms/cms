<?php
namespace Craft;

/**
 *
 */
class IOHelper
{
	/**
	 * Tests whether the given file path exists on the file system.
	 *
	 * @static
	 * @param  string  $path            The path to test.
	 * @param  bool    $caseInsensitive Whether to perform a case insensitive check or not.
	 * @return string The resolved path of the file if it exists.
	 */
	public static function fileExists($path, $caseInsensitive = false)
	{
		$resolvedPath = static::getRealPath($path);

		if ($resolvedPath)
		{
			if (is_file($resolvedPath))
			{
				return $resolvedPath;
			}
		}
		else if ($caseInsensitive)
		{
			$folder = static::getFolderName($path);
			$files = static::getFolderContents($folder, false);
			$lcaseFileName = mb_strtolower($path);

			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					$file = static::normalizePathSeparators($file);

					if (is_file($file))
					{
						if (mb_strtolower($file) === $lcaseFileName)
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
	 * @static
	 * @param  string  $path The path to test.
	 * @param  bool    $caseInsensitive Whether to perform a case insensitive check or not.
	 * @return boolean 'true' if the folder exists, otherwise 'false'.
	 */
	public static function folderExists($path, $caseInsensitive = false)
	{
		$path = static::getRealPath($path);

		if ($path)
		{
			if (is_dir($path))
			{
				return $path;
			}

			if ($caseInsensitive)
			{
				return mb_strtolower(static::getFolderName($path)) === mb_strtolower($path);
			}
		}

		return false;
	}

	/**
	 * If the file exists on the file system will return a new File instance, otherwise, false.
	 *
	 * @param $path
	 * @return File|bool
	 */
	public static function getFile($path)
	{
		if (static::fileExists($path))
		{
			return new File($path);
		}

		return false;
	}

	/**
	 * If the folder exists on the file system, will return a new Folder instance, otherwise, false.
	 *
	 * @param $path
	 * @return Folder|bool
	 */
	public static function getFolder($path)
	{
		if (static::folderExists($path))
		{
			return new Folder($path);
		}

		return false;
	}

	/**
	 * Returns the real filesystem path of the given path.
	 *
	 * @static
	 * @param  string $path The path to test.
	 * @return string The real file or folder path.
	 */
	public static function getRealPath($path)
	{
		$path = static::normalizePathSeparators($path);
		$path = realpath($path);

		if (is_dir($path))
		{
			$path = $path.'/';
		}

		return $path;
	}

	/**
	 * Tests whether the give filesystem path is readable.
	 *
	 * @static
	 * @param  string  $path The path to test.
	 * @return boolean 'true' if filesystem path is readable, otherwise 'false'.
	 */
	public static function isReadable($path)
	{
		$path = static::normalizePathSeparators($path);
		return is_readable($path);
	}

	/**
	 * Tests file and folder write-ability by attempting to create a temp file on the filesystem.
	 * PHP's is_writable has problems (especially on Windows).
	 * See: https://bugs.php.net/bug.php?id=27609 and https://bugs.php.net/bug.php?id=30931.
	 *
	 * @static
	 * @param  string $path The path to test.
	 * @return bool   'true' if filesystem object is writable, otherwise 'false'.
	 */
	public static function isWritable($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path))
		{
			$path = rtrim(str_replace('\\', '/', $path), '/').'/';
			return static::isWritable($path.uniqid(mt_rand()).'.tmp');
		}

		// Check tmp file for read/write capabilities
		$rm = static::fileExists($path);
		$f = @fopen($path, 'a');

		if ($f === false)
		{
			return false;
		}

		fclose($f);

		if (!$rm)
		{
			unlink($path);
		}

		return true;
	}

	/**
	 * Will return the file name of the given path with or without the extension.
	 *
	 * @static
	 * @param  string $path The path to test.
	 * @param  bool   $includeExtension Whether to include the extension in the file name.
	 * @return string The file name with or without the extension.
	 */
	public static function getFileName($path, $includeExtension = true)
	{
		$path = static::normalizePathSeparators($path);

		if ($includeExtension)
		{
			return pathinfo($path, PATHINFO_BASENAME);
		}
		else
		{
			return pathinfo($path, PATHINFO_FILENAME);
		}
	}

	/**
	 * Will return the folder name of the given path either as the full path or only the single top level folder.
	 *
	 * @static
	 * @param  string $path     The path to test.
	 * @param  bool   $fullPath Whether to include the full path in the return results or the top level folder only.
	 * @return string The folder name.
	 */
	public static function getFolderName($path, $fullPath = true)
	{
		$path = static::normalizePathSeparators($path);

		if ($fullPath)
		{
			$folder = static::normalizePathSeparators(pathinfo($path, PATHINFO_DIRNAME));

			// normalizePathSeparators() only enforces the trailing slash for known directories
			// so let's be sure that it'll be there.
			return rtrim($folder, '/').'/';
		}
		else
		{
			if (!is_dir($path))
			{
				// Chop off the file
				$path = pathinfo($path, PATHINFO_DIRNAME);
			}

			return pathinfo($path, PATHINFO_BASENAME);
		}
	}

	/**
	 * Returns the file extension for the given path.  If there is not one, then $default is returned instead.
	 *
	 * @static
	 * @param  string      $path    The path to test.
	 * @param  null|string $default If the file has no extension, this one will be returned by default.
	 * @return string      The file extension.
	 */
	public static function getExtension($path, $default = null)
	{
		$path = static::normalizePathSeparators($path);
		$extension = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

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
	 * A wrapper for {@link \CFileHelper::getMimeType}
	 *
	 * @static
	 * @param  string $path The path to test.
	 * @return string The mime type.
	 */
	public static function getMimeType($path)
	{
		return \CFileHelper::getMimeType($path);
	}

	/**
	 * A wrapper for {@link \CFileHelper::getMimeTypeByExtension}
	 *
	 * @static
	 * @param  string $path The path to test.
	 * @return string The mime type.
	 */
	public static function getMimeTypeByExtension($path)
	{
		return  \CFileHelper::getMimeTypeByExtension($path);
	}

	/**
	 * Returns the last modified time for the given path in DateTime format or false if the file or folder does not exist.
	 *
	 * @static
	 * @param  string   $path The path to test.
	 * @return int|bool The last modified timestamp or false if the file or folder does not exist.
	 */
	public static function getLastTimeModified($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
		{
			return new DateTime('@'.filemtime($path));
		}

		return false;
	}

	/**
	 * Returns the file size in bytes for the given path or false if the file does not exist.
	 *
	 * @static
	 * @param  string      $path The path to test.
	 * @return bool|string The file size in bytes or false if the file does not exist.
	 */
	public static function getFileSize($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path))
		{
			return sprintf("%u", filesize($path));
		}

		return false;
	}

	/**
	 * Returns the folder size in bytes for the given path or false if the folder does not exist.
	 *
	 * @static
	 * @param  string      $path The path to test.
	 * @return bool|string The folder size in bytes or false if the folder does not exist.
	 */
	public static function getFolderSize($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path))
		{
			return sprintf("%u", static::_folderSize($path));
		}

		return false;
	}

	/**
	 * Will take a given path and normalize it to use single forward slashes for path separators.  If it is a folder, it will append a trailing forward slash to the end of the path.
	 *
	 * @static
	 * @param  string $path The path to normalize.
	 * @return string The normalized path.
	 */
	public static function normalizePathSeparators($path)
	{
		$path = str_replace('\\', '/', $path);
		$path = str_replace('//', '/', $path);

		// Check if the path is just a slash.  If the server has openbase_dir restrictions in place calling is_dir on it will complain.
		if ($path !== '/')
		{
			// Use is_dir here to prevent an endless recursive loop
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
	 * @static
	 * @param  string $path The path to test.
	 * @return bool  Whether the file is empty or not.
	 */
	public static function isFileEmpty($path)
	{
		$path = static::normalizePathSeparators($path);

		if ((static::fileExists($path) && static::getFileSize($path) == 0))
		{
			return true;
		}

		return false;
	}

	/**
	 * Will take a path, make sure the folder exists and if the size of the folder is 0 bytes, return true.  Otherwise false.
	 *
	 * @static
	 * @param  string $path The path to test.
	 * @return bool   Whether the folder is empty or not.
	 */
	public static function isFolderEmpty($path)
	{
		$path = static::normalizePathSeparators($path);

		if ((static::folderExists($path) && static::getFolderSize($path) == 0))
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns owner of current filesystem object (UNIX systems). Returned value depends upon $getName parameter value.
	 *
	 * @static
	 * @param          $path The path to check.
	 * @param  boolean $getName Defaults to 'true', meaning that owner name instead of ID should be returned.
	 * @return mixed   Owner name, or ID if $getName set to 'false' or false if the file or folder does not exist.
	 */
	public static function getOwner($path, $getName = true)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
		{
			$owner = fileowner($path);
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
	 * Returns group of current filesystem object (UNIX systems). Returned value depends upon $getName parameter value.
	 *
	 * @static
	 * @param          $path The path to check.
	 * @param  boolean $getName Defaults to 'true', meaning that group name instead of ID should be returned.
	 * @return mixed   Group name, or ID if $getName set to 'false' or false if the file or folder does not exist.
	 */
	public static function getGroup($path, $getName = true)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
		{
			$group = filegroup($path);
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
	 * @static
	 * @param  string $path The path to check
	 * @return string Filesystem object permissions in octal format (i.e. '0755'), false if the file or folder doesn't exist
	 */
	public static function getPermissions($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
		{
			return mb_substr(sprintf('%o', fileperms($path)), -4);
		}

		return false;
	}

	/**
	 * Returns the contents of a folder as an array of file and folder paths, or false if the folder does not exist or is not readable.
	 *
	 * @static
	 * @param  string     $path      The path to test.
	 * @param  bool       $recursive Whether to do a recursive folder search.
	 * @param  bool       $filter    The filter to use when performing the search.
	 * @return array|bool An array of file and folder paths, or false if the folder does not exist or is not readable.
	 */
	public static function getFolderContents($path, $recursive = true, $filter = null)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path) && static::isReadable($path))
		{
			if (($contents = static::_folderContents($path, $recursive, $filter)) !== false)
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
	 * @static
	 * @param  string            $path  The path of the file.
	 * @param  bool              $array Whether to return the contents of the file as an array or not.
	 * @return bool|string|array The contents of the file as a string, an array, or false if the file does not exist or is not readable.
	 */
	public static function getFileContents($path, $array = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) && static::isReadable($path))
		{
			if ($array)
			{
				if (($contents = file($path)) !== false)
				{
					return $contents;
				}
			}
			else
			{
				if (($contents = file_get_contents($path)) !== false)
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
	 * Will create a file on the file system at the given path and return a {@link File} object or false if we don't have write permissions.
	 *
	 * @static
	 * @param  string    $path The path of the file to create.
	 * @return File|bool The newly created file as a {@link File} object or false if we don't have write permissions.
	 */
	public static function createFile($path)
	{
		$path = static::normalizePathSeparators($path);

		if (!static::fileExists($path))
		{
			if (($handle = fopen($path, 'w')) === false)
			{
				Craft::log('Tried to create a file at '.$path.', but could not.', LogLevel::Error);
				return false;
			}

			fclose($handle);
			return new File($path);
		}

		return false;
	}

	/**
	 * Will create a folder on the file system at the given path and return a {@link Folder} object or false if we don't have write permissions.
	 *
	 * @static
	 * @param  string      $path The path of the file to create.
	 * @param  int         $permissions The permissions to set the folder to.
	 * @return Folder|bool The newly created folder as a {@link Folder} object or false if we don't have write permissions.
	 */
	public static function createFolder($path, $permissions = null)
	{
		if ($permissions == null)
		{
			$permissions = craft()->config->get('defaultFolderPermissions');
		}

		$path = static::normalizePathSeparators($path);

		if (!static::folderExists($path))
		{
			$oldumask = umask(0);

			if (!mkdir($path, $permissions, true))
			{
				Craft::log('Tried to create a folder at '.$path.', but could not.', LogLevel::Error);
				return false;
			}

			// Because setting permission with mkdir is a crapshoot.
			chmod($path, $permissions);
			umask($oldumask);
			return new Folder($path);
		}

		Craft::log('Tried to create a folder at '.$path.', but the folder already exists.', LogLevel::Error);
		return false;
	}

	/**
	 * Will write $contents to a file.
	 *
	 * @static
	 * @param  string $path       The path of the file to write to.
	 * @param  string $contents   The contents to be written to the file.
	 * @param  bool   $autoCreate Whether or not to autocreate the file if it does not exist.
	 * @param  bool   $append     If true, will append the data to the contents of the file, otherwise it will overwrite the contents.
	 * @param  null   $noFileLock
	 * @return bool   'true' upon successful writing to the file, otherwise false.
	 */
	public static function writeToFile($path, $contents, $autoCreate = true, $append = false, $noFileLock = null)
	{
		$path = static::normalizePathSeparators($path);

		if (!static::fileExists($path) && $autoCreate)
		{
			$folderName = static::getFolderName($path);

			if (!static::folderExists($folderName))
			{
				if (!static::createFolder($folderName))
				{
					return false;
				}
			}

			if ((!static::createFile($path)) !== false)
			{
				return false;
			}
		}

		if (static::isWritable($path))
		{
			// We haven't cached file lock information yet and this is not a noFileLock request.
			if (($useFileLock = craft()->fileCache->get('useWriteFileLock')) === false && !$noFileLock)
			{
				// For file systems that don't support file locking... LOOKING AT YOU NFS!!!
				set_error_handler(array(new IOHelper(), 'handleError'));

				try
				{
					Craft::log('Trying to write to file at '.$path.' using LOCK_EX.', LogLevel::Info, true);
					if (static::_writeToFile($path, $contents, true, $append))
					{
						// Restore quickly.
						restore_error_handler();

						// Cache the file lock info to use LOCK_EX for 2 months.
						Craft::log('Successfully wrote to file at '.$path.' using LOCK_EX. Saving in cache.', LogLevel::Info, true);
						craft()->fileCache->set('useWriteFileLock', 'yes', 5184000);
						return true;
					}
					else
					{
						// Try again without the lock flag.
						Craft::log('Trying to write to file at '.$path.' without LOCK_EX.', LogLevel::Info, true);
						if (static::_writeToFile($path, $contents, false, $append))
						{
							// Cache the file lock info to not use LOCK_EX for 2 months.
							Craft::log('Successfully wrote to file at '.$path.' without LOCK_EX. Saving in cache.', LogLevel::Info, true);
							craft()->fileCache->set('useWriteFileLock', 'no', 5184000);
							return true;
						}
					}
				}
				catch (ErrorException $e)
				{
					// Restore here before we attempt to write again.
					restore_error_handler();

					// Try again without the lock flag.
					Craft::log('Trying to write to file at '.$path.' without LOCK_EX.', LogLevel::Info, true);
					if (static::_writeToFile($path, $contents, false, $append))
					{
						// Cache the file lock info to not use LOCK_EX for 2 months.
						Craft::log('Successfully wrote to file at '.$path.' without LOCK_EX. Saving in cache.', LogLevel::Info, true);
						craft()->fileCache->set('useWriteFileLock', 'no', 5184000);
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
					Craft::log('Cache says use LOCK_EX. Writing to '.$path.'.', LogLevel::Info);
					// Write with LOCK_EX
					if (static::_writeToFile($path, $contents, true, $append))
					{
						return true;
					}
				}
				else
				{
					Craft::log('Cache says not to use LOCK_EX. Writing to '.$path.'.', LogLevel::Info);
					// Write without LOCK_EX
					if (static::_writeToFile($path, $contents, false, $append))
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
		else
		{
			Craft::log('Tried to write to file at '.$path.', but the file is not writable.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Will attempt to change the owner of the given file system path (*nix only)
	 *
	 * @static
	 * @param  string $path  The path to change the owner of.
	 * @param         $owner The new owner's name.
	 * @param bool    $recursive
	 * @return bool   'true' if successful, 'false' if not or the given path does not exist.
	 */
	public static function changeOwner($path, $owner, $recursive = false)
	{
		$path = static::normalizePathSeparators($path);

		if (posix_getpwnam($owner) == false xor (is_numeric($owner) && posix_getpwuid($owner)== false))
		{
			Craft::log('Tried to change the owner of '.$path.', but the owner name "'.$owner.'" does not exist.', LogLevel::Error);
			return false;
		}

		if (static::fileExists($path) || static::folderExists($path))
		{
			$success = chown($path, $owner);

			if ($success && static::folderExists($path) && $recursive)
			{
				$contents = static::getFolderContents($path);

				foreach ($contents as $path)
				{
					$path = static::normalizePathSeparators($path);

					if (!chown($path, $owner))
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
	 * @static
	 * @param  string $path  The path to change the group of.
	 * @param         $group The new group name.
	 * @param bool    $recursive If the path is a directory, whether to recursively change the group of the child files and folders.
	 * @return bool   'true' if successful, 'false' if not, or the given path does not exist.
	 */
	public static function changeGroup($path, $group, $recursive = false)
	{
		$path = static::normalizePathSeparators($path);

		if (posix_getgrnam($group) == false xor (is_numeric($group) && posix_getgrgid($group) == false))
		{
			Craft::log('Tried to change the group of '.$path.', but the group name "'.$group.'" does not exist.', LogLevel::Error);
			return false;
		}

		if (static::fileExists($path) || static::folderExists($path))
		{
			$success = chgrp($path, $group);

			if ($success && static::folderExists($path) && $recursive)
			{
				$contents = static::getFolderContents($path);

				foreach ($contents as $path)
				{
					$path = static::normalizePathSeparators($path);

					if (!chgrp($path, $group))
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
	 * Will attempt to change the permission of the given file system path (*nix only)
	 *
	 * @static
	 * @param  string $path The path to change the permissions of.
	 * @param  int $permissions The new permissions.
	 * @return bool         'true' if successful, 'false' if not or the path does not exist.
	 */
	public static function changePermissions($path, $permissions)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
		{
			if (chmod($path, $permissions))
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
	 * @static
	 * @param  string $path        The source path of the file.
	 * @param  string $destination The destination path to copy the file to.
	 * @return bool   'true' if the copy was successful, 'false' if it was not, the source file is not readable or does not exist.
	 */
	public static function copyFile($path, $destination)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path))
		{
			$destFolder = static::getFolderName($destination);

			if (!static::folderExists($destFolder))
			{
				static::createFolder($destFolder, craft()->config->get('defaultFolderPermissions'));
			}

			if (static::isReadable($path))
			{
				if (copy($path, $destination))
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
	 * @static
	 * @param  string $path        The source path to copy.
	 * @param  string $destination The destination path to copy to.
	 * @param  bool   $validate    Whether to compare the size of the folders after the copy is complete.
	 * @return bool   'true' if the copy was successful, 'false' if it was not, or $validate is true and the size of the folders do not match after the copy.
	 */
	public static function copyFolder($path, $destination, $validate = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path))
		{
			$folderContents = static::getFolderContents($path, true, null);

			foreach ($folderContents as $item)
			{
				$itemDest = $destination.str_replace($path, '', $item);

				$destFolder = static::getFolderName($itemDest);

				if (!static::folderExists($destFolder))
				{
					static::createFolder($destFolder, craft()->config->get('defaultFolderPermissions'));
				}

				if (static::fileExists($item))
				{
					if (!copy($item, $itemDest))
					{
						Craft::log('Could not copy file from '.$item.' to '.$itemDest.'.', LogLevel::Error);
					}
				}
				elseif (static::folderExists($item))
				{
					if (!static::createFolder($itemDest))
					{
						Craft::log('Could not create destination folder '.$itemDest, LogLevel::Error);
					}
				}
			}

			if ($validate)
			{
				if (static::getFolderSize($path) !== static::getFolderSize($destination))
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
	 * @static
	 * @param  string $path    The original path of the file or folder.
	 * @param  string $newName The new name of the file or folder.
	 * @return bool   'true' if successful, 'false' if not or the source file or folder does not exist.
	 */
	public static function rename($path, $newName)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
		{
			if (static::isWritable($path))
			{
				if (rename($path, $newName))
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
	 * @param $path
	 * @param $newPath
	 * @return bool
	 */
	public static function move($path, $newPath)
	{
		return static::rename($path, $newPath);
	}

	/**
	 * Purges the contents of a file.
	 *
	 * @static
	 * @param  string $path The path of the file to clear.
	 * @return bool   'true' if the file was successfully cleared, 'false' if it wasn't, if the file is not writable or the file does not exist.
	 */
	public static function clearFile($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path))
		{
			if (static::isWritable($path))
			{
				static::writeToFile($path, '', false);
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
	 * @static
	 * @param  string $path           The path of the folder to clear.
	 * @param  bool   $suppressErrors Whether to suppress any errors (usually permissions related) when deleting the files or folders.
	 * @return bool  'true' if is successfully purges the folder, 'false' if the folder does not exist.
	 */
	public static function clearFolder($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path))
		{
			$folderContents = static::getFolderContents($path);

			foreach ($folderContents as $item)
			{
				$item = static::normalizePathSeparators($item);

				if (static::fileExists($item))
				{
					static::deleteFile($item, $suppressErrors);
				}
				elseif (static::folderExists($item))
				{
					static::deleteFolder($item, $suppressErrors);
				}
			}

			return true;
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
	 * @static
	 *
	 * @param  string $path           The path of the file to delete.
	 * @param  bool   $suppressErrors Whether to suppress any errors (usually permissions related) when deleting the file.
	 * @return bool   'true' if successful, 'false' if it cannot be deleted, it does not exist or it is not writable.
	 */
	public static function deleteFile($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path))
		{
			if (static::isWritable($path))
			{
				if ($suppressErrors)
				{
					if (@unlink($path))
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
					if (unlink($path))
					{
						return true;
					}
					else
					{
						Craft::log('Could not delete the file '.$path.'.', LogLevel::Error);
					}
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
	 * @static
	 * @param  string $path           The path of the folder to delete.
	 * @param  bool   $suppressErrors Whether to suppress any errors (usually permissions related) when deleting the folder.
	 * @return bool   'true' if successful, 'false' if it cannot be deleted, it does not exist or it is not writable.
	 */
	public static function deleteFolder($path, $suppressErrors = false)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path))
		{
			if (static::isWritable($path))
			{
				// Empty the folder contents first.
				static::clearFolder($path, $suppressErrors);

				if ($suppressErrors)
				{
					// Delete the folder.
					if (@rmdir($path))
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
					// Delete the folder.
					if (rmdir($path))
					{
						return true;
					}
					else
					{
						Craft::log('Could not delete the folder '.$path.'.', LogLevel::Error);
					}
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
	 * @static
	 * @param  string      $path The path of the file to calculate.
	 * @return bool|string The MD5 hash or false if it does not exist, isn't readable or could not be calculated.
	 */
	public static function getFileMD5($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) && static::isReadable($path))
		{
			return md5_file($path);
		}
		else
		{
			Craft::log('Could not calculate the MD5 for the file '.$path.' because the file does not exist.', LogLevel::Error);
		}

		return false;
	}

	/**
	 * Used by {@link getFolderSize} to calculate the size of a folder.
	 *
	 * @static
	 * @access private
	 * @param  string $path The path of the folder.
	 * @return int    The size of the folder in bytes.
	 */
	private static function _folderSize($path)
	{
		$size = 0;

		foreach (static::getFolderContents($path) as $item)
		{
			$item = static::normalizePathSeparators($item);

			if (static::fileExists($item))
			{
				$size += sprintf("%u", filesize($item));
			}
		}

		return $size;
	}

	/**
	 *
	 * @static
	 * @access private
	 * @param      $path
	 * @param bool $recursive
	 * @param null $filter
	 * @return array
	 */
	private static function _folderContents($path, $recursive = false, $filter = null)
	{
		$descendants = array();

		$path = static::normalizePathSeparators(static::getRealPath($path));

		if ($filter !== null)
		{
			if (is_string($filter))
			{
				$filter = array($filter);
			}
		}

		if (($contents = scandir($path)) !== false)
		{
			foreach ($contents as $key => $item)
			{
				$fullItem = $path.$item;
				$contents[$key] = $fullItem;

				// If it's hidden or a path specifier, skip it.
				if (isset($item[0]) && $item[0] == '.')
				{
					continue;
				}

				if (static::_filterPassed($contents[$key], $filter))
				{
					if (static::fileExists($contents[$key]))
					{
						$descendants[] = static::normalizePathSeparators($contents[$key]);
					}
					elseif (static::folderExists($contents[$key]))
					{
						$descendants[] = static::normalizePathSeparators($contents[$key]);
					}
				}

				if (static::folderExists($contents[$key]) && $recursive)
				{
					$descendants = array_merge($descendants, static::_folderContents($contents[$key], $recursive, $filter));
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
	 * Applies an array of filter rules to the string representing the file path. Used internally by {@link dirContents} method.
	 *
	 * @param string $str String representing filepath to be filtered
	 * @param array $filter An array of filter rules, where each rule is a string, supposing that the string starting with '/' is a regular
	 * expression. Any other string treated as an extension part of the given filepath (eg. file extension)
	 * @return boolean Returns 'true' if the supplied string matched one of the filter rules.
	 * @access private
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
	 * Get a list of allowed file extensions
	 * @return array
	 */
	public static function getAllowedFileExtensions()
	{
		return  ArrayHelper::stringToArray(craft()->config->get('allowedFileExtensions'));
	}

	/**
	 * Returns whether the extension is allowed
	 * @param $extension
	 * @return bool
	 */
	public static function isExtensionAllowed($extension)
	{
		return in_array($extension, static::getAllowedFileExtensions());
	}

	/**
	 * Returns a list of file kinds
	 * @return array
	 */
	public static function getFileKinds()
	{
		return array(
			'access'      => array('adp','accdb','mdb'),
			'audio'       => array('wav','aif','aiff','aifc','m4a','wma','mp3','aac','oga'),
			'excel'       => array('xls', 'xlsx'),
			'flash'       => array('fla','swf'),
			'html'        => array('html','htm'),
			'illustrator' => array('ai'),
			'image'       => array('jpg','jpeg','jpe','tiff','tif','png','gif','bmp','webp'),
			'pdf'         => array('pdf'),
			'photoshop'   => array('psd','psb'),
			'php'         => array('php'),
			'powerpoint'  => array('ppt', 'pptx'),
			'text'        => array('txt','text'),
			'video'       => array('mov','m4v','wmv','avi','flv','mp4','ogg','ogv','rm'),
			'word'        => array('doc','docx')
		);
	}

	/**
	 * Return a file's kind by extension
	 * @param $extension
	 * @return int|string
	 */
	public static function getFileKind($extension)
	{
		$extension = mb_strtolower($extension);
		$fileKinds = static::getFileKinds();
		foreach ($fileKinds as $kind => $extensions)
		{
			if (in_array($extension, $extensions))
			{
				return $kind;
			}
		}
	}

	/**
	 * Makes sure a folder exists. If it does not - creates one with write permissions
	 *
	 * @param $folderPath
	 * @return void
	 */
	public static function ensureFolderExists($folderPath)
	{
		if (!IOHelper::folderExists($folderPath))
		{
			IOHelper::createFolder($folderPath, self::getWritableFolderPermissions());
		}
	}

	/**
	 * Clean a filename.
	 *
	 * @param $fileName
	 * @return mixed
	 */
	public static function cleanFilename($fileName)
	{
		$fileName = StringHelper::asciiString(ltrim($fileName, '.'));
		return preg_replace('/[^@a-z0-9\-_\.]/i', '_', str_replace(chr(0), '', $fileName));
	}

	/**
	 * Will set the access and modification times of the given file to the given time, or the current time if it is not supplied.
	 *
	 * @param      $fileName
	 * @param  null $time
	 * @return bool
	 */
	public static function touch($fileName, $time = null)
	{
		if (!$time)
		{
			$time = time();
		}

		if (@touch($fileName, $time))
		{
			return true;
		}

		return false;
	}

	/**
	 * @return mixed
	 */
	public static function getDefaultFolderPermissions()
	{
		return craft()->config->get('defaultFolderPermissions');
	}

	/**
	 * @return mixed
	 */
	public static function getWritableFolderPermissions()
	{
		return craft()->config->get('writableFolderPermissions');
	}

	/**
	 * @return mixed
	 */
	public static function getWritableFilePermissions()
	{
		return craft()->config->get('writableFilePermissions');
	}

	/**
	 * @param       $errNo
	 * @param       $errStr
	 * @param       $errFile
	 * @param       $errLine
	 * @param array $errContext
	 * @return bool
	 * @throws ErrorException
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

	/**
	 * @param       $path
	 * @param       $contents
	 * @param  bool $lock
	 * @param  bool $append
	 *
	 * @return bool
	 */
	private static function _writeToFile($path, $contents, $lock = true, $append = true)
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

		if ((file_put_contents($path, $contents, $flags)) !== false)
		{
			return true;
		}

		return false;
	}
}
