<?php

namespace App\Filament\Resources\MerchantsResource\Pages;

use App\Filament\Resources\MerchantsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMerchants extends ManageRecords
{
    protected static string $resource = MerchantsResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\CreateAction::make(),
        ];
    }
}
