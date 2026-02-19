<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes;

use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\ForNode;
use Twig\Node\Node;

#[YieldReady]
final class NavNode extends ForNode
{
    private readonly NavItemNode $navItemNode;

    public function __construct(AssignContextVariable $keyTarget, AssignContextVariable $valueTarget, AbstractExpression $seq, Node $upperBody, ?Node $lowerBody, ?Node $indent, ?Node $outdent, int $lineno)
    {
        $this->navItemNode = new NavItemNode($valueTarget, $indent, $outdent, $lowerBody, $lineno);
        $body = new BaseNode([$this->navItemNode, $upperBody]);

        parent::__construct($keyTarget, $valueTarget, $seq, null, $body, null, $lineno);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function compile(Compiler $compiler): void
    {
        // Remember what 'nav' was set to before
        $compiler
            ->write("if (isset(\$context['nav'])) {\n")
            ->indent()
            ->write("\$_nav = \$context['nav'];\n")
            ->outdent()
            ->write("}\n");

        parent::compile($compiler);

        $compiler
            // Were there any items?
            ->write("if (isset(\$_thisItemLevel)) {\n")
            ->indent()
            // Remember the current context
            ->write("\$_tmpContext = \$context;\n")
            // Close out the unclosed items
            ->write("if (\$_thisItemLevel > \$_firstItemLevel) {\n")
            ->indent()
            ->write("for (\$_i = \$_thisItemLevel; \$_i > \$_firstItemLevel; \$_i--) {\n")
            ->indent()
            // Did we output an item at that level?
            ->write("if (isset(\$_contextsByLevel[\$_i])) {\n")
            ->indent()
            // Temporarily set the context to the element at this level
            ->write("\$context = \$_contextsByLevel[\$_i];\n")
            ->subcompile($this->navItemNode->getNode('lower_body'), false)
            ->subcompile($this->navItemNode->getNode('outdent'), false)
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n")
            // Close out the last item
            ->write("\$context = \$_contextsByLevel[\$_firstItemLevel];\n")
            ->subcompile($this->navItemNode->getNode('lower_body'))
            // Set the context back
            ->write("\$context = \$_tmpContext;\n")
            // Unset out variables
            ->write("unset(\$_thisItemLevel, \$_firstItemLevel, \$_i, \$_contextsByLevel, \$_tmpContext);\n")
            ->outdent()
            ->write("}\n")
            // Bring back the 'nav' variable
            ->write("if (isset(\$_nav)) {\n")
            ->indent()
            ->write("\$context['nav'] = \$_nav;\n")
            ->write("unset(\$_nav);\n")
            ->outdent()
            ->write("}\n");
    }
}
