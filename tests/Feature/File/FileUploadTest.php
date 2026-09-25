<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    Storage::fake('s3');
});

it('uploads an image for authenticated user', function () {
    $this->actingAsUser();

    $file = UploadedFile::fake()->image('photo.jpg', 200, 200);

    $response = $this->postJson('/api/upload-image', [
        'image' => $file,
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonStructure(['url']);

    expect($response->json('url'))->not->toBeEmpty();
});

it('rejects guest image upload', function () {
    $file = UploadedFile::fake()->image('photo.jpg');

    $this->postJson('/api/upload-image', [
        'image' => $file,
    ])->assertUnauthorized();
});

it('rejects invalid image upload with 422', function () {
    $this->actingAsUser();

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->postJson('/api/upload-image', [
        'image' => $file,
    ])->assertStatus(422);
});

it('rejects missing image with 422', function () {
    $this->actingAsUser();

    $this->postJson('/api/upload-image', [])->assertStatus(422);
});
