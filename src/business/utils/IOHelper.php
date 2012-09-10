<?php
namespace Blocks;

/**
 *
 */
class IOHelper
{
	const defaultFolderPermissions = 0754;

	public static function fileExists($path, $caseInsensitive = false)
	{
		$path = static::normalizePathSeparators($path);

		if (is_file($path))
			return $path;

		if ($caseInsensitive)
		{
			$dir = dirname($path);
			$files = glob($dir.'/*');
			$lcaseFileName = strtolower($path);

			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					$file = static::normalizePathSeparators($file);
					if (strtolower($file) === $lcaseFileName)
						return $file;
				}
			}
		}

		return false;
	}

	public static function folderExists($path, $caseInsensitive = false)
		{
			$path = static::normalizePathSeparators($path);

			if (is_dir($path))
				return $path;

			if ($caseInsensitive)
				return strtolower(static::getDirName($path)) === strtolower($path);

			return false;
		}

	/**
	 * Returns the real filesystem path of the given path.
	 *
	 * @param string $path Directory separator char (depends upon OS)
	 * @return string Real file path
	 */
	public static function getRealPath($path)
	{
		$path = static::normalizePathSeparators($path);
		return realpath($path);
	}

	public static function isReadable($path)
	{
		$path = static::normalizePathSeparators($path);
		return is_readable($path);
	}

	/**
	 * PHP's is_writable has problems (especially on Windows).
	 * See: https://bugs.php.net/bug.php?id=27609 and https://bugs.php.net/bug.php?id=30931.
	 * This function tests write-ability by creating a temp file on the filesystem.
	 *
	 * @param $path = the path to test.
	 * @return boolean 'True' if filesystem object is writable, otherwise 'false'
	 * @access private
	 */
	public static function isWritable($path)
	{
		$path = static::normalizePathSeparators($path);

		if (is_dir($path))
		{
			$path = rtrim(str_replace('\\', '/', $path), '/').'/';
			return static::isWritable($path.uniqid(mt_rand()).'.tmp');
		}

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

	public static function getFileName($path, $includeExtension = true)
	{
		$path = static::normalizePathSeparators($path);

		if ($includeExtension)
			return pathinfo($path, PATHINFO_BASENAME);
		else
			return pathinfo($path, PATHINFO_FILENAME);
	}

	public static function getFolderName($path, $fullPath = true)
	{
		$path = static::normalizePathSeparators($path);

		if ($fullPath)
			return static::normalizePathSeparators(pathinfo($path, PATHINFO_DIRNAME));
		else
		{
			$path = pathinfo($path, PATHINFO_DIRNAME);
			$parts = explode('/', $path);
			return $parts[count($parts) - 1];
		}
	}

	public static function getExtension($path, $default = null)
	{
		$path = static::normalizePathSeparators($path);
		$extension = pathinfo($path, PATHINFO_EXTENSION);

		if ($extension)
			return $extension;
		else
			return $default;
	}

	public static function getMimeType($path)
	{
		$path = static::normalizePathSeparators($path);
		return \CFileHelper::getMimeType($path);
	}

	public static function getMimeTypeByExtension($path)
	{
		$path = static::normalizePathSeparators($path);
		return  \CFileHelper::getMimeTypeByExtension($path);
	}

	public static function getLastTimeModified($path)
	{
		$path = static::normalizePathSeparators($path);
		return filemtime($path);
	}

	public static function getFileSize($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path))
			return sprintf("%u", filesize($path));

		return false;
	}

	public static function getFolderSize($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path))
		{
			return sprintf("%u", static::_folderSize($path));
		}

		return false;
	}

	public static function normalizePathSeparators($path)
	{
		$path = str_replace('\\', '/', $path);
		$path = str_replace('//', '/', $path);

		if (is_dir($path))
			$path = rtrim($path, '/').'/';

		return $path;
	}

	public static function isFileEmpty($path)
	{
		$path = static::normalizePathSeparators($path);
		if ((static::fileExists($path) && static::getFileSize($path) == 0))
			return true;

		return false;
	}

	public static function isFolderEmpty($path)
	{
		$path = static::normalizePathSeparators($path);
		if ((static::folderExists($path) && static::getFolderSize($path) == 0))
			return true;

		return false;
	}

	/**
	 * Returns owner of current filesystem object (UNIX systems). Returned value depends upon $getName parameter value.
	 *
	 * @param         $path The path to check.
	 * @param boolean $getName Defaults to 'true', meaning that owner name instead of ID should be returned.
	 * @return mixed Owner name, or ID if $getName set to 'false' or false if the file or folder does not exist.
	 */
	public static function getOwner($path, $getName = true)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
			$owner = fileowner($path);
		else
			$owner =  false;

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
	 * @param         $path The path to check.
	 * @param boolean $getName Defaults to 'true', meaning that group name instead of ID should be returned.
	 * @return mixed Group name, or ID if $getName set to 'false' or false if the file or folder does not exist.
	 */
	public static function getGroup($path, $getName = true)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
			$group = filegroup($path);
		else
			$group =  false;

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
	 * @param $path The patch to check
	 * @return string Filesystem object permissions in octal format (i.e. '0755'), false if the file or folder doesn't exist
	 */
	public static function getPermissions($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
			return substr(sprintf('%o', fileperms($path)), -4);

		return false;
	}

	public static function getFolderContents($path, $recursive, $filter)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) && static::isReadable($path))
		{
			if (($contents = static::_folderContents($path, $recursive, $filter)) !== false)
				return $contents;

			Blocks::log('Tried to read the file contents at '.$path.' and could not.', \CLogger::LEVEL_ERROR);
			return false;
		}

		Blocks::log('Tried to read the folder contents at '.$path.' and the folder does not exist or is not readable.', \CLogger::LEVEL_ERROR);
		return false;
	}

	public static function getFileContents($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) && static::isReadable($path))
		{
			if (($contents = file_get_contents($path)) !== false)
				return $contents;

			Blocks::log('Tried to read the file contents at '.$path.' and could not.', \CLogger::LEVEL_ERROR);
			return false;
		}

		Blocks::log('Tried to read the file contents at '.$path.' and the file does not exist or is not readable.', \CLogger::LEVEL_ERROR);
		return false;
	}

	public static function createFile($path)
	{
		$path = static::normalizePathSeparators($path);

		if (!static::fileExists($path))
		{
			if (($handle = fopen($path, 'w')) === false)
			{
				Blocks::log('Tried to create a file at '.$path.', but could not.', \CLogger::LEVEL_ERROR);
				return false;
			}

			fclose($handle);
			return new File($path);
		}

		Blocks::log('Tried to create a file at '.$path.', but the file already exists.', \CLogger::LEVEL_ERROR);
	}

	public static function createFolder($path, $permissions = static::defaultFolderPermissions)
	{
		$path = static::normalizePathSeparators($path);

		if (!static::folderExists($path))
		{
			$oldumask = umask(0);
			if (!@mkdir($path, $permissions, true))
			{
				Blocks::log('Tried to create a folder at '.$path.', but could not.', \CLogger::LEVEL_ERROR);
				return false;
			}

			// Because setting permission with mkdir is a crapshoot.
			@chmod($path, $permissions);
			@umask($oldumask);
			return new Folder($path);
		}

		Blocks::log('Tried to create a folder at '.$path.', but the folder already exists.', \CLogger::LEVEL_ERROR);
	}

	public static function writeToFile($path, $contents, $autoCreate = true, $flags = 0)
	{
		$path = static::normalizePathSeparators($path);

		if (!static::fileExists($path) && $autoCreate)
		{
			$folderName = static::getFolderName($path);
			if (!static::folderExists($folderName))
			{
				if (!static::createFolder($folderName))
					return false;
			}

			if ((!static::createFile($path)) !== false)
				return false;
		}

		if (static::isWritable($path))
		{
			if ((file_put_contents($path, $contents, $flags)) !== false)
				return true;

			Blocks::log('Tried to write to file at '.$path.', could not.', \CLogger::LEVEL_ERROR);
		}
		else
			Blocks::log('Tried to write to file at '.$path.', but the file is not writable.', \CLogger::LEVEL_ERROR);

		return false;
	}

	public static function changeOwner($path, $owner)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
		{
			if (chown($path, $owner))
				return true;

			Blocks::log('Tried to change the owner of '.$path.', but could not.', \CLogger::LEVEL_ERROR);
		}
		else
			Blocks::log('Tried to change owner of '.$path.', but that path does not exist.', \CLogger::LEVEL_ERROR);

		return false;
	}

	public static function changeGroup($path, $group)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
		{
			if (chgrp($path, $group))
				return true;

			Blocks::log('Tried to change the group of '.$path.', but could not.', \CLogger::LEVEL_ERROR);
		}
		else
			Blocks::log('Tried to change group of '.$path.', but that path does not exist.', \CLogger::LEVEL_ERROR);

		return false;
	}

	public static function changePermissions($path, $permissions)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path) || static::folderExists($path))
		{
			// '755' normalizes to octal '0755'
			$permissions = octdec(str_pad($permissions, 4, '0', STR_PAD_LEFT));

			if (chmod($path, $permissions))
				return true;

			Blocks::log('Tried to change the permissions of '.$path.', but could not.', \CLogger::LEVEL_ERROR);
		}
		else
			Blocks::log('Tried to change permissions of '.$path.', but that path does not exist.', \CLogger::LEVEL_ERROR);

		return false;
	}

	public static function copyFile($path, $destination)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path))
		{
			$destFolder = static::getFolderName($destination);

			if (!is_dir($destFolder))
				mkdir($destFolder, static::defaultFolderPermissions, true);

			if (static::isReadable($path))
			{
				if (copy($path, $destination))
					return true;

				Blocks::log('Tried to copy '.$path.' to '.$destination.', but could not.', \CLogger::LEVEL_ERROR);
			}
			else
			{
				Blocks::log('Tried to copy '.$path.' to '.$destination.', but could not read the source file.', \CLogger::LEVEL_ERROR);
			}
		}
		else
		{
			Blocks::log('Tried to copy '.$path.' to '.$destination.', but the source file does not exist.', \CLogger::LEVEL_ERROR);
		}

		return false;

	}

	public static function copyFolder($path, $destination)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path))
		{
			$folderContents = static::_folderContents($path, true);

			foreach ($folderContents as $item)
			{
				$itemDest = $destination.str_replace($path, '', $item);
				if (is_file($item))
				{
					if (!copy($item, $itemDest))
						Blocks::log('Could not copy file from '.$item.' to '.$itemDest.'.', \CLogger::LEVEL_ERROR);
				}
				elseif (is_dir($item))
				{
					if (!static::createFolder($itemDest))
						Blocks::log('Could not create destination folder '.$itemDest, \CLogger::LEVEL_ERROR);
				}
			}

			return true;
		}

		return false;
	}

	public static function rename($path, $newName)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path))
		{
			//$destRealPath = $this->_resolveDestPath($fileDest);

			if (static::isWritable($path))
			{
				if (rename($path, $newName))
					return true;
				else
					Blocks::log('Could not rename '.$path.' to '.$newName.'.', \CLogger::LEVEL_ERROR);
			}
			else
				Blocks::log('Could not rename '.$path.' to '.$newName.' because the source file or folder is not writable.', \CLogger::LEVEL_ERROR);
		}
		else
			Blocks::log('Could not rename '.$path.' to '.$newName.' because the source file or folder does not exist.', \CLogger::LEVEL_ERROR);

		return false;
	}

	public static function move($path, $newPath)
	{
		return static::rename($path, $newPath);
	}

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
				Blocks::log('Could not clear the contents of '.$path.' because the source file is not writable.', \CLogger::LEVEL_ERROR);
		}
		else
			Blocks::log('Could not clear the contents of '.$path.' because the source file does not exist.', \CLogger::LEVEL_ERROR);

		return false;
	}

	public static function clearFolder($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path))
		{
			$folderContents = static::_folderContents($path, true);

			foreach ($folderContents as $item)
			{
				$item = static::normalizePathSeparators($item);

				if (is_file($item))
					static::deleteFile($item);
				elseif (is_dir($item))
					static::deleteFolder($item);
			}

			return true;
		}
		else
			Blocks::log('Could not clear the contents of '.$path.' because the source folder does not exist.', \CLogger::LEVEL_ERROR);

		return false;
	}

	public static function deleteFile($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path))
		{
			if (static::isWritable($path))
			{
				if (unlink($path))
					return true;
				else
					Blocks::log('Could not delete the file '.$path.'.', \CLogger::LEVEL_ERROR);
			}
			else
				Blocks::log('Could not delete the file '.$path.' because it is not writable.', \CLogger::LEVEL_ERROR);
		}
		else
			Blocks::log('Could not delete the file '.$path.' because the file does not exist.', \CLogger::LEVEL_ERROR);

		return false;
	}

	public static function deleteFolder($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::folderExists($path))
		{
			if (static::isWritable($path))
			{
				if (rmdir($path))
					return true;
				else
					Blocks::log('Could not delete the folder '.$path.'.', \CLogger::LEVEL_ERROR);
			}
			else
				Blocks::log('Could not delete the folder '.$path.' because it is not writable.', \CLogger::LEVEL_ERROR);
		}
		else
			Blocks::log('Could not delete the folder '.$path.' because the folder does not exist.', \CLogger::LEVEL_ERROR);

		return false;
	}

	public static function getFileMD5($path)
	{
		$path = static::normalizePathSeparators($path);

		if (static::fileExists($path))
		{
			return md5_file($path);
		}
		else
			Blocks::log('Could not calculate the MD5 for the file '.$path.' because the file does not exist.', \CLogger::LEVEL_ERROR);
	}

	private static function _folderSize($path)
	{
		$size = 0;

		foreach (static::_folderContents($path, true) as $item)
		{
			$item = static::normalizePathSeparators($item);

			if (static::fileExists($item))
				$size += sprintf("%u", filesize($item));
		}

		return $size;
	}

	private static function _folderContents($path, $recursive = false, $filter = null)
	{
		$descendants = array();

		// TODO: Figure out filter.
		if ($filter !== null)
		{
			if (is_string($filter))
				$filter = array($filter);

			foreach ($filter as $key => $rule)
			{
				if ($rule[0] != '/')
					$filter[$key] = ltrim($rule, '.');
			}
		}

		if (($contents = @scandir($path)) !== false)
		{
			foreach ($contents as $key => $item)
			{
				// TODO: normalize item?
				$contents[$key] = $key.'/'.$item;

				if (!in_array($item, array('.', '..')))
				{
					if (static::_filterPassed($contents[$key], $filter))
					{
						if (is_dir($contents[$key]))
							$descendants[] = new Folder($contents[$key]);
						elseif (is_file($contents[$key]))
							$descendants[] = new File($contents[$key]);
					}

					if (is_dir($contents[$key]) && $recursive)
						$descendants = array_merge($descendants, static::_folderContents($contents[$key], $recursive, $filter));
				}
			}
		}
		else
		{
			Blocks::log(Blocks::t('Unable to get directory contents for “{path}”.', array('path' => $path), \CLogger::LEVEL_ERROR));
		}

		return $descendants;
	}

	/**
	 * Applies an array of filter rules to the string representing filepath. Used internally by {@link dirContents} method.
	 *
	 * @param string $str String representing filepath to be filtered
	 * @param array $filter An array of filter rules, where each rule is a string, supposing that the string starting with '/' is a regular
	 * expression. Any other string treated as an extension part of the given filepath (eg. file extension)
	 * @return boolean Returns 'true' if the supplied string matched one of the filter rules.
	 * @access private
	 */
	private function _filterPassed($str, $filter)
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
				{
					$passed = (bool)preg_match($rule, $str);
				}

				if ($passed)
					break;
			}
		}
		else
			$passed = true;

		return $passed;
	}
}

