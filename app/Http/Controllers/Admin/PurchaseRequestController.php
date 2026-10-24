<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Services\Catalog\PurchaseRequests;

class PurchaseRequestController extends Controller
{
    public function __construct(private readonly PurchaseRequests $requests) {}

    public function index()
    {
        $rows = PurchaseRequest::query()
            ->with(['month:id,title', 'student:id,name'])
            ->latest()
            ->limit(80)
            ->get();

        return response()->json($rows->map(fn (PurchaseRequest $row) => [
            'id' => $row->id,
            'name' => $row->name,
            'phone' => $row->phone,
            'note' => $row->note,
            'month' => $row->month?->title,
            'student' => $row->student?->name,
            'status' => $row->status,
            'created_at' => $row->created_at,
        ]));
    }

    public function close(PurchaseRequest $purchaseRequest)
    {
        $this->requests->close($purchaseRequest);

        return response()->json(['ok' => true]);
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->delete();

        return response()->json(['ok' => true]);
    }
}
