<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\gql\ArgumentManager;

/**
 * Class ArgumentHandler
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
abstract class ArgumentHandler implements ArgumentHandlerInterface
{
    /** @var ArgumentManager */
    protected ArgumentManager $argumentManager;

    /** @var string */
    protected string $argumentName;

    /**
     * @inheritdoc
     */
    public function setArgumentManager(ArgumentManager $argumentManager): void
    {
        $this->argumentManager = $argumentManager;
    }

    /**
     * Handle a single argument value
     *
     * @param mixed $argumentValue
     * @return mixed
     */
    abstract protected function handleArgument(mixed $argumentValue): mixed;

    /**
     * @inheritdoc
     */
    public function handleArgumentCollection(array $argumentList = []): array
    {
        $argumentList[$this->argumentName] = $this->handleArgument($argumentList[$this->argumentName]);

        return $argumentList;
    }
}
