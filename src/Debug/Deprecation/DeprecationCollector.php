<?php

declare(strict_types=1);

namespace CraftCms\Cms\Debug\Deprecation;

use CraftCms\Cms\Deprecator\Deprecator;
use CraftCms\Cms\Deprecator\Models\DeprecationError;
use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Override;

class DeprecationCollector extends DataCollector implements AssetProvider, Renderable
{
    public const string NAME = 'deprecations';

    public function __construct(
        private readonly Deprecator $deprecator,
    ) {}

    #[Override]
    public function collect(): array
    {
        $deprecations = [];

        foreach (array_values($this->deprecator->getRequestLogs()) as $index => $log) {
            $deprecations[] = $this->formatDeprecation($log, $index + 1);
        }

        return [
            'count' => count($deprecations),
            'deprecations' => $deprecations,
        ];
    }

    #[Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[Override]
    public function getWidgets(): array
    {
        return [
            self::NAME => [
                'icon' => 'logs',
                'widget' => 'PhpDebugBar.Widgets.CraftDeprecationsWidget',
                'map' => self::NAME,
                'default' => '{}',
                'title' => 'Deprecations',
            ],
            self::NAME.':badge' => [
                'map' => self::NAME.'.count',
                'default' => 0,
            ],
        ];
    }

    #[Override]
    public function getAssets(): array
    {
        return [
            'inline_js' => [
                'craft-debugbar-deprecations-widget' => $this->widgetScript(),
            ],
        ];
    }

