<?php
namespace Blocks;

/**
 * Stores entry drafts
 */
class EntryDraftRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'entrydrafts';
	}

	public function defineAttributes()
	{
		return array(
			/* BLOCKSPRO ONLY */
			'language' => array(AttributeType::Language, 'required' => true),
			/* end BLOCKSPRO ONLY */
			'data'     => array(AttributeType::Mixed, 'required' => true),
		);
	}

	public function defineRelations()
	{
		return array(
			'entry'  => array(static::BELONGS_TO, 'EntryRecord', 'required' => true),
			/* BLOCKSPRO ONLY */
			'author' => array(static::BELONGS_TO, 'UserRecord', 'required' => true),
			/* end BLOCKSPRO ONLY */
		);
	}
}
