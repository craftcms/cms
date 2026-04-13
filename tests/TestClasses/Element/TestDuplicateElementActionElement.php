<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Str;
use Override;

trait UsesLocalizedElementsOverride
{
    /** @var ElementInterface[] */
    public array $elements = [];

    #[Override]
    public function all($columns = ['*']): array
    {
        return $this->elements;
    }
}

class TestDuplicateElementActionElement extends Element
{
    public bool $returnUriErrorOnFirstValidate = false;

    public bool $throwSlugErrorWhenValidatingSlug = false;

    public array $supportedSitesOverride = [];

    public array $localizedElements = [];

    public ?ElementInterface $canonicalOverride = null;

    public int $validateCallCount = 0;

    public bool $useMockFieldValues = false;

    /** @var array<string, mixed> */
    public array $mockFieldValues = [];

    public ?string $forcedValidationAttribute = null;

    public ?string $forcedValidationMessage = null;

    /** @var string[] */
    public array $mockDirtyFields = [];

    public static function create(array $attributes = []): self
    {
        $element = new self;
        $element->id = 100;
        $element->uid = Str::uuid()->toString();
        $element->siteId = Site::first()->id;
        $element->title = 'Source title';
        $element->slug = 'source-title';
        $element->enabled = true;
        $element->dateCreated = now();
        $element->dateUpdated = now();

        foreach ($attributes as $key => $value) {
            $element->{$key} = $value;
        }

        return $element;
    }

    #[Override]
    public static function displayName(): string
    {
        return 'Duplicate Action Test Element';
    }

    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    #[Override]
    public static function hasUris(): bool
    {
        return true;
    }

    #[Override]
    public static function isLocalized(): bool
    {
        return true;
    }

    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }

    #[Override]
    public function getSupportedSites(): array
    {
        if ($this->supportedSitesOverride !== []) {
            return $this->supportedSitesOverride;
        }

        return parent::getSupportedSites();
    }

    #[Override]
    public function getLocalizedQuery(): ElementQuery
    {
        $query = new class(static::class) extends ElementQuery
        {
            use UsesLocalizedElementsOverride;
        };

        $query->elements = $this->localizedElements;

        return $query;
    }

    #[Override]
    public function getCanonical(bool $anySite = false): ElementInterface
    {
        return $this->canonicalOverride ?? $this;
    }

    #[Override]
    public function validate($attributeNames = null, $clearErrors = true, bool $throw = false): bool
    {
        $this->validateCallCount++;

        if ($clearErrors) {
            foreach (array_keys($this->errors()->getMessages()) as $attribute) {
                $this->errors()->forget($attribute);
            }
        }

        if ($attributeNames === ['slug'] && $this->throwSlugErrorWhenValidatingSlug) {
            $this->errors()->add('slug', 'Slug is invalid.');

            return false;
        }

        if ($this->returnUriErrorOnFirstValidate && $this->validateCallCount === 1) {
            $this->errors()->add('uri', 'URI is already taken.');

            return false;
        }

        if ($this->forcedValidationAttribute !== null) {
            $this->errors()->add($this->forcedValidationAttribute, $this->forcedValidationMessage ?? 'Validation failed.');

            return false;
        }

        return $this->errors()->isEmpty();
    }

    #[Override]
    public function getFieldValues(?array $fieldHandles = null): array
    {
        if (! $this->useMockFieldValues) {
            return parent::getFieldValues($fieldHandles);
        }

        if ($fieldHandles === null) {
            return $this->mockFieldValues;
        }

        return array_filter(
            $this->mockFieldValues,
            fn (string $handle) => in_array($handle, $fieldHandles, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    #[Override]
    public function getFieldValue(string $fieldHandle): mixed
    {
        if (! $this->useMockFieldValues) {
            return parent::getFieldValue($fieldHandle);
        }

        return $this->mockFieldValues[$fieldHandle] ?? null;
    }

    #[Override]
    public function setFieldValue(string $fieldHandle, mixed $value): void
    {
        if (! $this->useMockFieldValues) {
            parent::setFieldValue($fieldHandle, $value);

            return;
        }

        $this->mockFieldValues[$fieldHandle] = $value;
    }

    #[Override]
    public function getDirtyFields(): array
    {
        if (! $this->useMockFieldValues) {
            return parent::getDirtyFields();
        }

        return $this->mockDirtyFields;
    }

    #[Override]
    public function setDirtyFields(array $fieldHandles, bool $merge = true): void
    {
        if (! $this->useMockFieldValues) {
            parent::setDirtyFields($fieldHandles, $merge);

            return;
        }

        $this->mockDirtyFields = $merge
            ? [...$this->mockDirtyFields, ...$fieldHandles]
            : $fieldHandles;
    }
}
