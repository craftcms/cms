<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Asset as AssetElement;
use craft\errors\AssetDisallowedExtensionException;
use craft\events\ReplaceAssetEvent;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\models\Volume;
use craft\services\Assets;
use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Asset extends ElementMutationResolver
{
    /** @inheritdoc */
    protected array $immutableAttributes = ['id', 'uid', 'volumeId', 'folderId'];

    private ?string $filename = null;

    private UrlValidator $urlValidator;

    /**
     * Save an asset using the passed arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return AssetElement
     * @throws Throwable if reasons.
     */
    public function saveAsset(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): AssetElement
    {
        /** @var Volume $volume */
        $volume = $this->getResolutionData('volume');
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $elementService = Craft::$app->getElements();

        $newFolderId = $arguments['newFolderId'] ?? null;
        $assetService = Craft::$app->getAssets();

        if ($canIdentify) {
            $this->requireSchemaAction('volumes.' . $volume->uid, 'save');

            if (!empty($arguments['uid'])) {
                $asset = $elementService->createElementQuery(AssetElement::class)->uid($arguments['uid'])->one();
            } else {
                $asset = $elementService->getElementById($arguments['id'], AssetElement::class);
            }

            if (!$asset) {
                throw new Error('No such asset exists');
            }

            if ($asset->volumeId !== $volume->id) {
                $this->requireSchemaAction('volumes.' . $asset->getVolume()->uid, 'save');
            }
        } else {
            $this->requireSchemaAction('volumes.' . $volume->uid, 'create');

            if (empty($arguments['_file'])) {
                throw new UserError('Impossible to create an asset without providing a file');
            }

            if (empty($newFolderId)) {
                $newFolderId = $assetService->getRootFolderByVolumeId($volume->id)->id;
            }

            $asset = $elementService->createElement([
                'type' => AssetElement::class,
                'volumeId' => $volume->id,
                'newFolderId' => $newFolderId,
            ]);
        }

        if (empty($newFolderId)) {
            if (!$canIdentify) {
                $asset->newFolderId = $assetService->getRootFolderByVolumeId($volume->id)->id;
            }
        } else {
            $folder = $assetService->getFolderById($newFolderId);

            if (!$folder || $folder->volumeId != $volume->id) {
                throw new UserError('Invalid folder id provided');
            }
        }

        /** @var AssetElement $asset */
        $asset->setVolumeId($volume->id);

        $asset = $this->populateElementWithData($asset, $arguments, $resolveInfo);
        $triggerReplaceEvents = (
            $asset->getScenario() === AssetElement::SCENARIO_REPLACE &&
            (
                $assetService->hasEventHandlers(Assets::EVENT_BEFORE_REPLACE_ASSET) ||
                $assetService->hasEventHandlers(Assets::EVENT_AFTER_REPLACE_ASSET)
            )
        );

        if ($triggerReplaceEvents) {
            $assetService->trigger(Assets::EVENT_BEFORE_REPLACE_ASSET, new ReplaceAssetEvent([
                'asset' => $asset,
                'replaceWith' => $asset->tempFilePath,
                'filename' => $this->filename,
            ]));
        }

        $asset = $this->saveElement($asset);

        if ($triggerReplaceEvents) {
            $assetService->trigger(Assets::EVENT_AFTER_REPLACE_ASSET, new ReplaceAssetEvent([
                'asset' => $asset,
                'filename' => $this->filename,
            ]));
        }

        return $elementService->getElementById($asset->id, AssetElement::class);
    }

    /**
     * Delete an asset identified by the arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return bool
     * @throws Throwable if reasons.
     */
    public function deleteAsset(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): bool
    {
        $assetId = $arguments['id'];
        $hardDelete = $arguments['hardDelete'] ?? false;

        $elementService = Craft::$app->getElements();
        /** @var AssetElement|null $asset */
        $asset = $elementService->getElementById($assetId, AssetElement::class);

        if (!$asset) {
            return false;
        }

        $volumeUid = Db::uidById(Table::VOLUMES, $asset->getVolumeId());
        $this->requireSchemaAction('volumes.' . $volumeUid, 'delete');

        return $elementService->deleteElementById($assetId, hardDelete: $hardDelete);
    }

    /**
     * @inheritdoc
     */
    protected function populateElementWithData(ElementInterface $element, array $arguments, ?ResolveInfo $resolveInfo = null): ElementInterface
    {
        if (!empty($arguments['_file'])) {
            $fileInformation = $arguments['_file'];
            unset($arguments['_file']);
        }

        /** @var AssetElement $element */
        $element = parent::populateElementWithData($element, $arguments, $resolveInfo);

        if (!empty($fileInformation) && $this->handleUpload($element, $fileInformation)) {
            if ($element->id) {
                $element->setScenario(AssetElement::SCENARIO_REPLACE);
            } else {
                $element->setScenario(AssetElement::SCENARIO_CREATE);
            }
        }

        return $element;
    }

    /**
     * Handle file upload.
     *
     * @param AssetElement $asset
     * @param array $fileInformation
     * @return bool
     * @throws Exception
     */
    protected function handleUpload(AssetElement $asset, array $fileInformation): bool
    {
        $tempPath = null;
        $filename = null;

        $allowedExtensions = Craft::$app->getConfig()->getGeneral()->allowedFileExtensions;

        if (!empty($fileInformation['fileData'])) {
            $dataString = $fileInformation['fileData'];
            $fileData = null;

            if (preg_match('/^data:((?<type>[a-z0-9]+\/[a-z0-9\+\.\-]+);)?base64,(?<data>.+)/i', $dataString, $matches)) {
                // Decode the file
                $fileData = base64_decode($matches['data']);
            }

            if ($fileData) {
                if (empty($fileInformation['filename'])) {
                    // Make up a filename
                    $extension = null;
                    if (isset($matches['type'])) {
                        try {
                            $extension = FileHelper::getExtensionByMimeType($matches['type']);
                        } catch (InvalidArgumentException) {
                        }
                    }
                    if (!$extension) {
                        throw new UserError('Invalid file data provided.');
                    }
                    $filename = 'Upload.' . $extension;
                } else {
                    $filename = AssetsHelper::prepareAssetName($fileInformation['filename']);
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                }

                if (is_array($allowedExtensions) && !in_array($extension, $allowedExtensions, true)) {
                    throw new AssetDisallowedExtensionException(Craft::t('app', '“{extension}” is not an allowed file extension.', [
                        'extension' => $extension,
                    ]));
                }

                $tempPath = AssetsHelper::tempFilePath($extension);
                file_put_contents($tempPath, $fileData);
            } else {
                throw new UserError('Invalid file data provided');
            }
        } elseif (!empty($fileInformation['url'])) {
            $url = $fileInformation['url'];

            if (empty($fileInformation['filename'])) {
                $filename = AssetsHelper::prepareAssetName(pathinfo(UrlHelper::stripQueryString($url), PATHINFO_BASENAME));
            } else {
                $filename = AssetsHelper::prepareAssetName($fileInformation['filename']);
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (is_array($allowedExtensions) && !in_array($extension, $allowedExtensions, true)) {
                throw new AssetDisallowedExtensionException(Craft::t('app', '“{extension}” is not an allowed file extension.', [
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

        if (!$tempPath || !$filename) {
            return false;
        }

        $asset->tempFilePath = $tempPath;
        $this->filename = $filename;
        if ($asset->id !== null && $asset->getFilename() !== $filename) {
            $asset->newFilename = $filename;
        } else {
            $asset->setFilename($filename);
        }
        $asset->setMimeType(FileHelper::getMimeType($tempPath, checkExtension: false));
        $asset->avoidFilenameConflicts = true;

        return true;
    }

    private function urlValidator(): UrlValidator
    {
        return $this->urlValidator ??= new UrlValidator();
    }

    /**
     * Downloads a remote file to a temp path, pinning the connection to a set of
     * pre-validated IP addresses so cURL can’t re-resolve the hostname to a
     * different (potentially internal) address between validation and download.
     *
     * @throws UserError if the connection still resolves to a disallowed IP
     */
    private function downloadUrl(string $url, array $ips, string $tempPath): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT)
            ?? (strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80);

        $this->createGuzzleClient()->request('GET', $url, [
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::SINK => $tempPath,
            // Pin the connection to the IPs we already validated, so cURL doesn’t
            // re-resolve the hostname to a different address (DNS rebinding).
            'curl' => [
                CURLOPT_RESOLVE => ["$host:$port:" . implode(',', $ips)],
            ],
            RequestOptions::ON_STATS => function(TransferStats $stats) use ($url) {
                // Validate the IP again, in case the cURL handler isn’t in use (so CURLOPT_RESOLVE was ignored)
                $ip = $stats->getHandlerStat('primary_ip');
                if ($ip && !$this->urlValidator()->validateIp($ip)) {
                    throw new UserError("$url is invalid.");
                }
            },
        ]);
    }

    /**
     * Create the guzzle client.
     *
     * @return Client
     */
    protected function createGuzzleClient(): Client
    {
        return Craft::createGuzzleClient();
    }
}
