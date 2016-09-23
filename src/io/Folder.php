<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\io;

use craft\app\helpers\Io;

/**
 * Class Folder
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Folder extends BaseIO
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_size;

    /**
     * @var bool
     */
    private $_isEmpty;

    // Public Methods
    // =========================================================================

    /**
     * @param $path
     *
     * @return Folder
     */
    public function __construct($path)
    {
        clearstatcache();
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        if (!$this->_size) {
            $this->_size = Io::getFolderSize($this->getRealPath());
        }

        return $this->_size;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        if (!$this->_isEmpty) {
            $this->_isEmpty = Io::isFolderEmpty($this->getRealPath());
        }

        return $this->_isEmpty;
    }

    /**
     * @param $recursive
     * @param $filter
     *
     * @return mixed
     */
    public function getContents($recursive, $filter)
    {
        return Io::getFolderContents($this->getRealPath(), $recursive, $filter);
    }

    /**
     * @param $destination
     *
     * @return boolean
     */
    public function copy($destination)
    {
        if (!Io::copyFolder($this->getRealPath(), $destination)) {
            return false;
        }

        return true;
    }

    /**
     * @param boolean $suppressErrors
     *
     * @return boolean
     */
    public function clear($suppressErrors = false)
    {
        if (!Io::clearFolder($this->getRealPath(), $suppressErrors)) {
            return false;
        }

        return true;
    }

    /**
     * @return boolean
     */
    public function delete()
    {
        if (!Io::deleteFolder($this->getRealPath())) {
            return false;
        }

        return true;
    }
}
