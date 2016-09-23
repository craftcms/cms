<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\io;

use craft\app\helpers\Io;

/**
 * Class File
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class File extends BaseIO
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    private $_baseName;

    /**
     * @var string
     */
    private $_filename;

    /**
     * @var string
     */
    private $_extension;

    /**
     * @var string
     */
    private $_mimeType;

    /**
     * @var
     */
    private $_size;

    /**
     * @var bool
     */
    private $_isEmpty;

    /**
     * @var
     */
    private $_arrayContents;

    /**
     * @var
     */
    private $_stringContents;

    /**
     * @var
     */
    private $_md5;

    // Public Methods
    // =========================================================================

    /**
     * @param string $path
     *
     * @return File
     */
    public function __construct($path)
    {
        clearstatcache();
        $this->path = $path;
    }

    /**
     * @param boolean $includeExtension
     *
     * @return mixed
     */
    public function getFilename($includeExtension = true)
    {
        if ($includeExtension) {
            if (!$this->_filename) {
                $this->_filename = Io::getFilename($this->getRealPath(), $includeExtension);
            }

            return $this->_filename;
        }

        if (!$this->_baseName) {
            $this->_baseName = Io::getFilename($this->getRealPath(), $includeExtension);
        }

        return $this->_baseName;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        if (!$this->_extension) {
            $this->_extension = Io::getExtension($this->getRealPath());
        }

        return $this->_extension;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        if (!$this->_mimeType) {
            $this->_mimeType = Io::getMimeType($this->getRealPath());
        }

        return $this->_mimeType;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        if (!$this->_size) {
            $this->_size = Io::getFileSize($this->getRealPath());
        }

        return $this->_size;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        if (!$this->_isEmpty) {
            $this->_isEmpty = Io::isFileEmpty($this->getRealPath());
        }

        return $this->_isEmpty;
    }

    /**
     * @param boolean $array
     *
     * @return mixed
     */
    public function getContents($array = false)
    {
        if ($array) {
            if (!$this->_arrayContents) {
                $this->_arrayContents = Io::getFileContents($this->getRealPath(), $array);
            }

            return $this->_arrayContents;
        }

        if (!$this->_stringContents) {
            $this->_stringContents = Io::getFileContents($this->getRealPath(), $array);
        }

        return $this->_stringContents;
    }

    /**
     * @param $contents
     * @param $append
     *
     * @return boolean
     */
    public function write($contents, $append)
    {
        if (!Io::writeToFile($this->getRealPath(), $contents, false, $append)) {
            return false;
        }

        return true;
    }

    /**
     * @param $destination
     *
     * @return boolean
     */
    public function copy($destination)
    {
        if (!Io::copyFile($this->getRealPath(), $destination)) {
            return false;
        }

        return true;
    }

    /**
     * @return boolean
     */
    public function clear()
    {
        if (!Io::clearFile($this->getRealPath())) {
            return false;
        }

        return true;
    }

    /**
     * @return boolean
     */
    public function delete()
    {
        if (!Io::deleteFile($this->getRealPath())) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getMD5()
    {
        if (!$this->_md5) {
            $this->_md5 = Io::getFileMD5($this->getRealPath());
        }

        return $this->_md5;
    }

    /**
     * @return boolean
     */
    public function touch()
    {
        if (!Io::touch($this->getRealPath())) {
            return false;
        }

        return true;
    }
}
