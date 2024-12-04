<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
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

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Transactions';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'Business Management';
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'PAID')->count();
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_transaction');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_transaction');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update_transaction');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete_transaction');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->can('delete_any_transaction');
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()->can('view_transaction');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_transaction');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invoice_id')
                    ->relationship('invoice', 'id')
                    ->required(),
                Forms\Components\TextInput::make('transaction_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('control_number')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('provider_reference')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(3),
                Forms\Components\TextInput::make('payment_method')
                    ->required()
                    ->maxLength(255)
                    ->default('simba_money'),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('INITIATED'),
                Forms\Components\Textarea::make('payer_details')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('provider_response')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('processed_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable()
                    ->copyable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('control_number')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('TSH')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->colors([
                        'primary' => 'SIMBA_MONEY',
                        'warning' => 'MOBILE_MONEY',
                        'info' => 'CARD',
                    ]),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'INITIATED',
                        'warning' => 'PROCESSING',
                        'success' => 'PAID',
                        'danger' => 'FAILED',
                    ]),

                Tables\Columns\TextColumn::make('provider_reference')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Transaction::STATUS_INITIATED => 'Initiated',
                        Transaction::STATUS_PROCESSING => 'Processing',
                        Transaction::STATUS_CONFIRMED => 'Confirmed',
                        Transaction::STATUS_FAILED => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        Transaction::METHOD_SIMBA_MONEY => 'Simba Money',
                        Transaction::METHOD_MOBILE_MONEY => 'Mobile Money',
                        Transaction::METHOD_CARD => 'Card',
                    ]),

                Tables\Filters\Filter::make('processed_at')
                    ->form([
                        Forms\Components\DatePicker::make('processed_from'),
                        Forms\Components\DatePicker::make('processed_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['processed_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('processed_at', '>=', $date),
                            )
                            ->when(
                                $data['processed_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('processed_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
              Section::make('Transaction Information')
                    ->schema([
                    TextEntry::make('transaction_id')
                            ->copyable()
                            ->weight(FontWeight::Bold),

                      TextEntry::make('control_number')
                            ->copyable(),

                       TextEntry::make('amount')
                            ->money('TSH'),

                       TextEntry::make('currency'),

                       TextEntry::make('payment_method')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'SIMBA_MONEY' => 'primary',
                                'MOBILE_MONEY' => 'warning',
                                'CARD' => 'info',
                                default => 'gray',
                            }),

                      TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'INITIATED' => 'primary',
                                'PROCESSING' => 'warning',
                                'CONFIRMED' => 'success',
                                'FAILED' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

              Section::make('Payer Information')
                    ->schema([
                    TextEntry::make('payer_details.phone')
                            ->label('Phone Number')
                            ->icon('heroicon-m-phone'),

                        TextEntry::make('payer_details.email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope'),
                    ])
                    ->columns(2),

                Section::make('Provider Information')
                    ->schema([
                       TextEntry::make('provider_reference')
                            ->copyable(),

                  TextEntry::make('provider_response')
                            ->state(function (Transaction $record): string {
                                return json_encode($record->provider_response, JSON_PRETTY_PRINT);
                            })
                            ->prose()
                            ->columnSpanFull(),
                    ]),

                Section::make('Timestamps')
                    ->schema([
                       TextEntry::make('processed_at')
                            ->dateTime(),

                        TextEntry::make('created_at')
                            ->dateTime(),

                 TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(3),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTransactions::route('/'),
        ];
    }
}
