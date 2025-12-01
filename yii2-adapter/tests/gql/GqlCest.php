<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\gql;

use Craft;
use craft\models\GqlSchema;
use crafttests\fixtures\EntryWithFieldsFixture;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\GqlSchemasFixture;
use FunctionalTester;
use yii\base\Exception;

class GqlCest
{
    /**
     *
     */
    public function _fixtures(): array
    {
        return [
            'entriesWithField' => [
                'class' => EntryWithFieldsFixture::class,
            ],
            'gqlSchemas' => [
                'class' => GqlSchemasFixture::class,
            ],
            'globalSets' => [
                'class' => GlobalSetFixture::class,
            ],
        ];
    }

    private bool $tokenStatus;

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $gql = Craft::$app->getGql();
        $token = $gql->getPublicToken();
        $this->tokenStatus = $token->enabled;
        $token->enabled = false;
        $gql->saveToken($token);

        $this->_setSchema(1000);
    }

    /**
     * @param FunctionalTester $I
     */
    public function _after(FunctionalTester $I)
    {
        $gql = Craft::$app->getGql();
        $token = $gql->getPublicToken();
        $token->enabled = $this->tokenStatus;
        $gql->saveToken($token);

        $gql->flushCaches();
    }

    /**
     * @param int $schemaId
     * @return GqlSchema|null
     * @throws Exception
     */
    public function _setSchema(int $schemaId): ?GqlSchema
    {
        $gqlService = Craft::$app->getGql();
        $schema = $gqlService->getSchemaById($schemaId);
        $gqlService->setActiveSchema($schema);

        return $schema;
    }

    /**
     * Test whether missing query parameter is handled correctly.
     */
    public function forgetQueryParameter(FunctionalTester $I)
    {
        $I->amOnPage('?action=graphql/api');
        $I->see('No GraphQL query was supplied');
    }

    /**
     * Test whether malformed query parameter is handled correctly.
     */
    public function provideMalformedQueryParameter(FunctionalTester $I)
    {
        $I->amOnPage('?action=graphql/api&query=bogus}');
        $I->see('Syntax Error');
    }

    /**
     * Test whether all query types work correctly
     */
    public function testQuerying(FunctionalTester $I): void
    {
        $queryTypes = [
            'entries',
            'users',
            'assets',
            'globalSets',
        ];

        foreach ($queryTypes as $queryType) {
            $I->amOnPage('?action=graphql/api&query={' . $queryType . '{title}}');
            $I->see('"' . $queryType . '":[');
        }
    }

    /**
     * Test whether querying for wrong gql field returns the correct error.
     */
    public function testWrongGqlField(FunctionalTester $I): void
    {
        $parameter = 'bogus';
        $I->amOnPage('?action=graphql/api&query={entries{' . $parameter . '}}');
        $I->see('"Cannot query field \"' . $parameter . '\"');
    }

    /**
     * Test whether querying with wrong parameters returns the correct error.
     */
    public function testWrongGqlQueryParameter(FunctionalTester $I): void
    {
        $I->amOnPage('?action=graphql/api&query={entries(limit:[5,2]){title}}');
        $I->see('requires type Int');
    }

    /**
     * Test whether query results yield the expected results.
     */
    public function testQueryResults(FunctionalTester $I): void
    {
        $testData = file_get_contents(__DIR__ . '/data/gql.txt');
        foreach (explode('-----TEST DELIMITER-----', $testData) as $case) {
            [$query, $response] = explode('-----RESPONSE DELIMITER-----', $case);
            [$schemaId, $query] = explode('-----TOKEN DELIMITER-----', $query);
            $this->_setSchema((int)trim($schemaId));
            $I->amOnPage('?action=graphql/api&query=' . urlencode(trim($query)));
            $I->see(trim($response));
            $gqlService = Craft::$app->getGql();
            $gqlService->flushCaches();
        }
    }
}
