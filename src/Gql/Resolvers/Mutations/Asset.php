<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers\Mutations;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Events\AssetReplaced;
use CraftCms\Cms\Asset\Events\AssetReplacing;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Url;
use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class Asset extends ElementMutationResolver
{
    #[Override]
    protected array $immutableAttributes = ['id', 'uid', 'volumeId', 'folderId'];

    private ?string $filename = null;

    private UrlValidator $urlValidator;

    /** @param array<string, mixed> $arguments */
    public function saveAsset(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): AssetElement
    {
        /** @var Volume $volume */
        $volume = $this->getResolutionData('volume');
        $canIdentify = ! empty($arguments['id']) || ! empty($arguments['uid']);

        $newFolderId = $arguments['newFolderId'] ?? null;

        if ($canIdentify) {
            $this->requireSchemaAction('volumes.'.$volume->uid, 'save');

            if (! empty($arguments['uid'])) {
                $asset = Elements::createElementQuery(AssetElement::class)->uid($arguments['uid'])->one();
            } else {
                $asset = Elements::getElementById($arguments['id'], AssetElement::class);
            }

            if (! $asset) {
                throw new Error('No such asset exists');
            }

            /** @var AssetElement $asset */
            if ($asset->volumeId !== $volume->id) {
                $this->requireSchemaAction('volumes.'.$asset->getVolume()->uid, 'save');
            }
        } else {
            $this->requireSchemaAction('volumes.'.$volume->uid, 'create');

            if (empty($arguments['_file'])) {
                throw new UserError('Impossible to create an asset without providing a file');
            }

            if (empty($newFolderId)) {
                $newFolderId = Folders::getRootFolderByVolumeId($volume->id)->id;
            }

            $asset = Elements::createElement([
                'type' => AssetElement::class,
                'volumeId' => $volume->id,
                'newFolderId' => $newFolderId,
            ]);
        }

        if (empty($newFolderId)) {
            if (! $canIdentify) {
                /** @var AssetElement $asset */
                $asset->newFolderId = Folders::getRootFolderByVolumeId($volume->id)->id;
            }
        } else {
            $folder = Folders::getFolderById($newFolderId);

            if (! $folder || $folder->volumeId !== $volume->id) {
                throw new UserError('Invalid folder id provided');
            }
        }

        /** @var AssetElement $asset */
        $asset->setVolumeId($volume->id);

        $asset = $this->populateElementWithData($asset, $arguments, $resolveInfo);

        $triggerReplaceEvents = $asset->ruleset->getScenario() === AssetRules::SCENARIO_REPLACE;

        if ($triggerReplaceEvents) {
            event($event = new AssetReplacing(
                asset: $asset,
                replaceWith: $asset->tempFilePath,
                filename: $this->filename,
            ));
            $this->filename = $event->filename;
        }

        /** @var AssetElement $asset */
        $asset = $this->saveElement($asset);

        if ($triggerReplaceEvents) {
            event(new AssetReplaced(
                asset: $asset,
                filename: $this->filename,
            ));
        }

        /** @var AssetElement */
        return Elements::getElementById($asset->id, AssetElement::class);
    }

    /** @param array<string, mixed> $arguments */
    public function deleteAsset(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): bool
    {
        $assetId = $arguments['id'];
        $hardDelete = $arguments['hardDelete'] ?? false;

        /** @var AssetElement|null $asset */
        $asset = Elements::getElementById($assetId, AssetElement::class);

        if (! $asset) {
            return false;
        }

        $volumeUid = DB::table(Table::VOLUMES)->uidById($asset->getVolumeId());
        $this->requireSchemaAction('volumes.'.$volumeUid, 'delete');

        return Elements::deleteElementById($assetId, hardDelete: $hardDelete);
    }

    /** @param array<string, mixed> $arguments */
    #[Override]
    protected function populateElementWithData(ElementInterface $element, array $arguments, ?ResolveInfo $resolveInfo = null): ElementInterface
    {
        if (! empty($arguments['_file'])) {
            $fileInformation = Arr::pull($arguments, '_file');
        }

        /** @var AssetElement $element */
        $element = parent::populateElementWithData($element, $arguments, $resolveInfo);

        if (! empty($fileInformation) && $this->handleUpload($element, $fileInformation)) {
            $element->ruleset->useScenario($element->id
                ? AssetRules::SCENARIO_REPLACE
                : AssetRules::SCENARIO_CREATE
            );
        }

        return $element;
    }

    /** @param array{filename?: string, fileData?: string, url?: string} $fileInformation */
    protected function handleUpload(AssetElement $asset, array $fileInformation): bool
    {
        $tempPath = null;
        $filename = null;

        $allowedExtensions = Cms::config()->allowedFileExtensions;

        if (! empty($fileInformation['fileData'])) {
            $dataString = $fileInformation['fileData'];
            $fileData = null;

            if (preg_match('/^data:((?<type>[a-z0-9]+\/[a-z0-9\+\.\-]+);)?base64,(?<data>.+)/i', (string) $dataString, $matches)) {
                // Decode the file
                $fileData = base64_decode($matches['data']);
            }

            if ($fileData) {
                if (empty($fileInformation['filename'])) {
                    // Make up a filename
                    $extension = null;
                    if (isset($matches['type'])) {
                        try {
                            $extension = File::getExtensionByMimeType($matches['type']);
                        } catch (InvalidArgumentException) {
                        }
                    }
                    if (! $extension) {
                        throw new UserError('Invalid file data provided.');
                    }
                    $filename = 'Upload.'.$extension;
                } else {
                    $filename = AssetsHelper::prepareAssetName($fileInformation['filename']);
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                }

                if (! in_array($extension, $allowedExtensions, true)) {
                    throw new AssetDisallowedExtensionException(t('“{extension}” is not an allowed file extension.', [
                        'extension' => $extension,
                    ]));
                }

                $tempPath = AssetsHelper::tempFilePath($extension);
                file_put_contents($tempPath, $fileData);
            } else {
                throw new UserError('Invalid file data provided');
            }
        } elseif (! empty($fileInformation['url'])) {
            $url = $fileInformation['url'];

            if (empty($fileInformation['filename'])) {
                $filename = AssetsHelper::prepareAssetName(pathinfo(Url::stripQueryString($url), PATHINFO_BASENAME));
            } else {
                $filename = AssetsHelper::prepareAssetName($fileInformation['filename']);
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (! in_array($extension, $allowedExtensions, true)) {
                throw new AssetDisallowedExtensionException(t('“{extension}” is not an allowed file extension.', [
                    'extension' => $extension,
                ]));
            }

            // Validate the URL and resolve it to a known-good set of IPs *before*
            // opening any connection (guards against SSRF + DNS rebinding).
            try {
                $ips = $this->urlValidator()->validate($url);
            } catch (UrlValidationException $e) {
                throw new UserError("$url is invalid.", previous: $e);
            }

            // Download the file, pinning the connection to the validated IPs
            $tempPath = AssetsHelper::tempFilePath($extension);
            $this->downloadUrl($url, $ips, $tempPath);
        }

        if (! $tempPath || ! $filename) {
            return false;
        }

        $asset->tempFilePath = $tempPath;
        $this->filename = $filename;
        if ($asset->id !== null && $asset->getFilename() !== $filename) {
            $asset->newFilename = $filename;
        } else {
            $asset->setFilename($filename);
        }
        $asset->setMimeType(File::getMimeType($tempPath, checkExtension: false));
        $asset->avoidFilenameConflicts = true;

        return true;
    }

    private function urlValidator(): UrlValidator
    {
        return $this->urlValidator ??= new UrlValidator;
    }

    /**
     * Downloads a remote file to a temp path, pinning the connection to a set of
     * pre-validated IP addresses so cURL can’t re-resolve the hostname to a
     * different (potentially internal) address between validation and download.
     *
     * @throws UserError if the connection still resolves to a disallowed IP
     */
    /** @param list<string> $ips */
    private function downloadUrl(string $url, array $ips, string $tempPath): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT)
            ?? (strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80);

        Http::create()->withOptions([
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::SINK => $tempPath,
            // Pin the connection to the IPs we already validated, so cURL doesn’t
            // re-resolve the hostname to a different address (DNS rebinding).
            'curl' => [
                CURLOPT_RESOLVE => ["$host:$port:".implode(',', $ips)],
            ],
            RequestOptions::ON_STATS => function (TransferStats $stats) use ($url) {
                // Validate the IP again, in case the cURL handler isn’t in use (so CURLOPT_RESOLVE was ignored)
                $ip = $stats->getHandlerStat('primary_ip');
                if ($ip && ! $this->urlValidator()->validateIp($ip)) {
                    throw new UserError("$url is invalid.");
                }
            },
        ])->get($url)->throw();
    }
}
