<?php
/**
 * Redirection Rules
 *
 * Rules returned in this array are evaluated only after Craft would ordinarily
 * throw a 404 exception. They can be key-value pairs representing “from”
 * and “to” URIs…
 *
 * ```php
 * return [
 *     'old/path' => 'new/path',
 * ];
 * ```
 *
 * …or a nested array with `from`, `to`, `caseSensitive`, and `statusCode` keys:
 *
 * ```php
 * return [
 *     [
 *         'from' => 'Helpdesk.aspx',
 *         'to' => 'account/tickets',
 *         'caseSensitive' => true,
 *         'statusCode' => 301,
 *     ],
 * ];
 * ```
 *
 * Read all about Craft’s redirection behavior and capabilities, here:
 * @link https://craftcms.com/docs/5.x/system/routing.html#redirection
 */

return [];
