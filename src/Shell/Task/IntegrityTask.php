<?php
/**
 * @author Florian Krämer
 * @copyright 2012 - 2017 Florian Krämer
 * @license MIT
 */
namespace Burzum\FileStorage\Shell\Task;

use Burzum\FileStorage\Storage\StorageManager;
use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

/**
 * Task to do integrity checks on the file references
 */
class IntegrityTask extends Shell {

	public function exists() {
		$this->_table = TableRegistry::get($this->param('model'));
		$this->_loop();
	}

	protected function _loop() {
		$count = $this->_table->find()->count();
		$offset = 0;
		$limit = $this->param('limit');
		$notFound = 0;

		$this->out(__d('file_storage', '{0} record(s) will be checked.' . "\n", $count));

		do {
			$records = $this->_table->find()
				->offset($offset)
				->limit($limit)
				->all();

			if (!empty($records)) {
				foreach ($records as $record) {
					$adapter = StorageManager::getAdapter($record->get('adapter'));
					if (!$adapter->has($record->get('path'))) {
						$this->warn(__d('file_storage', '{0} file does not exist', [$record->get('id')]));
						$this->verbose('Adapter: ' . $record->get('adapter'));
						$this->verbose('Path: ' . $record->get('path'));
						$notFound++;
					}
				}
			}

			$offset += $limit;
			$this->out(__d('file_storage', '{0} of {1} records processed.', [$limit, $count]));
		} while ($records->count() > 0);
	}

	/**
	 * @inheritdoc
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->addSubcommand('exists', [
			'exists' => 'Check if files exist'
		]);
		$parser->addOption('adapter', [
			'short' => 'a',
			'help' => __('The adapter config name to use.'),
			'default' => 'Local'
		]);
		$parser->addOption('limit', [
			'short' => 'l',
			'help' => __('The limit of records to process in a batch.'),
			'default' => 50
		]);
		$parser->addOption('identifier', [
			'short' => 'i',
			'help' => __('The files identifier (`model` field in `file_storage` table).'),
			'default' => null
		]);
		$parser->addOption('model', [
			'short' => 'm',
			'help' => __('The model / table to use.'),
			'default' => 'Burzum/FileStorage.FileStorage'
		]);

		return $parser;
	}
}
