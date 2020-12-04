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
use craft\helpers\Gql;
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
     * @var bool Whether full schema should be printed or dumped.
     */
    public $fullSchema = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'token';
        $options[] = 'fullSchema';

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

        if (!$schema) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $schemaDef = $gqlService->getSchemaDef($schema, true);

        // Output the schema
        echo SchemaPrinter::doPrint($schemaDef);

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

        if (!$schema) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $schemaDef = $gqlService->getSchemaDef($schema, true);
        // Output the schema
        $filename = Inflector::slug($schema->name, '_') . self::GQL_SCHEMA_EXTENSION;
        $schemaDump = SchemaPrinter::doPrint($schemaDef);
        $this->stdout("Dumping GraphQL schema to {$filename} ... ", Console::FG_YELLOW);
        file_put_contents($filename, $schemaDump);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * @return \craft\models\GqlSchema|null
     * @throws BadRequestHttpException
     * @throws \yii\base\Exception
     */
    protected function getGqlSchema()
    {
        if ($this->fullSchema) {
            return Gql::createFullAccessSchema();
        }

        $gqlService = Craft::$app->getGql();
        $token = null;

        // First try to get the token from the passed in token
        if ($this->token !== null) {
            try {
                $token = $gqlService->getTokenByAccessToken($this->token);
            } catch (InvalidArgumentException $e) {
                $this->stderr("Invalid authorization token: {$this->token}" . PHP_EOL, Console::FG_RED);
                return null;
            }

            $schema = $token->getSchema();

            if (!$schema) {
                $this->stderr("No schema selected for token: {$this->token}" . PHP_EOL, Console::FG_RED);
                return null;
            }

            return $schema;
        }

        // Next look up the active token
        try {
            return $gqlService->getActiveSchema();
        } catch (GqlException $exception) {
            // Well, go for the public token then.
            $schema = $gqlService->getPublicSchema();

            if (!$schema) {
                $this->stderr('No public schema exists, and one can’t be created because allowAdminChanges is disabled.' .
                    PHP_EOL, Console::FG_RED);
                return null;
            }

            return $schema;
        }
    }
}
