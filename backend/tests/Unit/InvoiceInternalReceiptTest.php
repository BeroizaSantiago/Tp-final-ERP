<?php

namespace Tests\Unit;

use App\Models\Sales\Invoice;
use PHPUnit\Framework\TestCase;

class InvoiceInternalReceiptTest extends TestCase
{
    public function test_it_identifies_an_invoice_persisted_in_internal_mode(): void
    {
        $invoice = new Invoice(['mode' => 'internal', 'first_number' => '0099']);

        $this->assertTrue($invoice->is_internal_receipt);
    }

    public function test_it_keeps_legacy_0099_receipts_as_internal(): void
    {
        $invoice = new Invoice([
            'first_number' => '99',
            'full_number' => 'INT-0099-00000025',
        ]);

        $this->assertTrue($invoice->is_internal_receipt);
        $this->assertSame('0099-00000025', $invoice->display_number);
    }

    public function test_it_does_not_mark_the_arca_point_of_sale_as_internal(): void
    {
        $invoice = new Invoice(['mode' => 'fiscal', 'first_number' => '0004']);

        $this->assertFalse($invoice->is_internal_receipt);
    }

    public function test_it_reuses_the_receiver_document_returned_by_arca(): void
    {
        $invoice = new Invoice([
            'arca_response' => [
                'raw' => [
                    'FECAESolicitarResult' => [
                        'FeDetResp' => [
                            'FECAEDetResponse' => [
                                'DocTipo' => 96,
                                'DocNro' => 99999999,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(96, $invoice->arca_receiver_document_type);
        $this->assertSame(99999999, $invoice->arca_receiver_document_number);
    }
}
