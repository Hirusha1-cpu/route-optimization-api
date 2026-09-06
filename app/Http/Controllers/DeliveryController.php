<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmPaymentRequest;
use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Requests\UpdateDeliveryStatusRequest;
use App\Models\CodLedger;
use App\Models\Delivery;
use App\Models\Driver;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = Delivery::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->user()->isDriver()) {
            $query->where('driver_id', $request->user()->driver_id);
        }

        return response()->json($query->with('driver')->paginate(20));
    }

    public function store(StoreDeliveryRequest $request)
    {
        $delivery = Delivery::create([
            'company_id' => $request->user()->company_id,
            'customer_name' => $request->customer_name,
            'address' => $request->address,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'window_start' => $request->window_start,
            'window_end' => $request->window_end,
            'cod_amount' => $request->cod_amount,
            'status' => 'pending',
        ]);

        AuditLog::record('delivery.created', $delivery, $request->all());

        return response()->json($delivery, 201);
    }

    public function updateStatus(UpdateDeliveryStatusRequest $request, Delivery $delivery)
    {
        $oldStatus = $delivery->status;
        $newStatus = $request->status;

        // Driver can only update their own deliveries
        if ($request->user()->isDriver() && $delivery->driver_id !== $request->user()->driver_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $delivery->update(['status' => $newStatus]);

        AuditLog::record('delivery.status_changed', $delivery, [
            'from' => $oldStatus,
            'to' => $newStatus,
            'actor' => $request->user()->id,
        ]);

        // If failed, dispatch job to re-queue next day
        if ($newStatus === 'failed') {
            dispatch(new \App\Jobs\RequeueFailedDelivery($delivery));
        }

        return response()->json($delivery);
    }

    public function confirmPayment(ConfirmPaymentRequest $request, Delivery $delivery)
    {
        if ($delivery->status !== 'in_transit') {
            return response()->json(['error' => 'Delivery must be in_transit to confirm payment'], 422);
        }

        if ($request->user()->driver_id !== $delivery->driver_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::transaction(function () use ($request, $delivery) {
            // Create ledger entry (append-only)
            CodLedger::create([
                'driver_id' => $delivery->driver_id,
                'delivery_id' => $delivery->id,
                'amount_collected' => $request->amount_collected,
            ]);

            // Update delivery status
            $oldStatus = $delivery->status;
            $delivery->update(['status' => 'delivered']);

            AuditLog::record('cod.collected', $delivery, [
                'amount' => $request->amount_collected,
                'from' => $oldStatus,
                'to' => 'delivered',
            ]);

            // Dispatch confirmation job
            dispatch(new \App\Jobs\SendDeliveryConfirmation($delivery));
        });

        return response()->json(['message' => 'Payment confirmed successfully']);
    }

    public function show(Delivery $delivery)
    {
        return response()->json($delivery->load('driver', 'codLedgerEntries'));
    }
}