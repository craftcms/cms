<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Support\Facades\Folders;
use Illuminate\Foundation\Http\FormRequest;

class CreateAssetFolderRequest extends FormRequest
{
    private ?VolumeFolder $parentFolder = null;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'parentId' => ['required', 'integer'],
            'folderName' => ['required', 'string'],
        ];
    }

    public function parentFolderId(): int
    {
        return $this->integer('parentId');
    }

    public function folderName(): string
    {
        return AssetsHelper::prepareAssetName($this->string('folderName')->toString(), false);
    }

    public function parentFolder(): VolumeFolder
    {
        $folder = $this->parentFolder ?? Folders::findFolder(['id' => $this->parentFolderId()]);

        abort_if(! $folder, 400, 'The parent folder cannot be found');

        return $this->parentFolder = $folder;
    }
}
