<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\web\assets\updateswidget\UpdatesWidgetAsset;
use CraftCms\Cms\Updates\Updates as UpdatesService;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;

final class Updates extends Widget
{
    public function __construct(
        private readonly UpdatesService $updates,
        array $config = []
    ) {
        parent::__construct($config);
    }

    #[\Override]
    public static function displayName(): string
    {
        return t('Updates');
    }

    #[\Override]
    public static function isSelectable(): bool
    {
        // Gotta have update permission to get this widget
        return parent::isSelectable() && Auth::user()->can('performUpdates');
    }

    #[\Override]
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    #[\Override]
    public static function icon(): string
    {
        return 'certificate';
    }

    #[\Override]
    public function getBodyHtml(): ?string
    {
        // Make sure the user actually has permission to perform updates
        if (! Auth::user()->can('performUpdates')) {
            return null;
        }

        $view = Craft::$app->getView();
        $cached = $this->updates->isUpdateInfoCached();

        if (! $cached || ! $this->updates->totalAvailableUpdates()) {
            $view->registerAssetBundle(UpdatesWidgetAsset::class);
            $view->registerJs('new Craft.UpdatesWidget('.$this->id.', '.($cached ? 'true' : 'false').');');
        }

        if ($cached) {
            return $view->renderTemplate('_components/widgets/Updates/body.twig',
                [
                    'total' => $this->updates->totalAvailableUpdates(),
                ]);
        }

        return '<p class="centeralign">'.t('Checking for updates…').'</p>';
    }
}
