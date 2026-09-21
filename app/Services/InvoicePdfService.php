<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Setting;
use Carbon\Carbon;

class InvoicePdfService
{
    protected float $pageWidth = 595.28;
    protected float $pageHeight = 841.89;
    protected string $content = '';

    /**
     * Generate PDF binary content for a given Payment model.
     *
     * @param Payment $payment
     * @return string PDF binary data
     */
    public function generate(Payment $payment): string
    {
        $this->content = '';

        // Load relations if not already loaded
        $tenant = $payment->tenant;
        if ($tenant) {
            $tenant->loadMissing(['client', 'product', 'plan', 'domains']);
        }

        $client = $tenant?->client;
        $product = $tenant?->product;
        $plan = $tenant?->plan;
        $domain = $tenant?->domain?->domain ?? $tenant?->domains?->first()?->domain ?? 'N/A';

        // Fetch company settings
        $keys = ['company_name', 'company_email', 'company_address', 'company_phone'];
        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
        $companyName = !empty($settings['company_name']) ? $settings['company_name'] : 'Tidcraft Technologies';
        $companyEmail = !empty($settings['company_email']) ? $settings['company_email'] : 'support@tidcraft.com';
        $companyPhone = !empty($settings['company_phone']) ? $settings['company_phone'] : '+91 88667 61852';
        $companyAddress = !empty($settings['company_address']) ? $settings['company_address'] : 'India';

        // Payment & Invoice details
        $invoiceNumber = $payment->invoice_number ?: ('INV-' . date('Y') . '-' . str_pad($payment->id, 3, '0', STR_PAD_LEFT));
        $dateStr = Carbon::parse($payment->create_at ?? $payment->created_at ?? now())->format('d M Y');
        $currency = strtoupper($payment->currency ?: 'INR');
        $amountFormatted = $currency . ' ' . number_format((float)$payment->amount, 2);
        $paymentStatus = strtoupper($payment->status ?: 'PAID');
        $paymentMethod = ucwords(str_replace('_', ' ', $payment->payment_method ?: 'Online Payment'));
        $transactionId = $payment->transaction_id ?: ($payment->order_id ?: 'TXN-' . $payment->id);

        // Client info
        $clientName = $client?->name ?: ($tenant?->business_name ?: 'Valued Client');
        $clientEmail = $client?->email ?: ($tenant?->primary_contact_email ?: 'client@tidcraft.com');
        $clientPhone = $client?->phone_number ?: ($tenant?->phone_number ?: '');
        $clientAddress = $client?->address ?: ($tenant?->address ?: '');
        $businessName = $tenant?->business_name ?: '';

        // Plan / Product description
        $productName = $product?->name ?: 'Subscription Plan';
        $planName = $plan?->name ?: 'Standard Plan';
        $billingCycle = ucfirst($payment->billing_cycle ?: ($plan?->billing_cycle ?: 'Monthly'));

        // --- DRAWING PDF ---

        // 1. Header Banner
        $this->rect(0, 0, $this->pageWidth, 100, '#1e293b');
        $this->text(strtoupper($companyName), 40, 32, 18, true, '#ffffff');
        $this->text($companyEmail . '  |  ' . $companyPhone, 40, 56, 9, false, '#94a3b8');
        $this->text('TAX INVOICE / RECEIPT', 400, 32, 16, true, '#38bdf8', 'right', 155);
        $this->text('ORIGINAL FOR RECIPIENT', 400, 54, 8, false, '#94a3b8', 'right', 155);

        // 2. Invoice Details Grid
        $this->rect(40, 120, 515.28, 75, '#f8fafc', '#e2e8f0');

        // Left Column: Bill To
        $this->text('INVOICED TO:', 55, 130, 8, true, '#64748b');
        $this->text($clientName, 55, 144, 12, true, '#0f172a');
        $yPos = 160;
        if ($businessName && $businessName !== $clientName) {
            $this->text('Business: ' . $businessName, 55, $yPos, 9, false, '#475569');
            $yPos += 14;
        }
        $this->text('Email: ' . $clientEmail, 55, $yPos, 9, false, '#475569');
        if ($clientPhone) {
            $yPos += 14;
            $this->text('Phone: ' . $clientPhone, 55, $yPos, 9, false, '#475569');
        }

        // Right Column: Invoice Meta
        $this->text('Invoice No:', 360, 132, 9, true, '#64748b');
        $this->text($invoiceNumber, 440, 132, 10, true, '#0f172a');

        $this->text('Invoice Date:', 360, 148, 9, true, '#64748b');
        $this->text($dateStr, 440, 148, 9, false, '#0f172a');

        $this->text('Payment Method:', 360, 164, 9, true, '#64748b');
        $this->text($paymentMethod, 440, 164, 9, false, '#0f172a');

        $this->text('Payment Status:', 360, 180, 9, true, '#64748b');
        $statusColor = ($paymentStatus === 'PAID' || $paymentStatus === 'SUCCESS') ? '#16a34a' : '#ea580c';
        $this->text($paymentStatus, 440, 180, 9, true, $statusColor);

        // 3. Line Items Table
        $tableTop = 215;
        $this->rect(40, $tableTop, 515.28, 28, '#2563eb');
        $this->text('#', 55, $tableTop + 9, 9, true, '#ffffff');
        $this->text('DESCRIPTION', 80, $tableTop + 9, 9, true, '#ffffff');
        $this->text('BILLING CYCLE', 320, $tableTop + 9, 9, true, '#ffffff');
        $this->text('AMOUNT', 460, $tableTop + 9, 9, true, '#ffffff', 'right', 80);

        // Row 1
        $rowTop = $tableTop + 28;
        $this->rect(40, $rowTop, 515.28, 55, '#ffffff', '#e2e8f0');
        $this->text('1', 55, $rowTop + 14, 10, false, '#64748b');
        $this->text($productName . ' (' . $planName . ')', 80, $rowTop + 12, 11, true, '#0f172a');
        $this->text('Domain: ' . $domain, 80, $rowTop + 28, 9, false, '#64748b');
        $this->text('Transaction ID: ' . $transactionId, 80, $rowTop + 40, 8, false, '#94a3b8');
        $this->text($billingCycle, 320, $rowTop + 14, 10, false, '#475569');
        $this->text($amountFormatted, 460, $rowTop + 14, 11, true, '#0f172a', 'right', 80);

        // 4. Totals Box
        $totalsTop = $rowTop + 75;
        $this->rect(320, $totalsTop, 235.28, 85, '#f8fafc', '#cbd5e1');

        $this->text('Subtotal:', 340, $totalsTop + 14, 10, false, '#64748b');
        $this->text($amountFormatted, 440, $totalsTop + 14, 10, false, '#0f172a', 'right', 100);

        $this->text('Taxes / Fees (0%):', 340, $totalsTop + 32, 10, false, '#64748b');
        $this->text($currency . ' 0.00', 440, $totalsTop + 32, 10, false, '#0f172a', 'right', 100);

        $this->line(340, $totalsTop + 48, 540, $totalsTop + 48, '#cbd5e1', 1);

        $this->text('Total Paid:', 340, $totalsTop + 58, 11, true, '#1e293b');
        $this->text($amountFormatted, 440, $totalsTop + 56, 14, true, '#2563eb', 'right', 100);

        // 5. Notes & Team Contact Message (Required: "TidCraft team will contact you within 24 hours")
        $notesTop = $totalsTop + 105;
        $this->rect(40, $notesTop, 515.28, 65, '#f0fdf4', '#86efac');
        $this->text('NEXT STEPS & SUPPORT NOTICE', 55, $notesTop + 12, 9, true, '#166534');
        $this->text('Our TidCraft team will contact you within 24 hours to proceed with your setup.', 55, $notesTop + 28, 10, true, '#15803d');
        $this->text('If you have any questions or need immediate assistance, please reach out to us at ' . $companyEmail, 55, $notesTop + 44, 9, false, '#166534');

        // 6. Footer
        $footerTop = 780;
        $this->line(40, $footerTop, 555.28, $footerTop, '#e2e8f0', 1);
        $this->text('Thank you for choosing ' . $companyName . '!', 40, $footerTop + 15, 9, true, '#64748b');
        $this->text('Generated automatically on ' . date('d M Y, h:i A') . ' (UTC)', 320, $footerTop + 15, 8, false, '#94a3b8', 'right', 235);

        return $this->buildPdfString();
    }

