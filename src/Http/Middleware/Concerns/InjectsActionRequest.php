<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware\Concerns;

use CraftCms\Cms\Support\Json;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\action_url;

/**
 * Injects JavaScript into HTML responses that requests a controller action in the background.
 *
 * @internal
 *
 * @since 6.0.0
 */
trait InjectsActionRequest
{
    private function canInjectActionRequest(Request $request, Response $response): bool
    {
        if ($request->ajax() || ! $response->isSuccessful()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html') || str_contains($contentType, 'application/xhtml+xml');
    }

    private function injectActionRequest(Response $response, string $action): Response
    {
        $content = $response->getContent();

        if ($content === false) {
            return $response;
        }

        $url = Json::encode(action_url($action));

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
