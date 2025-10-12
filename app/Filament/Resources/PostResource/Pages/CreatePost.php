<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle thumbnail generation if image is provided and thumb is enabled
        if (!empty($data['image']) && isset($data['thumb']) && $data['thumb']) {
            $image = $data['image'];
            $path = parse_url($image, PHP_URL_PATH);
            $filename = basename($path);

            $data['thumbnail'] = str_replace($filename, 'thumbnails/' . $filename, $image);
            $data['slide'] = str_replace($filename, 'slides/' . $filename, $image);
        }

        // Set default values
        $data['view'] = $data['view'] ?? 0;
        $data['type'] = $data['type'] ?? 0;
        $data['special'] = $data['special'] ?? 0;
        $data['status'] = $data['status'] ?? 0;

        // Remove the thumb field as it's not part of the model
        unset($data['thumb']);

        return $data;
    }
}
