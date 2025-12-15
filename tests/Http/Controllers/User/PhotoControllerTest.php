<?php

use CraftCms\Cms\Http\Controllers\Users\PhotoController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    postJson(action([PhotoController::class, 'renderInput']))->assertUnauthorized();
    postJson(action([PhotoController::class, 'upload']))->assertUnauthorized();
    postJson(action([PhotoController::class, 'destroy']))->assertUnauthorized();
});

test('userId is required', function () {
    postJson(action([PhotoController::class, 'renderInput']))->assertJsonValidationErrorFor('userId');
    postJson(action([PhotoController::class, 'upload']))->assertJsonValidationErrorFor('userId');
    postJson(action([PhotoController::class, 'destroy']))->assertJsonValidationErrorFor('userId');
});

test('renderInput', function () {
    postJson(action([PhotoController::class, 'renderInput'], [
        'userId' => auth()->id(),
    ]))->assertJsonStructure([
        'html',
        'photoId',
        'headerPhotoHtml',
    ]);
});

test('upload', function () {})->todo('Add when volumes have been ported');
test('destroy', function () {})->todo('Add when volumes have been ported');
