<?php

namespace App\Filament\Resources;

use App\Enums\ProductTypeEnum as EnumsProductTypeEnum;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $activenavigationIcon = 'heroicon-o-check-badge';
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationGroup = 'Shop'; // group products under shop 
    protected static ?string $navigationLabel = 'Product'; //change the products name 
    // protected static ?string $recordTitleAttribute =  ['name']; // global search on name
    protected static int $globalSearchResultsLimit = 1;

    public static function getnavigationbadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description'];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Brand' => $record->brand->name,
            'Description' => $record->description
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['brand']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make("name")->required()->live(onBlur: true)->unique(Product::class, "name", ignoreRecord: true)->afterStateUpdated(
                                    function (string $operation, $state, Forms\Set $set) {
                                        if ($operation !== "create") {
                                            return;
                                        }
                                        $set("slug", Str::slug($state));
                                    }
                                ),
                                Forms\Components\TextInput::make("slug")->disabled()->dehydrated()->required()->unique(Product::class, "slug", ignoreRecord: true),
                                Forms\Components\MarkdownEditor::make("description")->columnSpan("full")
                            ])->columns(2),
                        Forms\Components\Section::make("Pricing & Inventory")
                            ->schema([
                                Forms\Components\TextInput::make("sku")->label("SKU (Stock Keeping Unit)")->unique(Product::class, "sku", ignoreRecord: true)->required(),
                                Forms\Components\TextInput::make("price")->numeric()->required(),
                                Forms\Components\TextInput::make("quantity")->numeric()->minValue(0)->maxValue(100)->required(),
                                Forms\Components\Select::make("type")->options([
                                    "downloadable" => EnumsProductTypeEnum::DOWNLOADABLE->value,
                                    "deliverable" => EnumsProductTypeEnum::DELIVERABLE->value,

                                ])->required()

                            ])->columns(2)
                    ]),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make("Status")
                            ->schema([
                                //

                                Forms\Components\Toggle::make("is_visible")->label("Visibility")->helperText("Enable or disable product visiblity")->default(true),
                                Forms\Components\Toggle::make("is_featured")->label("Featured")->helperText("Enable or disable product Featured status"),
                                Forms\Components\DatePicker::make("published_at")->label("Availability")->default(now()),
                            ]),
                        Forms\Components\Section::make("Image")->schema([

                            Forms\Components\FileUpload::make("image")->directory("form-attachments")->image()->imageEditor()->visibility("public"),
                        ])->collapsible(),
                        Forms\Components\Section::make("Associations")->schema([

                            Forms\Components\Select::make("brand_id")
                                ->relationship('brand', "name")->required()
                        ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\ImageColumn::make("image"),
                Tables\Columns\TextColumn::make("name")->searchable()->sortable(),
                Tables\Columns\TextColumn::make("brand.name")->searchable()->sortable()->toggleable(),
                Tables\Columns\IconColumn::make("is_visible")->boolean()->sortable()->toggleable()->label("visiblity"),
                Tables\Columns\TextColumn::make("price")->sortable()->toggleable(),
                Tables\Columns\TextColumn::make("quantity")->sortable()->toggleable(),
                Tables\Columns\TextColumn::make("published_at")->date()->sortable(),
                Tables\Columns\TextColumn::make("type"),
            ])
            ->filters([
                //
                Tables\Filters\TernaryFilter::make("is_visible")->label("visiblity")->boolean()->trueLabel("Only Visible Products")->falselabel("Only Hidden Products")->native(false),
                Tables\Filters\SelectFilter::make("brand")->relationship("brand", "name")
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}