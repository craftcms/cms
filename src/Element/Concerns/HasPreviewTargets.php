<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Element\Events\RegisterPreviewTargets;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Collection;

use function CraftCms\Cms\renderObjectTemplate;
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
     * @var bool Whether the element is currently being previewed.
     */
    public bool $previewing = false;

    /**
     * Returns the element's preview targets.
     *
     * @return array The preview targets, each containing at minimum a `label` and `url` key.
     */
    public function getPreviewTargets(): array
    {
        event($event = new RegisterPreviewTargets($this, $this->previewTargets()));

        // Normalize the targets
        return new Collection($event->previewTargets)
            ->map(function (array $previewTarget) {
                if (isset($previewTarget['urlFormat'])) {
                    $url = trim(renderObjectTemplate(Env::parse($previewTarget['urlFormat']), $this));

                    if ($url !== '') {
                        $previewTarget['url'] = $url;
                        unset($previewTarget['urlFormat']);
                    }
                }

                if (! isset($previewTarget['url'])) {
                    // No URL, no preview target
                    return null;
                }

                $previewTarget['url'] = Url::siteUrl($previewTarget['url'], siteId: $this->siteId);

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
