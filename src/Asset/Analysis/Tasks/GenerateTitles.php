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
 * Titles images that still have the title they were given from their filename, such as “IMG 4759”, in the language of
 * each site the asset’s title is translated for.
 *
 * Sites that share a title, per the volume’s title translation method, get one title, written in the language of the
 * site the asset is being analyzed in, or of the first of them. Saving it there propagates it to the others.
 *
 * @since 6.0.0
 */
class GenerateTitles extends ImageAnalysisTask
{
    /** The longest title written, in characters. */
    private const int MAX_LENGTH = 100;

    /** @var WeakMap<Asset, array<string, TranslatedSite>>|null */
    private static ?WeakMap $groups = null;

    public static function handle(): string
    {
        return 'craft:title';
    }

    public static function label(): string
    {
        return t('Generate titles');
    }

    public static function description(): ?string
    {
        return t('Titles images that are still titled after their filename, in each site’s language, when they’re uploaded or analyzed.');
    }

    /**
     * Gives an image that’s still titled after its filename its embedded title.
     */
    public static function fillFromMetadata(Asset $asset, ImageMetadata $metadata, ImageAnalysisChanges $changes): void
    {
        if ($metadata->title !== null && $asset->hasDefaultTitle()) {
            $changes->setForSite($asset->siteId, 'title', mb_substr($metadata->title, 0, 255));
        }
    }

    public static function needed(Asset $asset, bool $overwrite): bool
    {
        $groups = self::groups($asset);

        return $overwrite
            ? $groups !== []
            : array_any($groups, static fn (TranslatedSite $group): bool => $group->value === null);
    }

    public static function instructions(Asset $asset, bool $overwrite): string
    {
        $languages = array_map(
            static fn (string $property, TranslatedSite $group): string => "- $property: ".I18N::getLocaleById($group->language)->getDisplayName('en')." ($group->language)",
            array_keys(self::groups($asset)),
            self::groups($asset),
        );

        return implode("\n", [
            'Write a title for the image, like one an editor would give it in a media library: a short, specific name for what it shows, usually 2 to 8 words, in sentence case, without ending punctuation. Don’t start with “Image of” or “Photo of”. If text in the image names what it is, such as a product, a place, or a document’s title, use that.',
            '',
            'Write it in each of these languages, as a property of this task’s response:',
            ...$languages,
        ]);
    }

    public static function schema(JsonSchema $schema, Asset $asset, bool $overwrite): array
    {
        $properties = [];

        foreach (self::groups($asset) as $property => $group) {
            $properties[$property] = $schema->string()->max(self::MAX_LENGTH)->description("Title in $group->language.")->required();
        }

        return $properties;
    }

    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void
    {
        foreach (self::groups($asset) as $property => $group) {
            $title = is_string($result[$property] ?? null) ? trim(mb_substr(trim($result[$property]), 0, self::MAX_LENGTH)) : '';

            if ($title !== '' && ($overwrite || $group->value === null)) {
                $changes->setForSite($group->siteId, 'title', $title);
            }
        }
    }

    /**
     * Returns the asset’s sites grouped by their title translation key, indexed by the group’s response property.
     * Titles that are still the ones given from the filename are `null`.
     *
     * @return array<string, TranslatedSite>
     */
    private static function groups(Asset $asset): array
    {
        self::$groups ??= new WeakMap;

        return self::$groups[$asset] ??= TranslatedSite::groups(
            $asset,
            static fn (Asset $instance): string => $instance->getTitleTranslationKey(),
            static fn (Asset $instance): ?string => $instance->hasDefaultTitle() ? null : $instance->title,
        );
    }
}
