<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'Business Management';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'PAID')->count();
    }

    protected static bool $shouldRegisterNavigation = true;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_invoice');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_invoice');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_invoice');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_invoice');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_any_invoice');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view_invoice');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_invoice');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Invoice Details')
                            ->description('Basic invoice information')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Select::make('merchant_id')
                                    ->relationship('merchant', 'business_name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->default('INV-' . strtoupper(uniqid()))
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('bill_amount')
                                    ->label('Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('TSH')
                                    ->maxValue(42949672.95),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'PENDING' => 'Pending',
                                        'PROCESSING' => 'Processing',
                                        'PAID' => 'Paid',
                                        'FAILED' => 'Failed',
                                        'EXPIRED' => 'Expired',
                                    ])
                                    ->required()
                                    ->default('PENDING')
                                    ->disabled(),

                                Forms\Components\DateTimePicker::make('due_at')
                                    ->label('Due Date')
                                    ->required()
                                    ->default(now()->addDays(7)),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Customer Information')
                            ->description('Customer billing details')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\TextInput::make('customer_name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('customer_email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('customer_phone')
                                    ->tel()
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Additional Details')
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Invoice Description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status & Tracking')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Created at')
                                    ->content(fn (?Model $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Last modified at')
                                    ->content(fn (?Model $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('merchant.business_name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payer_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bill_amount')
                    ->money('TSH')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'primary' => 'PENDING',
                        'warning' => 'PROCESSING',
                        'success' => 'PAID',
                        'danger' => 'FAILED',
                        'gray' => 'EXPIRED',
                    ]),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('merchant')
                    ->relationship('merchant', 'business_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'PROCESSING' => 'Processing',
                        'PAID' => 'Paid',
                        'FAILED' => 'Failed',
                        'EXPIRED' => 'Expired',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Invoice Information')
                    ->schema([
                      TextEntry::make('invoice_number')
                            ->label('Invoice Number')
                            ->copyable()
                            ->weight(FontWeight::Bold),

                      TextEntry::make('merchant.business_name')
                            ->label('Merchant'),

                      TextEntry::make('bill_amount')
                            ->label('Amount')
                            ->money('TSH'),

                     TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'PENDING' => 'primary',
                                'PROCESSING' => 'warning',
                                'PAID' => 'success',
                                'FAILED' => 'danger',
                                'EXPIRED' => 'gray',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),


                Section::make('Customer Details')
                    ->schema([
                     TextEntry::make('customer_name'),
                        TextEntry::make('customer_email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        TextEntry::make('customer_phone')
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                    ])
                    ->columns(3),

             Section::make('Payment Details')
                    ->schema([
                        TextEntry::make('qrCode.control_number')
                            ->label('Control Number')
                            ->copyable(),
                        TextEntry::make('transactions.status')
                            ->label('Transaction Status')
                            ->badge(),
                        TextEntry::make('due_at')
                            ->label('Due Date')
                            ->dateTime(),
                       TextEntry::make('expires_at')
                            ->label('Expires At')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoices::route('/'),
        ];
    }
}
