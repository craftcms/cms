<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\base\ElementInterface;
use craft\events\ReplaceAssetEvent;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\services\Assets;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Database\Table;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;
use yii\base\Exception;
use function CraftCms\Cms\t;

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
        // Legacy service, tests depend on it
        $folders = Craft::$app->getAssets();

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

            /** @var AssetElement $asset */
            if ($asset->volumeId !== $volume->id) {
                $this->requireSchemaAction('volumes.' . $asset->getVolume()->uid, 'save');
            }
        } else {
            $this->requireSchemaAction('volumes.' . $volume->uid, 'create');

            if (empty($arguments['_file'])) {
                throw new UserError('Impossible to create an asset without providing a file');
            }

            if (empty($newFolderId)) {
                $newFolderId = $folders->getRootFolderByVolumeId($volume->id)->id;
            }

            $asset = $elementService->createElement([
                'type' => AssetElement::class,
                'volumeId' => $volume->id,
                'newFolderId' => $newFolderId,
            ]);
        }

        if (empty($newFolderId)) {
            if (!$canIdentify) {
                /** @var \CraftCms\Cms\Asset\Elements\Asset $asset */
                $asset->newFolderId = $folders->getRootFolderByVolumeId($volume->id)->id;
            }
        } else {
            $folder = $folders->getFolderById($newFolderId);

            if (!$folder || $folder->volumeId != $volume->id) {
                throw new UserError('Invalid folder id provided');
            }
        }

        /** @var AssetElement $asset */
        $asset->setVolumeId($volume->id);

        $asset = $this->populateElementWithData($asset, $arguments, $resolveInfo);

        // Legacy Yii2 event handling — must remain on legacy service
        $legacyAssets = Craft::$app->getAssets();
        $triggerReplaceEvents = (
            $asset->getScenario() === AssetElement::SCENARIO_REPLACE &&
            (
                $legacyAssets->hasEventHandlers(Assets::EVENT_BEFORE_REPLACE_ASSET) ||
                $legacyAssets->hasEventHandlers(Assets::EVENT_AFTER_REPLACE_ASSET)
            )
        );

        if ($triggerReplaceEvents) {
            $legacyAssets->trigger(Assets::EVENT_BEFORE_REPLACE_ASSET, new ReplaceAssetEvent([
                'asset' => $asset,
                'replaceWith' => $asset->tempFilePath,
                'filename' => $this->filename,
            ]));
        }

        $asset = $this->saveElement($asset);

        if ($triggerReplaceEvents) {
            $legacyAssets->trigger(Assets::EVENT_AFTER_REPLACE_ASSET, new ReplaceAssetEvent([
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

        $elementService = Craft::$app->getElements();
        /** @var AssetElement|null $asset */
        $asset = $elementService->getElementById($assetId, AssetElement::class);

        if (!$asset) {
            return false;
        }

        $volumeUid = DB::table(Table::VOLUMES)->uidById($asset->getVolumeId());
        $this->requireSchemaAction('volumes.' . $volumeUid, 'delete');

        return $elementService->deleteElementById($assetId);
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
                    throw new AssetDisallowedExtensionException(t('“{extension}” is not an allowed file extension.', [
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

            if (!$this->validateHostname($url)) {
                throw new UserError("$url contains an invalid hostname.");
            }

            if (empty($fileInformation['filename'])) {
                $filename = AssetsHelper::prepareAssetName(pathinfo(UrlHelper::stripQueryString($url), PATHINFO_BASENAME));
            } else {
                $filename = AssetsHelper::prepareAssetName($fileInformation['filename']);
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (is_array($allowedExtensions) && !in_array($extension, $allowedExtensions, true)) {
                throw new AssetDisallowedExtensionException(t('“{extension}” is not an allowed file extension.', [
                    'extension' => $extension,
                ]));
            }

            // Download the file
            $tempPath = AssetsHelper::tempFilePath($extension);
            Http::create()->withOptions([
                RequestOptions::ALLOW_REDIRECTS => false,
                RequestOptions::SINK => $tempPath,
                RequestOptions::ON_STATS => function(TransferStats $stats) use ($url) {
                    if (!$this->validateIp($stats->getHandlerStat('primary_ip'))) {
                        throw new UserError("$url resolves to an invalid IP address.");
                    }
                },
            ])->get($url, ['sink' => $tempPath]);
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

    private function validateHostname(string $url): bool
    {
        $hostname = parse_url($url, PHP_URL_HOST);

        // convert hex segments to decimal
        $hostname = Collection::make(explode('.', $hostname))
            ->map(function(string $chunk) {
                if (str_starts_with(strtolower($chunk), '0x')) {
                    $octets = str_split(substr($chunk, 2), 2);
                    return implode('.', array_map('hexdec', $octets));
                }
                return $chunk;
            })
            ->join('.');

        // make sure the hostname is alphanumeric and not an IP address
        if (
            !filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ||
            filter_var($hostname, FILTER_VALIDATE_IP)
        ) {
            return false;
        }

        // Check against well-known cloud metadata domains
        // h/t https://gist.github.com/BuffaloWill/fa96693af67e3a3dd3fb
        if (in_array($hostname, [
            'kubernetes.default',
            'kubernetes.default.svc',
            'kubernetes.default.svc.cluster.local',
            'metadata',
            'metadata.google.internal',
            'metadata.packet.net',
        ])) {
            return false;
        }

        return true;
    }

    private function validateIp(string $ip): bool
    {
        // make sure the hostname doesn’t resolve to a known cloud metadata IP
        // h/t https://gist.github.com/BuffaloWill/fa96693af67e3a3dd3fb
        if (in_array($ip, [
            '100.100.100.200', // Alibaba
            '169.254.169.254', // AWS, GCP, DO, Azure, Oracle, OpenStack/RackSpace
            '169.254.170.2', // ECS
            '192.0.0.192', // Oracle
        ])) {
            return false;
        }

        $v6Prefixes = [
            '::1', // Loopback
            '::ffff:', // IPv4-mapped IPv6
            'fd00:ec2::', // AWS IMDS, DNS, NTP
            'fd20:ce::', // GCP
            'fe80:', // Link-local
        ];

        foreach ($v6Prefixes as $prefix) {
            if (str_starts_with($ip, $prefix)) {
                return false;
            }
        }

        // Only allow publicly-routable IPs
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return false;
        }

        return true;
    }
}
