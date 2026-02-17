<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\RunQueue;
use CraftCms\Cms\Queue\JobProgress;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->queue = Mockery::mock(Queue::class);
    $this->progressService = Mockery::mock(JobProgress::class);
    $this->middleware = new RunQueue($this->queue, $this->progressService);
    $this->generalConfig = Cms::config();
});

it('does not inject script when runQueueAutomatically is disabled', function () {
    $this->generalConfig->runQueueAutomatically(false);

    $request = Request::create('/');
    $response = new Response('<html><body>Test</body></html>');
    $response->headers->set('Content-Type', 'text/html');

    $result = $this->middleware->handle($request, fn () => $response);

    expect($result->getContent())->not->toContain('<script');
});

it('does not inject script for ajax requests', function () {
    $this->generalConfig->runQueueAutomatically(true);

    $request = Request::create('/');
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $response = new Response('<html><body>Test</body></html>');
    $response->headers->set('Content-Type', 'text/html');

    $result = $this->middleware->handle($request, fn () => $response);

    expect($result->getContent())->not->toContain('<script');
});

it('does not inject script for non-HTML responses', function () {
    $this->generalConfig->runQueueAutomatically(true);
    $this->progressService->allows('getActive')->andReturn(collect());
    $this->queue->allows('size')->andReturn(1);

    $request = Request::create('/');
    $response = new Response('{"data": "json"}');
    $response->headers->set('Content-Type', 'application/json');

    $result = $this->middleware->handle($request, fn () => $response);

    expect($result->getContent())->not->toContain('<script');
});

it('does not inject script when jobs are already running', function () {
    $this->generalConfig->runQueueAutomatically(true);
    $this->progressService->allows('getActive')->andReturn(collect([
        ['uid' => 'test-123', 'description' => 'Test job', 'progress' => 50, 'progressLabel' => null],
    ]));

    $request = Request::create('/');
    $response = new Response('<html><body>Test</body></html>');
    $response->headers->set('Content-Type', 'text/html');

    $result = $this->middleware->handle($request, fn () => $response);

    expect($result->getContent())->not->toContain('<script');
});

it('does not inject script when queue is empty', function () {
    $this->generalConfig->runQueueAutomatically(true);
    $this->progressService->allows('getActive')->andReturn(collect());
    $this->queue->allows('size')->andReturn(0);

    $request = Request::create('/');
    $response = new Response('<html><body>Test</body></html>');
    $response->headers->set('Content-Type', 'text/html');

    $result = $this->middleware->handle($request, fn () => $response);

    expect($result->getContent())->not->toContain('<script');
});

it('injects queue runner script for HTML responses with waiting jobs', function () {
    $this->generalConfig->runQueueAutomatically(true);
    $this->progressService->allows('getActive')->andReturn(collect());
    $this->queue->allows('size')->andReturn(5);

    $request = Request::create('/');
    $response = new Response('<html><body>Test</body></html>');
    $response->headers->set('Content-Type', 'text/html');

    $result = $this->middleware->handle($request, fn () => $response);

    expect($result->getContent())
        ->toContain('<script')
        ->toContain('XMLHttpRequest')
        ->toContain('queue\\/run'); // Slashes are escaped in JSON
});

it('injects script before closing body tag', function () {
    $this->generalConfig->runQueueAutomatically(true);
    $this->progressService->allows('getActive')->andReturn(collect());
    $this->queue->allows('size')->andReturn(1);

    $request = Request::create('/');
    $response = new Response('<html><body><p>Content</p></body></html>');
    $response->headers->set('Content-Type', 'text/html');

    $result = $this->middleware->handle($request, fn () => $response);

    $content = $result->getContent();
    $scriptPos = strpos((string) $content, '<script');
    $bodyClosePos = strpos((string) $content, '</body>');

    expect($scriptPos)->toBeLessThan($bodyClosePos);
});

it('appends script if no closing body tag exists', function () {
    $this->generalConfig->runQueueAutomatically(true);
    $this->progressService->allows('getActive')->andReturn(collect());
    $this->queue->allows('size')->andReturn(1);

    $request = Request::create('/');
    $response = new Response('<html><div>Content</div></html>');
    $response->headers->set('Content-Type', 'text/html');

    $result = $this->middleware->handle($request, fn () => $response);

    expect($result->getContent())
        ->toContain('<script')
        ->toEndWith('</script>');
});

it('handles xhtml content type', function () {
    $this->generalConfig->runQueueAutomatically(true);
    $this->progressService->allows('getActive')->andReturn(collect());
    $this->queue->allows('size')->andReturn(1);

    $request = Request::create('/');
    $response = new Response('<html><body>Test</body></html>');
    $response->headers->set('Content-Type', 'application/xhtml+xml');

    $result = $this->middleware->handle($request, fn () => $response);

    expect($result->getContent())->toContain('<script');
});

it('does not inject script for error responses', function () {
    $this->generalConfig->runQueueAutomatically(true);

    $request = Request::create('/');
    $response = new Response('<html><body>Error</body></html>', 500);
    $response->headers->set('Content-Type', 'text/html');

    $result = $this->middleware->handle($request, fn () => $response);

    expect($result->getContent())->not->toContain('<script');
});
