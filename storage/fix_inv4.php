<?php
$i = \App\Models\Invoice::find(4);
if ($i) {
    $i->update(['payment_status' => 'partial']);
    echo "Status Updated: " . $i->fresh()->payment_status . "\n";
    echo "Total: " . $i->total . "\n";
    echo "Lines Sum: " . $i->lines->sum('total') . "\n";
} else {
    echo "Invoice 4 not found.\n";
}
