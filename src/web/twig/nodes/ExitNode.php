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
 * Class ExitNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ExitNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        if ($this->hasNode('status')) {
            $status = $this->getNode('status')->getAttribute('value');
            switch ($status) {
                case 400:
                    $class = BadRequestHttpException::class;
                    break;
                case 401:
                    $class = UnauthorizedHttpException::class;
                    break;
                case 403:
                    $class = ForbiddenHttpException::class;
                    break;
                case 404:
                    $class = NotFoundHttpException::class;
                    break;
                case 405:
                    $class = MethodNotAllowedHttpException::class;
                    break;
                case 406:
                    $class = NotAcceptableHttpException::class;
                    break;
                case 409:
                    $class = ConflictHttpException::class;
                    break;
                case 410:
                    $class = GoneHttpException::class;
                    break;
                case 415:
                    $class = UnsupportedMediaTypeHttpException::class;
                    break;
                case 416:
                    $class = RangeNotSatisfiableHttpException::class;
                    break;
                case 422:
                    $class = UnprocessableEntityHttpException::class;
                    break;
                case 429:
                    $class = TooManyRequestsHttpException::class;
                    break;
                case 500:
                    $class = ServerErrorHttpException::class;
                    break;
                case 503:
                    $class = ServiceUnavailableHttpException::class;
                    break;
                default:
                    $class = HttpException::class;
            }

            if ($class === HttpException::class) {
                $compiler
                    ->write("throw new {$class}({$status});\n");
            } else {
                $compiler
                    ->write("throw new {$class}();\n");
            }
        } else {
            $compiler->write(Craft::class . "::\$app->end();\n");
        }
    }
}
