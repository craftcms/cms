<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Middleware;

use Closure;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Queue\JobProgressService;
use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injects JavaScript into HTML responses to trigger the queue runner.
 *
 * This preserves the Craft behavior of automatically running queue jobs
 * on site (frontend) requests when `runQueueAutomatically` is enabled.
 */
final readonly class RunQueueMiddleware
{
    public function __construct(
        private Queue $queue,
        private JobProgressService $progressService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldInjectQueueRunner($request, $response)) {
            return $response;
        }

        return $this->injectQueueRunner($response);
    }

    private function shouldInjectQueueRunner(Request $request, Response $response): bool
    {
        if (! Cms::config()->runQueueAutomatically) {
            return false;
        }

        if ($request->ajax()) {
            return false;
        }

        if (! $response->isSuccessful()) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        if (! str_contains((string) $contentType, 'text/html') && ! str_contains((string) $contentType, 'application/xhtml+xml')) {
            return false;
        }

        if ($this->progressService->getActive()->isNotEmpty()) {
            return false;
        }

        if ($this->queue->size() === 0) {
            return false;
        }

        return true;
    }

    private function injectQueueRunner(Response $response): Response
    {
        $content = $response->getContent();

        if ($content === false) {
            return $response;
        }

        $url = Json::encode(UrlHelper::actionUrl('queue/run'));

        $js = <<<JS
<script type="text/javascript">
/*<![CDATA[*/
(function(){
  try {
    var req = new XMLHttpRequest();
    req.open('GET', $url, true)
    req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    if (req.readyState === 4) return;
    req.send();
  } catch (e) {}
})();
/*]]>*/
</script>

JS;

        $pos = strripos($content, '</body>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos).$js.substr($content, $pos);
        } else {
            $content .= $js;
        }

        $response->setContent($content);

        return $response;
    }
}
