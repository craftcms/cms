<?php

/**
 *
 */
class bInstallService extends CApplicationComponent
{
	/**
	 * Installs Blocks
	 */
	public function installBlocks()
	{
		if (Blocks::app()->isInstalled)
			throw new bException('Blocks is already installed.');

		$modelsDir = Blocks::app()->file->set(Blocks::app()->path->modelsPath);
		$modelFiles = $modelsDir->getContents(false, '.php');
		$models = array();

		foreach ($modelFiles as $filePath)
		{
			$file = Blocks::app()->file->set($filePath);
			$fileName = $file->fileName;

			// Ignore base classes
			if (strncmp($fileName, 'bBase', 5) === 0)
				continue;

			$models[] = new $fileName;
		}

		// start the transaction
		$transaction = Blocks::app()->db->beginTransaction();
		try
		{
			// create the tables
			foreach ($models as $model)
			{
				$model->createTable();
			}

			// create the foreign keys
			foreach ($models as $model)
			{
				$model->addForeignKeys();
			}

			foreach ($models as $model)
			{
				$model->addIndexes();
			}
		}
		catch (Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}
}
