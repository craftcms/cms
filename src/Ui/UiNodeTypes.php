<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Nodes\Action;
use CraftCms\Cms\Ui\Nodes\ActionMenu;
use CraftCms\Cms\Ui\Nodes\Callout;
use CraftCms\Cms\Ui\Nodes\CopyAttribute;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\LineBreak;
use CraftCms\Cms\Ui\Nodes\MarkdownContent;
use CraftCms\Cms\Ui\Nodes\Missing;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Ui\Nodes\Tab;
use CraftCms\Cms\Ui\Nodes\TemplateContent;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers Node type classes available to Control Panel UIs.
 *
 * @extends TypeRegistry<Node>
 *
 * @since 6.0.0
 */
#[Singleton]
class UiNodeTypes extends TypeRegistry
{
    protected const string CONTRACT = Node::class;

    protected const array DEFAULT_TYPES = [
        Action::class,
        ActionMenu::class,
        Callout::class,
        CopyAttribute::class,
        Field::class,
        Group::class,
        Heading::class,
        HiddenField::class,
        LineBreak::class,
        MarkdownContent::class,
        Missing::class,
        Separator::class,
        Tab::class,
        TemplateContent::class,
    ];
}
