<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

/**
 * Class RelationArgumentHandler
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
abstract class RelationArgumentHandler extends ArgumentHandler
{
    /** @var array */
    private array $_memoizedValues = [];

    /**
     * Get the IDs of elements returned by configuring the provided element query with given criteria.
     *
     * @param string $elementType
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param array $criteriaList
     * @return int[][]
     */
    protected function getIds(string $elementType, array $criteriaList = []): array
    {
        $idSets = [];

        foreach ($criteriaList as $criteria) {
            /** @var ElementQuery $elementQuery */
            $elementQuery = Craft::configure(Craft::$app->getElements()->createElementQuery($elementType), $criteria);
            $idSets[] = $elementQuery->ids();
        }

        return $idSets;
    }

    /**
     * @inheritdoc
     */
    public function handleArgumentCollection(array $argumentList = []): array
    {
        if (!array_key_exists($this->argumentName, $argumentList)) {
            return $argumentList;
        }

        $argumentValue = $argumentList[$this->argumentName];
        $hash = md5(serialize($argumentValue));

        // See if we have done something exactly like this already.
        if (!array_key_exists($hash, $this->_memoizedValues)) {
            $this->_memoizedValues[$hash] = $this->handleArgument($argumentValue);
        }

        $idSets = $this->_memoizedValues[$hash];

        // Enforce no matches, if no matches. Doh.
        if (empty($idSets)) {
            $idSets = [[0]];
        }

        $relatedTo = $this->prepareRelatedTo($argumentList['relatedTo'] ?? []);

        if (empty($relatedTo)) {
            $relatedTo = ['and'];
        }

        foreach ($idSets as $idSet) {
            $relatedTo[] = ['element' => $idSet];
        }

        $argumentList['relatedTo'] = $relatedTo;
        unset($argumentList[$this->argumentName]);

        return $argumentList;
    }

    /**
     * @inheritdoc
     */
    protected function handleArgument($argumentValue): mixed
    {
        // Recursively parse nested arguments.
        if (ArrayHelper::isAssociative($argumentValue)) {
            $argumentValue = $this->argumentManager->prepareArguments($argumentValue);
        } elseif (is_array($argumentValue)) {
            // Entirely possible that this a list of relation arguments.
            foreach ($argumentValue as &$nestedArgumentValue) {
                if (ArrayHelper::isAssociative($nestedArgumentValue)) {
                    $nestedArgumentValue = $this->argumentManager->prepareArguments($nestedArgumentValue);
                }
            }
        }

        return $argumentValue;
    }

    /**
     * Prepare the `relatedTo` argument.
     *
     * @param array $relatedTo
     * @return array
     */
    protected function prepareRelatedTo(array $relatedTo): array
    {
        // Convert numeric arrays to ['and', ['element' => [...]]]

        if (empty($relatedTo)) {
            return [];
        }

        // If it begins with an "and" or an "or", just drop it, but keep note of it.
        $firstOperand = StringHelper::toLowerCase($relatedTo[0]);
        if ($firstOperand === 'or' || $firstOperand === 'and') {
            array_shift($relatedTo);
        }

        if (ArrayHelper::isNumeric($relatedTo)) {
            // If it was "and", split out all the ids to their own condition
            if ($firstOperand === 'and') {
                $output = ['and'];

                foreach ($relatedTo as $relatedId) {
                    $output[] = ['element' => $relatedId];
                }

                return $output;
            }

            $relatedTo = ['and', ['element' => $relatedTo]];
        } else {
            array_unshift($relatedTo, 'and');
        }

        return $relatedTo;
    }
}
