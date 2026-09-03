<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\Updates;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\cp_redirect;
use function CraftCms\Cms\t;
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
                new ActionItem()->label(t('Utilities'))->href(Url::cpUrl('utilities')),
                new ActionItem()->label($class::displayName()),
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
            'subnav' => $this->subnav($id),
        ]);
    }

    /**
     * Joins the non-empty asset chunks captured for a utility.
     */
    private static function join(string ...$parts): string
    {
        return implode(PHP_EOL, array_filter($parts, fn (string $part) => $part !== ''));
    }

    /**
     * The utilities nav.
     *
     * Described rather than drawn by the page: the secondary nav renders these
     * as a list when it has the room and as menu items once it collapses, and
     * the breadcrumbs pick the selected one up as well.
     *
     * @return list<NavItem>
     */
    private function subnav(string $selectedId): array
    {
        return $this->utilitiesService
            ->getAuthorizedUtilityTypes()
            ->map(fn (string $class) => new NavItem()
                ->label($class::displayName())
                ->href(Url::cpUrl('utilities/'.$class::id()))
                ->icon($class::icon())
                ->badgeCount($class::badgeCount())
                ->selected($class::id() === $selectedId))
            ->values()
            ->all();
    }
}
