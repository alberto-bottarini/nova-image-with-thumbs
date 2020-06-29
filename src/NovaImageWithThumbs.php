<?php

namespace AlbertoBottarini\NovaImageWithThumbs;

use Illuminate\Http\Request;
use Intervention\Image\Facades\Image as Cropper;
use Laravel\Nova\Fields\Image;
use Storage;

class NovaImageWithThumbs extends Image
{
    /**
     * Settings about thumbnail generation.
     *
     * @var array
     */
    public $thumbConfigs = [];

    public function __construct($name, $attribute = null, $disk = 'public', $storageCallback = null)
    {
        parent::__construct($name, $attribute, $disk, $storageCallback);

        $this->store(function (Request $request, $model, $attribute, $requestAttribute) {
            return $this->storeWithThumbs($request, $model, $attribute, $requestAttribute);
        })->delete(function (Request $request, $model, $disk, $path) {
            return $this->deleteWithThumbs($request, $model, $disk, $path);
        });

    }

    public function thumbs(array $thumbConfigs)
    {
        $this->validateThumbConfigs($thumbConfigs);
        $this->thumbConfigs = $thumbConfigs;
        return $this;
    }

    private function validateThumbConfigs($configs)
    {
        collect($configs)->each(function ($thumbConfig) {
            if (!isset($thumbConfig['name'])) {
                throw new \InvalidArgumentException("Name attribute is mandatory in thumbConfigs for $this->attribute NovaImageWithThumbs Field");
            }
            if (!isset($thumbConfig['w']) || !is_numeric($thumbConfig['w'])) {
                throw new \InvalidArgumentException("Width attribute must be numeric in thumbConfigs for $this->attribute NovaImageWithThumbs Field");
            }
            if (!isset($thumbConfig['h']) || !is_numeric($thumbConfig['h'])) {
                throw new \InvalidArgumentException("Height attribute must be numeric in thumbConfigs for $this->attribute NovaImageWithThumbs Field");
            }
            if (!isset($thumbConfig['h']) || !in_array($thumbConfig['method'], ['fit', 'resize'])) {
                throw new \InvalidArgumentException("Height attribute must be one between 'fit' or 'resize' in thumbConfigs for $this->attribute NovaImageWithThumbs Field");
            }
        });
    }

    private function storeWithThumbs($request, $model, $attribute, $requestAttribute)
    {
        $original = $this->storeFile($request, $requestAttribute);
        $originalFilename = pathinfo($original, PATHINFO_FILENAME);
        $originalExtension = pathinfo($original, PATHINFO_EXTENSION);

        $index = 0;

        return collect($this->thumbConfigs)->reduce(function ($all, $config) use ($request, $model, $requestAttribute, $originalFilename, $originalExtension, &$index) {

            $fileName = $this->getStorageDir() . DIRECTORY_SEPARATOR . $originalFilename . '_t' . $index++ . '.' . $originalExtension;
            $method = $config['method'];
            $name = $config['name'];

            $imageThumb = Cropper::make($request->{$requestAttribute})->$method($config['w'], $config['h'], function ($c) {
                $c->upsize();
            })->encode($originalExtension, 90);

            Storage::disk($this->getStorageDisk())->put($fileName, (string) $imageThumb);

            $all[$name] = $fileName;

            if ($model->$name) {
                Storage::disk($this->getStorageDisk())->delete($model->$name);
            }

            return $all;
        }, [$attribute => $original]);

    }

    private function deleteWithThumbs($request, $model, $disk, $path)
    {

        if (!$path) {
            return;
        }

        Storage::disk($this->getStorageDisk())->delete($path);

        return collect($this->thumbConfigs)->reduce(function ($all, $config) use ($model) {
            $name = $config['name'];
            if (!$model->name) {
                return $all;
            }

            Storage::disk($this->getStorageDisk())->delete($model->name);

            $all[$name] = null;

            return $all;
        }, [$this->attribute => null]);
    }

}
