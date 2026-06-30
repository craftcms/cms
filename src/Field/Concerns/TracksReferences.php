<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Operations\ElementRefs;

trait TracksReferences
{
    public function getReferenceTargetIds(ElementInterface $element): array
    {
        $value = $this->serializeValue($element->getFieldValue($this->handle), $element);

        return is_string($value) ? $this->elementRefs()->targetIds($value, $element->siteId) : [];
    }

    public function replaceReferences(ElementInterface $element, array $oldTargetIds, int $newTargetId): bool
    {
        $value = $this->serializeValue($element->getFieldValue($this->handle), $element);

        if (! is_string($value)) {
            return false;
        }

        $newValue = $this->elementRefs()->replaceTargetRefs($value, $oldTargetIds, $newTargetId, $element->siteId);

        if ($newValue === $value) {
            return false;
        }

        $element->setFieldValue($this->handle, $newValue);

        return true;
    }

    private function elementRefs(): ElementRefs
    {
        return app(ElementRefs::class);
    }
}
