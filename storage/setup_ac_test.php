<?php
$c = \App\Models\Customer::first();
$v = \App\Models\Vessel::first();
$p = \App\Models\Product::first();
$so = \App\Models\SalesOrder::create([
    'customer_id' => $c->id,
    'vessel_id' => $v->id, 
    'title' => 'AC Test ' . rand(100,999), 
    'order_no' => 'SO-AC-' . rand(1000,9999), 
    'status' => 'confirmed', 
    'currency' => 'USD',
    'created_by' => 1
]);
$item = \App\Models\SalesOrderItem::create([
    'sales_order_id' => $so->id,
    'product_id' => $p->id,
    'item_type' => 'product', // Fixed: Added item_type
    'description' => 'Test Item ' . rand(100,999),
    'quantity' => 10,
    'unit_price' => 100,
    'tax_rate' => 20,
    'total' => 1000
]);
$shipment = \App\Models\SalesOrderShipment::create([
    'sales_order_id' => $so->id,
    'warehouse_id' => 1,
    'status' => 'draft',
    'created_by' => 1
]);
\App\Models\SalesOrderShipmentLine::create([
    'sales_order_shipment_id' => $shipment->id,
    'sales_order_item_id' => $item->id,
    'qty' => 10,  
]);
$shipment->update(['status' => 'posted', 'posted_at' => now()]);
echo "SO_ID:" . $so->id;
