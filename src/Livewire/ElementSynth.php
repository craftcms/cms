<?php

declare(strict_types=1);

namespace CraftCms\Cms\Livewire;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\PreviewRequestPreparer;
use Livewire\Mechanisms\HandleComponents\Synthesizers\Synth;
use UnexpectedValueException;

/**
 * Stores elements in Livewire snapshots as references and loads them again on each
 * Livewire request, the way Livewire handles Eloquent models.
 *
 * The reference points at the exact element that was rendered, so a draft or revision
 * shown in a preview stays the previewed version on later requests.
 *
 * @since 6.0.0
 */
class ElementSynth extends Synth
{
    /** @var string */
    public static $key = 'craft-element';

    /** @param mixed $target */
    public static function match($target): bool
    {
        return $target instanceof ElementInterface;
    }

    /**
     * @return array{null, array{class: class-string<ElementInterface>, id: int|null, siteId: int|null, previewing: bool}}
     */
    public function dehydrate(ElementInterface $target): array
    {
        return [null, [
            'class' => $target::class,
            'id' => $target->id,
            'siteId' => $target->siteId,
            'previewing' => $target->previewing,
        ]];
    }

    /**
     * @param  array{class?: mixed, id?: mixed, siteId?: mixed, previewing?: mixed}  $meta
     */
    public function hydrate(mixed $value, array $meta): ?ElementInterface
    {
        $class = $meta['class'] ?? null;

        if (! is_string($class) || ! is_a($class, ElementInterface::class, true)) {
            throw new UnexpectedValueException('Invalid element class in Livewire snapshot.');
        }

        if (empty($meta['id'])) {
            return null;
        }

        $element = app(Elements::class)->getElementById((int) $meta['id'], $class, $meta['siteId'] ?? null);

        if ($element && ! empty($meta['previewing'])) {
            app(PreviewRequestPreparer::class)->preparePreviewElement($element);
        }

        return $element;
    }
}
