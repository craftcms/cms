<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Event;

use craft\base\Event as YiiEvent;
use craft\events\DefineGqlArgumentsEvent;
use craft\events\DefineGqlTypeFieldsEvent;
use craft\events\MutationPopulateElementEvent;
use craft\events\RegisterGqlArgumentHandlersEvent;
use craft\events\RegisterGqlEagerLoadableFields as LegacyRegisterGqlEagerLoadableFields;
use craft\gql\ArgumentManager as LegacyArgumentManager;
use craft\gql\base\ElementArguments as LegacyElementArguments;
use craft\gql\base\ElementMutationResolver as LegacyElementMutationResolver;
use craft\gql\ElementQueryConditionBuilder as LegacyElementQueryConditionBuilder;
use craft\gql\TypeManager as LegacyTypeManager;
use CraftCms\Cms\Gql\Events\ElementPopulated;
use CraftCms\Cms\Gql\Events\ElementPopulating;
use CraftCms\Cms\Gql\Events\GqlArgumentHandlersResolving;
use CraftCms\Cms\Gql\Events\GqlArgumentsResolving;
use CraftCms\Cms\Gql\Events\GqlEagerLoadableFieldsResolving;
use CraftCms\Cms\Gql\Events\GqlTypeFieldsResolving;
use Illuminate\Support\Facades\Event;

class LegacyGqlEvents
{
    public static function register(): void
    {
        Event::listen(GqlTypeFieldsResolving::class, function(GqlTypeFieldsResolving $event) {
            if (!YiiEvent::hasHandlers(LegacyTypeManager::class, LegacyTypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS)) {
                return;
            }

            $yiiEvent = new DefineGqlTypeFieldsEvent([
                'fields' => $event->fields,
                'typeName' => $event->typeName,
            ]);
            YiiEvent::trigger(LegacyTypeManager::class, LegacyTypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS, $yiiEvent);
            $event->fields = $yiiEvent->fields;
        });

        Event::listen(GqlArgumentHandlersResolving::class, function(GqlArgumentHandlersResolving $event) {
            if (!YiiEvent::hasHandlers(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS)) {
                return;
            }

            $yiiEvent = new RegisterGqlArgumentHandlersEvent([
                'handlers' => $event->handlers,
            ]);
            YiiEvent::trigger(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS, $yiiEvent);
            $event->handlers = $yiiEvent->handlers;
        });

        Event::listen(GqlEagerLoadableFieldsResolving::class, function(GqlEagerLoadableFieldsResolving $event) {
            if (!YiiEvent::hasHandlers(LegacyElementQueryConditionBuilder::class, LegacyElementQueryConditionBuilder::EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS)) {
                return;
            }

            $yiiEvent = new LegacyRegisterGqlEagerLoadableFields([
                'fieldList' => $event->fieldList,
            ]);
            YiiEvent::trigger(LegacyElementQueryConditionBuilder::class, LegacyElementQueryConditionBuilder::EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS, $yiiEvent);
            $event->fieldList = $yiiEvent->fieldList;
        });

        Event::listen(GqlArgumentsResolving::class, function(GqlArgumentsResolving $event) {
            if (!YiiEvent::hasHandlers(LegacyElementArguments::class, LegacyElementArguments::EVENT_DEFINE_ARGUMENTS)) {
                return;
            }

            $yiiEvent = new DefineGqlArgumentsEvent([
                'arguments' => $event->arguments,
            ]);
            YiiEvent::trigger(LegacyElementArguments::class, LegacyElementArguments::EVENT_DEFINE_ARGUMENTS, $yiiEvent);
            $event->arguments = $yiiEvent->arguments;
        });

        Event::listen(ElementPopulating::class, function(ElementPopulating $event) {
            $legacyResolverClass = str_replace('CraftCms\\Cms\\Gql\\', 'craft\\gql\\', $event->resolverClass);
            if (!YiiEvent::hasHandlers($legacyResolverClass, LegacyElementMutationResolver::EVENT_BEFORE_POPULATE_ELEMENT)) {
                return;
            }

            $yiiEvent = new MutationPopulateElementEvent([
                'arguments' => $event->arguments,
                'element' => $event->element,
            ]);
            YiiEvent::trigger($legacyResolverClass, LegacyElementMutationResolver::EVENT_BEFORE_POPULATE_ELEMENT, $yiiEvent);
            $event->arguments = $yiiEvent->arguments;
            $event->element = $yiiEvent->element;
        });

        Event::listen(ElementPopulated::class, function(ElementPopulated $event) {
            $legacyResolverClass = str_replace('CraftCms\\Cms\\Gql\\', 'craft\\gql\\', $event->resolverClass);
            if (!YiiEvent::hasHandlers($legacyResolverClass, LegacyElementMutationResolver::EVENT_AFTER_POPULATE_ELEMENT)) {
                return;
            }

            $yiiEvent = new MutationPopulateElementEvent([
                'arguments' => $event->arguments,
                'element' => $event->element,
            ]);
            YiiEvent::trigger($legacyResolverClass, LegacyElementMutationResolver::EVENT_AFTER_POPULATE_ELEMENT, $yiiEvent);
            $event->arguments = $yiiEvent->arguments;
            $event->element = $yiiEvent->element;
        });
    }
}
