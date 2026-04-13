<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Enums;

use function CraftCms\Cms\t;

enum FileKind: string
{
    case Access = 'access';
    case Audio = 'audio';
    case CaptionsSubtitles = 'captionsSubtitles';
    case Compressed = 'compressed';
    case Excel = 'excel';
    case Flash = 'flash';
    case Html = 'html';
    case Illustrator = 'illustrator';
    case Image = 'image';
    case Javascript = 'javascript';
    case Json = 'json';
    case Pdf = 'pdf';
    case Photoshop = 'photoshop';
    case Php = 'php';
    case Powerpoint = 'powerpoint';
    case Text = 'text';
    case Video = 'video';
    case Word = 'word';
    case Xml = 'xml';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Access',
            self::Audio => t('Audio'),
            self::CaptionsSubtitles => t('Captions/Subtitles'),
            self::Compressed => t('Compressed'),
            self::Excel => 'Excel',
            self::Flash => 'Flash',
            self::Html => 'HTML',
            self::Illustrator => 'Illustrator',
            self::Image => t('Image'),
            self::Javascript => 'JavaScript',
            self::Json => 'JSON',
            self::Pdf => 'PDF',
            self::Photoshop => 'Photoshop',
            self::Php => 'PHP',
            self::Powerpoint => 'PowerPoint',
            self::Text => t('Text'),
            self::Video => t('Video'),
            self::Word => 'Word',
            self::Xml => 'XML',
            self::Unknown => t('Unknown'),
        };
    }

    public function extensions(): array
    {
        return match ($this) {
            self::Access => ['accdb', 'accde', 'accdr', 'accdt', 'adp', 'mdb'],
            self::Audio => ['3gp', 'aac', 'act', 'aif', 'aifc', 'aiff', 'alac', 'amr', 'au', 'dct', 'dss', 'dvf', 'flac', 'gsm', 'iklax', 'ivs', 'm4a', 'm4p', 'mmf', 'mp3', 'mpc', 'msv', 'oga', 'ogg', 'opus', 'ra', 'tta', 'vox', 'wav', 'wma', 'wv'],
            self::CaptionsSubtitles => ['asc', 'cap', 'cin', 'dfxp', 'itt', 'lrc', 'mcc', 'mpsub', 'rt', 'sami', 'sbv', 'scc', 'smi', 'srt', 'stl', 'sub', 'tds', 'ttml', 'vtt'],
            self::Compressed => ['7z', 'bz2', 'dmg', 'gz', 'rar', 's7z', 'tar', 'tgz', 'zip', 'zipx'],
            self::Excel => ['xls', 'xlsm', 'xlsx', 'xltm', 'xltx'],
            self::Flash => [],
            self::Html => ['htm', 'html'],
            self::Illustrator => ['ai'],
            self::Image => ['avif', 'bmp', 'gif', 'heic', 'heif', 'jfif', 'jp2', 'jpe', 'jpeg', 'jpg', 'jpx', 'pam', 'pfm', 'pgm', 'png', 'pnm', 'ppm', 'svg', 'tif', 'tiff', 'webp'],
            self::Javascript => ['js'],
            self::Json => ['json'],
            self::Pdf => ['pdf'],
            self::Photoshop => ['psb', 'psd'],
            self::Php => ['php'],
            self::Powerpoint => ['potx', 'pps', 'ppsm', 'ppsx', 'ppt', 'pptm', 'pptx'],
            self::Text => ['text', 'txt'],
            self::Video => ['asf', 'asx', 'avchd', 'avi', 'fla', 'flv', 'hevc', 'm1s', 'm2s', 'm2t', 'm2v', 'm4v', 'mkv', 'mng', 'mov', 'mp2v', 'mp4', 'mpeg', 'mpg', 'ogg', 'ogv', 'qt', 'rm', 'vob', 'webm', 'wmv'],
            self::Word => ['doc', 'docm', 'docx', 'dot', 'dotm', 'dotx'],
            self::Xml => ['xml'],
            self::Unknown => [],
        };
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label(),
            'extensions' => $this->extensions(),
        ];
    }
}
