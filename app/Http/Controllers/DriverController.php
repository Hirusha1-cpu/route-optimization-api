<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Get all drivers for the current company
     */
    public function index(Request $request)
    {
        $drivers = Driver::with('user')
            ->select('id', 'name', 'phone', 'wallet_balance')
            ->get();

        return response()->json($drivers);
    }

    /**
     * Get a specific driver
     */
    public function show(Driver $driver)
    {
        return response()->json($driver->load('deliveries', 'codLedgerEntries'));
    }

    /**
     * Get active drivers (with assigned or in_transit deliveries)
     */
    public function active(Request $request)
    {
        $drivers = Driver::whereHas('deliveries', function ($query) {
                $query->whereIn('status', ['assigned', 'in_transit']);
            })
            ->select('id', 'name', 'phone')
            ->get();

        return response()->json($drivers);
    }
}