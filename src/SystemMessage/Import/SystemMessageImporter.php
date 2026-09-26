<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Import;

use CraftCms\Cms\Import\Importers\ModelImporter;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use Override;

use function CraftCms\Cms\t;

/**
 * Imports data into SystemMessage models.
 */
class SystemMessageImporter extends ModelImporter
{
    public function __construct(?array $config = null)
    {
        parent::__construct($config);

        // match incoming data on key and language combination
        $this->matchCriteria = ['key' => 'key', 'language' => 'language'];
    }

    #[Override]
    public static function targetClass(): string
    {
        return SystemMessage::class;
    }

    #[Override]
    public static function displayName(): string
    {
        return t('System Messages');
    }

    /**
     * Convenience factory returning a new instance.
     */
    public static function create(): self
    {
        return new self;
    }
}
