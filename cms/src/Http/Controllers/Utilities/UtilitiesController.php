<?php

namespace CraftCms\Cms\Http\Controllers\Utilities;

use craft\helpers\Cp;
use craft\web\Application;
use craft\web\assets\utilities\UtilitiesAsset;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\Updates;
use CraftCms\Cms\Utility\Utilities\Upgrade;
use Illuminate\Container\Attributes\Give;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;

class UtilitiesController
{
    public function __construct(
        protected Utilities $utilitiesService,
        #[Give('Craft')] protected Application $craft,
    ) {}

    public function index()
    {
        $utilities = $this->utilitiesService->getAuthorizedUtilityTypes();

        if ($utilities->isEmpty()) {
            abort(403, 'User not permitted to view Utilities');
        }

        // Don’t go to the Updates or Upgrade utilities by default if there are any others
        $firstUtility = $utilities->first(function (string $utility) {
            return ! in_array($utility, [Updates::class, Upgrade::class]);
        }) ?? $utilities->first();

        /** @var class-string<\CraftCms\Cms\Utility\Utility> $firstUtility */
        return cp_redirect('utilities/'.$firstUtility::id());
    }

    public function show(string $id)
    {
        $class = $this->utilitiesService->getUtilityTypeById($id);

        if ($class === null) {
            return $this->index();
        }

        if ($this->utilitiesService->checkAuthorization($class) === false) {
            abort(403, 'User not permitted to access the "'.$class::displayName().'".');
        }

        $this->craft->getView()->registerAssetBundle(UtilitiesAsset::class);

        return $this->craft->getView()->renderPageTemplate('utilities/_index.twig', [
            'id' => $id,
            'displayName' => $class::displayName(),
            'contentHtml' => $class::contentHtml(),
            'toolbarHtml' => $class::toolbarHtml(),
            'footerHtml' => $class::footerHtml(),
            'utilities' => $this->utilityInfo(),
        ]);
    }

    private function utilityInfo(): Collection
    {
        return $this->utilitiesService
            ->getAuthorizedUtilityTypes()
            /**
             * @var class-string<\CraftCms\Cms\Utility\Utility> $class
             *
             * @phpstan-ignore argument.unresolvableType
             */
            ->map(function (string $class) {
                return [
                    'id' => $class::id(),
                    'iconSvg' => $this->utilityIconSvg($class),
                    'displayName' => $class::displayName(),
                    'iconPath' => $class::icon(),
                    'badgeCount' => $class::badgeCount(),
                ];
            });
    }

    /**
     * @param  class-string<\CraftCms\Cms\Utility\Utility>  $class
     */
    private function utilityIconSvg(string $class): string
    {
        $icon = $class::icon();

        if ($icon === null) {
            return $this->defaultUtilityIconSvg($class);
        }

        try {
            $svg = Cp::iconSvg($icon);
            if ($svg !== '') {
                return $svg;
            }
        } catch (InvalidArgumentException) {
        }

        return $this->defaultUtilityIconSvg($class);
    }

    /**
     * Returns the default icon SVG for a given utility type.
     *
     * @param  class-string<\CraftCms\Cms\Utility\Utility>  $class
     */
    private function defaultUtilityIconSvg(string $class): string
    {
        return $this->craft->getView()->renderTemplate('_includes/fallback-icon.svg.twig', [
            'label' => $class::displayName(),
        ]);
    }
}
