<?php

namespace craft\controllers;

use Craft;
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
        $capabilities = [
            'publicTools' => array_map(static fn($ref) => ['label' => $ref->tool->title ?? $ref->tool->name, 'value' => $ref->tool->name], array_values($definitions->getTools())),
            'publicResources' => array_map(static fn($ref) => ['label' => $ref->resource->name, 'value' => $ref->resource->uri], array_values($definitions->getResources())),
            'publicResourceTemplates' => array_map(static fn($ref) => ['label' => $ref->resourceTemplate->name, 'value' => $ref->resourceTemplate->uriTemplate], array_values($definitions->getResourceTemplates())),
            'publicPrompts' => array_map(static fn($ref) => ['label' => $ref->prompt->name, 'value' => $ref->prompt->name], array_values($definitions->getPrompts())),
        ];
        $options = static fn(array $models, string $message) => array_map(static fn($model) => [
            'label' => Craft::t('app', $message, ['name' => Craft::t('site', $model->name)]),
            'value' => $model->handle,
        ], $models);
        $elementTypes = Craft::$app->get('mcp')->getCapabilityContainer()->get(PublicElementTypes::class);
        $additionalTypes = array_filter($elementTypes->all(), static fn($name) => !$elementTypes->make($name)->isScopeControlled(), ARRAY_FILTER_USE_KEY);
        $scopes = [
            'publicSiteHandles' => $options(Craft::$app->getSites()->getAllSites(withDisabled: true), 'Query elements in the “{name}” site'),
            'publicSectionHandles' => $options(Craft::$app->getEntries()->getAllSections(), 'Query entries in the “{name}” section'),
            'publicNestedEntryFieldHandles' => $options(array_values(array_filter(Craft::$app->getFields()->getAllFields(), static fn($field) => in_array($field::class, Craft::$app->getFields()->getNestedEntryFieldTypes(), true))), 'Query entries in the “{name}” field'),
            'publicVolumeHandles' => $options(Craft::$app->getVolumes()->getAllVolumes(), 'Query assets in the “{name}” volume'),
            'publicElementTypes' => array_map(static fn($name, $class) => [
                'label' => Craft::t('app', 'Query {type}', ['type' => $class::pluralLowerDisplayName()]),
                'value' => $name,
            ], array_keys($additionalTypes), array_values($additionalTypes)),
            'publicUserGroupHandles' => $options(Craft::$app->getUserGroups()->getAllGroups(), 'Query users in the “{name}” user group'),
        ];

        return $this->renderTemplate('mcp/settings', compact('settings', 'capabilities', 'scopes'), View::TEMPLATE_MODE_CP);
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
