<?php

namespace Tests\Feature;

use App\Models\Clients\Client;
use App\Models\Finance\CashBox;
use App\Models\Finance\CashSheet;
use App\Models\Finance\CashSheetMovement;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoicePayment;
use App\Models\User;
use Tests\TestCase;

/**
 * ABM de clientes del módulo de ventas y registro de una venta sin planilla
 * de caja abierta.
 */
class SalesClientsAndCashSheetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El esquema real del ERP: las migraciones corren sobre el SQLite en
        // memoria que arma el phpunit.xml.
        $this->artisan('migrate');
    }

    public function test_client_crud_filters_and_baja(): void
    {
        $created = $this->postJson('/api/clients', [
            'name' => 'Biblioteca del Sur',
            'document_type' => 'CUIT',
            'document_number' => '30111222',
            'email' => 'compras@biblio.test',
            'discount' => 5,
        ])->assertCreated();

        $id = $created->json('id');

        $this->assertSame('Biblioteca del Sur', $created->json('name'));
        $this->assertTrue($created->json('is_active'));
        $this->assertSame('5.00', $created->json('discount'));

        // El nombre es obligatorio.
        $this->postJson('/api/clients', ['name' => ''])->assertUnprocessable();

        // El listado filtra por nombre.
        $this->getJson('/api/clients?search=Biblio')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Biblioteca del Sur');

        // Y por estado: los dados de baja quedan fuera del filtro de activos.
        // Las migraciones ya dejan cargado el cliente "Consumidor Final".
        $this->getJson('/api/clients?is_active=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->putJson('/api/clients/'.$id, [
            'name' => 'Biblioteca del Sur',
            'document_type' => 'CUIT',
            'document_number' => '30111222',
            'email' => 'compras@biblio.test',
            'discount' => 5,
            'is_active' => false,
        ])->assertOk()->assertJsonPath('is_active', false);

        $this->getJson('/api/clients?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/clients?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_sale_can_be_paid_without_an_open_cash_sheet(): void
    {
        $user = User::create([
            'name' => 'Cajero',
            'username' => 'cajero',
            'email' => 'cajero@example.com',
            'password' => 'ERP123',
            'is_active' => 1,
        ]);

        $client = Client::create(['name' => 'Consumidor Final', 'document_number' => '0']);

        // --- Sin planilla de caja abierta ------------------------------------
        $invoice = $this->invoiceFor($client);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/invoices/'.$invoice->id.'/payments', [
                'payment_method' => 'cash',
                'amount' => 121,
            ])
            ->assertOk();

        $this->assertSame(1, InvoicePayment::where('invoice_id', $invoice->id)->count());
        $this->assertSame(0, CashSheetMovement::where('invoice_id', $invoice->id)->count());
        $this->assertSame('0.00', $invoice->fresh()->balance);
        $this->assertSame('Cobrada', $invoice->fresh()->status_name);

        // --- Con planilla de caja abierta el movimiento se crea --------------
        $box = CashBox::create(['name' => 'Caja 1', 'box_type_name' => 'CAJA', 'branch_name' => 'Central']);

        CashSheet::create([
            'cash_box_id' => $box->id,
            'cash_box_name' => $box->name,
            'user_id' => $user->id,
            'number' => 1,
            'status_name' => 'Abierta',
            'opening_date' => now(),
        ]);

        $paid = $this->invoiceFor($client);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/invoices/'.$paid->id.'/payments', [
                'payment_method' => 'cash',
                'amount' => 121,
            ])
            ->assertOk();

        $this->assertSame(1, CashSheetMovement::where('invoice_id', $paid->id)->count());
    }

    private function invoiceFor(Client $client): Invoice
    {
        return Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now(),
            'payment_due_date' => now(),
            'receipt_type_name' => 'Comprobante interno',
            'receipt_types_prefix' => 'FV',
            'letter' => '-',
            'first_number' => '0099',
            'full_number' => '0099-'.str_pad((string) ((int) Invoice::max('id') + 1), 8, '0', STR_PAD_LEFT),
            'mode' => 'internal',
            'currency_name' => 'Pesos',
            'payment_condition_name' => 'CONTADO',
            'taxed_amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'balance' => 121,
            'status_name' => 'Pend. Cobro',
            'status_id' => 6,
            'customer_name' => $client->name,
        ]);
    }
}
