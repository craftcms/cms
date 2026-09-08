<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Support\Facades\Gate;

abstract class ActivityRequest extends ElementRequest
{
    private ?ElementInterface $subject = null;

    private ?ElementInterface $subjectElement = null;

    public function authorize(): bool
    {
        return Gate::allows('view', $this->subject());
    }

    public function subject(): ElementInterface
    {
        return $this->subject ??= $this->subjectElement()->getCanonical(true);
    }

    public function subjectElement(): ElementInterface
    {
        if ($this->subjectElement !== null) {
            return $this->subjectElement;
        }

        $element = $this->element();

        abort_unless($element instanceof ElementInterface, 400, 'No element was identified by the request.');

        return $this->subjectElement = $element;
    }
}
