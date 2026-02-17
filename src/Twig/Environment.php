<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use Twig\Environment as TwigEnvironment;
use Twig\Extension\EscaperExtension;
use Twig\Loader\LoaderInterface;
use Twig\Source;

use function CraftCms\Cms\debugbar;

class Environment extends TwigEnvironment
{
    /**
     * {@inheritdoc}
     */
    public function __construct(LoaderInterface $loader, array $options = [])
    {
        parent::__construct($loader, $options);

        $this->setDefaultEscaperStrategy();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function compileSource(Source $source): string
    {
        debugbar()->startMeasure($source->getName(), "Compile {$source->getName()}");

        try {
            return parent::compileSource($source);
        } finally {
            debugbar()->stopMeasure($source->getName());
        }
    }

    /**
     * @param  mixed  $strategy  The escaper strategy to set. If null, it will be determined based on the template name.
     */
    public function setDefaultEscaperStrategy(mixed $strategy = null): void
    {
        // don't have Twig escape HTML by default
        /** @var EscaperExtension $ext */
        $ext = $this->getExtension(EscaperExtension::class);
        $ext->setDefaultStrategy($strategy ?? $this->getDefaultEscaperStrategy(...));
    }

    /**
     * Returns the default escaper strategy to use based on the template name.
     */
    public function getDefaultEscaperStrategy(string $name): string|false
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return in_array($ext, ['txt', 'text'], true) ? false : 'html';
    }
}
