<?php

namespace App\Services;

use App\Models\Sale;
use Dompdf\Dompdf;
use Dompdf\Options;
use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\DataGroup\Element\CombinedAddress;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\QrBill;

class InvoicePdfService
{
    public function generate(Sale $sale): string
    {
        $sale->load('items', 'customer');
        $tenant = tenant();

        $qrCodePng = $this->buildQrCodePng($sale, $tenant);
        $html      = $this->buildHtml($sale, $tenant, $qrCodePng);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    // ─── QR Code generation (PNG, base64) ────────────────────────────────────

    private function buildQrCodePng(Sale $sale, $tenant): string
    {
        if (! $tenant->iban) {
            return '';
        }

        try {
            $qrBill = QrBill::create();

            $qrBill->setCreditorInformation(
                CreditorInformation::create(str_replace(' ', '', $tenant->iban))
            );

            $qrBill->setCreditor(
                CombinedAddress::create(
                    $tenant->company_name ?? $tenant->name,
                    $tenant->address ?? 'Via —',
                    ($tenant->zip ?? '0000') . ' ' . ($tenant->city ?? '—'),
                    $tenant->country ?? 'CH'
                )
            );

            $qrBill->setPaymentAmountInformation(
                PaymentAmountInformation::create('CHF', round((float) $sale->total, 2))
            );

            $qrBill->setPaymentReference(
                PaymentReference::create(PaymentReference::TYPE_NON)
            );

            $qrBill->setAdditionalInformation(
                AdditionalInformation::create(
                    mb_substr($sale->invoice_number, 0, 140)
                )
            );

            if ($sale->customer) {
                $qrBill->setUltimateDebtor(
                    CombinedAddress::create(
                        $sale->customer->full_name,
                        $sale->customer->address ?? '—',
                        ($sale->customer->zip ?? '') . ' ' . ($sale->customer->city ?? ''),
                        $sale->customer->country ?? 'CH'
                    )
                );
            }

            // Generate QR code as PNG using endroid/qr-code (bundled with sprain)
            $qrCode = \Sprain\SwissQrBill\QrCode\QrCode::create($qrBill->getQrCodeData());
            $qrCode->setSize(46); // mm

            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write(
                \Endroid\QrCode\QrCode::create($qrBill->getQrCodeData())
                    ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                    ->setSize(350)
                    ->setMargin(0)
                    ->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::Medium)
            );

            return base64_encode($result->getString());
        } catch (\Throwable $e) {
            return '';
        }
    }

    // ─── HTML (2 pages) ──────────────────────────────────────────────────────

