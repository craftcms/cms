<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette\SearchProviders;

use Closure;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Component\Contracts\Colorable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem;
use CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider;
use CraftCms\Cms\Cp\CommandPalette\SearchProviderRegistry;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementActionContext;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Str;

use function CraftCms\Cms\t;

/**
 * Finds elements of one type that the user can view and edit.
 *
 * Craft registers one for entries, assets and users. A plugin can register
 * one for its own element type with {@see SearchProviderRegistry}:
 *
 * ```php
 * $searchProviders->register(new ElementSearchProvider(
 *     Product::class,
 *     fn (CraftUser $user) => $user->can('commerce-manageProducts'),
 * ));
 * ```
 *
 * @since 6.0.0
 */
readonly class ElementSearchProvider implements SearchProvider
{
    /** How many matches to ask the search index for, before permission checks thin them out. */
    private const int LIMIT = 10;

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  (Closure(CraftUser): bool)|null  $available  Whether the user can use the provider. Defaults to whether the element type has any sources they can see.
     */
    public function __construct(private string $elementType, private ?Closure $available = null) {}

    public function getHandle(): string
    {
        return 'elements-'.Str::kebab((string) ($this->elementType::refHandle() ?? class_basename($this->elementType)));
    }

    public function getLabel(): string
    {
        return $this->elementType::pluralDisplayName();
    }

    public function isAvailable(CraftUser $user): bool
    {
        if ($this->available !== null) {
            return ($this->available)($user);
        }

        return match ($this->elementType) {
            Entry::class => Sections::getTotalEditableSections() > 0,
            Asset::class => Volumes::getTotalViewableVolumes() > 0,
            User::class => Edition::get() !== Edition::Solo && $user->can('viewUsers'),
            default => true,
        };
    }

    public function search(string $query, CraftUser $user): iterable
    {
        $elements = $this->elementType::find()
            ->siteId(app(RequestedSite::class)->get()?->id)
            ->status(null)
            ->search($query)
            ->orderByDesc('score')
            ->limit(self::LIMIT)
            ->get();

        foreach ($elements as $element) {
            $editUrl = $element->getCpEditUrl();

            if ($editUrl === null || ! $user->can('view', $element)) {
                continue;
            }

            yield $this->item($element, $editUrl);
        }
    }

    private function item(ElementInterface $element, string $editUrl): CommandPaletteItem
    {
        return new CommandPaletteItem()
            ->id(sprintf('element-%s', $element->id))
            ->label($element->getUiLabel())
            ->hint($this->hint($element))
            ->description($element::displayName())
            ->thumbHtml($element->getThumbHtml(64))
            // Shown when there's no thumbnail: an entry's type's icon, in its color.
            ->icon(($element instanceof Iconic ? CommandPaletteItem::systemIcon($element->getIcon()) : null) ?? 'file')
            ->iconColor($element instanceof Colorable ? $element->getColor()?->value : null)
            ->href($editUrl)
            ->elementActions($element->actionMenuDescriptors(ElementActionContext::Index));
    }

    /**
     * What tells apart elements with the same label: an entry's section, an
     * asset's volume and filename, a user's username (their email, where
     * that's what users sign in with).
     */
    private function hint(ElementInterface $element): ?string
    {
        return match (true) {
            $element instanceof Entry => ($section = $element->getSection()) ? t($section->name, category: 'site') : null,
            $element instanceof Asset => sprintf('%s · %s', t($element->getVolume()->name, category: 'site'), $element->getFilename()),
            $element instanceof User => $element->username,
            default => null,
        };
    }
}
