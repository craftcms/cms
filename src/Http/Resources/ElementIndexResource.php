<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Resources;

use CraftCms\Cms\Cp\JsonResource;
use CraftCms\Cms\Element\ElementIndexParams;
use CraftCms\Cms\Element\ElementIndexService;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use Illuminate\Http\Request;
use Override;

class ElementIndexResource extends JsonResource
{
    #[Override]
    public static $wrap;

    public function __construct(
        private readonly bool $includeContainer = true,
        private readonly bool $includeActions = true,
    ) {
        parent::__construct(null);
    }

    #[Override]
    public function toArray(Request $_): array
    {
        $request = app(ElementIndexRequest::class);
        $elementSources = app(ElementSources::class);
        $service = app(ElementIndexService::class);

        $params = ElementIndexParams::fromRequest(
            request: $request,
            elementSources: $elementSources,
            includeContainer: $this->includeContainer,
            includeActions: $this->includeActions,
        );

        return $service->getElementsHtml($params);
    }
}
