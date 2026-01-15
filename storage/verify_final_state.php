<?php
echo "Invoice Count for SO 16: " . App\Models\Invoice::where('sales_order_id', 16)->count() . "\n";
echo "Shipment Count for SO 16: " . App\Models\SalesOrderShipment::where('sales_order_id', 16)->count() . "\n";
echo "Auto-Shipments (Invoice Linked): " . App\Models\SalesOrderShipment::where('sales_order_id', 16)->whereNotNull('invoice_id')->count() . "\n";
echo "Shipment Statuses: " . App\Models\SalesOrderShipment::where('sales_order_id', 16)->pluck('status') . "\n";
