<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\io;

use craft\app\dates\DateTime;
use craft\app\helpers\Io;

/**
 * Class BaseIO
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class BaseIO
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    private $_realPath;

    /**
     * @var bool
     */
    private $_isReadable;

    /**
     * @var bool
     */
    private $_isWritable;

    /**
     * @var string
     */
    private $_fullFolderName;

    /**
     * @var string
     */
    private $_folderNameOnly;

    /**
     * @var
     */
    private $_lastTimeModified;

    /**
     * @var
     */
    private $_owner;

    /**
     * @var
     */
    private $_group;

    /**
     * @var
     */
    private $_permissions;

    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function getRealPath()
    {
        if (!$this->_realPath) {
            $this->_realPath = Io::getRealPath($this->path);
        }

        return $this->_realPath;
    }

    /**
     * @return boolean
     */
    public function isReadable()
    {
        if (!$this->_isReadable) {
            $this->_isReadable = Io::isReadable($this->getRealPath());
        }

        return $this->_isReadable;
    }

    /**
     * @return boolean
     */
    public function isWritable()
    {
        if (!$this->_isWritable) {
            $this->_isWritable = Io::isWritable($this->getRealPath());
        }

        return $this->_isWritable;
    }

    /**
     * @return string|integer|false
     */
    public function getOwner()
    {
        if (!isset($this->_owner)) {
            $this->_owner = Io::getOwner($this->getRealPath());
        }

        return $this->_owner;
    }

    /**
     * @return string|integer|false
     */
    public function getGroup()
    {
        if (!isset($this->_group)) {
            $this->_group = Io::getGroup($this->getRealPath());
        }

        return $this->_group;
    }

    /**
     * @return string|false
     */
    public function getPermissions()
    {
        if (!isset($this->_permissions)) {
            $this->_permissions = Io::getPermissions($this->getRealPath());
        }

        return $this->_permissions;
    }

    /**
     * @return DateTime|false
     */
    public function getLastTimeModified()
    {
        if (!isset($this->_lastTimeModified)) {
            $this->_lastTimeModified = Io::getLastTimeModified($this->getRealPath());
        }

        return $this->_lastTimeModified;
    }

    /**
     * @param string $owner
     *
     * @return boolean
     */
    public function changeOwner($owner)
    {
        if (!Io::changeOwner($this->getRealPath(), $owner)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $group
     *
     * @return boolean
     */
    public function changeGroup($group)
    {
        if (!Io::changeGroup($this->getRealPath(), $group)) {
            return false;
        }

        return true;
    }

    /**
     * @param integer $permissions
     *
     * @return boolean
     */
    public function changePermissions($permissions)
    {
        if (!Io::changePermissions($this->getRealPath(), $permissions)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $newName
     *
     * @return boolean
     */
    public function rename($newName)
    {
        if (!Io::rename($this->getRealPath(), $newName)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $newPath
     *
     * @return boolean
     */
    public function move($newPath)
    {
        if (!Io::move($this->getRealPath(), $newPath)) {
            return false;
        }

        return true;
    }

    /**
     * @param boolean $fullPath
     *
     * @return string
     */
    public function getFolderName($fullPath = true)
    {
        if ($fullPath) {
            if (!$this->_fullFolderName) {
                $this->_fullFolderName = Io::getFolderName($this->getRealPath(), $fullPath);
            }

            return $this->_fullFolderName;
        }

        if (!$this->_folderNameOnly) {
            $this->_folderNameOnly = Io::getFolderName($this->getRealPath(), $fullPath);
        }

        return $this->_folderNameOnly;
    }
}
