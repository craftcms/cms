<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Tasks;

use CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageMetadata;
use CraftCms\Cms\Support\Facades\I18N;
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
    /** @var WeakMap<Asset, array<string, TranslatedSite>>|null */
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

    /**
     * Fills in missing alt text with the image’s embedded alt text, or else its description, which is often written
     * to describe the image too.
     */
    public static function valuesFromMetadata(Asset $asset, ImageMetadata $metadata): array
    {
        $alt = $metadata->altText ?? $metadata->description;

        return ($asset->alt ?? '') === '' && $alt !== null ? ['alt' => $alt] : [];
    }

    public static function needed(Asset $asset, bool $overwrite): bool
    {
        $groups = self::groups($asset);

        return $overwrite
            ? $groups !== []
            : array_any($groups, static fn (TranslatedSite $group): bool => ($group->value ?? '') === '');
    }

    public static function instructions(Asset $asset): string
    {
        $languages = array_map(
            static fn (string $property, TranslatedSite $group): string => "- $property: ".I18N::getLocaleById($group->language)->getDisplayName('en')." ($group->language)",
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

            if ($alt !== '' && ($overwrite || ($group->value ?? '') === '')) {
                $changes->setForSite($group->siteId, 'alt', $alt);
            }
        }
    }

    /**
     * Returns the asset’s sites grouped by their alt text translation key, indexed by the group’s response property.
     *
     * @return array<string, TranslatedSite>
     */
    private static function groups(Asset $asset): array
    {
        self::$groups ??= new WeakMap;

        return self::$groups[$asset] ??= TranslatedSite::groups(
            $asset,
            static fn (Asset $instance): string => $instance->getAltTranslationKey(),
            static fn (Asset $instance): ?string => $instance->alt,
        );
    }
}
