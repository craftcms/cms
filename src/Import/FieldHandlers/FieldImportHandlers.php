<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\FieldHandlers;

use CraftCms\Cms\Asset\Import\AssetsFieldImportHandler;
use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers field import handlers, keyed by the field class each one handles.
 *
 * Plugins may register handlers from their service provider:
 *
 * ```php
 * public function boot(FieldImportHandlers $handlers): void
 * {
 *     $handlers->register(MyFieldImportHandler::class);
 * }
 * ```
 *
 * A field class has at most one handler. To replace a handler, remove it before registering another for its field class.
 *
 * @extends TypeRegistry<FieldImportHandlerInterface>
 *
 * @since 6.0.0
 */
#[Singleton]
class FieldImportHandlers extends TypeRegistry
{
    protected const string CONTRACT = FieldImportHandlerInterface::class;

    protected const array DEFAULT_TYPES = [
        AssetsFieldImportHandler::class,
    ];

    /**
     * Returns the registered handlers, keyed by the field class they handle.
     *
     * @return array<class-string<FieldInterface>, class-string<FieldImportHandlerInterface>>
     */
    public function all(): array
    {
        return $this->typesByIdentity()->all();
    }

    /**
     * Returns the handler registered for the field’s class, or for its nearest parent class that has one.
     *
     * @param  FieldInterface  $field  The field.
     */
    public function findFor(FieldInterface $field): ?FieldImportHandlerInterface
    {
        for ($class = $field::class; $class !== false; $class = get_parent_class($class)) {
            $type = $this->typeByIdentity($class);

            if ($type !== null) {
                return app($type);
            }
        }

        return null;
    }

    /** @param class-string<FieldImportHandlerInterface> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return $type::fieldClass();
    }
}
