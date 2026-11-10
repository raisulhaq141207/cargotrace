<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Shipment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public function __construct(
        protected EtaService $etaService,
        protected AuditService $auditService
    ) {}

    /**
     * Generate unique shipment number like CT-2026-0001
     */
    public function generateShipmentNumber(): string
    {
        $year = date('Y');
        $count = Shipment::withTrashed()->whereYear('created_at', $year)->count() + 1;
        
        do {
            $number = sprintf('CT-%s-%04d', $year, $count);
            $exists = Shipment::withTrashed()->where('shipment_number', $number)->exists();
            if ($exists) {
                $count++;
            }
        } while ($exists);

        return $number;
    }

    /**
     * Create a new shipment and calculate initial delay metrics
     */
    public function createShipment(array $data, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($data, $actor) {
            if (empty($data['shipment_number'])) {
                $data['shipment_number'] = $this->generateShipmentNumber();
            }

            if (empty($data['current_status'])) {
                $data['current_status'] = Shipment::STATUS_CREATED;
            }

            if (empty($data['estimated_arrival'])) {
                $data['estimated_arrival'] = $data['expected_arrival'];
            }

            $shipment = new Shipment($data);
            $this->etaService->syncShipmentDelay($shipment, false);
            $shipment->save();

            // Handle items if provided
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $shipment->items()->create($itemData);
                }
            }

            // Create initial audit log
            $this->auditService->log(
                'CREATE',
                $shipment,
                null,
                $shipment->toArray(),
                $actor?->id
            );

            // In-app notification to the assigned customer
            if ($shipment->customer_id) {
                Notification::create([
                    'user_id' => $shipment->customer_id,
                    'shipment_id' => $shipment->id,
                    'type' => 'INFO',
                    'title' => "Shipment Created: {$shipment->shipment_number}",
                    'message' => "Your shipment #{$shipment->shipment_number} has been registered and is currently in status CREATED.",
                ]);
            }

            return $shipment->load(['customer', 'carrier', 'origin', 'destination', 'items']);
        });
    }

    /**
     * Update an existing shipment
     */
    public function updateShipment(Shipment $shipment, array $data, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $oldValues = $shipment->toArray();

            // Prevent direct illegal status modification through update payload
            if (isset($data['current_status']) && $data['current_status'] !== $shipment->current_status) {
                $this->transitionStatus($shipment, $data['current_status'], $actor);
                unset($data['current_status']);
            }

            $shipment->fill($data);
            $this->etaService->syncShipmentDelay($shipment, false);
            $shipment->save();

            // Refresh items if specified
            if (isset($data['items']) && is_array($data['items'])) {
                $shipment->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $shipment->items()->create($itemData);
                }
            }

            $this->auditService->log(
                'UPDATE',
                $shipment,
                $oldValues,
                $shipment->fresh()->toArray(),
                $actor?->id
            );

            return $shipment->fresh(['customer', 'carrier', 'origin', 'destination', 'items']);
        });
    }

    /**
     * Enforce strict status lifecycle transition
     */
    public function transitionStatus(Shipment $shipment, string $targetStatus, ?User $actor = null, ?string $reason = null): Shipment
    {
        if ($shipment->current_status === $targetStatus) {
            return $shipment;
        }

        if (!$shipment->canTransitionTo($targetStatus)) {
            throw new DomainException(
                "Invalid status transition: Cannot transition shipment #{$shipment->shipment_number} from '{$shipment->current_status}' to '{$targetStatus}'."
            );
        }

        $oldStatus = $shipment->current_status;
        $shipment->current_status = $targetStatus;

        // Auto timestamps for operational progression
        if ($targetStatus === Shipment::STATUS_PICKED_UP && !$shipment->actual_departure) {
            $shipment->actual_departure = now();
        } elseif ($targetStatus === Shipment::STATUS_DELIVERED && !$shipment->actual_arrival) {
            $shipment->actual_arrival = now();
        }

        $this->etaService->syncShipmentDelay($shipment, false);
        $shipment->save();

        $this->auditService->log(
            'STATUS_CHANGE',
            $shipment,
            ['current_status' => $oldStatus],
            ['current_status' => $targetStatus, 'reason' => $reason],
            $actor?->id
        );

        if ($shipment->customer_id) {
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'type' => 'STATUS_UPDATE',
                'title' => "Shipment Status Updated: {$targetStatus}",
                'message' => "Shipment #{$shipment->shipment_number} status changed from {$oldStatus} to {$targetStatus}." . ($reason ? " Note: {$reason}" : ""),
            ]);
        }

        return $shipment;
    }

    /**
     * Soft-delete a shipment
     */
    public function deleteShipment(Shipment $shipment, ?User $actor = null): bool
    {
        if ($shipment->current_status === Shipment::STATUS_DELIVERED && (!$actor || !$actor->isAdmin())) {
            throw new DomainException("Cannot delete completed/delivered shipments.");
        }

        $this->auditService->log(
            'DELETE',
            $shipment,
            $shipment->toArray(),
            null,
            $actor?->id
        );

        return (bool) $shipment->delete();
    }
}

