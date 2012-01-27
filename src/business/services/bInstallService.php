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

		// Start the transaction
		$transaction = Blocks::app()->db->beginTransaction();
		try
		{
			// Create the tables
			foreach ($models as $model)
			{
				$model->createTable();
			}

			// Create the foreign keys
			foreach ($models as $model)
			{
				$model->addForeignKeys();
			}

			// Tell Blocks that it's installed now
			Blocks::app()->isInstalled = true;

			// Populate the info table
			$info = new bInfo;
			$info->edition = Blocks::getEdition(false);
			$info->version = Blocks::getVersion(false);
			$info->build = Blocks::getBuild(false);
			$info->save();

			$transaction->commit();
		}
		catch (Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}
}
