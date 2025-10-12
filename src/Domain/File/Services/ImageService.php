<?php

namespace Domain\File\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageService extends ImageToolsService
{

    public function save($image, $thumb = 0)
    {
        //set image
        $this->setImage($image);
        //execute provider
        $this->provider();

        // Save image
        // if($image->getClientOriginalExtension()=='gif'){

            // if(env('APP_ENV') == "production") {
                // $result = Storage::disk('liara')->put($this->getFinalImageDirectory(), $image);
                // $S3Path = Storage::disk('liara')->url($result);
            // }else{
            //     $result = $image->move(public_path($this->getFinalImageDirectory()),$this->getImageName() . "." . $this->getImageFormat());
            // }
        // }else{
            // if(env('APP_ENV') == "production") {
                // $result = Image::make($image->getRealPath())->encode($this->getImageFormat());

                // $result = Storage::disk('liara')->put($this->getFinalImageDirectory(), $image);
                $result = Storage::disk('s3')->put($this->getFinalImageDirectory(), $image, 'public');


                $url = Storage::disk('s3')->url($result);
                $path = parse_url($url, PHP_URL_PATH);

                if (!empty($thumb)) {
                    $fileName = '/thumbnails/' . basename($path);
                    $resizedImage = Image::make($image)->resize(150, 100)->encode('jpg');
                    // Storage::disk('liara')->put($this->getFinalImageDirectory() . $fileName, $resizedImage);
                    Storage::disk('s3')->put($this->getFinalImageDirectory() . $fileName, $resizedImage, 'public');

                    $fileName = '/slides/' . basename($path);
                    $resizedImage = Image::make($image)->resize(455, 303)->encode('jpg');
                    // Storage::disk('liara')->put($this->getFinalImageDirectory() . $fileName, $resizedImage);
                    Storage::disk('s3')->put($this->getFinalImageDirectory() . $fileName, $resizedImage, 'public');
                }

                // $result = Storage::disk('liara')->put($this->getFinalImageDirectory(), $image);
            // return  $S3Path = str_replace('https://storage.iran.liara.space', 'https://cdn.varzeshpod.com/' , Storage::disk('liara')->url($result));
        return  $result;
            // }else{
            //     $result = Image::make($image->getRealPath())->save(public_path($this->getImageAddress()), null, $this->getImageFormat());
            // }
        // }

        // return explode(config('filesystems.disks.s3.bucket') . "/",$S3Path)[1];
        return env('APP_ENV') == "production" ? explode(config('filesystems.disks.s3.bucket') . "/",$S3Path)[1] :  $this->getImageAddress();
    }

    public function fitAndSave($image, $width, $height)
    {
         //set image
         $this->setImage($image);
         //execute provider
         $this->provider();
         //save image
         $result = Image::make($image->getRealPath())->fit($width, $height)->save(public_path($this->getImageAddress()), null, $this->getImageFormat());
         return $result ? $this->getImageAddress() : false;
    }

    public function createIndexAndSave($image)
    {
            //get data from config
            $imageSizes = Config::get('image.index-image-sizes');

            //set image
            $this->setImage($image);

            //set directory
            $this->getImageDirectory() ?? $this->setImageDirectory(date("Y") . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d'));
            $this->setImageDirectory($this->getImageDirectory() . DIRECTORY_SEPARATOR . time(). rand(1111,99999));

            //set name
            $this->getImageName() ?? $this->setImageName(Str::uuid());
            $imageName = $this->getImageName();

            $indexArray = [];
            foreach($imageSizes as $sizeAlias => $imageSize)
            {

                //create and set this size name
                $currentImageName = $imageName . '_' . $sizeAlias;
                $this->setImageName($currentImageName);

                //execute provider
                $this->provider();

                //save image
                $result = Image::make($image->getRealPath())->fit($imageSize['width'], $imageSize['height'])->save(public_path($this->getImageAddress()), null, $this->getImageFormat());
                    if($result)
                        $indexArray[$sizeAlias] = $this->getImageAddress();
                    else
                    {
                        return false;
                    }

            }
            $images['indexArray'] = $indexArray;
            $images['directory'] = $this->getFinalImageDirectory();
            $images['currentImage'] = Config::get('image.default-current-index-image');

            return $images;
    }

    public function deleteImage($imagePath)
    {
        if(file_exists($imagePath))
        {
            unlink($imagePath);
        }
    }

    public function deleteIndex($images)
    {
        $directory = public_path($images['directory']);
        $this->deleteDirectoryAndFiles($directory);
    }

    public function deleteDirectoryAndFiles($directory)
    {
        if(!is_dir($directory))
        {
            return false;
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*', GLOB_MARK);
        foreach($files as $file)
        {
            if(is_dir($file))
            {
                $this->deleteDirectoryAndFiles($file);
            }
            else{
                unlink($file);
            }
        }

        $result = rmdir($directory);
        return $result;
    }


}