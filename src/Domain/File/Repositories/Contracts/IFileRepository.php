<?php

namespace Domain\File\Repositories\Contracts;

use Application\Api\File\Requests\FileRequest;
use Application\Api\File\Requests\ImageRequest;
use Application\Api\File\Requests\VideoRequest;

 /**
 * Interface IFileRepository.
 */
interface IFileRepository  {

    /**
     * Upload the image
     * @param ImageRequest $request
     * @return array
     */
    public function uploadImage(ImageRequest $request);

    /**
     * Upload the video
     * @param VideoRequest $request
     * @return array
     */
    public function uploadVideo(VideoRequest $request);

    /**
     * Upload the file
     * @param FileRequest $request
     * @return array
     */
    public function uploadFile(FileRequest $request);

}