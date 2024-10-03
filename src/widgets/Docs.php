<?php

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\web\assets\docs\DocsAsset;

/**
 * Docs widget type
 */
class Docs extends Widget
{
    public static function displayName(): string
    {
        return Craft::t('app', 'Docs');
    }
    

    public static function isSelectable(): bool
    {
        return true;
    }

    public static function icon(): ?string
    {
        return 'book';
    }

    /**
     * @inheritdoc
     */
    public function getSubtitle(): ?string
    {
        return Craft::t('app', 'Search for answers in the official documentation.');
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(DocsAsset::class);

        return $view->renderTemplate('_components/widgets/Docs/body.twig', [
            'widget' => $this,
        ]);
    }
}
