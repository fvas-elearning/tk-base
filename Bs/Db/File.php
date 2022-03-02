<?php

namespace Bs\Db;

use Bs\Config;
use Bs\Db\Traits\ForeignModelTrait;
use Bs\Db\Traits\TimestampTrait;
use Bs\Db\Traits\UserTrait;
use DateTime;
use Exception;
use Tk\Db\Map\Model;
use Tk\Db\ModelInterface;
use Tk\Log;
use Tk\Uri;
use Tk\ValidInterface;

/**
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class File extends Model implements ValidInterface
{

    use ForeignModelTrait;
    use TimestampTrait;
    use UserTrait;

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $userId = 0;

    /**
     * @var string
     */
    public $fkey = '';

    /**
     * @var int
     */
    public $fid = 0;

    /**
     * @var string
     */
    public $path = '';

    /**
     * @var int
     */
    public $bytes = 0;

    /**
     * @var string
     */
    public $mime = '';

    /**
     * @var string
     */
    public $label = '';

    /**
     * @var boolean
     */
    public $active = '';

    /**
     * @var string
     */
    public $notes = '';

    /**
     * @var string
     */
    public $hash = '';

    /**
     * @var DateTime
     */
    public $modified = null;

    /**
     * @var DateTime
     */
    public $created = null;


    /**
     * File constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->_TimestampTrait();
    }

    /**
     * @param ModelInterface $model
     * @param string $file Relative/Full path to a valid file
     * @param string $dataPath (optional) if none then \App\Config::getInstance()->getDataPath() is used
     * @param null|int $userId
     * @return static
     */
    public static function create($model, $file = '', $dataPath = '', $userId = null)
    {
        $obj = new static();
        $obj->setLabel(\Tk\File::removeExtension(basename($file)));
        $obj->setForeignModel($model);
        if ($userId === null) {
            if (method_exists($model, 'getUserId')) {
                $userId = $model->getUserId();
            } elseif (property_exists($model, 'userId')) {
                $userId = $model->userId;
            } else if ($obj->getConfig()->getAuthUser()) {
                $userId = $obj->getConfig()->getAuthUser()->getId();
            }
            $userId = 0;
        }
        $obj->setUserId($userId);


        if (!$dataPath) $dataPath = Config::getInstance()->getDataPath();
        if ($file) {
            $file = str_replace($dataPath, '', $file);
            $fullPath = $dataPath . $file;
            if (is_file($fullPath)) {
                $obj->setPath($file);
                $obj->setBytes(filesize($fullPath));
                $obj->setMime(\Tk\File::getMimeType($fullPath));
            }
        }
        return $obj;
    }

    /**
     * Save
     */
    public function save()
    {
        $this->getHash();
        parent::save();
    }

    /**
     * @param null|string $dataPath
     * @return int
     */
    public function delete($dataPath = null)
    {
        if (!$dataPath) $dataPath = Config::getInstance()->getDataPath();
        if ($dataPath && is_file($dataPath . $this->getPath())) {
            unlink($dataPath . $this->getPath());
            Log::alert('File deleted: ' . $dataPath . $this->getPath());
        } else {
            Log::warning('File not deleted: ' . $dataPath . $this->getPath());
        }
        return parent::delete();
    }

    /**
     * Get the user hash or generate one if needed
     *
     * @return string
     */
    public function getHash()
    {
        if (!$this->hash) {
            $this->hash = $this->generateHash();
        }
        return $this->hash;
    }

    /**
     * @return Uri
     */
    public function getUrl()
    {
        return Uri::create(Config::getInstance()->getDataUrl() . $this->getPath());
    }


    /**
     * Helper method to generate user hash
     *
     * @return string
     */
    public function generateHash()
    {
        // UNIQUE KEY (`fkey`, `fid`, `path`)
        return Config::getInstance()->hash(sprintf('%s%s%s', $this->getFkey(), $this->getFid(), $this->getPath()));
    }


    public function getIcon()
    {
        $ext = \Tk\File::getExtension($this->path);
        switch ($ext) {
            case 'zip':
            case 'gz':
            case 'tar':
            case 'gtz':
            case 'rar':
            case '7zip':
            case 'jar':
            case 'pkg':
            case 'deb':
                return 'fa-file-archive-o';
            case 'h':
            case 'c':
            case 'php':
            case 'js':
            case 'css':
            case 'less':
            case 'txt':
            case 'xml':
            case 'xslt':
            case 'json':
                return 'fa-file-code-o';
            case 'ods':
            case 'sdc':
            case 'sxc':
            case 'xls':
            case 'xlsm':
            case 'xlsx':
            case 'csv':
                return 'fa-file-excel-o';
            case 'bmp':
            case 'emf':
            case 'gif':
            case 'ico':
            case 'icon':
            case 'jpeg':
            case 'jpg':
            case 'pcx':
            case 'pic':
            case 'png':
            case 'psd':
            case 'raw':
            case 'tga':
            case 'tif':
            case 'tiff':
            case 'swf':
            case 'drw':
            case 'svg':
            case 'svgz':
            case 'ai':
                return 'fa-file-image-o';
            case 'aiff':
            case 'cda':
            case 'dvf':
            case 'flac':
            case 'm4a':
            case 'm4b':
            case 'midi':
            case 'mp3':
            case 'ogg':
            case 'pcm':
            case 'snd':
            case 'wav':
                return 'fa-file-audio-o';
            case 'avi':
            case 'mov':
            case 'mp4':
            case 'mpg':
            case 'mpeg':
            case 'mkv':
            case 'ogv':
            case 'flv':
            case 'webm':
            case 'wmv':
            case 'asx':
                return 'fa-file-video-o';
            case 'pdf':
                return 'fa-file-pdf-o';
            case 'ppt':
            case 'pot':
            case 'potx':
            case 'pps':
            case 'ppsx':
            case 'pptx':
            case 'pptm':
                return 'fa-file-powerpoint-o';
            case 'doc':
            case 'docm':
            case 'dotm':
            case 'dotx':
            case 'docx':
            case 'dot':
            case 'wri':
            case 'wps':
                return 'fa-file-word-o';
        }
        return 'fa-file-o';
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return int
     */
    public function getBytes(): int
    {
        return $this->bytes;
    }

    /**
     * @param int $bytes
     */
    public function setBytes(int $bytes): void
    {
        $this->bytes = $bytes;
    }

    /**
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * @param string $mime
     */
    public function setMime(string $mime): void
    {
        $this->mime = $mime;
    }

    public function isImage()
    {
        return preg_match('/^image\//', $this->getMime());
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return File
     */
    public function setLabel(string $label): File
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Use this as to tell if the files are to be attached to the PDF report
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Use this as to tell if the files are to be attached to the PDF report
     *
     * @param bool $active
     * @return File
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function validate()
    {
        $errors = array();

        if (!$this->getPath()) {
            $errors['path'] = 'Please enter a valid path';
        }
        if (!$this->getBytes()) {
            $errors['bytes'] = 'Please enter a file size';
        }
        if (!$this->getMime()) {
            $errors['mime'] = 'Please enter a file type';
        }

        $hashed = FileMap::create()->findByHash($this->getHash());
        if ($hashed && $hashed->getId() != $this->getVolatileId()) {
            $errors['duplicate'] = 'Cannot overwrite an existing file. [ID: ' . $hashed->getId() . ']';
        }

        return $errors;
    }
}