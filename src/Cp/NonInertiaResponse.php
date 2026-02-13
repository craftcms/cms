<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wraps legacy (non-Inertia) CP content in the Inertia app shell.
 *
 * This allows legacy Twig-rendered pages to be displayed inside the
 * Vue/Inertia layout, getting the modern CP chrome (sidebar, header, etc.)
 * while their content remains server-rendered HTML.
 *
 * Usage in a controller:
 *
 *     return NonInertiaResponse::fromHtml($renderedHtml);
 *     return NonInertiaResponse::fromView('craftcms::entries.index', $data);
 */
final class NonInertiaResponse implements Responsable
{
    private function __construct(
        private readonly string $content,
    ) {}

    /**
     * Create a response from pre-rendered HTML content.
     */
    public static function fromHtml(string $html): self
    {
        return new self($html);
    }

    /**
     * Create a response from a Laravel view (Blade or Twig).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromView(string $view, array $data = []): self
    {
        return new self(view($view, $data)->render());
    }

    public function toResponse($request): Response
    {
        return response()->view('c::legacy', [
            'legacyContent' => $this->content,
        ]);
    }
}
