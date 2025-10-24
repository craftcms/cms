<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\deprecationerrors\DeprecationErrorsAsset;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * DeprecationErrors represents a DeprecationErrors dashboard widget.
 */
final class DeprecationErrors extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Deprecation Warnings');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'deprecation-errors';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'bug';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function badgeCount(): int
    {
        return Deprecator::getTotalLogs();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(DeprecationErrorsAsset::class);

        return $view->renderTemplate('_components/utilities/DeprecationErrors/index.twig', [
            'logs' => Deprecator::getLogs(),
        ]);
    }
}