    protected function rect(float $x, float $y, float $w, float $h, string $fillHex = '', string $strokeHex = '', float $lineWidth = 1): self
    {
        $this->content .= "q\n";
        if ($lineWidth > 0) {
            $this->content .= sprintf("%.2f w\n", $lineWidth);
        }
        if ($strokeHex !== '') {
            [$r, $g, $b] = $this->hexToRgb($strokeHex);
            $this->content .= sprintf("%.3f %.3f %.3f RG\n", $r, $g, $b);
        }
        if ($fillHex !== '') {
            [$r, $g, $b] = $this->hexToRgb($fillHex);
            $this->content .= sprintf("%.3f %.3f %.3f rg\n", $r, $g, $b);
        }
        // Invert Y coordinate so 0,0 is top-left
        $pdfY = $this->pageHeight - $y - $h;
        $this->content .= sprintf("%.2f %.2f %.2f %.2f re\n", $x, $pdfY, $w, $h);
        if ($fillHex !== '' && $strokeHex !== '') {
            $this->content .= "B\n";
        } elseif ($fillHex !== '') {
            $this->content .= "f\n";
        } elseif ($strokeHex !== '') {
            $this->content .= "s\n";
        }
        $this->content .= "Q\n";
        return $this;
    }

    protected function line(float $x1, float $y1, float $x2, float $y2, string $strokeHex = '#e2e8f0', float $lineWidth = 1): self
    {
        [$r, $g, $b] = $this->hexToRgb($strokeHex);
        $pdfY1 = $this->pageHeight - $y1;
        $pdfY2 = $this->pageHeight - $y2;
        $this->content .= sprintf("q %.2f w %.3f %.3f %.3f RG %.2f %.2f m %.2f %.2f l s Q\n", $lineWidth, $r, $g, $b, $x1, $pdfY1, $x2, $pdfY2);
        return $this;
    }

