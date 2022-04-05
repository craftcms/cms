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
    public function compile(Compiler $compiler)
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
            if ($this->getAttribute('first')) {
                // TODO: Remove this in Craft 4, along with the deprecated `first` param
                $position = 'head';
            } else {
                // Default to endBody
                $position = 'endBody';
            }
        }

        $positionPhp = null;

        if ($position !== null) {
            // Figure out what the position's PHP value is
            switch ($position) {
                case 'head':
                case 'POS_HEAD':
                    $positionPhp = View::POS_HEAD;
                    break;
                case 'beginBody':
                case 'POS_BEGIN':
                    $positionPhp = View::POS_BEGIN;
                    break;
                case 'endBody':
                case 'POS_END':
                    $positionPhp = View::POS_END;
                    break;
                case 'ready':
                case 'POS_READY':
                    $positionPhp = View::POS_READY;
                    break;
                case 'load':
                case 'POS_LOAD':
                    $positionPhp = View::POS_LOAD;
                    break;
                default:
                    throw new NotSupportedException($position . ' is not a valid position');
            }
        }

        // Does the method have a dedicated `$position` argument?
        $positionArgument = ($position !== null && !$allowOptions) || $defaultPosition !== null;
        if ($positionArgument) {
            $compiler->raw(', ' . $positionPhp ?? $defaultPosition);
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
