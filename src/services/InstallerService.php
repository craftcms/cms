<?php
namespace Blocks;

/**
 *
 */
class InstallerService extends BaseComponent
{
	/**
	 * Run the installer
	 */
	public function run()
	{
		if (b()->isInstalled)
			throw new Exception('Blocks is already installed.');

		$modelsDir = b()->file->set(b()->path->modelsPath);
		$modelFiles = $modelsDir->getContents(false, '.php');
		$models = array();

		// Install Content and Blocks models first,
		// so we can start creating foreign keys to them right away
		$models[] = new Content;
		$models[] = new Block;

		foreach ($modelFiles as $filePath)
		{
			$file = b()->file->set($filePath);
			$fileName = $file->fileName;

			// Ignore the models we've already installed, and the BaseModel
			if ($fileName == 'Content' || $fileName == 'Block' || $fileName == 'BaseModel')
				continue;

			$class = __NAMESPACE__.'\\'.$fileName;
			$models[] = new $class;
		}

		// Start the transaction
		$transaction = b()->db->beginTransaction();
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
			b()->isInstalled = true;

			// Populate the info table
			$info = new Info;
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
