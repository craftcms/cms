<?php
namespace Blocks;

/**
 *
 */
class UserProfileBlocksService extends BaseBlocksService
{
	protected $blockPackageClass = 'UserProfileBlockPackage';
	protected $blockRecordClass = 'UserProfileBlockRecord';
	protected $contentRecordClass = 'UserProfileRecord';
	protected $placeBlockColumnsAfter = 'userId';
}
