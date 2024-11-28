<?php

namespace App\Http\Controllers;

use App\Models\QRCode;
use Illuminate\Http\Request;

class QRViewController extends Controller
{
    public function index()
    {
        $qrCodes = QRCode::with('invoice')
            ->latest()
            ->paginate(10);

        return view('qr.list', compact('qrCodes'));
    }

    public function show($controlNumber)
    {
        $qrCode = QRCode::where('control_number', $controlNumber)
            ->with('invoice')
            ->firstOrFail();

        return view('qr.show', [
            'qrCode' => $qrCode,
            'invoice' => $qrCode->invoice
        ]);
    }
}
