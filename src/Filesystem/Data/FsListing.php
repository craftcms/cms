<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Traits\Macroable;

final class FsListing extends Component
{
    use Macroable;

    public string $dirname {
        get => $this->_dirname;
        set(string $value) {
            $this->_dirname = ltrim($value, './');
        }
    }

    public string $basename {
        get => $this->_basename;
        set(string $value) {
            $this->_basename = $value;
        }
    }

    /** @phpstan-var 'file'|'dir' */
    public string $type {
        get => $this->_type;
        set(string $value) {
            $this->_type = $value;
        }
    }

    public ?int $fileSize {
        get => $this->_type !== 'dir' ? $this->_fileSize : null;
        set(?int $value) {
            $this->_fileSize = $value;
        }
    }

    public ?int $dateModified {
        get => $this->_dateModified;
        set(?int $value) {
            $this->_dateModified = $value;
        }
    }

    public string $uri {
        get => ($this->_dirname !== '' ? "$this->_dirname/" : '').$this->_basename;
    }

    public bool $isDir {
        get => $this->_type === 'dir';
    }

    private string $_dirname = '';

    private string $_basename = '';

    /** @phpstan-var 'file'|'dir' */
    private string $_type = 'file';

    private ?int $_fileSize = null;

    private ?int $_dateModified = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function getDirname(): string
    {
        return $this->dirname;
    }

    public function getBasename(): string
    {
        return $this->basename;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function getDateModified(): ?int
    {
        return $this->dateModified;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAdjustedUri(string $string = '', string $type = 'sub'): string
    {
        $uri = $this->uri;

        if ($type === 'sub') {
            if (str_starts_with($uri, $string)) {
                $uri = substr($uri, strlen($string));
            }
        } else {
            $uri = ($string !== '' ? Str::finish($string, '/') : '').$uri;
        }

        return $uri;
    }

    public function getIsDir(): bool
    {
        return $this->isDir;
    }
}
