<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\errors\GqlException;
use craft\helpers\Console;
use craft\console\Controller;

use yii\helpers\Inflector;
use yii\base\InvalidArgumentException;
use yii\console\ExitCode;
use yii\web\BadRequestHttpException;

use GraphQL\Utils\SchemaPrinter;

/**
 * Allows you to manage GraphQL schemas.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.2
 */
class GraphqlController extends Controller
{
    // Constants
    // =========================================================================

    const GQL_SCHEMA_EXTENSION = ".graphql";

    // Public Properties
    // =========================================================================

    /**
     * @var string The token to look up to determine the appropriate GraphQL schema
     */
    public $token = null;

    // Public Methods
    // =========================================================================

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
     * Print out a given GraphQL schema
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
     * Dump out a given GraphQL schema to a file
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
            $filename = Inflector::slug($schema->name, '_').self::GQL_SCHEMA_EXTENSION;
            $schemaDump = SchemaPrinter::doPrint($schemaDef);
            $result = file_put_contents($filename, $schemaDump);
            $this->stdout('Dumping GraphQL schema to file: ', Console::FG_YELLOW);
            $this->stdout($filename.PHP_EOL);
        }

        return ExitCode::OK;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return \craft\models\GqlSchema|null
     * @throws BadRequestHttpException
     * @throws \yii\base\Exception
     */
    protected function getGqlSchema()
    {
        $schema = null;
        $gqlService = Craft::$app->getGql();
        // First try to get the schema from the passed in token
        if ($this->token !== null) {
            try {
                $schema = $gqlService->getSchemaByAccessToken($this->token);
            } catch (InvalidArgumentException $e) {
                $this->stdout('Invalid authorization token: ', Console::FG_RED);
                $this->stdout($this->token.PHP_EOL, Console::FG_YELLOW);
                return null;
            }
        }
        // Next look up the active schema
        if ($schema === null) {
            try {
                $schema = $gqlService->getActiveSchema();
            } catch (GqlException $exception) {
                // Well, go for the public schema then.
                $schema = $gqlService->getPublicSchema();
            }
        }

        return $schema;
    }
}
