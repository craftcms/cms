<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class SetStatus extends ElementAction
{
    public const string ENABLED = 'enabled';

    public const string DISABLED = 'disabled';

    /**
     * @var string|null The status elements should be set to
     */
    public ?string $status = null;

    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Set Status');
    }

    #[\Override]
    public function getRules(): array
    {
        return [
            'status' => ['required', Rule::in([self::ENABLED, self::DISABLED])],
        ];
    }

    public function getTriggerHtml(): ?string
    {
        HtmlStack::jsWithVars(fn ($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        validateSelection: (selectedItems, elementIndex) => {
            for (let i = 0; i < selectedItems.length; i++) {
                const element = selectedItems.eq(i).find('.element');
                if (!Garnish.hasAttr(element, 'data-savable') || Garnish.hasAttr(element, 'data-disallow-status')) {
                    return false;
                }
            }
            return true;
        },
    })
})();
JS, [static::class]);

        return template('_components/elementactions/SetStatus/trigger');
    }

    #[\Override]
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $this->elementType;
        $isLocalized = $elementType::isLocalized() && Sites::isMultiSite();

        $elements = $query->all();
        $failCount = 0;

        foreach ($elements as $element) {
            if (! Gate::check('save', $element)) {
                continue;
            }

            switch ($this->status) {
                case self::ENABLED:
                    // Skip if there's nothing to change
                    if ($element->enabled && $element->getEnabledForSite()) {
                        continue 2;
                    }

                    $element->enabled = true;
                    $element->setEnabledForSite(true);
                    $element->setScenario(Element::SCENARIO_LIVE);
                    break;

                case self::DISABLED:
                    // Is this a multi-site element?
                    if ($isLocalized && count($element->getSupportedSites()) !== 1) {
                        // Skip if there's nothing to change
                        if (! $element->getEnabledForSite()) {
                            continue 2;
                        }
                        $element->setEnabledForSite(false);
                    } else {
                        // Skip if there's nothing to change
                        if (! $element->enabled) {
                            continue 2;
                        }
                        $element->enabled = false;
                    }
                    break;
            }

            if (Elements::saveElement($element) === false) {
                // Validation error
                $failCount++;
            }
        }

        // Did all of them fail?
        if ($failCount === count($elements)) {
            if (count($elements) === 1) {
                $this->setMessage(t('Could not update status due to a validation error.'));
            } else {
                $this->setMessage(t('Could not update statuses due to validation errors.'));
            }

            return false;
        }

        if ($failCount !== 0) {
            $this->setMessage(t('Status updated, with some failures due to validation errors.'));
        } else {
            if (count($elements) === 1) {
                $this->setMessage(t('Status updated.'));
            } else {
                $this->setMessage(t('Statuses updated.'));
            }
        }

        return true;
    }
}
