<?php

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\web\assets\updateswidget\UpdatesWidgetAsset;
use Illuminate\Support\Facades\Auth;

/** @since 6.0.0 */
final class Updates extends Widget
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Updates');
    }

    /**
     * {@inheritdoc}
     */
    public static function isSelectable(): bool
    {
        // Gotta have update permission to get this widget
        return parent::isSelectable() && Auth::user()->can('performUpdates');
    }

    /**
     * {@inheritdoc}
     */
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'certificate';
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyHtml(): ?string
    {
        // Make sure the user actually has permission to perform updates
        if (! Auth::user()->can('performUpdates')) {
            return null;
        }

        $view = Craft::$app->getView();
        $cached = Craft::$app->getUpdates()->getIsUpdateInfoCached();

        if (! $cached || ! Craft::$app->getUpdates()->getTotalAvailableUpdates()) {
            $view->registerAssetBundle(UpdatesWidgetAsset::class);
            $view->registerJs('new Craft.UpdatesWidget('.$this->id.', '.($cached ? 'true' : 'false').');');
        }

        if ($cached) {
            return $view->renderTemplate('_components/widgets/Updates/body.twig',
                [
                    'total' => Craft::$app->getUpdates()->getTotalAvailableUpdates(),
                ]);
        }

        return '<p class="centeralign">'.Craft::t('app', 'Checking for updates…').'</p>';
    }
}
