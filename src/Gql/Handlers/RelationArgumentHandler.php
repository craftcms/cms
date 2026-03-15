<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Handlers;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use Override;

abstract class RelationArgumentHandler extends ArgumentHandler
{
    private array $_memoizedValues = [];

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return int[][]
     */
    protected function getIds(string $elementType, array $criteriaList = []): array
    {
        $idSets = [];

        foreach ($criteriaList as $criteria) {
            /** @var ElementQuery $elementQuery */
            $elementQuery = Typecast::configure(Craft::$app->getElements()->createElementQuery($elementType), $criteria);
            $idSets[] = $elementQuery->ids();
        }

        return $idSets;
    }

    #[Override]
    public function handleArgumentCollection(array $argumentList = []): array
    {
        if (! array_key_exists($this->argumentName, $argumentList)) {
            return $argumentList;
        }

        $argumentValue = $argumentList[$this->argumentName];

        // Extract relatedViaField and relatedViaSite values
        $relationParams = [];
        foreach ($argumentValue as &$value) {
            $relationParams[] = array_filter([
                'field' => is_array($value) ? Arr::pull($value, 'relatedViaField') : null,
                'site' => is_array($value) ? Arr::pull($value, 'relatedViaSite') : null,
            ]);
        }

        $hash = md5(serialize($argumentValue));

        // See if we have done something exactly like this already.
        if (! array_key_exists($hash, $this->_memoizedValues)) {
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
            $relationParams = array_shift($relationParams) ?? [];
            $relatedTo[] = ['element' => $idSet] + $relationParams;
        }

        $argumentList['relatedTo'] = $relatedTo;

        unset($argumentList[$this->argumentName]);

        return $argumentList;
    }

    protected function handleArgument($argumentValue): mixed
    {
        // Recursively parse nested arguments.
        if (is_array($argumentValue) && Arr::isAssoc($argumentValue)) {
            return $this->argumentManager->prepareArguments($argumentValue);
        }

        if (is_array($argumentValue)) {
            // Entirely possible that this a list of relation arguments.
            foreach ($argumentValue as &$nestedArgumentValue) {
                if (is_array($nestedArgumentValue) && Arr::isAssoc($nestedArgumentValue)) {
                    $nestedArgumentValue = $this->argumentManager->prepareArguments($nestedArgumentValue);
                }
            }
        }

        return $argumentValue;
    }

    protected function prepareRelatedTo(array $relatedTo): array
    {
        // Convert numeric arrays to ['and', ['element' => [...]]]

        if (empty($relatedTo)) {
            return [];
        }

        // If it begins with an "and" or an "or", just drop it, but keep note of it.
        $firstOperand = is_string($relatedTo[0] ?? null) ? mb_strtolower($relatedTo[0]) : null;
        if ($firstOperand === 'or' || $firstOperand === 'and') {
            array_shift($relatedTo);
        }

        if (Arr::isNumeric($relatedTo)) {
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
