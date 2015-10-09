<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\io;

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
     * @return mixed
     */
    public function getRealPath()
    {
        if (!$this->_realPath) {
            $this->_realPath = Io::getRealPath($this->path);
        }

        return $this->_realPath;
    }

    /**
     * @return mixed
     */
    public function isReadable()
    {
        if (!$this->_isReadable) {
            $this->_isReadable = Io::isReadable($this->getRealPath());
        }

        return $this->_isReadable;
    }

    /**
     * @return mixed
     */
    public function isWritable()
    {
        if (!$this->_isWritable) {
            $this->_isWritable = Io::isWritable($this->getRealPath());
        }

        return $this->_isWritable;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        if (!$this->_owner) {
            $this->_owner = Io::getOwner($this->getRealPath());
        }

        return $this->_owner;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        if (!$this->_group) {
            $this->_group = Io::getGroup($this->getRealPath());
        }

        return $this->_group;
    }

    /**
     * @return mixed
     */
    public function getPermissions()
    {
        if (!$this->_permissions) {
            $this->_permissions = Io::getPermissions($this->getRealPath());
        }

        return $this->_permissions;
    }

    /**
     * @return mixed
     */
    public function getLastTimeModified()
    {
        if (!$this->_lastTimeModified) {
            $this->_lastTimeModified = Io::getLastTimeModified($this->getRealPath());
        }

        return $this->_lastTimeModified;
    }

    /**
     * @param $owner
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
     * @param $group
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
     * @param $permissions
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
        } else {
            if (!$this->_folderNameOnly) {
                $this->_folderNameOnly = Io::getFolderName($this->getRealPath(), $fullPath);
            }

            return $this->_folderNameOnly;
        }
    }
}
