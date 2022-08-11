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
use craft\helpers\DateTimeHelper;
use craft\helpers\Gql;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use GraphQL\Utils\SchemaPrinter;
use yii\base\Exception;
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
    public const GQL_SCHEMA_EXTENSION = ".graphql";

    /**
     * @var string|null The GraphQL schema UUID.
     * @since 3.7.15
     */
    public ?string $schema = null;

    /**
     * @var string|null The token to look up to determine the appropriate GraphQL schema.
     */
    public ?string $token = null;

    /**
     * @var bool Whether full schema should be printed or dumped.
     */
    public bool $fullSchema = false;

    /**
     * @var string|null The schema name
     * @since 3.7.15
     */
    public ?string $name = null;

    /**
     * @var string|null Expiry date
     * @since 3.7.15
     */
    public ?string $expiry = null;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'print-schema':
            case 'dump-schema':
                $options[] = 'schema';
                $options[] = 'token';
                $options[] = 'fullSchema';
                break;
            case 'create-token':
                $options[] = 'name';
                $options[] = 'expiry';
                break;
        }

        return $options;
    }

    /**
     * Lists all GraphQL schemas.
     *
     * @return int
     * @since 3.7.15
     */
    public function actionListSchemas(): int
    {
        $schemas = Craft::$app->getGql()->getSchemas();

        if (empty($schemas)) {
            $this->stdout('No GraphQL schemas exist.' . PHP_EOL);
            return ExitCode::OK;
        }

        foreach ($schemas as $schema) {
            $this->stdout('- ');
            $this->stdout($schema->uid, Console::FG_YELLOW);
            $this->stdout(" ($schema->name)" . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Prints a given GraphQL schema.
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
     * Dumps a given GraphQL schema to a file.
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
        $this->stdout("Dumping GraphQL schema to $filename ... ", Console::FG_YELLOW);
        file_put_contents($filename, $schemaDump);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Creates a new authorization token for a schema.
     *
     * @param string $schemaUid The schema UUID
     * @return int
     * @since 3.7.15
     */
    public function actionCreateToken(string $schemaUid): int
    {
        $gqlService = Craft::$app->getGql();

        $schema = $gqlService->getSchemaByUid($schemaUid);

        if ($schema === null) {
            $this->stderr("Invalid schema UUID: $schemaUid" . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $token = new GqlToken();
        $token->schemaId = $schema->id;
        $token->name = $this->name ?? $this->prompt('Schema name:', [
                'required' => true,
            ]);
        $token->accessToken = Craft::$app->getSecurity()->generateRandomString(32);

        if (isset($this->expiry)) {
            $token->expiryDate = DateTimeHelper::toDateTime($this->expiry);
            if (!$token->expiryDate) {
                $this->stderr("Invalid expiry date: $this->expiry" . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } elseif ($this->interactive && $this->confirm('Set an expiry date?')) {
            $expiryDate = $this->prompt('Expiry date:', [
                'required' => true,
                'validator' => function(string $input): bool {
                    return DateTimeHelper::toDateTime($input) !== false;
                },
            ]);
            $token->expiryDate = DateTimeHelper::toDateTime($expiryDate);
        }

        if (!$gqlService->saveToken($token)) {
            $message = "Couldn’t save token:" . PHP_EOL;
            foreach ($token->getFirstErrors() as $error) {
                $message .= "- $error" . PHP_EOL;
            }
            $this->stderr($message, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('Token saved: ', Console::FG_GREEN);
        $this->stdout($token->accessToken . PHP_EOL, Console::FG_YELLOW);
        return ExitCode::OK;
    }

    /**
     * @return GqlSchema|null
     * @throws BadRequestHttpException
     * @throws Exception
     */
    protected function getGqlSchema(): ?GqlSchema
    {
        if ($this->fullSchema) {
            return Gql::createFullAccessSchema();
        }

        $gqlService = Craft::$app->getGql();

        // Was a specific UID passed?
        if (isset($this->schema)) {
            $schema = $gqlService->getSchemaByUid($this->schema);
            if ($schema === null) {
                $this->stderr("Invalid schema UUID: $this->schema" . PHP_EOL, Console::FG_RED);
                return null;
            }
        }

        // First try to get the token from the passed in token
        if (isset($this->token)) {
            try {
                $token = $gqlService->getTokenByAccessToken($this->token);
            } catch (InvalidArgumentException) {
                $this->stderr("Invalid authorization token: $this->token" . PHP_EOL, Console::FG_RED);
                return null;
            }

            $schema = $token->getSchema();

            if (!$schema) {
                $this->stderr("No schema selected for token: $this->token" . PHP_EOL, Console::FG_RED);
                return null;
            }

            return $schema;
        }

        // Next look up the active token
        try {
            return $gqlService->getActiveSchema();
        } catch (GqlException) {
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
