<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\IconSets;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\File;
use Override;

/**
 * DirectoryIconSet is an icon set made up of the SVG files within a directory.
 *
 * Each file’s name (sans `.svg` extension) becomes its icon name.
 *
 * @since 6.0.0
 */
class DirectoryIconSet extends IconSet
{
    /**
     * @var array<string, string> The SVG file paths, indexed by icon name
     *
     * @see files()
     */
    private array $files;

    public function __construct(
        string $handle,
        string $name,
        /** The path to the directory, which can begin with an alias. */
        public string $path,
    ) {
        parent::__construct($handle, $name);
    }

    #[Override]
    public function getIcons(): array
    {
        return array_map(strval(...), array_keys($this->files()));
    }

    #[Override]
    public function getSvg(string $icon): ?string
    {
        $file = $this->files()[$icon] ?? null;

        return $file ? (string) file_get_contents($file) : null;
    }

    /**
     * @return array<string, string>
     */
    private function files(): array
    {
        if (isset($this->files)) {
            return $this->files;
        }

        $this->files = [];
        $dir = Aliases::get($this->path);

        if (! is_dir($dir)) {
            return $this->files;
        }

        foreach (File::files($dir) as $file) {
            if (strtolower($file->getExtension()) === 'svg') {
                $this->files[$file->getBasename('.'.$file->getExtension())] = $file->getPathname();
            }
        }

        ksort($this->files, SORT_NATURAL | SORT_FLAG_CASE);

        return $this->files;
    }
}
