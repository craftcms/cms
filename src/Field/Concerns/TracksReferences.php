<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Operations\ElementRefs;
use CraftCms\Cms\Support\Str;

trait TracksReferences
{
    public function getReferenceTargetIds(ElementInterface $element): array
    {
        $value = $this->referenceText($element);

        return $value !== null ? $this->elementRefs()->targetIds($value, $element->siteId) : [];
    }

    public function replaceReferences(ElementInterface $element, array $oldTargetIds, int $newTargetId): bool
    {
        $value = $this->referenceText($element);

        if ($value === null) {
            return false;
        }

        $newValue = $this->elementRefs()->replaceTargetRefs($value, $oldTargetIds, $newTargetId, $element->siteId);

        if ($newValue === $value) {
            return false;
        }

        $element->setFieldValue($this->handle, $this->serializeValue($newValue, $element));

        return true;
    }

    /**
     * Returns the field's text as it was written, with the emoji shortcode escaping that `serializeValue()` applies
     * undone, so ref tags whose refs look like shortcodes, such as `{entry:100:url}`, still match.
     */
    private function referenceText(ElementInterface $element): ?string
    {
        $value = $this->serializeValue($element->getFieldValue($this->handle), $element);

        return is_string($value) ? Str::unescapeShortcodes(Str::shortcodesToEmoji($value)) : null;
    }

    private function elementRefs(): ElementRefs
    {
        return app(ElementRefs::class);
    }
}
