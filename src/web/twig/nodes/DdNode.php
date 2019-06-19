<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Craft;
use craft\web\ServiceUnavailableHttpException;
use Twig\Compiler;
use Twig\Node\Node;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\GoneHttpException;
use yii\web\HttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotAcceptableHttpException;
use yii\web\NotFoundHttpException;
use yii\web\RangeNotSatisfiableHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;
use yii\web\UnauthorizedHttpException;
use yii\web\UnprocessableEntityHttpException;
use yii\web\UnsupportedMediaTypeHttpException;

/**
 * Class DdNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class DdNode extends Node
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write(Craft::class . '::dd(')
            ->subcompile($this->getNode('var'))
            ->raw(");\n");
    }
}
