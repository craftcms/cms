<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes;
use CraftCms\Cms\Field\LinkTypes\Url;

it('uses the current registry link types', function () {
    $registry = app(LinkTypes::class);
    $registry->register(RegistryLink::class);

    $first = Link::types();
    $registry->remove(RegistryLink::class);
    $second = Link::types();

    expect($first)->toHaveKey('registry', RegistryLink::class)
        ->and($second)->not()->toHaveKey('registry');
});

class RegistryLink extends Url
{
    #[Override]
    public static function id(): string
    {
        return 'registry';
    }
}
