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
use CraftCms\Cms\Gql\Events\AfterPopulateElement;
use CraftCms\Cms\Gql\Events\BeforePopulateElement;
use CraftCms\Cms\Gql\Events\DefineGqlArguments;
use CraftCms\Cms\Gql\Events\DefineGqlTypeFields;
use CraftCms\Cms\Gql\Events\RegisterGqlArgumentHandlers;
use CraftCms\Cms\Gql\Events\RegisterGqlEagerLoadableFields;
use Illuminate\Support\Facades\Event;

class LegacyGqlEvents
{
    public static function register(): void
    {
        Event::listen(DefineGqlTypeFields::class, function(DefineGqlTypeFields $event) {
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

        Event::listen(RegisterGqlArgumentHandlers::class, function(RegisterGqlArgumentHandlers $event) {
            if (!YiiEvent::hasHandlers(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS)) {
                return;
            }

            $yiiEvent = new RegisterGqlArgumentHandlersEvent([
                'handlers' => $event->handlers,
            ]);
            YiiEvent::trigger(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS, $yiiEvent);
            $event->handlers = $yiiEvent->handlers;
        });

        Event::listen(RegisterGqlEagerLoadableFields::class, function(RegisterGqlEagerLoadableFields $event) {
            if (!YiiEvent::hasHandlers(LegacyElementQueryConditionBuilder::class, LegacyElementQueryConditionBuilder::EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS)) {
                return;
            }

            $yiiEvent = new LegacyRegisterGqlEagerLoadableFields([
                'fieldList' => $event->fieldList,
            ]);
            YiiEvent::trigger(LegacyElementQueryConditionBuilder::class, LegacyElementQueryConditionBuilder::EVENT_REGISTER_GQL_EAGERLOADABLE_FIELDS, $yiiEvent);
            $event->fieldList = $yiiEvent->fieldList;
        });

        Event::listen(DefineGqlArguments::class, function(DefineGqlArguments $event) {
            if (!YiiEvent::hasHandlers(LegacyElementArguments::class, LegacyElementArguments::EVENT_DEFINE_ARGUMENTS)) {
                return;
            }

            $yiiEvent = new DefineGqlArgumentsEvent([
                'arguments' => $event->arguments,
            ]);
            YiiEvent::trigger(LegacyElementArguments::class, LegacyElementArguments::EVENT_DEFINE_ARGUMENTS, $yiiEvent);
            $event->arguments = $yiiEvent->arguments;
        });

        Event::listen(BeforePopulateElement::class, function(BeforePopulateElement $event) {
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

        Event::listen(AfterPopulateElement::class, function(AfterPopulateElement $event) {
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
