<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Services\Orders\OrderFulfillmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncShipmentTrackingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shipments:sync-tracking {--shipment= : Specific shipment ID to sync} {--provider= : Filter by provider (delhivery/shiprocket)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll couriers (Delhivery, Shiprocket) and synchronize live tracking status and milestones into Dhansree orders.';

    /**
     * Execute the console command.
     */
    public function handle(OrderFulfillmentService $fulfillmentService): int
    {
        $this->info('Starting shipment tracking synchronization...');

        $query = Shipment::whereNotNull('awb')
            ->whereNotIn('status', [
                ShipmentStatus::DELIVERED,
                ShipmentStatus::CANCELLED,
                ShipmentStatus::RTO_DELIVERED,
            ]);

        if ($shipmentId = $this->option('shipment')) {
            $query->where('id', (int) $shipmentId);
        }

        if ($provider = $this->option('provider')) {
            $query->where('provider', $provider);
        }

        $shipments = $query->get();
        $this->info("Found {$shipments->count()} active shipment(s) to synchronize.");

        $synced = 0;
        $delivered = 0;
        $failed = 0;

        foreach ($shipments as $shipment) {
            $this->line("Syncing Shipment #{$shipment->id} (AWB: {$shipment->awb}, Provider: {$shipment->provider})...");

            try {
                $result = $fulfillmentService->syncShipmentTracking($shipment);

                if ($result['success']) {
                    $synced++;
                    if (($result['status'] ?? '') === 'DELIVERED' || ($result['action'] ?? '') === 'marked_delivered') {
                        $delivered++;
                        $this->info("  -> MARKED DELIVERED! (Order #{$shipment->order?->order_number})");
                    } else {
                        $this->line("  -> Status: " . ($result['status'] ?? 'OK') . " ({$result['message']})");
                    }
                } else {
                    $failed++;
                    $this->warn("  -> Sync returned warning: " . ($result['message'] ?? 'Unknown response'));
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  -> Error: {$e->getMessage()}");
                Log::error("Shipment tracking sync command error for #{$shipment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Synchronization finished. Synced: {$synced}, Marked Delivered: {$delivered}, Errors: {$failed}.");

        return self::SUCCESS;
    }
}
