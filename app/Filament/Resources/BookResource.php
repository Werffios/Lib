<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookResource\Pages;
use App\Filament\Resources\BookResource\RelationManagers;
use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\Filter;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $modelLabel = 'libro';
    protected static ?string $navigationLabel = 'libros';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Libro')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('ISBN')
                            ->label('ISBN')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(17),
                        TextInput::make('stock')
                            ->label('Stock')
                            ->numeric()
                            ->required(),
                        TextInput::make('purchase_price')
                            ->label('Precio de Compra')
                            ->numeric()
                            ->required(),
                        TextInput::make('sale_price')
                            ->label('Precio de Venta')
                            ->numeric()
                            ->required(),
                        TextInput::make('location')
                            ->label('Ubicación en Tienda/Almacén')
                            ->helperText('Ejemplo: Pasillo 3, Estante B, Sección Ficción')
                            ->maxLength(100),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Select::make('authors')
                            ->label('Autores del Libro')
                            ->relationship('authors', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                            ]),
                        Select::make('genre_id')
                            ->label('Género')
                            ->relationship('genre', 'name')
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre del Género')
                                    ->required(),
                            ]),
                        Select::make('publisher_id')
                            ->label('Editorial')
                            ->relationship('publisher', 'name')
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre de la Editorial')
                                    ->required(),
                            ]),
                    ])
                ->columns(2)


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ISBN')
                    ->label('ISBN')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn (Book $record): string => $record->stock <= 5 ? 'danger' : 'success'),
                TextColumn::make('purchase_price')
                    ->label('Precio de Compra')
                    ->money('MXN')
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->label('Precio de Venta')
                    ->money('MXN')
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('authors.name')
                    ->label('Autores')
                    ->limit(30)
                    ->listWithLineBreaks()
                    ->searchable(),
                TextColumn::make('genre.name')
                    ->label('Género')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('publisher.name')
                    ->label('Editorial')
                    ->toggleable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('genre_id')
                    ->label('Género')
                    ->preload()
                    ->relationship('genre', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('publisher_id')
                    ->label('Editorial')
                    ->relationship('publisher', 'name')
                    ->searchable(),
                Tables\Filters\Filter::make('stock_low')
                    ->label('Stock Bajo')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 5)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('registerSale')
                    ->label('Vender')
                    ->icon('heroicon-o-shopping-cart')
                    ->url(fn (Book $record): string => route('filament.admin.resources.sales.create', ['book_id' => $record->id]))
                    ->hidden(fn (Book $record): bool => $record->stock <= 0),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'edit' => Pages\EditBook::route('/{record}/edit'),
        ];
    }

//    public static function getGlobalSearchEloquentQuery(): Builder
//    {
//        return parent::getGlobalSearchEloquentQuery()
//            ->with(['authors', 'genre', 'publisher']);
//    }
//
//    public static function getGloballySearchableAttributes(): array
//    {
//        return ['title', 'ISBN', 'authors.name', 'genre.name', 'publisher.name'];
//    }
}
