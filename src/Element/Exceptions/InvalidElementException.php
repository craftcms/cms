<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Exceptions;

use craft\base\ElementInterface;
use CraftCms\Cms\Support\Arr;
use Throwable;

use function CraftCms\Cms\t;

/*
 * InvalidElementException represents an exception caused by setting an invalid element.
 */
class InvalidElementException extends ElementException
{
    public function __construct(
        public ElementInterface $element,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        if ($message === null) {
            $error = Arr::first($element->getFirstErrors());
            $message = $error
                ? str_replace('*', '', $error)
                : t('The {type} is invalid.', [
                    'type' => $element::lowerDisplayName(),
                ]);
        }

        parent::__construct($element, $message, $code, $previous);
    }
}
