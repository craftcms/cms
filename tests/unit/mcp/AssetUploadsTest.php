<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\mcp\AssetUploads;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use yii\console\Application;

/** Exercises file-reference admission before any network request or filesystem write. */
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
    public function testRejectsUnsafeFileReferences(string $url, string $filename, string $message): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage($message);

        (new AssetUploads())->consumeFile($url, $filename, static function(): void {
            self::fail('Rejected file references must not be handed to asset persistence.');
        });
    }

    public static function unsafeFileReferences(): array
    {
        return [
            'plain HTTP' => ['http://example.com/file.jpg', 'file.jpg', 'File references must use an HTTPS URL'],
            'credentials' => ['https://user:password@example.com/file.jpg', 'file.jpg', 'File references must use an HTTPS URL'],
            'alternate port' => ['https://example.com:8443/file.jpg', 'file.jpg', 'File references must use an HTTPS URL'],
            'directory traversal' => ['https://example.com/file.jpg', '../file.jpg', 'Provide a valid filename.'],
            'executable extension' => ['https://example.com/file.php', 'file.php', 'The file extension is not allowed.'],
            'loopback' => ['https://127.0.0.1/file.jpg', 'file.jpg', 'File references must resolve to public IP addresses.'],
            'private network' => ['https://10.0.0.1/file.jpg', 'file.jpg', 'File references must resolve to public IP addresses.'],
            'link local metadata' => ['https://169.254.169.254/file.jpg', 'file.jpg', 'File references must resolve to public IP addresses.'],
            'IPv6 loopback' => ['https://[::1]/file.jpg', 'file.jpg', 'File references must resolve to public IP addresses.'],
            'IPv4-mapped loopback' => ['https://[::ffff:127.0.0.1]/file.jpg', 'file.jpg', 'File references must resolve to public IP addresses.'],
        ];
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
}
