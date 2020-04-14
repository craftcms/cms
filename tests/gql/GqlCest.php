<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace tests\gql;

use Craft;
use crafttests\fixtures\EntryWithFieldsFixture;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\GqlSchemasFixture;
use FunctionalTester;

class GqlCest
{
    public function _fixtures()
    {
        return [
            'entriesWithField' => [
                'class' => EntryWithFieldsFixture::class
            ],
            'gqlSchemas' => [
                'class' => GqlSchemasFixture::class
            ],
            'globalSets' => [
                'class' => GlobalSetFixture::class
            ]
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->_setSchema(1000);
    }

    public function _after(FunctionalTester $I)
    {
        $gqlService = Craft::$app->getGql();
        $gqlService->flushCaches();
    }

    public function _setSchema(int $tokenId)
    {
        $gqlService = Craft::$app->getGql();
        $schema = $gqlService->getSchemaById($tokenId);
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
    public function testQuerying(FunctionalTester $I)
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
    public function testWrongGqlField(FunctionalTester $I)
    {
        $parameter = 'bogus';
        $I->amOnPage('?action=graphql/api&query={entries{' . $parameter . '}}');
        $I->see('"Cannot query field \"' . $parameter . '\"');
    }

    /**
     * Test whether querying with wrong parameters returns the correct error.
     */
    public function testWrongGqlQueryParameter(FunctionalTester $I)
    {
        $I->amOnPage('?action=graphql/api&query={entries(limit:[5,2]){title}}');
        $I->see('"debugMessage":"Expected');
    }

    /**
     * Test whether query results yield the expected results.
     */
    public function testQueryResults(FunctionalTester $I)
    {
        $testData = file_get_contents(__DIR__ . '/data/gql.txt');
        foreach (explode('-----TEST DELIMITER-----', $testData) as $case) {
            list ($query, $response) = explode('-----RESPONSE DELIMITER-----', $case);
            list ($schemaId, $query) = explode('-----TOKEN DELIMITER-----', $query);
            $schema = $this->_setSchema(trim($schemaId));
            $I->amOnPage('?action=graphql/api&query=' . urlencode(trim($query)));
            $I->see(trim($response));
            $gqlService = Craft::$app->getGql();
            $gqlService->flushCaches();
        }
    }
}
