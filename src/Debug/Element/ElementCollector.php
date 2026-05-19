<?php

declare(strict_types=1);

namespace CraftCms\Cms\Debug\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use DebugBar\DataCollector\ObjectCountCollector;
use Override;

class ElementCollector extends ObjectCountCollector
{
    public const string NAME = 'elements';

    public function __construct()
    {
        parent::__construct(self::NAME);

        $this->setKeyMap([
            'retrieved' => 'Retrieved',
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
        ]);
        $this->collectCountSummary(true);
    }

    /**
     * @param  array<ElementInterface>  $elements
     */
    public function countElements(array $elements, string $key): void
    {
        foreach ($elements as $element) {
            $this->countElement($element, $key);
        }
    }

    public function countElement(ElementInterface $element, string $key): void
    {
        $this->countClass($element::class, 1, $key);
    }

    #[Override]
    public function getWidgets(): array
    {
        $widgets = parent::getWidgets();
        $widgets[$this->getName()]['icon'] = 'leaf';

        return $widgets;
    }
}
