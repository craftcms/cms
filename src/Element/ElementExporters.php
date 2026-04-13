<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\BaseWriter;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

#[Singleton]
class ElementExporters
{
    private const array FORMATTABLE_FORMATS = ['csv', 'xlsx', 'json', 'xml', 'yaml'];

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return ElementExporterInterface[]
     */
    public function availableExporters(string $elementType, string $sourceKey): array
    {
        $exporters = $elementType::exporters($sourceKey);

        foreach ($exporters as $index => $exporter) {
            $exporters[$index] = $this->createExporter($exporter, $elementType);
        }

        return array_values($exporters);
    }

    /**
     * @param  ElementExporterInterface|class-string<ElementExporterInterface>|array{type:class-string<ElementExporterInterface>}  $exporter
     * @param  class-string<ElementInterface>  $elementType
     */
    public function createExporter(mixed $exporter, string $elementType): ElementExporterInterface
    {
        if ($exporter instanceof ElementExporterInterface) {
            $exporter->setElementType($elementType);

            return $exporter;
        }

        if (is_string($exporter)) {
            $exporter = ['type' => $exporter];
        }

        $exporter['elementType'] = $elementType;

        /** @var ElementExporterInterface */
        return ComponentHelper::createComponent($exporter, ElementExporterInterface::class);
    }

    /**
     * @param  iterable<ElementExporterInterface>  $exporters
     */
    public function serializeExporters(iterable $exporters): array
    {
        $data = [];

        foreach ($exporters as $exporter) {
            $data[] = [
                'type' => $exporter::class,
                'name' => $exporter::displayName(),
                'formattable' => $exporter::isFormattable(),
            ];
        }

        return $data;
    }

    /**
     * @param  iterable<ElementExporterInterface>  $exporters
     */
    public function resolveExporter(iterable $exporters, string $exporterClass): ?ElementExporterInterface
    {
        class_exists($exporterClass);

        foreach ($exporters as $availableExporter) {
            if (
                $availableExporter::class === $exporterClass ||
                is_a($availableExporter, $exporterClass)
            ) {
                return clone $availableExporter;
            }
        }

        return null;
    }

    public function export(
        ElementExporterInterface $exporter,
        ElementQueryInterface $query,
        string $format = 'csv',
    ): Response {
        $filename = $exporter->getFilename();
        $export = $exporter->export($query);

        if ($exporter::isFormattable()) {
            $format = $this->normalizeFormat($format);
            $filename .= ".$format";

            if (is_callable($export)) {
                $export = $export();
            }

            if (! is_iterable($export)) {
                throw new UnexpectedValueException($exporter::class.'::export() must return an array or generator function since isFormattable() returns true.');
            }

            return $this->formattedResponse(
                format: $format,
                data: $this->normalizeRows($export),
                filename: $filename,
                rootTag: Str::camel($query->elementType::pluralLowerDisplayName()),
            );
        }

        return $this->rawResponse($export, $filename);
    }

    private function normalizeFormat(string $format): string
    {
        if (in_array($format, self::FORMATTABLE_FORMATS, true)) {
            return $format;
        }

        throw new InvalidArgumentException("Unsupported export format: $format");
    }

    /**
     * @param  iterable<mixed>  $data
     */
    private function normalizeRows(iterable $data): array
    {
        $rows = [];

        foreach ($data as $row) {
            $rows[] = is_array($row) ? $row : (array) $row;
        }

        return $rows;
    }

