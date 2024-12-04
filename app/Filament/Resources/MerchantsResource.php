<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantsResource\Pages;
use App\Filament\Resources\MerchantsResource\RelationManagers;
use App\Models\Merchant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Collection;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class MerchantsResource extends Resource
{
    protected static ?string $model = Merchant::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'ACTIVE')->count();
    }



    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        // Primary Information Section
                        Forms\Components\Section::make('Business Details')
                            ->description('Primary business information')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Forms\Components\TextInput::make('business_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        if ($operation !== 'create') {
                                            return;
                                        }
                                        $set('merchant_code', Str::slug($state));
                                    }),

                                Forms\Components\TextInput::make('merchant_code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->columnSpan(2)
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('generate')
                                            ->icon('heroicon-m-arrow-path')
                                            ->action(function (Forms\Set $set) {
                                                $set('merchant_code', 'MER'.strtoupper(Str::random(8)));
                                            })
                                    ),

                                Forms\Components\TextInput::make('notification_email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'ACTIVE' => 'Active',
                                        'SUSPENDED' => 'Suspended',
                                        'INACTIVE' => 'Inactive',
                                    ])
                                    ->required()
                                    ->default('ACTIVE')
                                    ->helperText('Changing status will affect merchant\'s ability to process payments')
                                    ->columnSpan(2),
                            ])
                            ->columns(2)
                            ->collapsible(),

                        // Integration Section
                        Forms\Components\Section::make('Integration Settings')
                            ->description('API and webhook configuration')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\TextInput::make('callback_url')
                                    ->url()
                                    ->required()
                                    ->columnSpan(2)
                                    ->helperText('URL where payment notifications will be sent'),
                            ])
                            ->columns(2)
                            ->collapsible(),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        // User Association Section
                        Forms\Components\Section::make('Account Association')
                            ->description('Link merchant to a user account')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('User Account')
                                    ->options(User::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->unique('users', 'email'),
                                        Forms\Components\TextInput::make('password')
                                            ->password()
                                            ->required()
                                            ->maxLength(255)
                                            ->dehydrateStateUsing(fn ($state) => bcrypt($state)),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->collapsible(),

                        // API Credentials Section
                        Forms\Components\Section::make('API Credentials')
                            ->description('Security credentials')
                            ->icon('heroicon-o-key')
                            ->schema([
                                Forms\Components\TextInput::make('webhook_secret')
                                    ->label('Webhook Secret')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('api_key')
                                    ->label('API Key')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\Placeholder::make('api_key_generated_at')
                                    ->label('Last Generated')
                                    ->content(fn (?Model $record): string => $record?->api_key_generated_at?->diffForHumans() ?? 'Never'),
                            ])
                            ->collapsible(),

                        // Activity Section
                        Forms\Components\Section::make('Activity Log')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Created at')
                                    ->content(fn (?Model $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Last modified at')
                                    ->content(fn (?Model $record): string => $record?->updated_at?->diffForHumans() ?? '-'),

                                Forms\Components\Placeholder::make('last_login_at')
                                    ->label('Last login at')
                                    ->content(fn (?Model $record): string => $record?->last_login_at?->diffForHumans() ?? 'Never'),
                            ])
                            ->collapsible(),
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
                // Primary Information
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Model $record): string {
                        return $record->business_name;
                    })
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('merchant_code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->icon('heroicon-m-identification'),

                // Status & Activity
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'success' => 'ACTIVE',
                        'warning' => 'SUSPENDED',
                        'danger' => 'INACTIVE',
                    ]),

                Tables\Columns\TextColumn::make('notification_email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->icon('heroicon-m-envelope'),

                // Security Credentials
                Tables\Columns\TextColumn::make('api_key')
                    ->label('API Key')
                    ->formatStateUsing(fn ($state) => str_repeat('•', 8))
                    ->copyable()
                    ->copyMessage('API key copied!')
                    ->icon('heroicon-m-key')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('webhook_secret')
                    ->label('Webhook Secret')
                    ->formatStateUsing(fn ($state) => str_repeat('•', 8))
                    ->copyable()
                    ->copyMessage('Secret copied!')
                    ->icon('heroicon-m-lock-closed')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Statistics
                Tables\Columns\TextColumn::make('invoices_count')
                    ->counts('invoices')
                    ->label('Invoices')
                    ->sortable()
                    ->alignRight()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->money('TSH')
                    ->alignRight()
                    ->color('success')
                    ->getStateUsing(function (Model $record): float {
                        return $record->invoices()
                            ->whereHas('transactions', fn ($query) => $query->where('status', 'PAID'))
                            ->sum('bill_amount');
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'ACTIVE' => 'Active',
                        'SUSPENDED' => 'Suspended',
                        'INACTIVE' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('regenerateApiKey')
                        ->label('New API Key')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalIcon('heroicon-o-shield-exclamation')
                        ->modalHeading('Regenerate API Key')
                        ->modalDescription('Are you sure you want to regenerate the API key? The old key will stop working immediately.')
                        ->modalSubmitActionLabel('Yes, regenerate')
                        ->action(function (Model $record) {
                            $apiKey = $record->generateApiKey();

                            Notification::make()
                                ->success()
                                ->title('API Key Generated')
                                ->body("New API Key: {$apiKey}")
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('copy')
                                        ->label('Copy API Key')
                                        ->icon('heroicon-m-clipboard')
                                        ->action(fn () => null)
                                        ->extraAttributes([
                                            'x-on:click' => "window.navigator.clipboard.writeText('{$apiKey}')",
                                            'type' => 'button'
                                        ]),
                                ])
                                ->persistent()
                                ->send();
                        }),
                ])->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->icon('heroicon-o-check-circle')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'ACTIVE' => 'Active',
                                    'SUSPENDED' => 'Suspended',
                                    'INACTIVE' => 'Inactive',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['status' => $data['status']]);
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                 Section::make('Business Information')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        TextEntry::make('business_name')
                            ->label('Business Name')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('merchant_code')
                            ->label('Merchant Code')
                            ->copyable()
                            ->copyMessage('Code copied!'),

                       TextEntry::make('notification_email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                       TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'ACTIVE' => 'success',
                                'SUSPENDED' => 'warning',
                                'INACTIVE' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                 Section::make('API Credentials')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextEntry::make('api_key')
                            ->label('API Key')
                            ->formatStateUsing(fn ($state) => str_repeat('•', 8))
                            ->copyable()
                            ->copyMessage('API key copied!'),

                    TextEntry::make('webhook_secret')
                            ->label('Webhook Secret')
                            ->formatStateUsing(fn ($state) => str_repeat('•', 8))
                            ->copyable()
                            ->copyMessage('Secret copied!'),

                       TextEntry::make('callback_url')
                            ->label('Callback URL')
                            ->copyable()
                            ->url(fn ($state) => $state, true),

                   TextEntry::make('api_key_generated_at')
                            ->label('Last Key Generation')
                            ->date('F j, Y H:i:s')
                            ->icon('heroicon-m-clock'),
                    ])
                    ->columns(2),

                    Section::make('Statistics')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                       TextEntry::make('invoices_count')
                            ->label('Total Invoices')
                            ->state(function (Model $record): int {
                                return $record->invoices()->count();
                            }),

                   TextEntry::make('total_revenue')
                            ->label('Total Revenue')
                            ->money('TSH')
                            ->state(function (Model $record): float {
                                return $record->invoices()
                                    ->whereHas('transactions', fn ($query) => $query->where('status', 'PAID'))
                                    ->sum('bill_amount');
                            }),
                    ])
                    ->columns(2),

                Section::make('Activity Log')
                    ->icon('heroicon-o-clock')
                    ->schema([
                     TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),

                     TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),

                       TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime()
                            ->placeholder('Never'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMerchants::route('/'),
        ];
    }
}
