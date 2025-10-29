<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\helpers\Cp;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class SiteGroupsController
{
    use RespondsWithFlash;

    public function __construct(
        private SiteGroups $siteGroups,
    ) {}

    public function showGroupRenameField(Request $request): JsonResponse
    {
        $view = \Craft::$app->getView();
        $view->startJsBuffer();

        $html = $view->namespaceInputs(fn () => Cp::autosuggestFieldHtml([
            'label' => t('Group Name'),
            'instructions' => t('What this group will be called in the control panel.'),
            'id' => 'name',
            'name' => 'name',
            'value' => $request->input('name', ''),
            'suggestEnvVars' => true,
            'required' => true,
        ]), 'name'.Str::random(10));
        $js = $view->clearJsBuffer();

        return new JsonResponse(compact('html', 'js'));
    }

    public function store(SiteGroup $siteGroup): Response
    {
        $this->siteGroups->saveGroup($siteGroup);

        $data = $siteGroup->toArray();
        $data['name'] = t($data['name'], category: 'site');

        return $this->asSuccess(data: [
            'group' => $data,
        ]);
    }

    public function destroy(Request $request): Response
    {
        $groupId = $request->validate([
            'id' => ['required', 'integer'],
        ])['id'];

        if (! $this->siteGroups->deleteGroupById($groupId)) {
            return $this->asFailure();
        }

        return $this->asSuccess(t('Group deleted.'));
    }
}
