<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\icons;

use Craft;
use craft\helpers\FileHelper;

/**
 * DirectoryIconSet is an icon set made up of the SVG files within a directory.
 *
 * Each file’s name (sans `.svg` extension) becomes its icon name.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class DirectoryIconSet extends IconSet
{
    /**
     * @var string The path to the directory, which can begin with an alias.
     */
    public string $path;

    /**
     * @var array<string,string> The SVG file paths, indexed by icon name
     * @see files()
     */
    private array $_files;

    /**
     * @inheritdoc
     */
    public function getIcons(): array
    {
        return array_map('strval', array_keys($this->files()));
    }

    /**
     * @inheritdoc
     */
    public function getSvg(string $icon): ?string
    {
        $file = $this->files()[$icon] ?? null;
        return $file ? file_get_contents($file) : null;
    }

    /**
     * @return array<string,string>
     */
    private function files(): array
    {
        if (!isset($this->_files)) {
            $this->_files = [];
            $dir = Craft::getAlias($this->path);
            if (is_dir($dir)) {
                $files = FileHelper::findFiles($dir, [
                    'only' => ['*.svg'],
                    'caseSensitive' => false,
                    'recursive' => false,
                ]);
                foreach ($files as $file) {
                    $this->_files[pathinfo($file, PATHINFO_FILENAME)] = $file;
                }
                ksort($this->_files, SORT_NATURAL | SORT_FLAG_CASE);
            }
        }

        return $this->_files;
    }
}
