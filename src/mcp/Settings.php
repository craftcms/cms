<?php

namespace craft\mcp;

use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\base\Model;

/** Public MCP approvals, stored in project config. @since 5.12.0 */
class Settings extends Model
{
    public bool $publicEnabled = false;
    public string $publicRoute = '/mcp';
    public array $publicSiteHandles = [];
    public array $publicSectionHandles = [];
    public array $publicNestedEntryFieldHandles = [];
    public array $publicVolumeHandles = [];
    public array $publicUserGroupHandles = [];
    public array $publicElementTypes = [];
    public bool $publicAllowDrafts = false;
    public bool $publicAllowRevisions = false;
    public bool $publicAllowInactive = false;
    public array $publicTools = [];
    public array $publicResources = [];
    public array $publicResourceTemplates = [];
    public array $publicPrompts = [];

    protected function defineRules(): array
    {
        return [
            [['publicEnabled', 'publicAllowDrafts', 'publicAllowRevisions', 'publicAllowInactive'], 'boolean'],
            ['publicRoute', 'required'],
            ['publicRoute', 'string', 'max' => 255],
            ['publicRoute', 'validateRoute'],
            [[...$this->scopeAttributes(), 'publicTools', 'publicResources', 'publicResourceTemplates', 'publicPrompts'], 'each', 'rule' => ['string', 'max' => 512]],
            [$this->scopeAttributes(), 'validateScopes'],
        ];
    }

    public function validateRoute(string $attribute): void
    {
        $path = trim($this->publicRoute, '/');
        $general = Craft::$app->getConfig()->getGeneral();
        $reserved = array_filter([$general->cpTrigger, $general->actionTrigger, '.well-known']);

        if ($path === '' || !preg_match('~^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*$~', $path) || in_array(explode('/', $path)[0], $reserved, true)) {
            $this->addError($attribute, Craft::t('app', 'Choose a nonempty public path outside the control panel and action routes.'));
            return;
        }

        foreach (array_keys(Craft::$app->getRoutes()->getConfigFileRoutes()) as $route) {
            $route = preg_replace('/^(?:GET|POST|PUT|PATCH|DELETE|OPTIONS|HEAD)(?:,[A-Z]+)*\s+/', '', $route);

            if (trim($route, '/') === $path) {
                $this->addError($attribute, Craft::t('app', 'This path is already used by a route.'));
                return;
            }
        }

        foreach (Craft::$app->getRoutes()->getProjectConfigRoutes() as $route) {
            if (($route['pattern'] ?? null) === $path) {
                $this->addError($attribute, Craft::t('app', 'This path is already used by a route.'));
            }
        }
    }

    public function validateScopes(string $attribute): void
    {
        foreach ($this->$attribute as $handle) {
            $model = match ($attribute) {
                'publicSiteHandles' => Craft::$app->getSites()->getSiteByHandle($handle, true),
                'publicSectionHandles' => Craft::$app->getEntries()->getSectionByHandle($handle),
                'publicNestedEntryFieldHandles' => Craft::$app->getFields()->getFieldByHandle($handle),
                'publicVolumeHandles' => Craft::$app->getVolumes()->getVolumeByHandle($handle),
                'publicUserGroupHandles' => Craft::$app->getUserGroups()->getGroupByHandle($handle),
                'publicElementTypes' => Craft::$app->get('mcp')->getCapabilityContainer()->get(PublicElementTypes::class)->resolve($handle),
                default => throw new \InvalidArgumentException("Unknown public scope [$attribute]."),
            };

            if (!$model || ($attribute === 'publicNestedEntryFieldHandles' && (!$model instanceof ElementContainerFieldInterface || !in_array($model::class, Craft::$app->getFields()->getNestedEntryFieldTypes(), true)))) {
                $this->addError($attribute, Craft::t('app', 'Unknown public content source: {handle}', ['handle' => $handle]));
            }
        }
    }

    private function scopeAttributes(): array
    {
        return ['publicSiteHandles', 'publicSectionHandles', 'publicNestedEntryFieldHandles', 'publicVolumeHandles', 'publicUserGroupHandles', 'publicElementTypes'];
    }
}