    private function buildHtml(Sale $sale, $tenant, string $qrCodePng): string
    {
        $fmt = fn($v) => number_format((float) $v, 2, '.', "'") . ' CHF';

        $logoData   = $this->imageToBase64($tenant->logo_path ?? '');
        $bgCss      = $this->buildBgCss($tenant->invoice_background_path ?? '');
        $primaryColor = $tenant->primary_color ?? '#6366f1';

        $itemsHtml = $this->buildItemsHtml($sale, $fmt);
        $customerBlock = $this->buildCustomerBlock($sale);
        $totalsHtml = $this->buildTotalsHtml($sale, $fmt, $primaryColor);

        $issuedAt = $sale->issued_at ? $sale->issued_at->format('d.m.Y') : now()->format('d.m.Y');
        $dueAt    = $sale->due_at    ? $sale->due_at->format('d.m.Y')    : '';
        $typeLabel = match($sale->type) {
            'quote'       => 'PREVENTIVO',
            'credit_note' => 'NOTA DI CREDITO',
            default       => 'FATTURA',
        };

        $notesHtml = $sale->notes
            ? "<div style='margin-top:14px;padding:10px 12px;background:#f8f9fa;border-radius:5px;font-size:10.5px;color:#555;'><strong>Note:</strong> " . htmlspecialchars($sale->notes) . "</div>"
            : '';

        $bankHtml = $tenant->iban
            ? "<div style='margin-top:10px;font-size:10px;color:#888;'>Banca: " . htmlspecialchars($tenant->bank_name ?? '') . " — IBAN: " . htmlspecialchars($tenant->iban) . "</div>"
            : '';

        // ── Page 1: Invoice ──────────────────────────────────────────────────
        $page1 = <<<HTML
<div class="page" style="{$bgCss}">

  <!-- Header: logo + company info -->
  <table style="width:100%;margin-bottom:22px;">
    <tr>
      <td style="vertical-align:top;">
        {$logoData}
      </td>
      <td style="text-align:right;vertical-align:top;font-size:10.5px;color:#555;line-height:1.7;">
        <strong style="font-size:13px;color:#1a1a2e;">{$tenant->company_name}</strong><br>
        {$tenant->address}<br>
        {$tenant->zip} {$tenant->city}<br>
        {$tenant->country}<br>
        " . ($tenant->phone ? "Tel: " . htmlspecialchars($tenant->phone) . "<br>" : '') . "
        " . ($tenant->email ? htmlspecialchars($tenant->email) . "<br>" : '') . "
        " . ($tenant->uid_number ? "<span style='font-size:10px;'>UID: " . htmlspecialchars($tenant->uid_number) . "</span>" : '') . "
      </td>
    </tr>
  </table>

  <!-- Title + customer + meta -->
  <table style="width:100%;margin-bottom:20px;">
    <tr>
      <td style="vertical-align:top;width:55%;">
        {$customerBlock}
      </td>
      <td style="vertical-align:top;text-align:right;">
        <div style="font-size:24px;font-weight:900;color:{$primaryColor};letter-spacing:-0.02em;">{$typeLabel}</div>
        <div style="font-size:14px;font-weight:700;margin-top:3px;">{$sale->invoice_number}</div>
        <div style="font-size:11px;color:#666;margin-top:8px;line-height:1.7;">
          Data emissione: <strong>{$issuedAt}</strong><br>
          " . ($dueAt ? "Scadenza pagamento: <strong>{$dueAt}</strong>" : '') . "
        </div>
      </td>
    </tr>
  </table>

  <!-- Line items -->
  <table class="items-table" style="width:100%;border-collapse:collapse;font-size:11px;">
    <thead>
      <tr style="background:#f4f4f8;">
        <th style="padding:8px 6px;text-align:left;color:#555;border-bottom:2px solid {$primaryColor};width:45%;">Descrizione</th>
        <th style="padding:8px 6px;text-align:center;color:#555;border-bottom:2px solid {$primaryColor};width:8%;">Qtà</th>
        <th style="padding:8px 6px;text-align:right;color:#555;border-bottom:2px solid {$primaryColor};width:18%;">Prezzo unit.</th>
        <th style="padding:8px 6px;text-align:right;color:#555;border-bottom:2px solid {$primaryColor};width:10%;">IVA</th>
        <th style="padding:8px 6px;text-align:right;color:#555;border-bottom:2px solid {$primaryColor};width:19%;">Importo</th>
      </tr>
    </thead>
    <tbody>
      {$itemsHtml}
    </tbody>
  </table>

  <!-- Totals -->
  {$totalsHtml}

  {$notesHtml}
  {$bankHtml}

</div>
HTML;

        // ── Page 2: Payment slip (cedola di versamento / QR-Rechnung) ────────
        $page2 = $this->buildPaymentSlip($sale, $tenant, $qrCodePng, $fmt, $primaryColor);

        return <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1a1a2e; }
  .page { padding: 18mm 15mm 15mm 20mm; width: 210mm; min-height: 297mm; }
  .page-break { page-break-after: always; }
</style>
</head>
<body>
  <div class="page-break">
    {$page1}
  </div>
  {$page2}
</body>
</html>
HTML;
    }

    // ─── Payment slip (page 2) ─────────────────────────────────────────────

    private function buildPaymentSlip(Sale $sale, $tenant, string $qrCodePng, callable $fmt, string $primaryColor): string
    {
        $creditorLines = implode('<br>', array_filter([
            htmlspecialchars($tenant->company_name ?? $tenant->name ?? ''),
            htmlspecialchars($tenant->address ?? ''),
            htmlspecialchars(trim(($tenant->zip ?? '') . ' ' . ($tenant->city ?? ''))),
        ]));

        $debtorLines = '';
        if ($sale->customer) {
            $c = $sale->customer;
            $debtorLines = implode('<br>', array_filter([
                htmlspecialchars($c->full_name),
                htmlspecialchars($c->address ?? ''),
                htmlspecialchars(trim(($c->zip ?? '') . ' ' . ($c->city ?? ''))),
            ]));
        }

        $iban    = $tenant->iban ? chunk_split(str_replace(' ', '', $tenant->iban), 4, ' ') : '— IBAN non configurato —';
        $amount  = $fmt($sale->total);
        $ref     = htmlspecialchars($sale->invoice_number);
        $qrImg   = $qrCodePng
            ? "<img src=\"data:image/png;base64,{$qrCodePng}\" style=\"width:46mm;height:46mm;\">"
            : "<div style='width:46mm;height:46mm;border:2px dashed #ccc;display:flex;align-items:center;justify-content:center;font-size:9px;color:#aaa;text-align:center;'>QR non<br>disponibile<br>(IBAN mancante)</div>";

        return <<<HTML
<div style="font-family:Helvetica,Arial,sans-serif;padding:0;margin:0;">

  <!-- Title -->
  <div style="text-align:center;font-size:11px;letter-spacing:0.15em;text-transform:uppercase;
              color:#666;padding:10mm 0 6mm;border-top:0.5pt solid #bbb;">
    Cedola di versamento — QR-Rechnung — Bulletin de versement
  </div>

  <!-- Slip body: Receipt | Payment section -->
  <table style="width:100%;border-collapse:collapse;font-size:10.5px;border-top:0.5pt solid #aaa;">
    <tr>

      <!-- RICEVUTA (sinistra, 52mm) -->
      <td style="width:52mm;vertical-align:top;padding:5mm 3mm 5mm 5mm;border-right:0.5pt solid #aaa;">
        <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:3mm;">Ricevuta</div>

        <div style="font-size:8px;color:#555;margin-bottom:1mm;">Pagabile a</div>
        <div style="font-size:9px;font-weight:600;line-height:1.5;margin-bottom:3mm;">
          <div style="font-size:8.5px;">{$iban}</div>
          {$creditorLines}
        </div>

        " . ($debtorLines ? "
        <div style='font-size:8px;color:#555;margin-bottom:1mm;margin-top:2mm;'>Pagabile da</div>
        <div style='font-size:9px;line-height:1.5;margin-bottom:3mm;'>{$debtorLines}</div>" : '') . "

        <div style="font-size:8px;color:#555;margin-bottom:1mm;">Importo</div>
        <div style="font-size:11px;font-weight:700;margin-bottom:3mm;">{$amount}</div>

        <div style="font-size:8px;color:#555;margin-bottom:1mm;">Valuta</div>
        <div style="font-size:9px;font-weight:600;margin-bottom:3mm;">CHF</div>

        <div style="margin-top:auto;font-size:7.5px;color:#888;border-top:0.5pt solid #ddd;padding-top:2mm;">
          Punto accettazione
          <div style="margin-top:8mm;border-top:0.5pt solid #555;font-size:7px;color:#555;padding-top:1mm;">Firma autorizzata</div>
        </div>
      </td>

      <!-- SEZIONE DI PAGAMENTO (destra) -->
      <td style="vertical-align:top;padding:5mm 5mm 5mm 6mm;">
        <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4mm;">Sezione di pagamento</div>

        <table style="width:100%;border-collapse:collapse;">
          <tr>
            <!-- QR Code + currency/amount under -->
            <td style="vertical-align:top;width:56mm;">
              {$qrImg}
              <table style="margin-top:3mm;font-size:10px;">
                <tr>
                  <td style="padding-right:6mm;">
                    <div style="font-size:8px;color:#555;">Valuta</div>
                    <div style="font-weight:700;font-size:11px;">CHF</div>
                  </td>
                  <td>
                    <div style="font-size:8px;color:#555;">Importo</div>
                    <div style="font-weight:700;font-size:13px;color:{$primaryColor};">{$amount}</div>
                  </td>
                </tr>
              </table>
            </td>

            <!-- Creditor info + reference + debtor -->
            <td style="vertical-align:top;padding-left:5mm;font-size:9.5px;">
              <div style="font-size:8px;color:#555;margin-bottom:1mm;">Pagabile a</div>
              <div style="font-size:8.5px;margin-bottom:1mm;">{$iban}</div>
              <div style="font-weight:600;line-height:1.5;margin-bottom:3mm;">{$creditorLines}</div>

              <div style="font-size:8px;color:#555;margin-bottom:1mm;">Riferimento</div>
              <div style="font-family:monospace;font-size:8.5px;font-weight:600;margin-bottom:3mm;letter-spacing:0.04em;">{$ref}</div>

              <div style="font-size:8px;color:#555;margin-bottom:1mm;">Informazioni aggiuntive</div>
              <div style="font-size:8.5px;color:#666;margin-bottom:3mm;">{$ref}</div>

              " . ($debtorLines ? "
              <div style='font-size:8px;color:#555;margin-bottom:1mm;'>Pagabile da</div>
              <div style='font-size:9px;line-height:1.5;'>{$debtorLines}</div>" : '') . "
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Footer note -->
  <div style="text-align:center;font-size:8px;color:#aaa;margin-top:4mm;">
    Questa cedola è conforme allo standard Swiss QR-bill (SIX Group) — scadenza: " . ($sale->due_at ? $sale->due_at->format('d.m.Y') : '30 giorni dalla data di emissione') . "
  </div>
</div>
HTML;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function imageToBase64(string $path): string
    {
        if (! $path) return '';
        $full = storage_path('app/public/' . $path);
        if (! file_exists($full)) return '';
        $mime = mime_content_type($full);
        $b64  = base64_encode(file_get_contents($full));
        return "<img src=\"data:{$mime};base64,{$b64}\" style=\"max-height:60px;max-width:180px;\">";
    }

    private function buildBgCss(string $path): string
    {
        if (! $path) return '';
        $full = storage_path('app/public/' . $path);
        if (! file_exists($full)) return '';
        $mime = mime_content_type($full);
        $b64  = base64_encode(file_get_contents($full));
        return "background-image: url('data:{$mime};base64,{$b64}'); background-size: cover; background-position: top;";
    }

    private function buildItemsHtml(Sale $sale, callable $fmt): string
    {
        $html = '';
        foreach ($sale->items as $item) {
            $lineTotal   = $item->qty * $item->unit_price * (1 - $item->discount_pct / 100);
            $discountTx  = $item->discount_pct > 0 ? " <span style='color:#e53e3e;font-size:9px;'>(-{$item->discount_pct}%)</span>" : '';
            $html .= "
            <tr>
              <td style='padding:7px 6px;border-bottom:1px solid #f0f0f0;'>" . htmlspecialchars($item->description) . "</td>
              <td style='padding:7px 6px;border-bottom:1px solid #f0f0f0;text-align:center;'>{$item->qty}</td>
              <td style='padding:7px 6px;border-bottom:1px solid #f0f0f0;text-align:right;'>" . $fmt($item->unit_price) . "{$discountTx}</td>
              <td style='padding:7px 6px;border-bottom:1px solid #f0f0f0;text-align:right;'>{$item->vat_rate}%</td>
              <td style='padding:7px 6px;border-bottom:1px solid #f0f0f0;text-align:right;font-weight:600;'>" . $fmt($lineTotal) . "</td>
            </tr>";
        }
        return $html;
    }

    private function buildCustomerBlock(Sale $sale): string
    {
        if (! $sale->customer) return '';
        $c = $sale->customer;
        return "
        <div>
          <div style='font-size:9.5px;color:#888;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em;'>Fatturare a</div>
          <div style='font-weight:700;font-size:13px;'>" . htmlspecialchars($c->full_name) . "</div>
          " . ($c->company ? "<div style='font-size:11px;color:#555;'>" . htmlspecialchars($c->company) . "</div>" : '') . "
          " . ($c->address ? "<div>" . htmlspecialchars($c->address) . "</div>" : '') . "
          " . ($c->city    ? "<div>" . htmlspecialchars(trim($c->zip . ' ' . $c->city)) . "</div>" : '') . "
          " . ($c->tax_number ? "<div style='font-size:10px;color:#666;margin-top:2px;'>UID/P.IVA: " . htmlspecialchars($c->tax_number) . "</div>" : '') . "
        </div>";
    }

    private function buildTotalsHtml(Sale $sale, callable $fmt, string $color): string
    {
        $discountRow = $sale->discount_amount > 0
            ? "<tr><td style='padding:4px 0;color:#e53e3e;'>Sconto</td><td style='text-align:right;padding:4px 0;color:#e53e3e;'>- " . $fmt($sale->discount_amount) . "</td></tr>"
            : '';

        return "
        <table style='width:100%;margin-top:14px;'>
          <tr>
            <td style='width:60%;'></td>
            <td style='width:40%;'>
              <table style='width:100%;font-size:11.5px;'>
                <tr>
                  <td style='padding:4px 0;color:#555;'>Subtotale</td>
                  <td style='text-align:right;padding:4px 0;'>" . $fmt($sale->subtotal) . "</td>
                </tr>
                <tr>
                  <td style='padding:4px 0;color:#555;'>IVA</td>
                  <td style='text-align:right;padding:4px 0;'>" . $fmt($sale->vat_amount) . "</td>
                </tr>
                {$discountRow}
                <tr>
                  <td colspan='2'><hr style='border:none;border-top:2px solid {$color};margin:6px 0;'></td>
                </tr>
                <tr>
                  <td style='font-weight:700;font-size:13px;'>TOTALE</td>
                  <td style='text-align:right;font-weight:800;font-size:16px;color:{$color};'>" . $fmt($sale->total) . "</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>";
    }
}
