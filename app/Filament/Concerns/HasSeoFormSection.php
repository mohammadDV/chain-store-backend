<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

trait HasSeoFormSection
{
    /**
     * @return list<Component>
     */
    protected static function seoFormSection(bool $includeSlug = true, string $ogDirectory = 'seo/og'): array
    {
        $fields = [];

        if ($includeSlug) {
            $fields[] = TextInput::make('slug')
                ->label(__('site.slug'))
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText(__('site.seo_slug_help'))
                ->columnSpanFull();
        }

        $fields = array_merge($fields, [
            Grid::make(2)
                ->schema([
                    TextInput::make('meta_title')
                        ->label(__('site.meta_title'))
                        ->maxLength(255)
                        ->helperText(__('site.meta_title_help')),
                    TextInput::make('meta_keywords')
                        ->label(__('site.meta_keywords'))
                        ->maxLength(255)
                        ->helperText(__('site.meta_keywords_help')),
                ]),
            Textarea::make('meta_description')
                ->label(__('site.meta_description'))
                ->rows(3)
                ->maxLength(500)
                ->helperText(__('site.meta_description_help'))
                ->columnSpanFull(),
            FileUpload::make('og_image')
                ->label(__('site.og_image'))
                ->image()
                ->imageEditor()
                ->disk('s3')
                ->directory($ogDirectory)
                ->visibility('public')
                ->helperText(__('site.og_image_help'))
                ->columnSpanFull(),
        ]);

        return [
            Section::make(__('site.seo'))
                ->icon('heroicon-o-magnifying-glass')
                ->schema($fields)
                ->columns(1)
                ->collapsed()
                ->collapsible(),
        ];
    }
}