    private function formattedResponse(string $format, array $data, string $filename, string $rootTag): Response
    {
        return match ($format) {
            'csv' => $this->binaryResponse(
                content: $this->spreadsheetContent($data, fn (Spreadsheet $spreadsheet): BaseWriter => new Csv($spreadsheet)),
                filename: $filename,
                contentType: 'text/csv; charset=UTF-8',
            ),
            'xlsx' => $this->binaryResponse(
                content: $this->spreadsheetContent($data, fn (Spreadsheet $spreadsheet): BaseWriter => new Xlsx($spreadsheet)),
                filename: $filename,
                contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ),
            'json' => $this->binaryResponse(
                content: Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                filename: $filename,
                contentType: 'application/json; charset=UTF-8',
            ),
            'xml' => $this->binaryResponse(
                content: (new XmlEncoder)->encode($data, 'xml', [
                    'xml_root_node_name' => $rootTag,
                ]),
                filename: $filename,
                contentType: 'application/xml; charset=UTF-8',
            ),
            'yaml' => $this->binaryResponse(
                content: Yaml::dump($data, 20, 2),
                filename: $filename,
                contentType: 'application/x-yaml; charset=UTF-8',
            ),
            default => throw new InvalidArgumentException("Unsupported export format: $format"),
        };
    }

    private function rawResponse(mixed $export, string $filename): Response
    {
        if (
            is_callable($export) ||
            is_resource($export) ||
            (is_array($export) && isset($export[0]) && is_resource($export[0]))
        ) {
            return $this->streamResponse($export, $filename);
        }

        return $this->binaryResponse(
            content: is_string($export) ? $export : (string) $export,
            filename: $filename,
            contentType: 'application/octet-stream',
        );
    }

    private function binaryResponse(string|false $content, string $filename, string $contentType): Response
    {
        return new Response(
            content: $content ?: '',
            status: 200,
            headers: [
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
                'Content-Type' => $contentType,
            ],
        );
    }

    private function streamResponse(mixed $stream, string $filename): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($stream): void {
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

                    while (! feof($handle) && ($position = ftell($handle)) <= $end) {
                        if ($position + $chunkSize > $end) {
                            $chunkSize = $end - $position + 1;
                        }

                        echo fread($handle, $chunkSize);
                    }

                    fclose($handle);

                    return;
                }

                while (! feof($stream)) {
                    echo fread($stream, $chunkSize);
                }

                fclose($stream);
            },
            200,
            [
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
                'Content-Type' => 'application/octet-stream',
            ],
        );
    }

    /**
     * @param  callable(Spreadsheet):BaseWriter  $writerFactory
     */
    private function spreadsheetContent(array $data, callable $writerFactory): string|false
    {
        [$headers, $rows] = $this->spreadsheetRows($data);

        if ($headers === [] && $rows === []) {
            return '';
        }

        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();

        if ($headers !== []) {
            $worksheet->fromArray($headers);
            $worksheet->getStyle('1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '000000'],
                ],
            ]);
        }

        if ($rows !== []) {
            $worksheet->fromArray($rows, startCell: $headers !== [] ? 'A2' : 'A1');
        }

        $path = tempnam(sys_get_temp_dir(), 'export');
        $handle = fopen($path, 'wb');
        $writerFactory($spreadsheet)->save($handle);
        fclose($handle);
        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }

    /**
     * @return array{0:array<int|string>,1:array<int, array<int, string>>}
     */
    private function spreadsheetRows(array $data): array
    {
        if ($data === []) {
            return [[], []];
        }

        $headers = [];

        foreach ($data as $row) {
            foreach (array_keys($row) as $key) {
                $headers[$key] = null;
            }
        }

        $headerRow = array_keys($headers);
        $rows = [];
        $suspectCharacters = ['=', '-', '+', '@'];

        foreach ($data as $row) {
            $normalizedRow = [];

            foreach ($headerRow as $key) {
                $field = $row[$key] ?? '';

                if (is_scalar($field)) {
                    $field = (string) $field;

                    if ($field !== '' && in_array($field[0], $suspectCharacters, true)) {
                        $field = "\t$field";
                    }
                } else {
                    $field = Json::encode($field);
                }

                $normalizedRow[] = $field ?: '';
            }

            $rows[] = $normalizedRow;
        }

        return [$headerRow, $rows];
    }
}
