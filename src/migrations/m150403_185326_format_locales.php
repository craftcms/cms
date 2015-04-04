<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;

/**
 * m150403_185326_format_locales migration.
 */
class m150403_185326_format_locales extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$locales = (new Query())
			->select('locale')
			->from('{{%locales}}')
			->column();

		foreach ($locales as $locale)
		{
			$parts = explode('_', $locale);
			if (isset($parts[1]))
			{
				$parts[1] = strtoupper($parts[1]);
			}
			$newLocale = implode('-', $parts);

			if ($newLocale !== $locale)
			{
				$this->update('{{%locales}}', ['locale' => $newLocale], ['locale' => $locale]);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m150403_185326_format_locales cannot be reverted.\n";
		return false;
	}
}
