<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Tasks;

use CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use WeakMap;

use function CraftCms\Cms\t;

/**
 * Writes alt text for images, in the language of each site the asset’s alt text is translated for.
 *
 * Sites that share alt text, per the volume’s alt text translation method, get one alt text, written in the language of
 * the site the asset is being analyzed in, or of the first of them. Saving it there propagates it to the others.
 *
 * @since 6.0.0
 */
class GenerateAltText extends ImageAnalysisTask
{
    /** @var WeakMap<Asset, array<string, AltTextSite>>|null */
    private static ?WeakMap $groups = null;

    public static function handle(): string
    {
        return 'craft:altText';
    }

    public static function label(): string
    {
        return t('Generate alt text');
    }

    public static function description(): ?string
    {
        return t('Writes alternative text for images, in each site’s language, when they’re uploaded or analyzed.');
    }

    public static function needed(Asset $asset, bool $overwrite): bool
    {
        $groups = self::groups($asset);

        return $overwrite
            ? $groups !== []
            : array_any($groups, static fn (AltTextSite $group): bool => ($group->alt ?? '') === '');
    }

    public static function instructions(Asset $asset): string
    {
        $languages = array_map(
            static fn (string $property, AltTextSite $group): string => "- $property: ".I18N::getLocaleById($group->language)->getDisplayName('en')." ($group->language)",
            array_keys(self::groups($asset)),
            self::groups($asset),
        );

        return implode("\n", [
            'Write alt text for the image: a concise description of what it shows, for people who can’t see it. Keep it under 125 characters if you can. Describe what matters about the image, rather than every detail. Don’t start with “Image of” or “Photo of”, and don’t mention that it’s an image unless that matters, such as for a screenshot or a painting. Include any text that’s important to understanding the image.',
            '',
            'Write it in each of these languages, as a property of this task’s response:',
            ...$languages,
        ]);
    }

    public static function schema(JsonSchema $schema, Asset $asset): array
    {
        $properties = [];

        foreach (self::groups($asset) as $property => $group) {
            $properties[$property] = $schema->string()->description("Alt text in $group->language.")->required();
        }

        return $properties;
    }

    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void
    {
        foreach (self::groups($asset) as $property => $group) {
            $alt = is_string($result[$property] ?? null) ? trim($result[$property]) : '';

            if ($alt !== '' && ($overwrite || ($group->alt ?? '') === '')) {
                $changes->setForSite($group->siteId, 'alt', $alt);
            }
        }
    }

    /**
     * Returns the asset’s sites grouped by their alt text translation key, indexed by the group’s response property.
     *
     * @return array<string, AltTextSite>
     */
    private static function groups(Asset $asset): array
    {
        self::$groups ??= new WeakMap;

        if (isset(self::$groups[$asset])) {
            return self::$groups[$asset];
        }

        $instances = $asset->id
            ? Asset::find()->id($asset->id)->site('*')->status(null)->all()
            : [];
        $instances = array_filter($instances, static fn (Asset $instance): bool => $instance->siteId !== $asset->siteId);
        $groups = [];

        // The asset’s own site comes first, so its group is written in its language
        foreach ([$asset, ...$instances] as $instance) {
            $key = $instance->getAltTranslationKey();
            $site = Sites::getSiteById($instance->siteId, true);

            if (! isset($groups[$key]) && $site !== null) {
                $groups[$key] = new AltTextSite($instance->siteId, $site->language, $instance->alt);
            }
        }

        $indexed = [];

        foreach ($groups as $group) {
            $indexed["site$group->siteId"] = $group;
        }

        return self::$groups[$asset] = $indexed;
    }
}
