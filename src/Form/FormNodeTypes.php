<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Nodes\Callout;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Form\Nodes\Heading;
use CraftCms\Cms\Form\Nodes\LineBreak;
use CraftCms\Cms\Form\Nodes\MarkdownContent;
use CraftCms\Cms\Form\Nodes\Missing;
use CraftCms\Cms\Form\Nodes\Separator;
use CraftCms\Cms\Form\Nodes\Tab;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers Node type classes available to Control Panel Forms.
 *
 * @extends TypeRegistry<Node>
 */
#[Singleton]
class FormNodeTypes extends TypeRegistry
{
    protected const string CONTRACT = Node::class;

    protected const array DEFAULT_TYPES = [
        Callout::class,
        Field::class,
        Group::class,
        Heading::class,
        LineBreak::class,
        MarkdownContent::class,
        Missing::class,
        Separator::class,
        Tab::class,
    ];
}
