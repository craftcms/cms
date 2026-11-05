<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\mcp\AssetUploads;
use CraftCms\UrlValidator\UrlValidator;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use yii\console\Application;

/** Exercises file-reference admission before any file is handed to asset persistence. */
class AssetUploadsTest extends TestCase
{
    private mixed $originalApp;

    protected function setUp(): void
    {
        $this->originalApp = Craft::$app;
        new AssetUploadsTestApplication(['id' => 'mcp-upload-test', 'basePath' => __DIR__]);
    }

    protected function tearDown(): void
    {
        Craft::$app = $this->originalApp;
    }

    /** @dataProvider unsafeFileReferences */
    public function testRejectsUnsafeFileReferences(array $file, ?string $filename, string $message): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage($message);

        // Every hostname resolves to a private address, as a DNS rebinding attacker would arrange
        $uploads = new AssetUploads(new UrlValidator(static fn() => ['10.0.0.1']));
        $uploads->consumeFile($file, $filename, static function(): void {
            self::fail('Rejected file references must not be handed to asset persistence.');
        });
    }

    public static function unsafeFileReferences(): array
    {
        return [
            'no source' => [['file_id' => 'x'], 'file.jpg', 'exactly one of'],
            'two sources' => [['download_url' => 'https://example.com/file.jpg', 'data' => 'AAAA'], 'file.jpg', 'exactly one of'],
            'plain HTTP' => [['download_url' => 'http://example.com/file.jpg'], null, 'File references must use an HTTPS URL'],
            'credentials' => [['download_url' => 'https://user:password@example.com/file.jpg'], null, 'File references must use an HTTPS URL'],
            'alternate port' => [['download_url' => 'https://example.com:8443/file.jpg'], null, 'File references must use an HTTPS URL'],
            'directory traversal' => [['download_url' => 'https://example.com/file.jpg'], '../file.jpg', 'Provide a valid filename.'],
            'executable extension' => [['download_url' => 'https://example.com/file.php'], null, 'The “php” file extension is not allowed. Allowed extensions: jpg.'],
            'hostname resolving to a private IP' => [['download_url' => 'https://example.com/file.jpg'], null, 'must use a public hostname'],
            'loopback' => [['download_url' => 'https://127.0.0.1/file.jpg'], null, 'must use a public hostname'],
            'link local metadata' => [['download_url' => 'https://169.254.169.254/file.jpg'], null, 'must use a public hostname'],
            'IPv4-mapped loopback' => [['download_url' => 'https://[::ffff:127.0.0.1]/file.jpg'], null, 'must use a public hostname'],
            'invalid base64' => [['data' => 'not base64!'], 'file.jpg', 'must be base64-encoded'],
            'oversized base64' => [['data' => base64_encode(str_repeat('a', 2048))], 'file.jpg', 'exceeds the maximum upload size'],
            'base64 without filename' => [['data' => 'AAAA'], null, 'Provide filename'],
            'relative path' => [['path' => 'file.jpg'], null, 'must be an absolute path'],
            'unknown upload' => [['upload_id' => '00000000-0000-0000-0000-000000000000'], 'file.jpg', 'Upload not found.'],
        ];
    }

    public function testDecodesBase64DataUrls(): void
    {
        $contents = (new AssetUploads())->consumeFile(['data' => 'data:image/jpeg;base64,' . base64_encode('hello')], 'file.jpg', static function(string $path, string $filename): string {
            self::assertSame('file.jpg', $filename);
            return file_get_contents($path);
        });

        self::assertSame('hello', $contents);
    }
}

class AssetUploadsTestApplication extends Application
{
    public function getConfig(): object
    {
        return new class() {
            public function getGeneral(): object
            {
                return (object)['allowedFileExtensions' => ['jpg'], 'maxUploadFileSize' => 1024];
            }
        };
    }

    public function getPath(): object
    {
        return new class() {
            public function getTempPath(): string
            {
                return sys_get_temp_dir();
            }
        };
    }
}
