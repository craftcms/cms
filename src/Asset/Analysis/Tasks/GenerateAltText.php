<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Tasks;

use CraftCms\Cms\Ai\Ai;
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
     * Fills in missing alt text with the alt text embedded in the image, in each site’s language if the file has it.
     *
     * The asset’s own site falls back to the file’s default alt text, and then, if AI alt text isn’t available, to its
     * description, which is often written to describe the image too. With AI, the description is context for the
     * model instead, since it’s often a caption rather than a description.
     *
     * Other sites only get alt text written in their language. It also replaces alt text that’s just a copy of the
     * file’s alt text in another language, as new assets are given in every site.
     */
    public static function fillFromMetadata(Asset $asset, ImageMetadata $metadata, ImageAnalysisChanges $changes): void
    {
        $fromFile = array_filter([$metadata->altText, ...array_values($metadata->altTextTranslations)]);
        $fallback = $metadata->altText ?? (app(Ai::class)->available(self::handle()) ? null : $metadata->description);

        foreach (self::groups($asset, cached: false) as $group) {
            $own = $metadata->altTextIn($group->language);

            if (($group->value ?? '') === '') {
                $alt = $own ?? ($group->siteId === $asset->siteId ? $fallback : null);
            } elseif ($own !== null && $own !== $group->value && in_array($group->value, $fromFile, true)) {
                $alt = $own;
            } else {
                continue;
            }

            if ($alt !== null) {
                $changes->setForSite($group->siteId, 'alt', $alt);
            }
        }
    }

    public static function needed(Asset $asset, bool $overwrite): bool
    {
        $groups = self::groups($asset);

        return $overwrite
            ? $groups !== []
            : array_any($groups, static fn (TranslatedSite $group): bool => ($group->value ?? '') === '');
    }

    public static function instructions(Asset $asset, bool $overwrite): string
    {
        $lines = [
            'Write alt text for the image: a concise description of what it shows, for people who can’t see it. Keep it under 125 characters if you can. Describe what matters about the image, rather than every detail. Don’t start with “Image of” or “Photo of”, and don’t mention that it’s an image unless that matters, such as for a screenshot or a painting. Include any text that’s important to understanding the image.',
        ];

        if (($description = $asset->analysis?->metadata?->description) !== null) {
            array_push(
                $lines,
                '',
                'The image’s file has this description embedded in it. It may name people, places, or events the image doesn’t make clear by itself, but it may also be a caption or a credit, so only use what helps describe the image:',
                "\"$description\"",
            );
        }

        $written = $overwrite ? [] : array_filter(self::groups($asset), static fn (TranslatedSite $group): bool => ($group->value ?? '') !== '');

        if ($written !== []) {
            array_push(
                $lines,
                '',
                'The image already has alt text in these languages. Write the others as translations of it, so every language describes the image the same way, unless it’s clearly wrong for the image:',
                ...array_map(static fn (TranslatedSite $group): string => '- '.self::languageName($group).": \"$group->value\"", array_values($written)),
            );
        }

        return implode("\n", [
            ...$lines,
            '',
            'Write it in each of these languages, as a property of this task’s response:',
            ...array_map(
                static fn (string $property, TranslatedSite $group): string => "- $property: ".self::languageName($group),
                array_keys(self::pending($asset, $overwrite)),
                self::pending($asset, $overwrite),
            ),
        ]);
    }

    public static function schema(JsonSchema $schema, Asset $asset, bool $overwrite): array
    {
        $properties = [];

        foreach (self::pending($asset, $overwrite) as $property => $group) {
            $properties[$property] = $schema->string()->description("Alt text in $group->language.")->required();
        }

        return $properties;
    }

    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void
    {
        foreach (self::pending($asset, $overwrite) as $property => $group) {
            $alt = is_string($result[$property] ?? null) ? trim($result[$property]) : '';

            if ($alt !== '') {
                $changes->setForSite($group->siteId, 'alt', $alt);
            }
        }
    }

    /**
     * Returns the groups of sites the model writes alt text for: every group if their alt text may be replaced, or
     * else the ones missing it.
     *
     * @return array<string, TranslatedSite>
     */
    private static function pending(Asset $asset, bool $overwrite): array
    {
        return $overwrite
            ? self::groups($asset)
            : array_filter(self::groups($asset), static fn (TranslatedSite $group): bool => ($group->value ?? '') === '');
    }

    private static function languageName(TranslatedSite $group): string
    {
        return I18N::getLocaleById($group->language)->getDisplayName('en')." ($group->language)";
    }

    /**
     * Returns the asset’s sites grouped by their alt text translation key, indexed by the group’s response property.
     *
     * @return array<string, TranslatedSite>
     */
    private static function groups(Asset $asset, bool $cached = true): array
    {
        $groups = static fn (): array => TranslatedSite::groups(
            $asset,
            static fn (Asset $instance): string => $instance->getAltTranslationKey(),
            static fn (Asset $instance): ?string => $instance->alt,
        );

        if (! $cached) {
            return $groups();
        }

        self::$groups ??= new WeakMap;

        return self::$groups[$asset] ??= $groups();
    }
}
