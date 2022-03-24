<?php
namespace craft\debug\controllers;

use craft\debug\Module;
use craft\errors\FsException;
use yii\web\NotFoundHttpException;
use Opis\Closure;

class DefaultController extends \yii\debug\controllers\DefaultController
{

    /**
     * @var Module
     */
    public $module;

    /**
     * @var array|null
     */
    private ?array $_manifest = null;

    /**
     * @inheritDoc
     */
    protected function getManifest($forceReload = false): array
    {
        if (!$this->module->fs) {
            return parent::getManifest($forceReload);
        }

        if ($this->_manifest === null || $forceReload) {
            if ($forceReload) {
                clearstatcache();
            }

            $content = '';

            try {
                $fp = $this->module->fs->getFileStream("{$this->module->dataPath}/index.data");
                @flock($fp, LOCK_SH);
                $content = stream_get_contents($fp);
                @flock($fp, LOCK_UN);
                fclose($fp);
            } catch(FsException $e) {
                // TODO: log?
            }

            if ($content !== '') {
                $this->_manifest = array_reverse(Closure\unserialize($content), true);
            } else {
                $this->_manifest = [];
            }
        }

        return $this->_manifest;
    }

    /**
     * @inheritDoc
     */
    public function loadData($tag, $maxRetry = 0): void
    {
        if (!$this->module->fs) {
            parent::loadData($tag, $maxRetry);
            return;
        }

        // retry loading debug data because the debug data is logged in shutdown function
        // which may be delayed in some environment if xdebug is enabled.
        // See: https://github.com/yiisoft/yii2/issues/1504
        for ($retry = 0; $retry <= $maxRetry; ++$retry) {
            $manifest = $this->getManifest($retry > 0);
            if (isset($manifest[$tag])) {
                $filePath = "{$this->module->dataPath}/$tag.data";
                $stream = $this->module->fs->getFileStream($filePath);
                $contents = stream_get_contents($stream);
                fclose($stream);
                $data = Closure\unserialize($contents);
                $exceptions = $data['exceptions'];
                foreach ($this->module->panels as $id => $panel) {
                    if (isset($data[$id])) {
                        $panel->tag = $tag;
                        $panel->load(Closure\unserialize($data[$id]));
                    }
                    if (isset($exceptions[$id])) {
                        $panel->setError($exceptions[$id]);
                    }
                }
                $this->summary = $data['summary'];

                return;
            }
            sleep(1);
        }

        throw new NotFoundHttpException("Unable to find debug data tagged with '$tag'.");
    }
}
