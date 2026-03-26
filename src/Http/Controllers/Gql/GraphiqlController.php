<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use Craft;
use craft\web\assets\graphiql\GraphiqlAsset;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Support\URL;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use InvalidArgumentException;

use function CraftCms\Cms\t;

readonly class GraphiqlController extends GqlController
{
    public function __construct(
        private Gql $gql,
    ) {
        $this->ensureGqlEnabled();
    }

    public function __invoke(Request $request): View
    {
        Craft::$app->getView()->registerAssetBundle(GraphiqlAsset::class);

        // Ensure the public schema exists
        $this->gql->getPublicSchema();

        $schemaUid = $request->query('schemaUid');

        if ($schemaUid && $schemaUid !== '*') {
            try {
                $selectedSchema = $this->gql->getSchemaByUid($schemaUid);
            } catch (InvalidArgumentException) {
                abort(400, 'Invalid token UID.');
            }

            abort_if(is_null($selectedSchema), 400, 'Invalid token UID.');

            SessionAuth::authorize("graphql-schema:$schemaUid");
        } else {
            $selectedSchema = GqlHelper::createFullAccessSchema();
        }

        $schemas = [[
            'label' => t('Full Schema'),
            'value' => '*',
        ]];

        foreach ($this->gql->getSchemas() as $schema) {
            $schemas[] = [
                'label' => $schema->name,
                'value' => $schema->uid,
            ];
        }

        return view('graphql.graphiql', [
            'url' => URL::actionUrl('graphql/api'),
            'schemas' => $schemas,
            'selectedSchema' => $selectedSchema,
        ]);
    }
}
