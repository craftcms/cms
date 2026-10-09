<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

interface ElementExporterInterface extends ComponentInterface
{
    public static function isFormattable(): bool;

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    public function setElementType(string $elementType): void;

    public function export(ElementQueryInterface $query): mixed;

    public function getFilename(): string;
}
