<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Commands;

use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Override;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateTokenCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:graphql:create-token
        {schemaUid : The schema UUID.}
        {--name= : The token name.}
        {--expiry= : The expiry date.}
    ';

    #[Override]
    protected $description = 'Creates a new GraphQL authorization token.';

    #[Override]
    protected $aliases = ['graphql/create-token'];

    public function handle(Gql $gql): int
    {
        $schemaUid = $this->argument('schemaUid');

        if ($schemaUid === null) {
            $this->components->error('No GraphQL schemas exist.');

            return self::FAILURE;
        }

        $schema = $gql->getSchemaByUid($schemaUid);

        if ($schema === null) {
            $this->components->error("Invalid schema UUID: $schemaUid");

            return self::FAILURE;
        }

        $token = new GqlToken([
            'schemaId' => $schema->id,
            'accessToken' => $gql->generateToken(),
        ]);

        $tokenName = $this->option('name');

        if (is_string($tokenName) && $tokenName !== '') {
            $token->name = $tokenName;
        } elseif (! $this->input->isInteractive()) {
            $this->components->error('Please provide a token name with the --name option when running non-interactively.');

            return self::FAILURE;
        } else {
            $token->name = text(
                label: 'Token name:',
                required: true,
            );
        }

        if (($expiry = $this->option('expiry')) !== null) {
            $expiryDate = DateTimeHelper::toDateTime($expiry);

            if ($expiryDate === false) {
                $this->components->error("Invalid expiry date: $expiry");

                return self::FAILURE;
            }

            $token->expiryDate = $expiryDate;
        } elseif ($this->input->isInteractive() && confirm('Set an expiry date?')) {
            $expiry = text(
                label: 'Expiry date:',
                required: true,
                validate: fn (string $value) => DateTimeHelper::toDateTime($value) !== false
                    ? null
                    : 'Please enter a valid expiry date.',
            );
            $token->expiryDate = DateTimeHelper::toDateTime($expiry);
        }

        if (! $gql->saveToken($token)) {
            $message = "Couldn't save token:".PHP_EOL;

            foreach ($token->getFirstErrors() as $error) {
                $message .= "- $error".PHP_EOL;
            }

            $this->components->error(rtrim($message));

            return self::FAILURE;
        }

        info("Token saved: {$token->accessToken}");

        return self::SUCCESS;
    }

    /** @return array<string, \Closure(): mixed> */
    #[Override]
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'schemaUid' => function () {
                $options = collect(app(Gql::class)->getSchemas())
                    ->mapWithKeys(
                        fn (GqlSchema $schema) => ["{$schema->name} ({$schema->uid})" => $schema->uid],
                    )
                    ->all();

                if ($options === []) {
                    return null;
                }

                $selection = select(
                    label: 'Select a GraphQL schema',
                    options: array_keys($options),
                );

                return $options[$selection];
            },
        ];
    }
}
