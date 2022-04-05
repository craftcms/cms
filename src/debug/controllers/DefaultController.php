<?php

namespace craft\debug\controllers;

use craft\debug\Module;
use Opis\Closure;
use yii\web\NotFoundHttpException;

/**
 * Debugger controller provides browsing over available debug logs.
 *
 * @see \yii\debug\Panel
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
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

            $content = $this->module->fs->read("{$this->module->dataPath}/index.data");

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
                $contents = $this->module->fs->read("{$this->module->dataPath}/$tag.data");
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
