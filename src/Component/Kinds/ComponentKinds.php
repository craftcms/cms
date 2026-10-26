<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Kinds;

use CraftCms\Cms\Component\TypeRegistry;
use Illuminate\Container\Attributes\Singleton;
use Override;

/**
 * The registry of component kinds, which describe extendable component types and their settings.
 *
 * @extends TypeRegistry<ComponentKind>
 *
 * @since 6.0.0
 */
#[Singleton]
class ComponentKinds extends TypeRegistry
{
    protected const string CONTRACT = ComponentKind::class;

    protected const array DEFAULT_TYPES = [
        FieldKind::class,
        WorkflowStageKind::class,
        AssetTransformDriverKind::class,
        FieldLayoutElementKind::class,
        ConditionRuleKind::class,
    ];

    /** Returns the kind with the given handle. */
    public function get(string $handle): ?ComponentKind
    {
        $type = $this->typeByIdentity($handle);

        return $type !== null ? app($type) : null;
    }

    /** @return list<string> */
    public function handles(): array
    {
        return $this->typesByIdentity()->keys()->all();
    }

    #[Override]
    protected function identity(string $type): string
    {
        return $type::handle();
    }
}
