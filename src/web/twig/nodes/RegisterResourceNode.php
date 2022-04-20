<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\web\View;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeCaptureInterface;
use yii\base\NotSupportedException;

/**
 * Class RegisterResourceNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RegisterResourceNode extends Node implements NodeCaptureInterface
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $method = $this->getAttribute('method');
        $position = $this->getAttribute('position');
        $defaultPosition = $this->getAttribute('defaultPosition');
        $allowOptions = $this->getAttribute('allowOptions');
        $value = $this->getNode('value');
        $options = $this->hasNode('options') ? $this->getNode('options') : null;

        $compiler->addDebugInfo($this);

        if ($this->getAttribute('capture')) {
            $compiler
                ->write("ob_start();\n")
                ->subcompile($value)
                ->write("$method(ob_get_clean()");
        } else {
            $compiler
                ->write("$method(")
                ->subcompile($value);
        }

        if ($position === null && $this->getAttribute('allowPosition')) {
            // Default to endBody
            $position = 'endBody';
        }

        $positionPhp = null;

        if ($position !== null) {
            // Figure out what the position's PHP value is
            $positionPhp = match ($position) {
                'head', 'POS_HEAD' => View::POS_HEAD,
                'beginBody', 'POS_BEGIN' => View::POS_BEGIN,
                'endBody', 'POS_END' => View::POS_END,
                'ready', 'POS_READY' => View::POS_READY,
                'load', 'POS_LOAD' => View::POS_LOAD,
                default => throw new NotSupportedException($position . ' is not a valid position'),
            };
        }

        // Does the method have a dedicated `$position` argument?
        $positionArgument = ($position !== null && !$allowOptions) || $defaultPosition !== null;
        if ($positionArgument) {
            $compiler->raw(', ' . ($positionPhp ?? $defaultPosition));
        }

        if ($allowOptions) {
            $positionOption = $position !== null && !$positionArgument;

            if ($positionOption || $options !== null) {
                $compiler->raw(', ');

                if ($positionOption) {
                    // Do we have to merge the position with other options?
                    if ($options !== null) {
                        $compiler
                            ->raw('array_merge(')
                            ->subcompile($options)
                            ->raw(", ['position' => $positionPhp])");
                    } else {
                        $compiler
                            ->raw("['position' => $positionPhp]");
                    }
                } else {
                    $compiler
                        ->subcompile($options);
                }
            }
        }

        $compiler->raw(");\n");
    }
}
