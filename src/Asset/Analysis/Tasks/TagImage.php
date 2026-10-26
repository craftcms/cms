<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Tasks;

use CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Contracts\JsonSchema\JsonSchema;

use function CraftCms\Cms\t;

/**
 * Tags images with keywords that describe them, and the text that appears in them, so they can be searched for.
 *
 * @since 6.0.0
 */
class TagImage extends ImageAnalysisTask
{
    /** The most keywords an image is tagged with. */
    private const int MAX_KEYWORDS = 20;

    public static function handle(): string
    {
        return 'craft:tagImage';
    }

    public static function label(): string
    {
        return t('Tag images');
    }

    public static function description(): ?string
    {
        return t('Tags images with keywords that describe them, and the text that appears in them, so they can be searched for.');
    }

    public static function needed(Asset $asset, bool $overwrite): bool
    {
        // Images that have been tagged have keywords, even if they have no text
        return $overwrite || $asset->keywords === null;
    }

    public static function instructions(Asset $asset): string
    {
        $language = Sites::getPrimarySite()->language;
        $max = self::MAX_KEYWORDS;

        return "List up to $max keywords that someone might search for to find the image, most important first: its subjects, the objects and people in it (without naming people), where it is, what’s happening, its style, mood, and prominent colors. Use single words or short phrases, in lowercase unless they’re proper nouns, written in ".I18N::getLocaleById($language)->getDisplayName('en')." ($language).\n\nAlso transcribe any text that appears in the image, such as on signs, labels, or documents, as it’s written, keeping line breaks between separate pieces of text. If there isn’t any, use an empty string.";
    }

    public static function schema(JsonSchema $schema, Asset $asset): array
    {
        return [
            'keywords' => $schema->array()->items($schema->string())->max(self::MAX_KEYWORDS)->required(),
            'visibleText' => $schema->string()->description('Text that appears in the image, or an empty string.')->required(),
        ];
    }

    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void
    {
        if (is_array($result['keywords'] ?? null) && ($overwrite || $asset->keywords === null)) {
            $keywords = array_values(array_unique(array_filter(array_map(
                static fn (mixed $keyword): string => is_string($keyword) ? trim($keyword) : '',
                $result['keywords'],
            ))));

            $changes->set('keywords', array_slice($keywords, 0, self::MAX_KEYWORDS));
        }

        if (is_string($result['visibleText'] ?? null) && ($overwrite || $asset->keywords === null)) {
            $changes->set('visibleText', trim($result['visibleText']) ?: null);
        }
    }
}
