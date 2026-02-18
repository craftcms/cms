<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use craft\helpers\Cp;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Vite;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Control panel Twig extension
 */
class CpExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * {@inheritdoc}
     */
    public function getGlobals(): array
    {
        return [
            'CraftEdition' => Edition::get()->value,
            'CraftSolo' => Edition::Solo->value,
            'CraftTeam' => Edition::Team->value,
            'CraftPro' => Edition::Pro->value,
            'CraftEnterprise' => Edition::Enterprise->value,
            'requestedSite' => Cp::requestedSite(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cardViewDesigner', Cp::cardViewDesignerHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('chip', Cp::chipHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('customSelect', Cp::customSelectHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('disclosureMenu', Cp::disclosureMenu(...), ['is_safe' => ['html']]),
            new TwigFunction('elementCard', Cp::elementCardHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('elementChip', Cp::elementChipHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('elementIndex', Cp::elementIndexHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('fieldLayoutDesigner', Cp::fieldLayoutDesignerHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('findCrumb', fn (array $items) => $this->findCrumb($items)),
            new TwigFunction('generatedFieldsTable', Cp::generatedFieldsTableHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('iconSvg', Cp::iconSvg(...), ['is_safe' => ['html']]),
            new TwigFunction('siteMenuItems', Cp::siteMenuItems(...)),
            new TwigFunction('statusIndicator', Cp::statusIndicatorHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('readOnlyNotice', Cp::readOnlyNoticeHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('vite', $this->vite(...), ['is_safe' => ['html']]),
        ];
    }

    public function vite(array $entryPoints, string $buildDirectory = 'vendor/craft/build'): string
    {
        try {
            return Vite::useHotFile(Aliases::get('@resources/hot'))
                ->withEntryPoints($entryPoints)
                ->useBuildDirectory($buildDirectory)
                ->toHtml();
        } catch (ViteException $e) {
            if (Cms::config()->devMode) {
                AssetRegistry::jsWithVars(fn ($message) => "console.error($message)", [
                    'message' => $e->getMessage(),
                ]);
            }

            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('cpmd', Cp::parseMarkdown(...), ['is_safe' => ['html']]),
        ];
    }

    private function findCrumb(array|Collection $items): array
    {
        if ($items instanceof Collection) {
            $items = $items->all();
        }

        foreach ($items as $item) {
            if (array_key_exists('selected', $item)) {
                if ($item['selected']) {
                    return $item;
                }

                continue;
            }

            if (isset($item['items'])) {
                $selected = $this->findCrumb($item['items']);
                if (! empty($selected)) {
                    return $selected;
                }
            }
        }

        return [];
    }
}
