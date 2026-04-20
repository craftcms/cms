<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\User\Elements\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());
});

function mockElementRequest(array $routeParams, mixed $element): ElementRequest
{
    $request = Mockery::mock(ElementRequest::class);
    $request->shouldReceive('route')
        ->once()
        ->with('id')
        ->andReturn($routeParams['id']);
    $request->shouldReceive('route')
        ->once()
        ->with('uid')
        ->andReturn($routeParams['uid']);
    $request->shouldReceive('element')
        ->once()
        ->with($routeParams)
        ->andReturn($element);

    app()->instance(ElementRequest::class, $request);

    return $request;
}

it('returns responses returned by the element request', function () {
    mockElementRequest([
        'id' => 123,
        'uid' => null,
    ], response('teapot', 418));

    get(cp_url('edit/123-test'))
        ->assertStatus(418)
        ->assertSeeText('teapot');
});

it('returns responses returned by the element request for uuid routes', function () {
    $uuid = '6d4f8ad5-9bb1-4b79-9140-64d3d4e9f3b5';

    mockElementRequest([
        'id' => null,
        'uid' => $uuid,
    ], response('teapot', 418));

    get(cp_url("edit/$uuid"))
        ->assertStatus(418)
        ->assertSeeText('teapot');
});

it('redirects to non-standard control panel edit urls', function () {
    $element = Mockery::mock(ElementInterface::class);
    $element->shouldReceive('getCpEditUrl')
        ->once()
        ->andReturn('https://example.com/custom-edit');

    mockElementRequest([
        'id' => 123,
        'uid' => null,
    ], $element);

    get(cp_url('edit/123-test'))
        ->assertRedirect('https://example.com/custom-edit');
});

it('aborts when the element has no control panel edit url', function () {
    $element = Mockery::mock(ElementInterface::class);
    $element->shouldReceive('getCpEditUrl')
        ->once()
        ->andReturn(null);

    mockElementRequest([
        'id' => 456,
        'uid' => null,
    ], $element);

    $this->withoutExceptionHandling();

    expect(fn () => get(cp_url('edit/456-test')))
        ->toThrow(HttpException::class, 'The element doesn’t have an edit page.');
});

it('returns inline edit responses for standard control panel edit urls', function () {})->todo();
