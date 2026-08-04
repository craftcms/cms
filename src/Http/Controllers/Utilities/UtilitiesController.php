<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\Updates;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

use function CraftCms\Cms\cp_redirect;
use function CraftCms\Cms\template;

readonly class UtilitiesController
{
    public function __construct(
        private Utilities $utilitiesService,
    ) {}

    public function badgeCount(): JsonResponse
    {
        return new JsonResponse([
            'badgeCount' => $this->utilitiesService->getUtilitiesBadgeCount(),
        ]);
    }

    public function index(): RedirectResponse
    {
        $utilities = $this->utilitiesService->getAuthorizedUtilityTypes();

        if ($utilities->isEmpty()) {
            abort(403, 'User not permitted to view Utilities');
        }

        // Don’t go to the Updates or Upgrade utilities by default if there are any others
        $firstUtility = $utilities->first(fn (string $utility) => $utility !== Updates::class) ?? $utilities->first();

        /** @var class-string<Utility> $firstUtility */
        return cp_redirect('utilities/'.$firstUtility::id());
    }

    public function show(string $id, HtmlStack $htmlStack): RedirectResponse|Response
    {
        $class = $this->utilitiesService->getUtilityTypeById($id);

        if ($class === null) {
            return $this->index();
        }

        if ($this->utilitiesService->checkAuthorization($class) === false) {
            abort(403, sprintf('User not permitted to access the “%s” utility.', $class::displayName()));
        }

        // Capture each chunk so only the assets the utility itself registers
        // end up on the page props. Draining the whole stack here would also
        // swallow the global CP asset bundle (jQuery, Garnish, legacy cp.js,
        // the `window.Craft` config) that `HandleInertiaRequests` registers for
        // every CP request, leaving `app.blade.php`'s `<head>` empty on the
        // initial page load.
        $content = $htmlStack->capture(fn (): string => $class::contentHtml());
        $toolbar = $htmlStack->capture(fn (): string => $class::toolbarHtml());
        $footer = $htmlStack->capture(fn (): string => $class::footerHtml());

        return Inertia::render('utilities/Show', [
            'crumbs' => [
                ['label' => 'Utilities', 'url' => Url::cpUrl('utilities')],
                ['label' => $class::displayName(), 'url' => null],
            ],
            'id' => $id,
            'title' => $class::displayName(),
            // The HTML itself still renders through `DynamicHtmlRenderer`,
            // which compiles it as a Vue template — utility content can
            // reference Vue components (e.g. `<ProjectConfig>`).
            'contentHtml' => $content->html,
            'toolbarHtml' => $toolbar->html,
            'footerHtml' => $footer->html,
            // Picked up by `AppLayout`'s `useAppendHtml()`, which appends and
            // executes these against the document on mount and on subsequent
            // Inertia visits.
            'headHtml' => self::join($content->headHtml, $toolbar->headHtml, $footer->headHtml),
            'bodyHtml' => self::join($content->bodyHtml, $toolbar->bodyHtml, $footer->bodyHtml),
            'utilities' => $this->utilityInfo(),
        ]);
    }

    /**
     * Joins the non-empty asset chunks captured for a utility.
     */
    private static function join(string ...$parts): string
    {
        return implode(PHP_EOL, array_filter($parts, fn (string $part) => $part !== ''));
    }

    /** @return Collection<int, covariant array{id:string, url:string, iconSvg:string, displayName:string, iconPath:string|null, badgeCount:int}> */
    private function utilityInfo(): Collection
    {
        return $this->utilitiesService
            ->getAuthorizedUtilityTypes()
            ->map(fn (string $class) => [
                'id' => $class::id(),
                'url' => Url::cpUrl('utilities/'.$class::id()),
                'iconSvg' => $this->utilityIconSvg($class),
                'displayName' => $class::displayName(),
                'iconPath' => $class::icon(),
                'badgeCount' => $class::badgeCount(),
            ]);
    }

    /**
     * @param  class-string<Utility>  $class
     */
    private function utilityIconSvg(string $class): string
    {
        $icon = $class::icon();

        if ($icon === null) {
            return $this->defaultUtilityIconSvg($class);
        }

        try {
            $svg = Icons::svg($icon);
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
     * @param  class-string<Utility>  $class
     */
    private function defaultUtilityIconSvg(string $class): string
    {
        return template('_includes/fallback-icon-svg', [
            'label' => $class::displayName(),
        ]);
    }
}
