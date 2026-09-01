<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Support\Facades\Folders;
use Illuminate\Foundation\Http\FormRequest;

class MoveAssetFolderRequest extends FormRequest
{
    private ?VolumeFolder $destinationFolder = null;

    private ?VolumeFolder $folderToMove = null;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'folderId' => ['required', 'integer'],
            'parentId' => ['required', 'integer'],
        ];
    }

    public function folderBeingMovedId(): int
    {
        return $this->integer('folderId');
    }

    public function newParentFolderId(): int
    {
        return $this->integer('parentId');
    }

    public function force(): bool
    {
        return $this->boolean('force');
    }

    public function shouldMergeFolders(): bool
    {
        return ! $this->force() && $this->boolean('merge');
    }

    public function folderToMove(): VolumeFolder
    {
        $folder = $this->folderToMove ?? Folders::getFolderById($this->folderBeingMovedId());

        abort_if(! $folder, 400, 'The folder you are trying to move does not exist');

        return $this->folderToMove = $folder;
    }

    public function destinationFolder(): VolumeFolder
    {
        $folder = $this->destinationFolder ?? Folders::getFolderById($this->newParentFolderId());

        abort_if(! $folder, 400, 'The destination folder does not exist');

        return $this->destinationFolder = $folder;
    }
}
