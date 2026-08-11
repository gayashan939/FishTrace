<?php

namespace App\Services\Traceability;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Response;

class QrCodeRenderer
{
    public function traceSvg(string $publicToken): string
    {
        return (new SvgWriter)
            ->write(new QrCode(url('/trace/'.$publicToken)))
            ->getString();
    }

    public function trace(string $publicToken): Response
    {
        $writer = new SvgWriter;
        $result = $writer->write(new QrCode(url('/trace/'.$publicToken)));

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
