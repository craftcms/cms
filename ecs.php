<?php

declare(strict_types=1);

use craft\ecs\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function(ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/bootstrap',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __FILE__,
    ]);
    $parameters->set(Option::SKIP, [
        __DIR__ . '/tests/_craft/storage',
    ]);

    $containerConfigurator->import(SetList::CRAFT_CMS_4);
};
