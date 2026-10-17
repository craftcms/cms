<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Import;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Import\FieldHandlers\FieldImportHandlerInterface;
use CraftCms\Cms\Import\Importers\BaseImporter;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * A field import handler for plain text fields that prefixes each incoming string and queues an after-item callback,
 * recording what it was called for.
 */
class TestFieldImportHandler implements FieldImportHandlerInterface
{
    /**
     * @var list<string> The hooks called so far, in order.
     */
    public static array $calls = [];

    #[Override]
    public static function fieldClass(): string
    {
        return PlainText::class;
    }

    #[Override]
    public function normalizeValue(FieldInterface $field, mixed $value, BaseImporter $importer, ?ElementInterface $rootOwner = null, array $importSettings = []): mixed
    {
        self::$calls[] = 'normalizeValue';

        $importer->afterItemImported(function (ElementInterface|Model|null $item) {
            self::$calls[] = $item?->id ? 'afterItemImported' : 'afterItemImported without an ID';
        });

        return is_string($value) ? "normalized: $value" : $value;
    }

    #[Override]
    public function mappingSettings(FieldInterface $field): array
    {
        return [];
    }
}