    /**
     * @return array{
     *     number: int,
     *     key: string|null,
     *     message: string|null,
     *     file: string|null,
     *     line: int|null,
     *     origin: string,
     *     xdebug_link: array{url: string, ajax: bool, filename: string, line: string}|null,
     *     traces: array<int, array{
     *         call: string,
     *         file: string|null,
     *         line: int|null,
     *         origin: string,
     *         args: string|null,
     *         xdebug_link: array{url: string, ajax: bool, filename: string, line: string}|null
     *     }>
     * }
     */
    private function formatDeprecation(DeprecationError $log, int $number): array
    {
        $file = $log->file ?: null;
        $line = $log->line;

        return [
            'number' => $number,
            'key' => $log->key,
            'message' => $log->message,
            'file' => $file,
            'line' => $line,
            'origin' => $this->formatOrigin($file, $line),
            'xdebug_link' => $file ? $this->getXdebugLink($file, $line) : null,
            'traces' => array_map($this->formatTrace(...), $log->traces ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $trace
     * @return array{
     *     call: string,
     *     file: string|null,
     *     line: int|null,
     *     origin: string,
     *     args: string|null,
     *     xdebug_link: array{url: string, ajax: bool, filename: string, line: string}|null
     * }
     */
    private function formatTrace(array $trace): array
    {
        $file = $trace['file'] ?? null;
        $line = $trace['line'] ?? null;

        return [
            'call' => $this->formatTraceCall($trace),
            'file' => $file,
            'line' => $line,
            'origin' => $this->formatOrigin($file, $line),
            'args' => $trace['args'] ?? null,
            'xdebug_link' => $file ? $this->getXdebugLink($file, $line) : null,
        ];
    }

    /** @param array<string, mixed> $trace */
    private function formatTraceCall(array $trace): string
    {
        $class = $trace['class'] ?? $trace['objectClass'] ?? null;
        $method = $trace['method'] ?? null;

        if ($class && $method) {
            return "$class::$method()";
        }

        if ($method) {
            return "$method()";
        }

        return $trace['file'] ?? '';
    }

    private function formatOrigin(?string $file, ?int $line): string
    {
        if (! $file) {
            return '';
        }

        return $line ? "$file:$line" : $file;
    }

    private function widgetScript(): string
    {
        return <<<'JS'
(function () {
    const csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    class CraftDeprecationsWidget extends PhpDebugBar.Widget {
        get className() {
            return csscls('templates');
        }

        render() {
            this.status = document.createElement('div');
            this.status.classList.add(csscls('status'));
            this.el.append(this.status);

            this.list = new PhpDebugBar.Widgets.ListWidget({
                itemRenderer(li, deprecation) {
                    const message = document.createElement('span');
                    message.classList.add(csscls('name'));
                    message.textContent = deprecation.message || '(no message)';
                    li.append(message);

                    if (deprecation.origin) {
                        const origin = document.createElement('span');
                        origin.setAttribute('title', 'Origin');
                        origin.classList.add(csscls('type'));
                        origin.textContent = deprecation.origin;
                        li.append(origin);
                    }

                    const table = document.createElement('table');
                    table.classList.add(csscls('params'));
                    table.hidden = true;
                    table.append(createHeader('Details'));

                    const tbody = document.createElement('tbody');
                    appendRow(tbody, 'Key', deprecation.key);
                    appendRow(tbody, 'Origin', deprecation.origin, deprecation.xdebug_link);
                    appendRow(tbody, 'Message', deprecation.message);
                    appendTraceRows(tbody, deprecation.traces || []);
                    table.append(tbody);

                    li.append(table);
                    li.style.cursor = 'pointer';
                    li.addEventListener('click', (event) => {
                        if (window.getSelection().type === 'Range' || event.target.closest('.sf-dump')) {
                            return;
                        }

                        table.hidden = !table.hidden;
                    });
                },
            });
            this.el.append(this.list.el);

            this.bindAttr('data', function (data) {
                const deprecations = data.deprecations || [];
                const count = data.count || deprecations.length;
                this.status.textContent = `${count} ${count === 1 ? 'deprecation was' : 'deprecations were'} logged`;
                this.list.set('data', deprecations);
            });
        }
    }

    function createHeader(label) {
        const thead = document.createElement('thead');
        const row = document.createElement('tr');
        const cell = document.createElement('th');
        cell.colSpan = 2;
        cell.textContent = label;
        row.append(cell);
        thead.append(row);

        return thead;
    }

    function appendRow(tbody, name, value, xdebugLink) {
        const row = document.createElement('tr');
        const nameCell = document.createElement('td');
        nameCell.className = csscls('name');
        nameCell.textContent = name;
        row.append(nameCell);

        const valueCell = document.createElement('td');
        valueCell.className = csscls('value');
        valueCell.textContent = value || '';

        if (xdebugLink) {
            valueCell.append(PhpDebugBar.Widgets.editorLink(xdebugLink));
        }

        row.append(valueCell);
        tbody.append(row);
    }

    function appendTraceRows(tbody, traces) {
        if (!traces.length) {
            return;
        }

        const row = document.createElement('tr');
        const nameCell = document.createElement('td');
        nameCell.className = csscls('name');
        nameCell.textContent = 'Trace';
        row.append(nameCell);

        const valueCell = document.createElement('td');
        valueCell.className = csscls('value');
        const traceTable = document.createElement('table');
        traceTable.classList.add(csscls('params'));
        traceTable.append(createHeader('Stack trace'));

        const traceBody = document.createElement('tbody');
        traces.forEach((trace, index) => {
            const traceRow = document.createElement('tr');
            const callCell = document.createElement('td');
            callCell.className = csscls('name');
            callCell.textContent = `${index + 1}. ${trace.call || '(unknown)'}`;
            traceRow.append(callCell);

            const originCell = document.createElement('td');
            originCell.className = csscls('value');
            originCell.textContent = trace.origin || '';

            if (trace.args) {
                const args = document.createElement('div');
                args.textContent = trace.args;
                originCell.append(args);
            }

            if (trace.xdebug_link) {
                originCell.append(PhpDebugBar.Widgets.editorLink(trace.xdebug_link));
            }

            traceRow.append(originCell);
            traceBody.append(traceRow);
        });

        traceTable.append(traceBody);
        valueCell.append(traceTable);
        row.append(valueCell);
        tbody.append(row);
    }

    PhpDebugBar.Widgets.CraftDeprecationsWidget = CraftDeprecationsWidget;
})();
JS;
    }
}
