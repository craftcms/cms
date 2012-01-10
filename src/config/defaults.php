<?php

/*
 *
 */
$blocksConfig['devMode'] = false;

/*
 *
 */
$blocksConfig['resourceTriggerWord'] = 'resources';

/*
 *
 */
$blocksConfig['actionTriggerWord'] = 'actions';

/*
 *
 */
$blocksConfig['cacheTimeSeconds'] = 86400;

/*
 *
 */
$blocksConfig['devCacheTimeSeconds'] = 5;

/*
 *  If set to UrlFormat::Auto, Blocks will attempt to check if the web server's PATH_INFO is enabled.
 *  If set to UrlFormat::PathInfo, URLs will be treated in path format (index.php/path/to/file).
 *  If set to UrlFormat::QueryString, URLs will be treated as such (index.php?{routeVar}=path/to/file).
 */
$blocksConfig['urlFormat'] = UrlFormat::Auto
;

/*
 * The variable to use when specifying paths in non PATH_INFO format.
 */
$blocksConfig['pathVar'] = 'p';
