<?php

namespace App\Services;

use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class StockService
{
    /**
     * Create a stock movement and update inventory balance.
     *
     * @param int $warehouseId
     * @param int $productId
     * @param float $qty (Must be positive)
     * @param string $direction (in|out)
     * @param string $type (manual_in, manual_out, workorder_consume, etc.)
     * @param Model|null $reference (WorkOrder, etc.)
     * @param string|null $note
     * @param int|null $userId
     * @return StockMovement|null
     */
    public function createMovement(
        int $warehouseId, 
        int $productId, 
        float $qty, 
        string $direction, 
        string $type, 
        ?Model $reference = null, 
        ?string $note = null, 
        ?int $userId = null
    ): ?StockMovement
    {
        $product = Product::find($productId);
        if (!$product || !$product->track_stock) {
            return null; // Do not track stock for services or non-tracked items
        }

        return DB::transaction(function () use ($warehouseId, $productId, $qty, $direction, $type, $reference, $note, $userId) {
            
            // 1. Create Movement
            $movement = StockMovement::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'qty' => $qty,
                'direction' => $direction,
                'type' => $type,
                'occurred_at' => now(),
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'note' => $note,
                'created_by' => $userId,
            ]);

            // 2. Update Balance
            $balance = InventoryBalance::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId
            ]);

            // Ensure distinct default if it's new
            if (!$balance->exists) {
                $balance->qty_on_hand = 0;
            }

            if ($direction === 'in') {
                $balance->qty_on_hand += $qty;
            } else {
                $balance->qty_on_hand -= $qty;
            }
            
            $balance->save();

            return $movement;
        });
    }
    public function postTransfer(int $transferId): bool
    {
        return DB::transaction(function () use ($transferId) {
            $transfer = \App\Models\StockTransfer::lockForUpdate()->find($transferId);

            if (!$transfer || $transfer->status === 'posted') {
                return false; // Already posted or not found
            }

            foreach ($transfer->lines as $line) {
                // OUT from Source
                $this->createMovement(
                    warehouseId: $transfer->from_warehouse_id,
                    productId: $line->product_id,
                    qty: $line->qty,
                    direction: 'out',
                    type: 'transfer_out',
                    reference: $transfer,
                    note: "Transfer #{$transfer->id}: {$transfer->fromWarehouse->name} -> {$transfer->toWarehouse->name}",
                    userId: $transfer->created_by
                );

                // IN to Destination
                $this->createMovement(
                    warehouseId: $transfer->to_warehouse_id,
                    productId: $line->product_id,
                    qty: $line->qty,
                    direction: 'in',
                    type: 'transfer_in',
                    reference: $transfer,
                    note: "Transfer #{$transfer->id}: {$transfer->fromWarehouse->name} -> {$transfer->toWarehouse->name}",
                    userId: $transfer->created_by
                );
            }

            $transfer->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            return true;
        });
    }
    public function postSalesOrderShipment(\App\Models\SalesOrderShipment $shipment): bool
    {
        return DB::transaction(function () use ($shipment) {
            $shipment->lockForUpdate();

            if ($shipment->status === 'posted') {
                return true; // Idempotent
            }

            foreach ($shipment->lines as $line) {
                if ($line->product_id && $line->product && $line->product->track_stock) {
                    $this->createMovement(
                        warehouseId: $shipment->warehouse_id,
                        productId: $line->product_id,
                        qty: $line->qty,
                        direction: 'out',
                        type: 'sale_out', // or shipment_out if preferred, keeping simple
                        reference: $shipment,
                        note: "Sevkiyat #{$shipment->id} (SO #{$shipment->salesOrder->order_no})",
                        userId: $shipment->created_by
                    );
                }
            }

            $shipment->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            return true;
        });
    }

    public function postSalesOrderReturn(\App\Models\SalesOrderReturn $return): bool
    {
        return DB::transaction(function () use ($return) {
            $return->lockForUpdate();

            if ($return->status === 'posted') {
                return true; // Idempotent
            }

            foreach ($return->lines as $line) {
                if ($line->product_id) { // Always create movement if product is set
                    $this->createMovement(
                        warehouseId: $return->warehouse_id,
                        productId: $line->product_id,
                        qty: $line->qty, // Positive qty
                        direction: 'in', // IN movement for return
                        type: 'return_in', 
                        reference: $return,
                        note: "İade #{$return->id} (Sevkiyat #{$return->sales_order_shipment_id}, SO #{$return->sales_order_id})",
                        userId: $return->created_by
                    );
                }
            }

            $return->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            return true;
        });
    }
}
