<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\errors\GqlException;
use craft\helpers\Console;
use GraphQL\Utils\SchemaPrinter;
use yii\base\InvalidArgumentException;
use yii\console\ExitCode;
use yii\helpers\Inflector;
use yii\web\BadRequestHttpException;

/**
 * Allows you to manage GraphQL schemas.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.2
 */
class GraphqlController extends Controller
{
    const GQL_SCHEMA_EXTENSION = ".graphql";

    /**
     * @var string The token to look up to determine the appropriate GraphQL schema
     */
    public $token = null;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'token';

        return $options;
    }

    /**
     * Print out a given GraphQL schema.
     *
     * @return int
     */
    public function actionPrintSchema(): int
    {
        $gqlService = Craft::$app->getGql();
        $schema = $this->getGqlSchema();

        if ($schema !== null) {
            $schemaDef = $gqlService->getSchemaDef($schema, true);
            // Output the schema
            echo SchemaPrinter::doPrint($schemaDef);
        }

        return ExitCode::OK;
    }

    /**
     * Dump out a given GraphQL schema to a file.
     *
     * @return int
     */
    public function actionDumpSchema(): int
    {
        $gqlService = Craft::$app->getGql();
        $schema = $this->getGqlSchema();

        if ($schema !== null) {
            $schemaDef = $gqlService->getSchemaDef($schema, true);
            // Output the schema
            $filename = Inflector::slug($schema->name, '_') . self::GQL_SCHEMA_EXTENSION;
            $schemaDump = SchemaPrinter::doPrint($schemaDef);
            $result = file_put_contents($filename, $schemaDump);
            $this->stdout('Dumping GraphQL schema to file: ', Console::FG_YELLOW);
            $this->stdout($filename . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * @return \craft\models\GqlSchema|null
     * @throws BadRequestHttpException
     * @throws \yii\base\Exception
     */
    protected function getGqlSchema()
    {
        $token = null;
        $gqlService = Craft::$app->getGql();

        // First try to get the token from the passed in token
        if ($this->token !== null) {
            try {
                $token = $gqlService->getTokenByAccessToken($this->token);
            } catch (InvalidArgumentException $e) {
                $this->stdout('Invalid authorization token: ', Console::FG_RED);
                $this->stdout($this->token . PHP_EOL, Console::FG_YELLOW);
                return null;
            }

            $schema = $token->getSchema();
        }

        // Next look up the active token
        if ($token === null) {
            try {
                $schema = $gqlService->getActiveSchema();
            } catch (GqlException $exception) {
                // Well, go for the public token then.
                $schema = $gqlService->getPublicSchema();
            }
        }

        return $schema;
    }
}
