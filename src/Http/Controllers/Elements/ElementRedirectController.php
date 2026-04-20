<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Http\Controllers\Elements\Concerns\EditsElement;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Url;
use Symfony\Component\HttpFoundation\Response;

readonly class ElementRedirectController
{
    use EditsElement;

    public function __construct(
        private ElementRequest $request
    ) {}

    public function __invoke(): Response
    {
        $id = $this->request->route('id');
        $uid = $this->request->route('uid');

        if (is_numeric($id)) {
            $id = (int) $id;
        } else {
            $id = null;
        }

        $element = $this->request->element([
            'id' => $id,
            'uid' => $uid,
        ]);

        if ($element instanceof Response) {
            return $element;
        }

        $url = $element->getCpEditUrl();

        abort_if(! $url, 500, 'The element doesn’t have an edit page.');

        $editUrl = Url::removeParam(Url::cpUrl('edit'), 'site');

        if (str_starts_with((string) $url, $editUrl)) {
            return $this->editResponse($element);
        }

        return redirect($url);
    }
}
