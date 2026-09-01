<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Support\Facades\Folders;
use Illuminate\Foundation\Http\FormRequest;

class RenameAssetFolderRequest extends FormRequest
{
    private ?VolumeFolder $folder = null;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'folderId' => ['required', 'integer'],
            'newName' => ['required', 'string'],
        ];
    }

    public function folderId(): int
    {
        return $this->integer('folderId');
    }

    public function newName(): string
    {
        return $this->string('newName')->toString();
    }

    public function folder(): VolumeFolder
    {
        $folder = $this->folder ?? Folders::getFolderById($this->folderId());

        abort_if(! $folder, 400, 'The folder cannot be found');

        return $this->folder = $folder;
    }
}
