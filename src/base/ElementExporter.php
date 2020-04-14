<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * ElementExporter is the base class for classes representing element exporters in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
abstract class ElementExporter extends Component implements ElementExporterInterface
{
    /**
     * @var string|ElementInterface
     */
    protected $elementType;

    /**
     * @inheritdoc
     */
    public function setElementType(string $elementType)
    {
        $this->elementType = $elementType;
    }

    /**
     * @inheritdoc
     */
    public function getFilename(): string
    {
        /** @var ElementInterface $elementType */
        $elementType = $this->elementType;
        return $elementType::pluralLowerDisplayName();
    }
}
