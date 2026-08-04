<?php

namespace craft\debug;

use Exception;
use Illuminate\Support\Collection;
use Throwable;
use yii\debug\FlattenException;

/**
 * The debug LogTarget is used to store logs for later use in the debugger tool
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class LogTarget extends \yii\debug\LogTarget
{
    /**
     * @var Module
     */
    public $module;

    /**
     * @inheritdoc
     */
    public function export(): void
    {
        if (!$this->module->disk) {
            parent::export();
            return;
        }

        $path = $this->module->dataPath;
        $summary = $this->collectSummary();
        $data = [];
        $exceptions = [];

        foreach ($this->module->panels as $id => $panel) {
            try {
                $panelData = $panel->save();
                if ($id === 'profiling') {
                    $summary['peakMemory'] = $panelData['memory'];
                    $summary['processingTime'] = $panelData['time'];
                }
                $data[$id] = serialize($panelData);
            } catch (Exception $exception) {
                $exceptions[$id] = new FlattenException($exception);
            }
        }

        $data['summary'] = $summary;
        $data['exceptions'] = $exceptions;

        $this->module->disk->put(
            "$path/{$this->tag}.data",
            serialize($data),
        );

        $this->_updateIndexFile("$path/index.data", $summary);
    }

    /**
     * @inheritdoc
     */
    public function loadManifest(): array
    {
        if (!$this->module->disk) {
            return parent::loadManifest();
        }

        $indexFile = $this->module->dataPath . '/index.data';
        $content = $this->module->disk->exists($indexFile)
            ? $this->module->disk->get($indexFile)
            : '';

        if ($content !== '') {
            return array_reverse(unserialize($content), true);
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function loadTagToPanels($tag): array
    {
        if (!$this->module->disk) {
            return parent::loadTagToPanels($tag);
        }

        $dataFile = $this->module->dataPath . "/$tag.data";
        $data = unserialize($this->module->disk->get($dataFile));
        $exceptions = $data['exceptions'];
        foreach ($this->module->panels as $id => $panel) {
            if (isset($data[$id])) {
                $panel->tag = $tag;
                $panel->load(unserialize($data[$id]));
            }
            if (isset($exceptions[$id])) {
                $panel->setError($exceptions[$id]);
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function gc(&$manifest): void
    {
        if (!$this->module->disk) {
            parent::gc($manifest);
            return;
        }

        $mailPanel = $this->module->panels['mail'] ?? null;

        if (count($manifest) > $this->module->historySize + 10) {
            $n = count($manifest) - $this->module->historySize;
            foreach (array_keys($manifest) as $tag) {
                $this->module->disk->delete("{$this->module->dataPath}/$tag");
                if (isset($manifest[$tag]['mailFiles']) && $mailPanel instanceof MailPanel) {
                    foreach ($manifest[$tag]['mailFiles'] as $mailFile) {
                        $this->module->disk->delete("$mailPanel->mailPath/$mailFile");
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
     * @inheritdoc
     */
    protected function removeStaleDataFiles($manifest): void
    {
        if (!$this->module->disk) {
            parent::removeStaleDataFiles($manifest);
            return;
        }

        Collection::make($this->module->disk->files($this->module->dataPath))
            ->reject(function(string $path) use ($manifest) {
                $basename = pathinfo($path, PATHINFO_BASENAME);
                $tag = pathinfo($basename, PATHINFO_FILENAME);

                return $basename === 'index.data' || array_key_exists($tag, $manifest);
            })
            ->each(function(string $path) {
                $this->module->disk->delete($path);
            });
    }

    /**
     * Updates index file with summary log data
     *
     * @param string $indexFile path to index file
     * @param array $summary summary log data
     */
    private function _updateIndexFile(string $indexFile, array $summary): void
    {
        try {
            $manifest = $this->module->disk->exists($indexFile)
                ? unserialize($this->module->disk->get($indexFile))
                : [];
        } catch (Throwable) {
            $manifest = [];
        }

        $manifest[$this->tag] = $summary;
        $this->gc($manifest);

        $this->module->disk->put(
            $indexFile,
            serialize($manifest),
        );
    }
}
