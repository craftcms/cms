<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Cp\FieldLayoutDesigner\CardDesigner;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Collection;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CpExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'Edition' => Edition::get(),
            'CraftEdition' => Edition::get()->value,
            'CraftSolo' => Edition::Solo->value,
            'CraftTeam' => Edition::Team->value,
            'CraftPro' => Edition::Pro->value,
            'CraftEnterprise' => Edition::Enterprise->value,
            'requestedSite' => app(RequestedSite::class)->get(),
        ];
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cardViewDesigner', app(CardDesigner::class)->html(...), ['is_safe' => ['html']]),
            new TwigFunction('chip', app(ElementHtml::class)->chipHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('customSelect', FormFields::customSelectHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('disclosureMenu', app(MenuHtml::class)->disclosureMenu(...), ['is_safe' => ['html']]),
            new TwigFunction('elementCard', app(ElementHtml::class)->elementCardHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('elementChip', app(ElementHtml::class)->elementChipHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('elementIndex', app(ElementIndexHtml::class)->html(...), ['is_safe' => ['html']]),
            new TwigFunction('fieldLayoutDesigner', app(FieldLayoutDesigner::class)->html(...), ['is_safe' => ['html']]),
            new TwigFunction('fieldLayoutDesignerField', app(FieldLayoutDesigner::class)->fieldHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('findCrumb', fn (array $items) => $this->findCrumb($items)),
            new TwigFunction('generatedFieldsTable', app(FieldLayoutDesigner::class)->generatedFieldsTableHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('iconSvg', Icons::svg(...), ['is_safe' => ['html']]),
            new TwigFunction('siteMenuItems', app(MenuHtml::class)->siteMenuItems(...)),
            new TwigFunction('statusIndicator', app(StatusHtml::class)->statusIndicatorHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('readOnlyNotice', app(ContentHtml::class)->readOnlyNoticeHtml(...), ['is_safe' => ['html']]),
            new TwigFunction('cpVite', $this->vite(...), ['is_safe' => ['html']]),
            new TwigFunction('cpEnvOptions', $this->envOptions(...)),
            new TwigFunction('cpEnvSuggestions', $this->envSuggestions(...)),
            new TwigFunction('cpTemplateSuggestions', $this->templateSuggestions(...)),

            // Legacy Assets - remove once all dependencies on these are removed
            new TwigFunction('registerLegacyAsset', fn (string $bundle) => app(InternalAssetRegistry::class)->register($bundle)),
        ];
    }

    public function envOptions(?array $allowedValues = null): array
    {
        return $this->formatLegacyOptions(SelectOptions::getEnvOptions($allowedValues));
    }

    public function envSuggestions(bool $includeAliases = false, ?callable $filter = null): array
    {
        return $this->formatLegacySuggestions(SelectOptions::getEnvSuggestions($includeAliases, $filter));
    }

    public function templateSuggestions(): array
    {
        return $this->formatLegacySuggestions(SelectOptions::getTemplateSuggestions());
    }

    public function vite(array $entryPoints): string
    {
        try {
            return Cp::vite()
                ->withEntryPoints($entryPoints)
                ->toHtml();
        } catch (ViteException $e) {
            if (Cms::config()->devMode) {
                HtmlStack::jsWithVars(fn ($message) => "console.error($message)", [
                    'message' => $e->getMessage(),
                ]);
            }

            return '';
        }
    }

    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('cpmd', app(ContentHtml::class)->parseMarkdown(...), ['is_safe' => ['html']]),
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

    private function formatLegacySuggestions(array $options): array
    {
        return array_map(fn ($group) => [
            'label' => $group['label'],
            'data' => array_map(fn (array $option) => [
                'name' => $option['label'],
                'hint' => $option['data']['hint'] ?? null,
            ], $group['options']),
        ], $options);
    }

    private function formatLegacyOptions(array $originalOptions): array
    {
        $options = [];

        foreach ($originalOptions as $value) {
            if (($value['type'] ?? null) === 'optgroup') {
                $options[] = ['optgroup' => $value['label']];
                array_push($options, ...($value['options'] ?? []));
            } else {
                $options[] = [
                    'label' => $value['label'],
                    'value' => $value['value'],
                    'data' => $value['data'] ?? null,
                ];
            }
        }

        return $options;
    }
}
