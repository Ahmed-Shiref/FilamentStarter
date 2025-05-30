<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatusEnum as EnumsOrderStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Wizard;


class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Shop'; // group products under shop 
    protected static ?string $navigationLabel = 'order'; //change the products name 
    public static function getnavigationbadge(): ?string
    {
        return static::getModel()::where("status", "=", "processing")->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Order Details')
                        ->schema([
                            Forms\Components\TextInput::make("number")->default("OR-" . random_int(100000, 9999999))->disabled()->dehydrated()->required(),
                            Forms\Components\Select::make("customer_id")->relationship("customer", "name")->searchable()->required(),
                            Forms\Components\TextInput::make("shipping_price")->label("Shipping Costs")->dehydrated()->numeric()->required(),

                            Forms\Components\Select::make("status")->options([
                                "pending" => EnumsOrderStatusEnum::PENDING->value,
                                "processing" => EnumsOrderStatusEnum::PROCESSING->value,
                                "completed" => EnumsOrderStatusEnum::COMPLETED->value,
                                "declined" => EnumsOrderStatusEnum::DECLINED->value,
                            ]),
                            Forms\Components\MarkdownEditor::make("notes")->required()->columnSpanFull(),

                        ])->columns(2),
                    Wizard\Step::make('Order Items')
                        ->schema([
                            Forms\Components\Repeater::make("items")->relationship()->schema([
                                Forms\Components\select::make("product_id")->label("product")
                                    ->options(Product::query()->pluck("name", "id"))
                                    ->reactive()->required()->afterStateUpdated(fn($state, Forms\Set $set) =>
                                    $set("unit_price", Product::find($state)?->price ?? 0)),
                                Forms\Components\TextInput::make("quantity")->numeric()->dehydrated()->live()->default(1)->required(), //quantatiy shouldn't be choosen biger that the quntity of product in inventory 
                                Forms\Components\TextInput::make("unit_price")->label("Unit Price")->disabled()->dehydrated()->numeric()->required(),
                                Forms\Components\Placeholder::make("total_price")->label("Total price")->content(function ($get) {
                                    return $get("quantity") * $get("unit_price");
                                })
                            ])->columns(4)
                        ]),
                ])->columnSpanFull()
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("number")->searchable()->sortable(),
                Tables\Columns\TextColumn::make("customer.name")->searchable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make("status")->searchable()->sortable(),
                // Tables\Columns\TextColumn::make("total_price")->searchable()->sortable()->summarize([
                //     Tables\Columns\Summarizers\Sum::make()->money()
                // ]),
                Tables\Columns\TextColumn::make("created_at")->label("Order Date")->date(),
                //
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
