<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Entry as EntryElement;
use craft\errors\GqlException;
use GraphQL\Error\UserError;

/**
 * Class MutationResolver
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class ElementMutationResolver extends MutationResolver
{
    /**
     * Constant used to reference content fields in resolution data storage.
     */
    const CONTENT_FIELD_KEY = '_contentFields';

    /**
     * A list of attributes that are unchangeable by mutations.
     * @var string[]
     */
    protected $immutableAttributes = ['id', 'uid'];

    /**
     * Populate the element with submitted data.
     *
     * @param Element $element
     * @param array $arguments
     * @return EntryElement
     * @throws GqlException if data not found.
     */
    protected function populateElementWithData(Element $element, array $arguments): Element
    {
        $contentFields = $this->getResolutionData(self::CONTENT_FIELD_KEY) ?? [];

        foreach ($this->immutableAttributes as $attribute) {
            unset($arguments[$attribute]);
        }

        foreach ($arguments as $argument => $value) {
            if (isset($contentFields[$argument])) {
                $value = $this->normalizeValue($argument, $value);
                $element->setFieldValue($argument, $value);
            } else {
                if (property_exists($element, $argument)) {
                    $element->{$argument} = $value;
                }
            }
        }

        return $element;
    }

    /**
     * Save an element.
     *
     * @param ElementInterface $element
     * @throws UserError if validation errors.
     */
    protected function saveElement(ElementInterface $element): ElementInterface
    {
        /** @var Element $element */
        if ($element->enabled && $element->getScenario() == Element::SCENARIO_DEFAULT) {
            $element->setScenario(Element::SCENARIO_LIVE);
        }

        Craft::$app->getElements()->saveElement($element);

        if ($element->hasErrors()) {
            $validationErrors = [];

            foreach ($element->getFirstErrors() as $attribute => $errorMessage) {
                $validationErrors[] = $errorMessage;
            }

            throw new UserError(implode("\n", $validationErrors));
        }

        return $element;
    }
}
