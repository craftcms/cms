<?php

namespace craft\controllers;

use Craft;
use craft\elements\User;
use craft\mcp\CapabilityDiscovery;
use craft\mcp\PublicElementTypes;
use craft\mcp\Settings;
use craft\web\Controller;
use craft\web\View;
use yii\web\Response;

class McpSettingsController extends Controller
{
    public function actionIndex(?Settings $settings = null): Response
    {
        $this->requireAdmin(false);
        $settings ??= Craft::$app->get('mcp')->getSettings();
        $definitions = Craft::$app->get('mcp')->getCapabilityContainer()->get(CapabilityDiscovery::class)->discoverPublic(dirname(__DIR__) . '/mcp/capabilities', ['.']);
        $option = static fn(string $attribute, string $value, string $label) => compact('attribute', 'value', 'label');
        $group = static fn(string $label, array $options) => ['label' => Craft::t('app', $label), 'options' => $options];
        $models = static fn(string $attribute, array $models, string $message) => array_map(static fn($model) => $option(
            $attribute,
            $model->handle,
            Craft::t('app', $message, ['name' => Craft::t('site', $model->name)]),
        ), $models);

        $capabilities = [
            $group('Tools', array_map(static fn($ref) => $option('publicTools', $ref->tool->name, $ref->tool->title ?? $ref->tool->name), array_values($definitions->getTools()))),
            $group('Resources', array_map(static fn($ref) => $option('publicResources', $ref->resource->uri, $ref->resource->name), array_values($definitions->getResources()))),
            $group('Resource Templates', array_map(static fn($ref) => $option('publicResourceTemplates', $ref->resourceTemplate->uriTemplate, $ref->resourceTemplate->name), array_values($definitions->getResourceTemplates()))),
            $group('Prompts', array_map(static fn($ref) => $option('publicPrompts', $ref->prompt->name, $ref->prompt->name), array_values($definitions->getPrompts()))),
        ];

        $elementTypes = Craft::$app->get('mcp')->getCapabilityContainer()->get(PublicElementTypes::class);
        $otherTypes = array_filter($elementTypes->all(), static fn($class, $name) => !$elementTypes->make($name)->isScopeControlled() && !is_a($class, User::class, true), ARRAY_FILTER_USE_BOTH);
        $content = [
            $group('Sites', $models('publicSiteHandles', Craft::$app->getSites()->getAllSites(withDisabled: true), 'Query elements in the “{name}” site')),
            $group('Entries', $models('publicSectionHandles', Craft::$app->getEntries()->getAllSections(), 'Query entries in the “{name}” section')),
            $group('Nested Entry Fields', $models('publicNestedEntryFieldHandles', array_values(array_filter(Craft::$app->getFields()->getAllFields(), static fn($field) => in_array($field::class, Craft::$app->getFields()->getNestedEntryFieldTypes(), true))), 'Query entries in the “{name}” field')),
            $group('Assets', $models('publicVolumeHandles', Craft::$app->getVolumes()->getAllVolumes(), 'Query assets in the “{name}” volume')),
            // Each other element type gets its own group, such as Addresses
            ...array_map(static fn($name, $class) => ['label' => $class::pluralDisplayName(), 'options' => [
                $option('publicElementTypes', $name, Craft::t('app', 'Query {type}', ['type' => $class::pluralLowerDisplayName()])),
            ]], array_keys($otherTypes), array_values($otherTypes)),
            $group('Users', [
                $option('publicElementTypes', $elementTypes->name(User::class), Craft::t('app', 'Query all users')),
                ...$models('publicUserGroupHandles', Craft::$app->getUserGroups()->getAllGroups(), 'Query users in the “{name}” user group'),
            ]),
        ];

        return $this->renderTemplate('mcp/settings', compact('settings', 'capabilities', 'content'), View::TEMPLATE_MODE_CP);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();
        $settings = new Settings();
        $data = $this->request->getBodyParam('settings', []);
        foreach ($settings->getAttributes() as $attribute => $value) {
            if (is_array($value) && ($data[$attribute] ?? null) === '') {
                $data[$attribute] = [];
            }
        }

        $settings->setAttributes($data);

        if (!$settings->validate()) {
            $this->setFailFlash(Craft::t('app', 'Could not save MCP settings.'));
            return $this->actionIndex($settings);
        }

        Craft::$app->getProjectConfig()->set('mcp', $settings->getAttributes(), 'Update MCP settings');
        Craft::$app->get('mcp')->resetSettings();
        $this->setSuccessFlash(Craft::t('app', 'MCP settings saved.'));
        return $this->redirectToPostedUrl();
    }
}
