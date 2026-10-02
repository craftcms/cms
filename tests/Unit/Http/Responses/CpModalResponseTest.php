<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\Responses\CpModalResponse;
use CraftCms\Cms\Support\Facades\InputNamespace;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

it('accepts markup for html sections', function (string $method, string $property) {
    $markup = new Markup('<div>HTML</div>', 'UTF-8');
    $response = new CpModalResponse;

    expect($response->$method($markup))->toBe($response)
        ->and($response->$property)->toBe($markup);
})->with([
    ['contentHtml', 'contentHtml'],
    ['errorSummary', 'errorSummary'],
]);

it('renders Forms and legacy HTML under the same modal namespace', function () {
    InputNamespace::set('outer');

    $response = new CpModalResponse()
        ->action('entries/reassign')
        ->contentHtml(fn () => '<input name="legacy" value="kept">')
        ->form(Form::make([
            HiddenField::make('hardDelete'),
            HiddenField::make(['oldUserIds', '0']),
            HiddenField::make(['oldUserIds', '1']),
        ]), ['hardDelete' => '0', 'oldUserIds' => [12, 34]]);

    $data = $response->toResponse(Request::create('/'))->getData(true);
    $form = new Crawler('<form>'.$data['content'].'</form>', 'http://localhost')->filter('form')->form();

    expect($form->getPhpValues())->toBe([
        $data['namespace'] => [
            'legacy' => 'kept',
            'hardDelete' => '0',
            'oldUserIds' => ['12', '34'],
            'action' => 'entries/reassign',
        ],
    ])->and(InputNamespace::get())->toBe('outer');
});

it('allows a Form to be configured during modal preparation', function () {
    $request = Request::create('/', server: ['HTTP_X_CRAFT_CONTAINER_ID' => 'modal']);
    $response = new CpModalResponse()->prepareModal(function (CpModalResponse $response) {
        $response->form(Form::make([HiddenField::make('token')]), ['token' => 'prepared']);
    });

    $data = $response->toResponse($request)->getData(true);
    $form = new Crawler('<form>'.$data['content'].'</form>', 'http://localhost')->filter('form')->form();

    expect($form->getPhpValues())->toBe([$data['namespace'] => ['token' => 'prepared']]);
});