    protected function text(string $text, float $x, float $y, float $size = 10, bool $bold = false, string $colorHex = '#1e293b', string $align = 'left', float $width = 0): self
    {
        [$r, $g, $b] = $this->hexToRgb($colorHex);
        $fontName = $bold ? '/F2' : '/F1';
        $escaped = $this->escape($text);
        
        $charWidth = $size * ($bold ? 0.58 : 0.52);
        $textWidth = strlen($text) * $charWidth;

        $posX = $x;
        if ($align === 'right' && $width > 0) {
            $posX = $x + $width - $textWidth;
        } elseif ($align === 'center' && $width > 0) {
            $posX = $x + ($width - $textWidth) / 2;
        }

        $pdfY = $this->pageHeight - $y - $size;

        $this->content .= sprintf("BT %s %.2f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n",
            $fontName, $size, $r, $g, $b, $posX, $pdfY, $escaped
        );
        return $this;
    }

    protected function escape(string $text): string
    {
        $text = iconv('UTF-8', 'Windows-1252//IGNORE', $text) ?: $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $val = hexdec($hex);
        return [
            (($val >> 16) & 255) / 255.0,
            (($val >> 8) & 255) / 255.0,
            ($val & 255) / 255.0,
        ];
    }

    protected function buildPdfString(): string
    {
        $streamLen = strlen($this->content);

        $out = "%PDF-1.4\n%âãÏÓ\n";
        $offsets = [];

        // 1: Catalog
        $offsets[1] = strlen($out);
        $out .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // 2: Pages
        $offsets[2] = strlen($out);
        $out .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // 3: Page
        $offsets[3] = strlen($out);
        $out .= sprintf("3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj\n", $this->pageWidth, $this->pageHeight);

        // 4: Contents
        $offsets[4] = strlen($out);
        $out .= "4 0 obj\n<< /Length " . $streamLen . " >>\nstream\n" . $this->content . "\nendstream\nendobj\n";

        // 5: Font Helvetica
        $offsets[5] = strlen($out);
        $out .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        // 6: Font Helvetica-Bold
        $offsets[6] = strlen($out);
        $out .= "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

        // xref
        $xrefOffset = strlen($out);
        $out .= "xref\n0 7\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 6; $i++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $out .= "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $out;
    }
}
