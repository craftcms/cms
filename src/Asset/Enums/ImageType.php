<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Enums;

use CraftCms\Cms\Asset\Analysis\Tasks\ClassifyImage;

use function CraftCms\Cms\t;

/**
 * What kind of image an image is, as found by the “Classify images” AI task.
 *
 * @see ClassifyImage
 * @since 6.0.0
 */
enum ImageType: string
{
    /** A photograph. */
    case Photo = 'photo';
    /** A drawing, painting, or other artwork, including 3D renders. */
    case Illustration = 'illustration';
    /** A capture of a screen, app, or website. */
    case Screenshot = 'screenshot';
    /** A chart, graph, diagram, map, or infographic, which conveys information visually. */
    case Diagram = 'diagram';
    /** A logo, icon, or emblem. */
    case Logo = 'logo';
    /** A scan or photo of a document, such as a page, receipt, or form, that’s mostly text. */
    case Document = 'document';
    /** A pattern, texture, or background, with no subject of its own. */
    case Pattern = 'pattern';

    /**
     * @param  string|null  $locale  The language to return the label in, if not the current one
     */
    public function label(?string $locale = null): string
    {
        return match ($this) {
            self::Photo => t('Photo', locale: $locale),
            self::Illustration => t('Illustration', locale: $locale),
            self::Screenshot => t('Screenshot', locale: $locale),
            self::Diagram => t('Diagram', locale: $locale),
            self::Logo => t('Logo', locale: $locale),
            self::Document => t('Document', locale: $locale),
            self::Pattern => t('Pattern', locale: $locale),
        };
    }

    /**
     * Returns what the type covers, for telling the model what each type means.
     */
    public function description(): string
    {
        return match ($this) {
            self::Photo => 'a photograph',
            self::Illustration => 'a drawing, painting, or other artwork, including 3D renders',
            self::Screenshot => 'a capture of a screen, app, or website',
            self::Diagram => 'a chart, graph, diagram, map, or infographic, which conveys information visually',
            self::Logo => 'a logo, icon, or emblem',
            self::Document => 'a scan or photo of a document that’s mostly text, such as a page, receipt, or form',
            self::Pattern => 'a pattern, texture, or background, with no subject of its own',
        };
    }
}
