<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        // Merge image_upload or image_url into image field
        // Handle FileUpload - it can be an array or string
        if (isset($data['image_upload']) && !empty($data['image_upload'])) {
            if (is_array($data['image_upload'])) {
                // If it's an array, get the first element (the uploaded file path)
                $data['image'] = !empty($data['image_upload']) ? reset($data['image_upload']) : null;
            } else {
                // If it's already a string (path), use it directly
                $data['image'] = $data['image_upload'];
            }
        } elseif (isset($data['image_url']) && !empty($data['image_url'])) {
            // Use the URL directly
            $data['image'] = $data['image_url'];
        }

        // Remove helper fields
        unset($data['image_upload'], $data['image_url'], $data['image_source']);

        return $data;
    }
}
