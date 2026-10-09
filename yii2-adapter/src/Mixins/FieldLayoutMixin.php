<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Yii2Adapter\FieldLayout\FieldLayoutForm;
use InvalidArgumentException;

/**
 * Craft 5's `FieldLayout` methods, mixed onto the Craft 6 class.
 *
 * These used to live on a `CraftCms\Yii2Adapter\FieldLayout\FieldLayout` subclass
 * that `craft\models\FieldLayout` was aliased to. That made the legacy name a
 * *subclass* of what Craft 6 actually builds, so every legacy signature written
 * against it — `getFieldLayout(): ?FieldLayout` being the common one — rejected
 * the plain core layouts that `Fields::getLayoutByType()` returns:
 *
 *     Return value must be of type ?craft\models\FieldLayout,
 *     CraftCms\Cms\FieldLayout\FieldLayout returned
 *
 * As macros on the core class there's only one layout type, so the variance
 * problem can't arise. Nothing underneath needed the subclass: both
 * {@see FieldLayoutForm::fromLayout()} and `FieldLayoutCompiler::compile()`
 * already take the core type.
 */
class FieldLayoutMixin
{
    /**
     * Craft 5's `createForm()`.
     *
     * The `namespace` key is the only form config Craft 6 can still honour;
     * anything else described rendering behaviour that no longer exists, so it's
     * rejected rather than silently ignored.
     */
    public function createForm(): Closure
    {
        return function(?ElementInterface $element = null, bool $static = false, array $config = []): FieldLayoutForm {
            Deprecator::log('FieldLayout-createForm', 'Calling ->createForm on a FieldLayout is deprecated. Craft 6 UI rendering should be used instead.');

            $namespace = $config['namespace'] ?? null;
            unset($config['namespace']);

            if ($config !== []) {
                throw new InvalidArgumentException(sprintf(
                    'Legacy FieldLayout form configuration [%s] is incompatible with UI rendering.',
                    implode(', ', array_keys($config)),
                ));
            }

            /** @phpstan-ignore-next-line */
            return FieldLayoutForm::fromLayout($this, $element, $static, $namespace ?? []);
        };
    }
}
