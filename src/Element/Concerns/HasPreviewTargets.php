<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use Craft;
use craft\events\RegisterPreviewTargetsEvent;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Support\Env;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

/**
 * Provides preview target functionality for elements.
 *
 * This trait handles methods related to defining and retrieving preview targets
 * for elements, which are used in the Control Panel for live preview functionality.
 *
 * @internal
 */
trait HasPreviewTargets
{
    /**
     * @event RegisterPreviewTargetsEvent The event that is triggered when registering the element's preview targets.
     *
     * @since 3.2.0
     */
    public const EVENT_REGISTER_PREVIEW_TARGETS = 'registerPreviewTargets';

    /**
     * Returns the element's preview targets.
     *
     * @return array The preview targets, each containing at minimum a `label` and `url` key.
     */
    public function getPreviewTargets(): array
    {
        $previewTargets = $this->previewTargets();

        // Fire a 'registerPreviewTargets' event
        if ($this->hasEventHandlers(self::EVENT_REGISTER_PREVIEW_TARGETS)) {
            $event = new RegisterPreviewTargetsEvent(['previewTargets' => $previewTargets]);
            $this->trigger(self::EVENT_REGISTER_PREVIEW_TARGETS, $event);
            $previewTargets = $event->previewTargets;
        }

        // Normalize the targets
        return new Collection($previewTargets)
            ->map(function (array $previewTarget) {
                if (isset($previewTarget['urlFormat'])) {
                    $url = trim(Craft::$app->getView()->renderObjectTemplate(Env::parse($previewTarget['urlFormat']), $this));

                    if ($url !== '') {
                        $previewTarget['url'] = $url;
                        unset($previewTarget['urlFormat']);
                    }
                }

                if (! isset($previewTarget['url'])) {
                    // No URL, no preview target
                    return null;
                }

                $previewTarget['url'] = UrlHelper::siteUrl($previewTarget['url'], siteId: $this->siteId);

                if (! isset($previewTarget['refresh'])) {
                    $previewTarget['refresh'] = true;
                }

                return $previewTarget;
            })
            ->filter()
            ->all();
    }

    /**
     * Returns the additional locations that should be available for previewing the element, besides its primary [[getUrl()|URL]].
     *
     * Each target should be represented by a sub-array with `'label'` and `'url'` keys.
     *
     * @see getPreviewTargets()
     * @since 3.2.0
     */
    protected function previewTargets(): array
    {
        $previewTargets = [];

        if ($url = $this->getUrl()) {
            $previewTargets[] = [
                'label' => t('Primary {type} page', [
                    'type' => static::lowerDisplayName(),
                ]),
                'url' => $url,
            ];
        }

        return $previewTargets;
    }
}
