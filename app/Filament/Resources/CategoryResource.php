<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Shop'; // group products under shop 
    protected static ?string $navigationLabel = 'category'; //change the products name 


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema(
                    [
                        Forms\Components\Section::make([
                            Forms\Components\TextInput::make("name")->required()->live(onBlur: true)->unique(Category::class, "name", ignoreRecord: true)->afterStateUpdated(
                                function (string $operation, $state, Forms\Set $set) {
                                    if ($operation !== "create") {
                                        return;
                                    }
                                    $set("slug", Str::slug($state));
                                }
                            ),
                            Forms\Components\TextInput::make("slug")->disabled()->dehydrated()->required()->unique(Category::class, "slug", ignoreRecord: true),
                            Forms\Components\MarkdownEditor::make("description")->columnSpan("full")
                        ])->columns(2)
                    ]
                ),
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make("Status")->schema([
                        Forms\Components\Toggle::make("is_visible")->label("Visibility")->helperText("Enable or disable product visiblity")->default(true),
                        Forms\Components\Select::make("parent_id")->relationship("parent", "name")
                    ]),

                ]),

                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")->searchable()->sortable(),
                Tables\Columns\TextColumn::make("parent.name")->label("Parent")->searchable()->sortable(),
                Tables\Columns\IconColumn::make("is_visible")->boolean()->sortable()->label("visiblity"),
                Tables\Columns\TextColumn::make("updated_at")->label("Updated Date")->date()->sortable(),

            ])
            ->filters([
                //
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
