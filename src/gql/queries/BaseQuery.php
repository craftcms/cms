<?php
namespace craft\gql\queries;

/**
 * Class BaseQuery
 */
abstract class BaseQuery
{
    /**
     * Return the queries defined by the class as an array
     *
     * @param bool $checkToken Whether the token should be checked for allowed queries. Defaults to `true`.
     * @return array
     */
    abstract public static function getQueries($checkToken = true): array;
}