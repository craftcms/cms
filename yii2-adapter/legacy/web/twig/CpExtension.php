<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use craft\helpers\Cp;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Facades\Vite;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Control panel Twig extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.8
 */
class CpExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cardViewDesigner', [Cp::class, 'cardViewDesignerHtml'], ['is_safe' => ['html']]),
            new TwigFunction('chip', [Cp::class, 'chipHtml'], ['is_safe' => ['html']]),
            new TwigFunction('customSelect', [Cp::class, 'customSelectHtml'], ['is_safe' => ['html']]),
            new TwigFunction('disclosureMenu', [Cp::class, 'disclosureMenu'], ['is_safe' => ['html']]),
            new TwigFunction('elementCard', [Cp::class, 'elementCardHtml'], ['is_safe' => ['html']]),
            new TwigFunction('elementChip', [Cp::class, 'elementChipHtml'], ['is_safe' => ['html']]),
            new TwigFunction('elementIndex', [Cp::class, 'elementIndexHtml'], ['is_safe' => ['html']]),
            new TwigFunction('fieldLayoutDesigner', [Cp::class, 'fieldLayoutDesignerHtml'], ['is_safe' => ['html']]),
            new TwigFunction('findCrumb', fn(array $items) => $this->findCrumb($items)),
            new TwigFunction('generatedFieldsTable', [Cp::class, 'generatedFieldsTableHtml'], ['is_safe' => ['html']]),
            new TwigFunction('iconSvg', [Cp::class, 'iconSvg'], ['is_safe' => ['html']]),
            new TwigFunction('siteMenuItems', [Cp::class, 'siteMenuItems']),
            new TwigFunction('statusIndicator', [Cp::class, 'statusIndicatorHtml'], ['is_safe' => ['html']]),
            new TwigFunction('readOnlyNotice', [Cp::class, 'readOnlyNoticeHtml'], ['is_safe' => ['html']]),
            new TwigFunction('vite', [$this, 'vite'], ['is_safe' => ['html']]),
        ];
    }

    public function vite(array $entryPoints, string $buildDirectory = 'vendor/craft'): string
    {
        try {
            return Vite::useHotFile(Craft::getAlias('@resources/hot'))
                ->withEntryPoints($entryPoints)
                ->useBuildDirectory($buildDirectory)
                ->toHtml();
        } catch (ViteException $e) {
            if (Cms::config()->devMode) {
                Craft::$app->getView()->registerJsWithVars(fn($message) => "console.error($message)", [
                    'message' => $e->getMessage(),
                ]);
            }
            return '';
        }
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('cpmd', [Cp::class, 'parseMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    private function findCrumb(array $items): array
    {
        foreach ($items as $item) {
            if (array_key_exists('selected', $item)) {
                if ($item['selected']) {
                    return $item;
                }
                continue;
            }

            if (isset($item['items'])) {
                $selected = $this->findCrumb($item['items']);
                if (!empty($selected)) {
                    return $selected;
                }
            }
        }

        return [];
    }
}
