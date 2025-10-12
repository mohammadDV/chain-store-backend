<?php

namespace Core\Http\traits;

use App\Services\Image\ImageService;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Auth;

trait GlobalFunc
{
    /**
     * Check the level access
     * @param bool $conditions
     * @return void
     */
    public function checkLevelAccess(bool $condition = false) {

        if (!$condition && Auth::user()->level != 3) {
            throw New \Exception('Unauthorized', 403);
        }
    }

    /**
     * Check the level access
     * @param bool $conditions
     * @return bool
     */
    public function checkNickname(string $nickname, int $userId = 0) : bool {

        if (User::query()
            ->where('nickname', $nickname)
            ->when(!empty($userId), function ($query) use($userId) {
                $query->where('id', '!=', $userId);
            })
            ->count() > 0) {
                return false;
        }

        return true;
    }

    /**
     * Check the level access
     * @param ImageService $imageService
     * @param $file
     * @param string $url
     * @param string $image
     * @return void
     */
    // public function uploadImage(ImageService $imageService, $file,string $url, $image){
    //     $imageService->setExclusiveDirectory($url);
    //     $result = $imageService->save($file);
    //     if ($result && !empty($image)){
    //         if(env('APP_ENV') == "production"){
    //             Storage::disk('s3')->delete($image);
    //         }else{
    //             $imageService->deleteImage($image);
    //         }
    //     }
    //     $imageService->reset();

    //     return $result;
    // }
}
;
