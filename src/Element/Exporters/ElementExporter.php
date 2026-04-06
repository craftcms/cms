<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Exporters;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Element\Contracts\ElementExporterInterface;

abstract class ElementExporter extends Component implements ElementExporterInterface
{
    /**
     * @var class-string<ElementInterface>
     */
    protected string $elementType;

    public static function isFormattable(): bool
    {
        return true;
    }

    public function setElementType(string $elementType): void
    {
        $this->elementType = $elementType;
    }

    public function getFilename(): string
    {
        $elementType = $this->elementType;

        return $elementType::pluralLowerDisplayName();
    }
}
