<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use Craft;
use craft\base\Element;
use craft\base\Event as YiiEvent;
use craft\base\FieldLayoutComponent;
use craft\controllers\UsersController;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\events\EditionChangeEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\fieldlayoutelements\BaseField;
use craft\imagetransforms\ImageTransformer;
use craft\models\FieldLayout;
use craft\services\Addresses;
use craft\services\Auth;
use craft\services\Dashboard;
use craft\services\Drafts;
use craft\services\Entries;
use craft\services\Fields;
use craft\services\Fs;
use craft\services\Gc;
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
use craft\web\twig\variables\Cp as CpVariable;
use craft\web\View;
use CraftCms\Cms\Cp\Events\RegisterCpNavItems;
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

final readonly class EventCompatibility
{
    public function boot(): void
    {
        /**
         * Elements
         */
        Element::registerEvents();
        Asset::registerEvents();
        Entry::registerEvents();
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
        FieldEvents::registerEvents();

        /**
         * Services
         */
        Addresses::registerEvents();
        Auth::registerEvents();
        Drafts::registerEvents();
        Entries::registerEvents();
        Fields::registerEvents();
        Fs::registerEvents();
        Gc::registerEvents();
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
        UsersController::registerEvents();

        /**
         * Utilities
         */
        AssetIndexes::registerEvents();
        ClearCaches::registerEvents();

        /**
         * Variables
         */
        Cp::registerEvents();

        Event::listen(function(RegisterCpNavItems $event) {
            if (YiiEvent::hasHandlers(CpVariable::class, 'registerCpNavItems')) {
                $yiiEvent = new RegisterCpNavItemsEvent(['navItems' => $event->navItems]);

                YiiEvent::trigger(CpVariable::class, 'registerCpNavItems', $yiiEvent);

                $event->navItems = $yiiEvent->navItems;
            }
        });

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
    }
}
