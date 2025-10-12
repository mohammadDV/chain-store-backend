<?php

namespace Domain\File\Repositories;

use Application\Api\File\Requests\FileRequest;
use Application\Api\File\Requests\ImageRequest;
use Application\Api\File\Requests\VideoRequest;
use Domain\File\Repositories\Contracts\IFileRepository;
use Domain\File\Services\FileService;
use Domain\File\Services\ImageService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class FileRepository implements IFileRepository {

    /**
     * @param ImageService $imageService
     * @param FileService $fileService
     */
    public function __construct(protected ImageService $imageService, protected FileService $fileService)
    {

    }

    /**
     * Upload the image
     * @param ImageRequest $request
     * @return array
     */
    public function uploadImage(ImageRequest $request)
    {

        if (empty(Auth::user()->status)) {
            return [
                'status' => 0,
                'message' => __('site.Your account is not active yet. Please send a message to the admin from ticket section.'),
            ];
        }

        if ($request->hasFile('image')) {
            $this->imageService->setExclusiveDirectory('oshtow' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $request->input('dir', 'default'));
                $imageResult = $this->imageService->save($request->file('image'),
                !empty($request->input('thumb')) ? 1 : 0
            );

            if (!$imageResult){
                throw new \Exception(__('site.Error in save data'));
            }

            return [
                'status' => !empty($imageResult) ? 1 : 0,
                'url' => $imageResult
            ];
        }

        return [
            'status' => 0,
            'url' => ''
        ];

    }

    /**
     * Upload the video
     * @param VideoRequest $request
     * @return array
     */
    public function uploadVideo(VideoRequest $request)
    {
        if (empty(Auth::user()->status)) {
            return [
                'status' => 0,
                'message' => __('site.Your account is not active yet. Please send a message to the admin from ticket section.'),
            ];
        }


        if ($request->hasFile('video')) {

            $this->fileService->setExclusiveDirectory('oshtow' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . $request->input('dir', 'default'));
            $videoResult = $this->fileService->moveToStorage($request->file('video'));

            if (!$videoResult){
                throw new \Exception(__('site.Error in save data'));
            }

            return [
                'status' => !empty($videoResult) ? 1 : 0,
                'url' => $videoResult
            ];
        }

        return [
            'status' => 0,
            'url' => ''
        ];

    }

    /**
     * Upload the video
     * @param FileRequest $request
     * @return array
     */
    public function uploadFile(FileRequest $request)
    {
        if (empty(Auth::user()->status)) {
            return [
                'status' => 0,
                'message' => __('site.Your account is not active yet. Please send a message to the admin from ticket section.'),
            ];
        }

        if ($request->hasFile('file')) {

            $this->fileService->setExclusiveDirectory('oshtow' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $request->input('dir', 'default'));
            $fileResult = $this->fileService->moveToStorage($request->file('file'));

            if (!$fileResult){
                throw new \Exception(__('site.Error in save data'));
            }

            return [
                'status' => !empty($fileResult) ? 1 : 0,
                'url' => $fileResult
            ];
        }

        return [
            'status' => 0,
            'url' => ''
        ];

    }
}