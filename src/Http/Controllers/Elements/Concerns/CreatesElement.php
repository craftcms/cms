<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Http\Requests\ElementRequest;
use Illuminate\Support\Facades\Gate;

trait CreatesElement
{
    protected readonly ElementRequest $request;

    protected function createElement(): ElementInterface
    {
        $elementType = $this->request->elementType();

        $this->request->validateElementType($elementType);

        /** @var ElementInterface $element */
        $element = app()->make($elementType);

        if ($this->request->has('siteId') && $element::isLocalized()) {
            $element->siteId = $this->request->integer('siteId');
        }

        if ($this->request->has('ownerId') && $element instanceof NestedElementInterface) {
            $element->setOwnerId($this->request->integer('ownerId'));
        }

        $element->setAttributesFromRequest($this->request->validated() + array_filter(['fieldId' => $this->request->input('fieldId')]));

        Gate::authorize('save', $element);

        if (! $element->slug) {
            $element->slug = ElementHelper::tempSlug();
        }

        return $element;
    }
}
