<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\helpers\IOHelper;
use yii\db\Expression;

/**
 * m150403_184729_type_columns migration.
 */
class m150403_184729_type_columns extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$map = [
			'craft\app\volumes' => ['{{%assetsources}}'],
			//'craft\app\volumes'  => ['{{%volumes}}'],
			'craft\app\elements' => ['{{%elements}}', '{{%fieldlayouts}}', '{{%templatecachecriteria}}'],
			'craft\app\fields'   => ['{{%fields}}'],
			'craft\app\tasks'    => ['{{%tasks}}'],
			'craft\app\widgets'  => ['{{%widgets}}'],
		];

		foreach ($map as $namespace => $tables)
		{
			$folderPath = Craft::getAlias('@app/'.substr($namespace, 10));
			$files = IOHelper::getFolderContents($folderPath, false);
			$classes = [];

			foreach ($files as $file)
			{
				$class = IOHelper::getFilename($file, false);
				if (strncmp($class, 'Base', 4) !== 0)
				{
					$classes[] = $class;
				}
			}

			$columns = [
				'type' => new Expression('concat(\''.addslashes($namespace.'\\').'\', type)')
			];

			$condition = ['in', 'type', $classes];

			foreach ($tables as $table)
			{
				$this->alterColumn($table, 'type', 'string not null');
				$this->update($table, $columns, $condition, [], false);
			}
		}

		// S3 is now AwsS3
		$this->update('{{%assetsources}}', ['type' => 'craft\app\volumes\AwsS3'], ['type' => 'craft\app\volumes\S3']);
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m150403_184729_type_columns cannot be reverted.\n";
		return false;
	}
}
