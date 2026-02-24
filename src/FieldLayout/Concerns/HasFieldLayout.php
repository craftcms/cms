<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Concerns;

use craft\base\ElementInterface;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;
use RuntimeException;
use Throwable;

trait HasFieldLayout
{
    /**
     * @var string|null The attribute on the owner that holds the field layout ID
     */
    public ?string $idAttribute = null;

    /**
     * @var int|string|callable|null The field layout ID, or the name of a method on the owner that will return it, or a callback function that will return it
     */
    private $fieldLayoutIdConfig;

    /**
     * @var FieldLayout|null The field layout associated with the owner
     */
    private ?FieldLayout $fieldLayout = null;

    /**
     * @return class-string<ElementInterface> The element type that the field layout will be associated with
     */
    abstract public function getElementType(): string;

    /**
     * Returns the owner's field layout ID.
     *
     * @throws RuntimeException if the field layout ID could not be determined
     */
    public function getFieldLayoutId(): int
    {
        if (! isset($this->fieldLayoutIdConfig) && ! isset($this->idAttribute)) {
            $this->idAttribute = 'fieldLayoutId';
        }

        if (is_int($this->fieldLayoutIdConfig)) {
            return $this->fieldLayoutIdConfig;
        }

        if (isset($this->idAttribute)) {
            if ($this->canReadFieldLayoutIdAttribute($this->idAttribute)) {
                $id = $this->{$this->idAttribute};
            } elseif ($this->idAttribute === 'fieldLayoutId') {
                // Keep backwards compatibility with providers that don't declare a fieldLayoutId property.
                $id = $this->fieldLayoutIdConfig;
            }
        } elseif (is_callable($this->fieldLayoutIdConfig)) {
            $id = call_user_func($this->fieldLayoutIdConfig);
        } elseif (is_string($this->fieldLayoutIdConfig)) {
            $id = $this->{$this->fieldLayoutIdConfig}();
        }

        if (! isset($id) || ! is_numeric($id)) {
            throw new RuntimeException('Unable to determine the field layout ID for '.$this::class.'.');
        }

        return $this->fieldLayoutIdConfig = (int) $id;
    }

    /**
     * Sets the owner's field layout ID.
     */
    public function setFieldLayoutId(callable|int|string|null $id): void
    {
        $this->fieldLayoutIdConfig = $id;
    }

    private function canReadFieldLayoutIdAttribute(string $attribute): bool
    {
        if (property_exists($this, $attribute)) {
            return true;
        }

        try {
            return isset($this->{$attribute}) || $this->{$attribute} === null;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Returns the owner's field layout.
     *
     * @throws RuntimeException if the configured field layout ID is invalid
     */
    public function getFieldLayout(): FieldLayout
    {
        if (isset($this->fieldLayout)) {
            return $this->fieldLayout;
        }

        try {
            $id = $this->getFieldLayoutId();
        } catch (RuntimeException) {
            $id = null;
        }

        if ($id) {
            $fieldLayout = Fields::getLayoutById($id, true);
            if (! $fieldLayout) {
                throw new RuntimeException('Invalid field layout ID: '.$id);
            }
        } else {
            $fieldLayout = new FieldLayout([
                'type' => $this->getElementType(),
            ]);
        }

        if ($this instanceof EntryType) {
            // Set the provider to the original entry type, so it uses the original provider handle
            // (see https://github.com/craftcms/cms/pull/17213)
            // todo: FieldLayoutProviderInterface could define a getProvider() method
            $fieldLayout->provider = $this->original ?? $this;
            /** @phpstan-ignore instanceof.alwaysFalse */
        } elseif ($this instanceof FieldLayoutProviderInterface) {
            $fieldLayout->provider = $this;
        }

        return $this->fieldLayout = $fieldLayout;
    }

    /**
     * Sets the owner's field layout.
     */
    public function setFieldLayout(FieldLayout $fieldLayout): void
    {
        $this->fieldLayout = $fieldLayout;
    }

    /**
     * Returns the custom fields associated with the owner's field layout.
     *
     * @return FieldInterface[]
     */
    public function getCustomFields(): array
    {
        return $this->getFieldLayout()->getCustomFields();
    }
}
