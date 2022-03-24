<?php

namespace craft\debug;

use Craft;
use craft\errors\FsException;
use craft\errors\VolumeException;
use craft\fs\Temp;
use craft\helpers\Assets;
use craft\models\FsListing;
use Illuminate\Support\Collection;
use yii\base\InvalidConfigException;
use yii\debug\FlattenException;
use yii\helpers\FileHelper;
use Opis\Closure;
use Generator;

class LogTarget extends \yii\debug\LogTarget
{
    /**
     * @var Module
     */
    public $module;

    /**
     * @inheritDoc
     */
    public function export(): void
    {
        if (!$this->module->fs) {
            parent::export();
            return;
        }
        $path = $this->module->dataPath;
        $summary = $this->collectSummary();
        $dataFile = "$path/{$this->tag}.data";
        $data = [];
        $exceptions = [];
        foreach ($this->module->panels as $id => $panel) {
            try {
                $panelData = $panel->save();
                if ($id === 'profiling') {
                    $summary['peakMemory'] = $panelData['memory'];
                    $summary['processingTime'] = $panelData['time'];
                }
                $data[$id] = Closure\serialize($panelData);
            } catch (\Exception $exception) {
                $exceptions[$id] = new FlattenException($exception);
            }
        }
        $data['summary'] = $summary;
        $data['exceptions'] = $exceptions;

        $stream = tmpfile();
        fwrite($stream, Closure\serialize($data));
        rewind($stream);

        // TODO: pass $config for fileMode, etc
        $this->module->fs->writeFileFromStream($dataFile, $stream);

        $this->_updateIndexFile("$path/index.data", $summary);
    }

    /**
     * @inheritDoc
     */
    protected function gc(&$manifest): void
    {
        if (!$this->module->fs) {
            parent::gc($manifest);
            return;
        }

        if (count($manifest) > $this->module->historySize + 10) {
            $n = count($manifest) - $this->module->historySize;
            foreach (array_keys($manifest) as $tag) {
                $this->module->fs->deleteFile(
                    sprintf('%s/%s.data', $this->module->dataPath, $tag)
                );
                if (isset($manifest[$tag]['mailFiles'])) {
                    foreach ($manifest[$tag]['mailFiles'] as $mailFile) {
                        $this->module->fs->deleteFile(
                            sprintf(
                                '%s/%s',
                                Craft::getAlias($this->module->panels['mail']->mailPath),
                                $mailFile
                            )
                        );
                    }
                }
                unset($manifest[$tag]);
                if (--$n <= 0) {
                    break;
                }
            }
            $this->removeStaleDataFiles($manifest);
        }
    }

    /**
     * @inheritDoc
     */
    protected function removeStaleDataFiles($manifest): void
    {
        if (!$this->module->fs) {
            parent::removeStaleDataFiles($manifest);
            return;
        }

        Collection::make($this->module->fs->getFileList($this->module->dataPath, false))
            ->reject(function (FsListing $listing) use($manifest) {
                $basename = $listing->getBasename();
                $tag = pathinfo($basename, PATHINFO_FILENAME);

                return $basename === 'index.data' || array_key_exists($tag, $manifest);
            })
            ->map(fn(FsListing $listing) => $listing->getUri())
            ->each(function(string $path) {
                $this->module->fs->deleteFile($path);
            });
    }

    /**
     * Updates index file with summary log data
     *
     * @param string $indexFile path to index file
     * @param array $summary summary log data
     * @throws \yii\base\InvalidConfigException
     * @throws \craft\errors\FsException
     */
    private function _updateIndexFile($indexFile, $summary)
    {
        $stream = $this->module->fs->fileExists($indexFile) ?
            $this->module->fs->getFileStream($indexFile) :
            null;
        $manifest = [];

        if ($stream) {
            @flock($stream, LOCK_EX);
            $manifest = '';
            while (($buffer = fgets($stream)) !== false) {
                $manifest .= $buffer;
            }
            if (!feof($stream) || empty($manifest)) {
                // error while reading index data, ignore and create new
                $manifest = [];
            } else {
                $manifest = Closure\unserialize($manifest);
            }
        }

        $manifest[$this->tag] = $summary;
        $this->gc($manifest);

        $stream = tmpfile();
        fwrite($stream, Closure\serialize($manifest));
        rewind($stream);

        // TODO: pass $config for fileMode, etc
        $this->module->fs->writeFileFromStream($indexFile, $stream);
    }
}
