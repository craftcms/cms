<?php

namespace craft\config;

/** MCP transport configuration. @since 5.12.0 */
class McpConfig extends BaseConfig
{
    public string $instructions = '';
    /** Yii action filter definitions applied to authenticated MCP requests. */
    public array $filters = [];

    public function instructions(string $value): self
    {
        $this->instructions = $value;
        return $this;
    }
}
