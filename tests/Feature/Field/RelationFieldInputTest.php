<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Users;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Symfony\Component\DomCrawler\Crawler;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

function relationInputHtml(Users|Assets $field): string
{
    return (fn () => $this->inputHtml($this->normalizeValue(null, null), null, false))->call($field);
}

it('renders the element select custom element', function () {
    $field = new Users(['handle' => 'reviewers']);

    expect(relationInputHtml($field))->toContain('<craft-element-select-input ');
});

it('refuses a template-based input without the adapter', function () {
    $field = new class(['handle' => 'reviewers']) extends Users
    {
        protected string $inputTemplate = 'my-plugin/_input';
    };

    expect(fn () => relationInputHtml($field))
        ->toThrow(LogicException::class, 'only craftcms/yii2-adapter supports');
});

it('passes the asset upload and folder settings to the asset selector', function () {
    $html = (fn () => $this->elementSelectHtml([
        'id' => 'files',
        'name' => 'files',
        'elementType' => Asset::class,
        'describedBy' => 'files-instructions',
        'canUpload' => true,
        'defaultFieldLayoutId' => 5,
        'showFolders' => false,
        'showSourcePath' => false,
        'defaultSource' => 'volume:abc',
        'modalSettings' => ['defaultSiteId' => 1],
    ]))->call(new Assets(['handle' => 'files']));

    $settings = Json::decode(new Crawler($html)->filter('craft-asset-select-input')->attr('settings'));

    expect($settings)->toMatchArray([
        'canUpload' => true,
        'defaultFieldLayoutId' => 5,
        'describedBy' => 'files-instructions',
    ])->and($settings['modalSettings'])->toMatchArray([
        'defaultSiteId' => 1,
        'defaultSource' => 'volume:abc',
        'showSourcePath' => false,
        'indexSettings' => ['showFolders' => false],
    ]);
});
