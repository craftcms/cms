<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use CraftCms\Yii2Adapter\ModelWrapper;
use CraftCms\Yii2Adapter\Validation\LegacyYiiRules;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ElementAction is the base class for classes representing element actions in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Element\Actions\ElementAction} instead.
 */
abstract class ElementAction extends \CraftCms\Cms\Element\Actions\ElementAction implements ElementActionInterface
{
    public function getRules(): array
    {
        return LegacyYiiRules::mergeAttributeRules(
            rules: parent::getRules(),
            target: $this,
            yiiRules: $this->defineRules(),
            validatorTarget: fn() => new ModelWrapper($this),
            allowMethodValidators: true,
        );
    }

    public function getResponse(): ?Response
    {
        $response = parent::getResponse();

        if ($response !== null || !static::isDownload()) {
            return $response;
        }

        return $this->downloadResponse(Craft::$app->getResponse());
    }

    /**
     * @return array<int, array|string>
     */
    protected function defineRules(): array
    {
        return [];
    }

    private function downloadResponse(\craft\web\Response $response): Response
    {
        $headers = $response->getHeaders()->toArray();

        if ($response->stream === null) {
            return new Response(
                content: $response->content ?? '',
                status: $response->getStatusCode(),
                headers: $headers,
            );
        }

        return new StreamedResponse(
            function() use ($response): void {
                $stream = $response->stream;

                if (is_callable($stream)) {
                    foreach ($stream() as $chunk) {
                        echo $chunk;
                    }

                    return;
                }

                $chunkSize = 8 * 1024 * 1024;

                if (is_array($stream)) {
                    [$handle, $begin, $end] = $stream;

                    if (stream_get_meta_data($handle)['seekable']) {
                        fseek($handle, $begin);
                    }

                    while (!feof($handle) && ($position = ftell($handle)) <= $end) {
                        if ($position + $chunkSize > $end) {
                            $chunkSize = $end - $position + 1;
                        }

                        echo fread($handle, $chunkSize);
                    }

                    fclose($handle);

                    return;
                }

                while (!feof($stream)) {
                    echo fread($stream, $chunkSize);
                }

                fclose($stream);
            },
            $response->getStatusCode(),
            $headers,
        );
    }
}
