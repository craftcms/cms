<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Tasks;

use Closure;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\Facades\Sites;

/**
 * A site that a task writes a translatable attribute for, such as alt text, standing in for the sites that share it.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class TranslatedSite
{
    public function __construct(
        public int $siteId,
        public string $language,
        public ?string $value,
    ) {}

    /**
     * Returns the asset’s sites grouped by an attribute’s translation key, indexed by each group’s response property.
     *
     * The asset’s own site comes first, so its group is written in its language, and saving the attribute there
     * propagates it to the others in the group.
     *
     * @param  Closure(Asset): string  $translationKey
     * @param  Closure(Asset): ?string  $value
     * @return array<string, self>
     */
    public static function groups(Asset $asset, Closure $translationKey, Closure $value): array
    {
        $instances = $asset->id
            ? Asset::find()->id($asset->id)->site('*')->status(null)->all()
            : [];
        $instances = array_filter($instances, static fn (Asset $instance): bool => $instance->siteId !== $asset->siteId);
        $groups = [];

        foreach ([$asset, ...$instances] as $instance) {
            $key = $translationKey($instance);
            $site = Sites::getSiteById($instance->siteId, true);

            if (! isset($groups[$key]) && $site !== null) {
                $groups[$key] = new self($instance->siteId, $site->language, $value($instance));
            }
        }

        $indexed = [];

        foreach ($groups as $group) {
            $indexed["site$group->siteId"] = $group;
        }

        return $indexed;
    }
}
