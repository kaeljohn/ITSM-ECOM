<?php

namespace Modules\OrderFulfillment\Http\Controllers\Concerns;

use Modules\OrderFulfillment\Models\ReturnItem;
use Modules\OrderFulfillment\Models\Shipment;

trait CancelsShipmentToReturn
{
    /**
     * Cancel a shipment and move it into Returns.
     */
    protected function cancelShipmentToReturn(Shipment $shipment, string $reason): void
    {
        $orderId = $shipment->order_id;

        // Mark the shipment as CANCELLED
        $shipment->update(['status' => 'CANCELLED']);

        // Create a Return record for this cancellation
        ReturnItem::create([
            'order_id'   => $orderId,
            'reason'     => $reason,
            'status'     => 'pending',
        ]);
    }

    /**
     * Statuses that cannot be cancelled.
     */
    protected function nonCancellableShipmentStatuses(): array
    {
        return ['OUT_FOR_DELIVERY', 'DELIVERED'];
    }
}
