<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\UserGroupSelect;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Data\UserGroup;
use Symfony\Component\DomCrawler\Crawler;

it('resolves user groups while storing their project-config UIDs', function () {
    $editors = new UserGroup([
        'id' => 10,
        'uid' => '11111111-1111-4111-8111-111111111111',
        'name' => 'Editors',
        'handle' => 'editors',
        'description' => 'Reviews editorial content.',
    ]);
    $publishers = new UserGroup([
        'id' => 20,
        'uid' => '22222222-2222-4222-8222-222222222222',
        'name' => 'Publishers',
        'handle' => 'publishers',
        'description' => null,
    ]);
    UserGroups::shouldReceive('getAllGroups')->twice()->andReturn(collect([$editors, $publishers]));
    $form = Form::make([
        Field::make('User groups', UserGroupSelect::make('groups'))->required(),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        values: ['settings' => ['groups' => [$editors->uid]]],
    ));
    $control = $payload->nodes[0]->control;
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));
    $combobox = $crawler->filter('craft-combobox[name="settings[groups]"]');

    expect($control?->component)->toBe('craft:user-group-select')
        ->and($control?->props['canCreate'])->toBeFalse()
        ->and($control?->props['groups'])->toBe([
            [
                'id' => $editors->id,
                'uid' => $editors->uid,
                'name' => 'Editors',
                'handle' => 'editors',
                'description' => 'Reviews editorial content.',
            ],
            [
                'id' => $publishers->id,
                'uid' => $publishers->uid,
                'name' => 'Publishers',
                'handle' => 'publishers',
                'description' => null,
            ],
        ])
        ->and($payload->values['settings']['groups'])->toBe([$editors->uid])
        ->and($combobox)->toHaveCount(1)
        ->and($combobox->attr('multiple-choice'))->not->toBeNull()
        ->and(json_decode((string) $combobox->attr('model-value'), true))->toBe([$editors->uid]);

    $readOnly = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        values: ['settings' => ['groups' => [$editors->uid]]],
        mode: ControlMode::ReadOnly,
    ));

    expect(new Crawler(app(FormHtmlRenderer::class)->render($readOnly))->filter('[name]'))->toHaveCount(0);
});

it('uses an empty ordered list as its canonical default', function () {
    UserGroups::shouldReceive('getAllGroups')->once()->andReturn(collect());
    $payload = app(FormResolver::class)->resolve(
        Form::make([Field::make()->control(UserGroupSelect::make('groups'))]),
        new FormContext(namespace: 'settings'),
    );

    expect($payload->values)->toBe(['settings' => ['groups' => []]]);
});
