<?php

declare(strict_types=1);

namespace craft\fs\bridge;

use League\Flysystem\Config;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;

class LegacyFsPathPrefixedAdapter extends PathPrefixedAdapter
{
    public function getUrl(string $path): string
    {
        return $this->publicUrl($path, new Config());
    }
}
