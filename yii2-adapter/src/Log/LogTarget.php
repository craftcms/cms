<?php

namespace CraftCms\Yii2Adapter\Log;

use Illuminate\Support\Facades\Log;
use yii\helpers\VarDumper;

class LogTarget extends \yii\log\Target
{
    use LogTrait;

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        foreach ($this->messages as $message) {
            [$text, $level, $category, $timestamp] = $message;

            $context = [
                'time' => $timestamp,
                'category' => $category,
            ];

            if (!is_string($text)) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($text instanceof \Throwable) {
                    $context['exception'] = $text;
                    $text = (string) $text;
                } else {
                    $text = VarDumper::export($text);
                }
            }

            Log::log($this->convertLogLevel($level), $text, $context);
        }
    }
}
