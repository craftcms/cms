<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Craft;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bridges Yii2 asset bundles into Laravel responses.
 *
 * This middleware collects assets registered via Yii2's View component
 * (registerAssetBundle, registerJs, registerCss, etc.) and injects them
 * into the response.
 *
 * For full page loads (HTML responses), assets are injected directly into
 * the HTML before </head> and </body> tags.
 *
 * For Inertia partial reloads (XHR requests), assets are merged into the
 * JSON response props as `bridgedHeadHtml` and `bridgedBodyHtml`.
 */
final readonly class BridgeAssetBundles
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $view = Craft::$app->getView();

        // Get collected assets from Yii2's View
        $headHtml = $view->getHeadHtml();
        $bodyHtml = $view->getBodyHtml();

        // No assets to bridge
        if ($headHtml === '' && $bodyHtml === '') {
            return $response;
        }

        // Handle Inertia XHR requests (partial page loads)
        if ($request->header('X-Inertia')) {
            return $this->handleInertiaRequest($response, $headHtml, $bodyHtml);
        }

        // Handle full HTML page loads
        return $this->handleHtmlRequest($response, $headHtml, $bodyHtml);
    }

    /**
     * Merges bridged assets into Inertia JSON response props.
     */
    private function handleInertiaRequest(Response $response, string $headHtml, string $bodyHtml): Response
    {
        $content = $response->getContent();
        if ($content === false || $content === '') {
            return $response;
        }

        $data = json_decode($content, true);
        if (! is_array($data) || ! isset($data['props'])) {
            return $response;
        }

        if ($headHtml !== '') {
            $data['props']['bridgedHeadHtml'] = $headHtml;
        }
        if ($bodyHtml !== '') {
            $data['props']['bridgedBodyHtml'] = $bodyHtml;
        }

        $response->setContent(json_encode($data));

        return $response;
    }

    /**
     * Injects bridged assets directly into HTML response.
     */
    private function handleHtmlRequest(Response $response, string $headHtml, string $bodyHtml): Response
    {
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains((string) $contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return $response;
        }

        // Inject head assets before </head>
        if ($headHtml !== '') {
            $content = str_replace('</head>', $headHtml.'</head>', $content);
        }

        // Inject body assets before </body>
        if ($bodyHtml !== '') {
            $content = str_replace('</body>', $bodyHtml.'</body>', $content);
        }

        $response->setContent($content);

        return $response;
    }
}
