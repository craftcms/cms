<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Template;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Profile Node.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class ProfileNode extends Node
{
    /**
     * Constructor.
     *
     * @param string $stage The profiling stage ('begin' or 'end')
     * @param string $type The type of template element being profiled ('template', 'block', or 'macro')
     * @param string $name The name of the template element
     */
    public function __construct(string $stage, string $type, string $name)
    {
        parent::__construct([], compact('stage', 'type', 'name'));
    }

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->write(Template::class . '::' . $this->getAttribute('stage') . 'Profile(')
            ->repr($this->getAttribute('type'))
            ->raw(', ')
            ->repr($this->getAttribute('name'))
            ->raw(");\n");
    }
}
