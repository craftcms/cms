<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route\Data;

use CraftCms\Cms\Support\Html;
use Illuminate\Routing\Router;
use Spatie\LaravelData\Dto;

final class Route extends Dto
{
    public function __construct(
        /**
         * @var string $uid The route UID.
         */
        public ?string $uid,

        /**
         * @var array $uriParts The URI as defined by the user. This is an array where each element is either a
         *            string or an array containing the name of a subpattern and the subpattern
         */
        public array $uriParts {
            get => array_filter($this->uriParts);
            set(array $value) => $this->uriParts = $value;
        },

        /**
         * @var string $template The template to route matching requests to
         */
        public string $template,

        /**
         * @var string|null The site UID the route should be limited to, if any
         */
        public ?string $siteUid,

        public ?int $sortOrder = null,
    ) {
    }

    public function configData(): array
    {
        return [
            'template' => $this->template,
            'uriParts' => $this->uriParts,
            'siteUid' => $this->siteUid,
        ];
    }

    public function getUri(): string
    {
        return collect($this->uriParts)->map(function (string|array|null $part) {
            if (is_string($part)) {
                return $part;
            }

            return "{{$part[0]}}";
        })->implode('');
    }

    public function register(Router $router): void
    {
        $route = $router->view($this->getUri(), $this->template);

        foreach ($this->uriParts as $part) {
            if (is_string($part)) {
                continue;
            }

            $route->where($part[0], $part[1]);
        }
    }

    public function uriDisplayHtml(): string
    {
        if (empty($this->uriParts)) {
            return '';
        }

        $uriDisplayHtml = '';

        foreach ($this->uriParts as $part) {
            if (is_string($part)) {
                $uriDisplayHtml .= Html::encode($part);

                continue;
            }

            $uriDisplayHtml .= Html::encodeParams(
                '<span class="token" data-name="{name}" data-value="{value}"><span>{name}</span></span>',
                [
                    'name' => $part[0],
                    'value' => $part[1],
                ],
            );
        }

        return $uriDisplayHtml;
    }
}
