<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\Invoice;
use App\Models\QRCode;
use Illuminate\Support\Str;

class QRCodeService
{
    public function generateForInvoice(Invoice $invoice): QRCode
    {
        // Generate control number (you can customize the format)
        $controlNumber = $this->generateControlNumber($invoice);

        // Generate QR code with just the control number
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        try {
            $qrCodeSvg = $writer->writeString($controlNumber);

            // Store QR code
            return QRCode::create([
                'invoice_id' => $invoice->id,
                'control_number' => $controlNumber,
                'qr_data' => $qrCodeSvg,
                'status' => 'ACTIVE',
                'expires_at' => now()->addHours(24)
            ]);
        } catch (\Exception $e) {
            \Log::error('QR Code generation failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id
            ]);

            throw new \Exception('Failed to generate QR code: ' . $e->getMessage());
        }
    }

    private function generateControlNumber(Invoice $invoice): string
    {
        // Format: SBM-YYMMDDHH-XXXX-R
        // SBM: Simba Money prefix
        // YYMMDDHH: Year, Month, Day, Hour
        // XXXX: Random alphanumeric
        // R: Check digit

        $prefix = 'SBM';
        $dateTime = now()->format('ymdH');
        $random = strtoupper(Str::random(4));
        $baseNumber = $prefix . $dateTime . $random;


        $checkDigit = $this->calculateCheckDigit($baseNumber);

        return $baseNumber . $checkDigit;
    }

    private function calculateCheckDigit(string $number): string
    {
        $sum = 0;
        foreach (str_split($number) as $char) {
            $sum += ord($char);
        }
        return strtoupper(dechex($sum % 16));
    }
}
