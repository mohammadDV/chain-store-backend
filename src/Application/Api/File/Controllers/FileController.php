<?php

namespace Application\Api\File\Controllers;

use Application\Api\File\Requests\FileRequest;
use Application\Api\File\Requests\ImageRequest;
use Application\Api\File\Requests\VideoRequest;
use Core\Http\Controllers\Controller;
use Domain\File\Repositories\Contracts\IFileRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class FileController extends Controller
{
    /**
     * Constructor of ILiveRepository.
     */
    public function __construct(protected IFileRepository $repository)
    {
        //
    }

    /**
     * Upload the image.
     * @param ImageRequest $request
     */
    public function uploadImage(ImageRequest $request): JsonResponse
    {
        return response()->json($this->repository->uploadImage($request), Response::HTTP_OK);
    }

    /**
     * Upload the video.
     * @param VideoRequest $request
     */
    public function uploadVideo(VideoRequest $request): JsonResponse
    {
        return response()->json($this->repository->uploadVideo($request), Response::HTTP_OK);
    }
    /**
     * Upload the video.
     * @param FileRequest $request
     */
    public function uploadFile(FileRequest $request): JsonResponse
    {
        return response()->json($this->repository->uploadFile($request), Response::HTTP_OK);
    }

}
