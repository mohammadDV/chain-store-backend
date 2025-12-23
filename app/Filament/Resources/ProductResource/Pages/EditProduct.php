<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set initial state based on existing image
        if (!empty($data['image'])) {
            if (str_starts_with($data['image'], 'https://') || str_starts_with($data['image'], 'http://')) {
                $data['image_source'] = 'url';
                $data['image_url'] = $data['image'];
            } else {
                $data['image_source'] = 'upload';
                // Set the existing S3 path for FileUpload to display
                $data['image_upload'] = $data['image'];
            }
        } else {
            // Default to upload if no image exists
            $data['image_source'] = 'upload';
        }

        // Get raw database value for amount (bypass the accessor that multiplies by exchange_rate)
        if (isset($this->record)) {
            $data['amount'] = $this->record->getRawOriginal('amount');
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
        } elseif (isset($data['image']) && !empty($data['image'])) {
            // Keep existing image if no new one is provided
            // This handles the case where user doesn't change the image
        }

        // Remove helper fields
        unset($data['image_upload'], $data['image_url'], $data['image_source']);

        return $data;
    }
}
