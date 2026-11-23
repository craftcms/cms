<?php

declare(strict_types=1);

use craft\web\CpScreenResponseBehavior;
use craft\web\CpScreenResponseFormatter;
use craft\web\Response;
use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Http\Responses\BridgedScreen;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Support\Header;

uses(DatabaseTestCase::class);

it('encrypts a bridged screen’s save shortcut redirect', function() {
    $this->actingAs(User::findOne());

    $request = Request::create('/admin/test');
    $request->headers->add([
        Header::INERTIA => 'true',
        Header::VERSION => (string)Cp::vite()->manifestHash(),
        Header::PARTIAL_COMPONENT => BridgedScreen::PAGE,
        Header::PARTIAL_ONLY => 'saveShortcutRedirect',
    ]);
    app()->instance('request', $request);

    $response = new Response();
    $response->attachBehavior(CpScreenResponseBehavior::NAME, CpScreenResponseBehavior::class);
    $response
        ->action('test/save')
        ->saveShortcutRedirectUrl('test/{id}')
        ->contentHtml('<p>Fields</p>');

    (new CpScreenResponseFormatter())->format($response);

    $redirect = Json::decode($response->content)['props']['saveShortcutRedirect'];

    expect(Crypt::decrypt($redirect))->toBe('test/{id}');
});
