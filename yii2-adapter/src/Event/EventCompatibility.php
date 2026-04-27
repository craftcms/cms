<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use Craft;
use craft\base\Element;
use craft\base\FieldLayoutComponent;
use craft\console\controllers\ResaveController;
use craft\controllers\ElementsController;
use craft\controllers\UsersController;
use craft\db\Connection;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\NestedElementManager;
use craft\events\EditionChangeEvent;
use craft\fieldlayoutelements\BaseField;
use craft\fields\Assets as LegacyAssetsField;
use craft\fields\BaseOptionsField as LegacyBaseOptionsField;
use craft\fields\Link as LegacyLinkField;
use craft\fields\Matrix as LegacyMatrixField;
use craft\helpers\Assets;
use craft\helpers\Cp as CpHelper;
use craft\imagetransforms\ImageTransformer;
use craft\models\FieldLayout;
use craft\services\Addresses;
use craft\services\Auth;
use craft\services\Dashboard;
use craft\services\Drafts;
use craft\services\Elements;
use craft\services\Entries;
use craft\services\Fields;
use craft\services\Fs;
use craft\services\Gc;
use craft\services\Gql as LegacyGql;
use craft\services\ImageTransforms;
use craft\services\Plugins as LegacyPlugins;
use craft\services\ProjectConfig as LegacyProjectConfig;
use craft\services\Revisions;
use craft\services\Routes;
use craft\services\Search as LegacySearch;
use craft\services\Sites;
use craft\services\Structures;
use craft\services\SystemMessages;
use craft\services\UserGroups;
use craft\services\UserPermissions;
use craft\services\Users;
use craft\services\Utilities;
use craft\services\Volumes;
use craft\utilities\AssetIndexes;
use craft\utilities\ClearCaches;
use craft\web\Application;
use craft\web\twig\variables\Cp;
use craft\web\View;
use CraftCms\Cms\Edition\Events\EditionChanged;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\Events\RegisterTemplateCacheCollectors;
use CraftCms\DependencyAwareCache\Events\TagsInvalidated;
use CraftCms\Yii2Adapter\IdentityWrapper;
use CraftCms\Yii2Adapter\View\LegacyAssetBundleCollector;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use yii\caching\TagDependency as YiiTagDependency;

readonly class EventCompatibility
{
    public function boot(): void
    {
        app(BulkOpDeferralBridge::class)->boot();

        /**
         * Elements
         */
        Element::registerEvents();
        Asset::registerEvents();
        Entry::registerEvents();
        NestedElementManager::registerEvents();
        \craft\elements\User::registerEvents();

        /**
         * FieldLayouts
         */
        BaseField::registerEvents();
        FieldLayout::registerEvents();
        FieldLayoutComponent::registerEvents();

        /**
         * Fields
         */
        LegacyAssetsField::registerEvents();
        LegacyBaseOptionsField::registerEvents();
        LegacyLinkField::registerEvents();
        LegacyMatrixField::registerEvents();
        FieldEvents::registerEvents();

        /**
         * Helpers
         */
        Assets::registerEvents();
        CpHelper::registerEvents();

        /**
         * Services
         */
        Addresses::registerEvents();
        Auth::registerEvents();
        Connection::registerEvents();
        Drafts::registerEvents();
        Elements::registerEvents();
        Entries::registerEvents();
        Fields::registerEvents();
        Fs::registerEvents();
        Gc::registerEvents();
        LegacyGql::registerEvents();
        LegacySearch::registerEvents();
        Utilities::registerEvents();
        Dashboard::registerEvents();
        LegacyPlugins::registerEvents();
        LegacyProjectConfig::registerEvents();
        Revisions::registerEvents();
        Routes::registerEvents();
        Sites::registerEvents();
        Structures::registerEvents();
        SystemMessages::registerEvents();
        UserGroups::registerEvents();
        UserPermissions::registerEvents();
        Users::registerEvents();
        View::registerEvents();
        Volumes::registerEvents();
        ImageTransforms::registerEvents();
        ImageTransformer::registerEvents();

        /**
         * Controllers
         */
        ResaveController::registerEvents();
        UsersController::registerEvents();
        ElementsController::registerEvents();

        /**
         * Utilities
         */
        AssetIndexes::registerEvents();
        ClearCaches::registerEvents();

        /**
         * Variables
         */
        Cp::registerEvents();

        Event::listen(function(EditionChanged $event) {
            /** @var Application $craft */
            $craft = app('Craft');

            // Fire an 'afterEditionChange' event
            if (!$craft->hasEventHandlers(Application::EVENT_AFTER_EDITION_CHANGE)) {
                return;
            }

            $craft->trigger(Application::EVENT_AFTER_EDITION_CHANGE, new EditionChangeEvent([
                'oldEdition' => $event->oldEdition->value,
                'newEdition' => $event->newEdition->value,
            ]));
        });

        Event::listen(Authenticated::class, function(Authenticated $event) {
            /** @var User $user */
            $user = $event->user;
            app('Craft')->getUser()->setIdentity(new IdentityWrapper($user));
        });

        Event::listen(Login::class, function(Login $event) {
            /** @var User $user */
            $user = $event->user;
            app('Craft')->getUser()->setIdentity(new IdentityWrapper($user));
        });

        Event::listen(Logout::class, function() {
            app('Craft')->getUser()->setIdentity(null);
        });

        Event::listen(TagsInvalidated::class, function(TagsInvalidated $event) {
            YiiTagDependency::invalidate(Craft::$app->getCache(), $event->tags);
        });

        Event::listen(RegisterTemplateCacheCollectors::class, function(RegisterTemplateCacheCollectors $event) {
            $event->types->add(LegacyAssetBundleCollector::class);
        });

        LegacyGqlEvents::register();
    }
}
