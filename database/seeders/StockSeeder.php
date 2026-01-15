<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Warehouse;
use App\Models\StockMovement;
use App\Models\InventoryBalance;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Warehouses
        $mainWarehouse = Warehouse::firstOrCreate(
            ['name' => 'Merkez Depo'],
            ['is_default' => true, 'is_active' => true, 'notes' => 'Ana depo']
        );
        
        $boatWarehouse = Warehouse::firstOrCreate(
            ['name' => 'Tekne Deposu'],
            ['is_default' => false, 'is_active' => true, 'notes' => 'Teknelere sevk edilen malzemeler']
        );

        $scrapWarehouse = Warehouse::firstOrCreate(
            ['name' => 'Hurda Deposu'],
            ['is_default' => false, 'is_active' => true, 'notes' => 'Kullanılamaz durumdaki malzemeler']
        );

        // Categories
        $categories = [
            'Elektronik',
            'Motor Parçaları',
            'Boya & Bakım',
            'Güverte Ekipmanı',
            'Hizmetler',
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat]);
        }

        // Tags
        $tags = [
            ['name' => 'Acil', 'color' => 'red'],
            ['name' => 'Stokta Yok', 'color' => 'gray'],
            ['name' => 'Yeni', 'color' => 'green'],
            ['name' => 'İthal', 'color' => 'blue'],
            ['name' => 'Yerli', 'color' => 'yellow'],
            ['name' => 'Sezonluk', 'color' => 'purple'],
            ['name' => 'Kampanya', 'color' => 'pink'],
            ['name' => 'Özel Sipariş', 'color' => 'indigo'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['name' => $tag['name']], ['color' => $tag['color']]);
        }

        // Products (10 items)
        $products = [
            ['name' => 'Raymarine Axiom 9', 'type' => 'product', 'cat' => 'Elektronik', 'price' => 45000, 'currency' => 'TRY'],
            ['name' => 'Volvo Penta D4 Filtre Seti', 'type' => 'product', 'cat' => 'Motor Parçaları', 'price' => 3500, 'currency' => 'TRY'],
            ['name' => 'International Trilux 33', 'type' => 'product', 'cat' => 'Boya & Bakım', 'price' => 2800, 'currency' => 'TRY'],
            ['name' => 'Jabsco Tuvalet Pompası', 'type' => 'product', 'cat' => 'Güverte Ekipmanı', 'price' => 5200, 'currency' => 'TRY'],
            ['name' => '12V Sintine Pompası', 'type' => 'product', 'cat' => 'Güverte Ekipmanı', 'price' => 1500, 'currency' => 'TRY'],
            ['name' => 'Garmin VHF 115i', 'type' => 'product', 'cat' => 'Elektronik', 'price' => 12000, 'currency' => 'TRY'],
            ['name' => 'Impeller Kit', 'type' => 'product', 'cat' => 'Motor Parçaları', 'price' => 950, 'currency' => 'TRY'],
            ['name' => 'Tik Yağı 1L', 'type' => 'product', 'cat' => 'Boya & Bakım', 'price' => 650, 'currency' => 'TRY'],
            ['name' => 'Led Seyir Feneri', 'type' => 'product', 'cat' => 'Elektronik', 'price' => 1800, 'currency' => 'TRY'],
            ['name' => 'Usturmaça F2', 'type' => 'product', 'cat' => 'Güverte Ekipmanı', 'price' => 2200, 'currency' => 'TRY'],
        ];

        foreach ($products as $p) {
            $catId = Category::where('name', $p['cat'])->value('id');
            Product::create([
                'name' => $p['name'],
                'type' => $p['type'],
                'category_id' => $catId,
                'default_sell_price' => $p['price'],
                'currency_code' => $p['currency'],
                'sku' => strtoupper(substr($p['name'], 0, 3)) . '-' . rand(100, 999),
                'track_stock' => true,
            ]);
        }

        // Services (5 items)
        $services = [
            'Motor Bakım İşçiliği',
            'Zehirli Boya Uygulama',
            'Elektrik Arıza Tespit',
            'Kışlama Hizmeti',
            'Genel Temizlik',
        ];

        $serviceCatId = Category::where('name', 'Hizmetler')->value('id');

        foreach ($services as $svc) {
            Product::create([
                'name' => $svc,
                'type' => 'service',
                'category_id' => $serviceCatId,
                'track_stock' => false,
                'default_sell_price' => 0,
                'currency_code' => 'TRY',
            ]);
        }
        
        // Seed WorkOrder Items for existing WorkOrders
        $workOrders = \App\Models\WorkOrder::inRandomOrder()->limit(3)->get();
        if($workOrders->isEmpty()) {
             // If no work orders, maybe create one? 
             // skipping to avoid dependency complexity, assuming user has data or will create WO.
        } else {
            $allProducts = Product::all();
            foreach($workOrders as $wo) {
                // Add 1-3 items
                $count = rand(1, 3);
                for($i=0; $i<$count; $i++) {
                    $prod = $allProducts->random();
                    \App\Models\WorkOrderItem::create([
                        'work_order_id' => $wo->id,
                        'product_id' => $prod->id,
                        'description' => $prod->name, // defaulting description
                        'qty' => rand(1, 10),
                        'unit' => 'Adet',
                        'sort_order' => $i
                    ]);
                }
                
                // Add one custom item
                \App\Models\WorkOrderItem::create([
                    'work_order_id' => $wo->id,
                    'product_id' => null,
                    'description' => 'Özel İmalat Parça ' . rand(1, 100),
                    'qty' => 1,
                    'unit' => 'Set',
                    'sort_order' => $count + 1
                ]);
            }
        }

        // Seed Initial Stock for random products in Main Warehouse
        $productsToStock = Product::where('track_stock', true)->inRandomOrder()->limit(10)->get();
        foreach($productsToStock as $prod) {
            $qty = rand(10, 100);
            
            // Create Movement
            StockMovement::create([
                'warehouse_id' => $mainWarehouse->id,
                'product_id' => $prod->id,
                'qty' => $qty,
                'direction' => 'in',
                'type' => 'manual_in',
                'occurred_at' => now()->subDays(rand(1, 30)),
                'note' => 'Açılış stoğu',
                'created_by' => 1 // Assuming user 1 exists
            ]);

            // Update Balance
            InventoryBalance::updateOrCreate(
                ['warehouse_id' => $mainWarehouse->id, 'product_id' => $prod->id],
                ['qty_on_hand' => $qty]
            );
        }



        // 5. Sales Orders Demo
        $randomCustomer = \App\Models\Customer::first();
        if (!$randomCustomer) {
            $randomCustomer = \App\Models\Customer::create(['name' => 'Demo Müşteri A.Ş.']);
        }
        
        $randomVessel = \App\Models\Vessel::where('customer_id', $randomCustomer->id)->first();
        if (!$randomVessel) {
            $randomVessel = \App\Models\Vessel::create([
                'name' => 'Demo Tekne',
                'customer_id' => $randomCustomer->id,
                'type' => 'Motor Yacht',
                'flag' => 'TR'
            ]);
        }

        // SO 1: Products and Services
        $so1 = \App\Models\SalesOrder::create([
            'customer_id' => $randomCustomer->id,
            'vessel_id' => $randomVessel->id,
            'title' => 'Tekne Bakım ve Malzeme Siparişi',
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'EUR',
            'created_by' => 1
        ]);

        $prod1 = Product::where('track_stock', true)->first();
        $svc1 = Product::where('track_stock', false)->first();

        // Product Item
        if ($prod1) {
            \App\Models\SalesOrderItem::create([
                'sales_order_id' => $so1->id,
                'product_id' => $prod1->id,
                'section' => 'Malzemeler',
                'item_type' => 'product',
                'description' => $prod1->name,
                'qty' => 2,
                'unit' => 'Adet',
                'unit_price' => 1200, // EUR
                'vat_rate' => 20
            ]);

            // Ensure stock exists for this product to test deduction
            if (\App\Models\InventoryBalance::where('product_id', $prod1->id)->where('warehouse_id', $mainWarehouse->id)->sum('qty_on_hand') < 5) {
                 StockMovement::create([
                    'warehouse_id' => $mainWarehouse->id,
                    'product_id' => $prod1->id,
                    'qty' => 10,
                    'direction' => 'in',
                    'type' => 'manual_in',
                    'occurred_at' => now(),
                    'note' => 'SO Test Stoğu',
                    'created_by' => 1
                ]);
                 $balance = \App\Models\InventoryBalance::firstOrNew([
                    'warehouse_id' => $mainWarehouse->id,
                    'product_id' => $prod1->id
                 ]);
                 $balance->qty_on_hand = ($balance->qty_on_hand ?? 0) + 10;
                 $balance->save();
            }
        }

        // Service Item
        if ($svc1) {
             \App\Models\SalesOrderItem::create([
                'sales_order_id' => $so1->id,
                'product_id' => $svc1->id,
                'section' => 'Hizmetler',
                'item_type' => 'service',
                'description' => $svc1->name,
                'qty' => 5,
                'unit' => 'Saat',
                'unit_price' => 50, // EUR
                'vat_rate' => 20
            ]);
        }
        $so1->recalculateTotals();

        // SO 2: Products Only (Draft)
        $so2 = \App\Models\SalesOrder::create([
            'customer_id' => $randomCustomer->id,
            'vessel_id' => $randomVessel->id,
            'title' => 'Yedek Parça Siparişi',
            'status' => 'draft',
            'order_date' => now(),
            'currency' => 'TRY',
            'created_by' => 1
        ]);

        $prod2 = Product::where('track_stock', true)->skip(1)->first();
        if ($prod2) {
             \App\Models\SalesOrderItem::create([
                'sales_order_id' => $so2->id,
                'product_id' => $prod2->id,
                'item_type' => 'product',
                'description' => $prod2->name,
                'qty' => 1,
                'unit' => 'Adet',
                'unit_price' => 2500, // TRY
                'vat_rate' => 20
            ]);
             // Add generic item without product_id
             \App\Models\SalesOrderItem::create([
                'sales_order_id' => $so2->id,
                'product_id' => null,
                'item_type' => 'product',
                'description' => 'Özel Sipariş Conta',
                'qty' => 10,
                'unit' => 'Adet',
                'unit_price' => 50, 
                'vat_rate' => 20
            ]);
        }
        $so2->recalculateTotals();
    }
}
