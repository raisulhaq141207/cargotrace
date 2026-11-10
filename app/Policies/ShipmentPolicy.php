<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtered in query scope based on role
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->isAdmin() || $user->isOperator()) {
            return true;
        }

        return $shipment->customer_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Shipment $shipment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOperator()) {
            return in_array($shipment->current_status, [Shipment::STATUS_CREATED, Shipment::STATUS_BOOKED], true);
        }

        return false;
    }

    /**
     * Determine whether the user can add tracking events.
     */
    public function addTracking(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    /**
     * Determine whether the user can manage exceptions.
     */
    public function manageExceptions(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }
}

