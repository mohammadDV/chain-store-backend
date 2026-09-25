<?php

use Domain\File\Services\FileService;
use Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

it('uploads a file to the s3 disk', function () {
    Storage::fake('s3');

    $user = new User;
    $user->id = 42;
    Auth::setUser($user);

    $service = new FileService;
    $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

    $result = $service->moveToStorage($file);

    expect($result)->toBeString()->not->toBeEmpty();
    Storage::disk('s3')->assertExists($result);
});

it('uses exclusive and file directories when provided', function () {
    Storage::fake('s3');

    $service = new FileService;
    $service->setExclusiveDirectory('brands');
    $service->setFileDirectory('logos');
    $service->setFileName('logo');

    $file = UploadedFile::fake()->create('logo.png', 50, 'image/png');
    $result = $service->moveToStorage($file);

    expect($result)->toStartWith('brands/logos/')
        ->and($service->getFinalFileDirectory())->toBe('brands'.DIRECTORY_SEPARATOR.'logos')
        ->and($service->getFinalFileName())->toBe('logo.png')
        ->and($service->getFileAddress())->toBe('brands'.DIRECTORY_SEPARATOR.'logos'.DIRECTORY_SEPARATOR.'logo.png');

    Storage::disk('s3')->assertExists($result);
});

it('deletes an existing file path', function () {
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'file-service-'.uniqid().'.txt';
    file_put_contents($path, 'temp');

    expect(file_exists($path))->toBeTrue();

    (new FileService)->deleteFile($path);

    expect(file_exists($path))->toBeFalse();
});

it('deletes a directory tree recursively', function () {
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'file-service-dir-'.uniqid();
    $nested = $root.DIRECTORY_SEPARATOR.'nested';
    mkdir($nested, 0755, true);
    file_put_contents($nested.DIRECTORY_SEPARATOR.'a.txt', 'a');

    $service = new FileService;

    expect($service->deleteDirectoryAndFiles($root))->toBeTrue()
        ->and(is_dir($root))->toBeFalse()
        ->and($service->deleteDirectoryAndFiles($root.'/missing'))->toBeFalse();
});

it('builds file address from final directory and name', function () {
    $service = new FileService;
    $service->setFinalFileDirectory('uploads/2026');
    $service->setFinalFileName('doc.pdf');

    expect($service->getFileAddress())
        ->toBe('uploads/2026'.DIRECTORY_SEPARATOR.'doc.pdf');
});
