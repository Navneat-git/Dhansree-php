<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminUserStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\BulkInvoice;
use App\Models\BulkInvoiceItem;
use App\Models\StoreSettings;
use App\Support\Auth\AdminSessionManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminBulkInvoiceTest extends TestCase
{
    use DatabaseTransactions;

    private AdminUser $admin;
    private string $rawAdminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::firstOrCreate(
            ['code' => 'SUPER_ADMIN'],
            ['name' => 'Super Administrator', 'grants_all_permissions' => true]
        );

        $this->admin = AdminUser::create([
            'email' => 'bulk_admin_' . uniqid() . '@dhansree.test',
            'name' => 'Wholesale Admin',
            'password_hash' => '$argon2id$v=19$m=19456,t=2,p=1$fakehash',
            'status' => AdminUserStatus::ACTIVE,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'password_change_required' => false,
        ]);
        $this->admin->roles()->attach($role->id);

        $sessionData = AdminSessionManager::createSession($this->admin);
        $this->rawAdminToken = $sessionData['token'];
    }

    private function asAdmin()
    {
        return $this->withUnencryptedCookie(AdminSessionManager::getCookieName(), $this->rawAdminToken);
    }

    public function test_guest_is_redirected_to_admin_login_when_accessing_bulk_invoices(): void
    {
        $response = $this->get('/admin/bulk-invoices');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_bulk_invoices_index(): void
    {
        $response = $this->asAdmin()->get('/admin/bulk-invoices');
        $response->assertStatus(200);
        $response->assertSee('Wholesale Deal Invoices');
        $response->assertSee('Create Bulk Invoice');
    }

    public function test_admin_can_view_create_invoice_page(): void
    {
        $response = $this->asAdmin()->get('/admin/bulk-invoices/create');
        $response->assertStatus(200);
        $response->assertSee('Create Wholesale / Bulk Deal Invoice');
        $response->assertSee('Dhansree Trends');
    }

    public function test_admin_can_create_intra_state_bulk_invoice_with_cgst_and_sgst(): void
    {
        $invoiceNumber = 'INV-TEST-' . uniqid();

        $payload = [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'payment_terms' => 'Due on Receipt',
            'payment_status' => 'UNPAID',
            'customer_name' => 'Rajesh Sharma',
            'business_name' => 'Sharma Retail Emporium',
            'customer_gstin' => '08AAACS1234A1Z5',
            'customer_pan' => 'AAACS1234A',
            'customer_phone' => '+919829012345',
            'customer_email' => 'rajesh@sharmaretail.test',
            'billing_line1' => 'Shop 42, Johari Bazar',
            'billing_city' => 'Jaipur',
            'billing_state_code' => '08',
            'billing_postal_code' => '302003',
            'shipping_same_as_billing' => 1,
            'place_of_supply_state_code' => '08', // Rajasthan -> Intra-state
            'shipping_charges' => 200,
            'packaging_charges' => 50,
            'additional_discount' => 100,
            'round_off' => 0,
            'items' => [
                [
                    'item_name' => 'Cotton Printed Anarkali Kurti (Wholesale Lot)',
                    'description' => 'Sizes M, L, XL - Pack of 10',
                    'hsn_code' => '6204',
                    'quantity' => 10,
                    'unit' => 'PCS',
                    'unit_price' => 500.00, // 500 * 10 = 5000 taxable
                    'discount' => 0,
                    'gst_rate' => 5.0, // 5% GST -> 2.5% CGST (125) + 2.5% SGST (125) = 250
                ],
                [
                    'item_name' => 'Silk Festive Dupatta Set',
                    'description' => 'Lot of 5 pcs',
                    'hsn_code' => '6204',
                    'quantity' => 5,
                    'unit' => 'SET',
                    'unit_price' => 800.00, // 800 * 5 = 4000 taxable
                    'discount' => 200.00, // 4000 - 200 = 3800 taxable
                    'gst_rate' => 12.0, // 12% GST -> 6% CGST (228) + 6% SGST (228) = 456
                ],
            ],
        ];

        $response = $this->asAdmin()->post('/admin/bulk-invoices', $payload);

        $response->assertSessionHasNoErrors();
        $invoice = BulkInvoice::where('invoice_number', $invoiceNumber)->first();
        $this->assertNotNull($invoice);

        $response->assertRedirect(route('admin.bulk-invoices.show', $invoice));

        // Subtotal taxable: 5000 + 3800 = 8800 Rs = 880000 paise
        $this->assertEquals(880000, $invoice->subtotal_taxable_paise);
        $this->assertEquals('INTRA_STATE', $invoice->supply_type);

        // CGST: 125 + 228 = 353 Rs = 35300 paise
        // SGST: 125 + 228 = 353 Rs = 35300 paise
        $this->assertEquals(35300, $invoice->cgst_paise);
        $this->assertEquals(35300, $invoice->sgst_paise);
        $this->assertEquals(0, $invoice->igst_paise);
        $this->assertEquals(70600, $invoice->total_tax_paise);

        // Grand total: 8800 (taxable) + 706 (tax) + 200 (ship) + 50 (pack) - 100 (disc) = 9656 Rs = 965600 paise
        $this->assertEquals(965600, $invoice->grand_total_paise);
        $this->assertEquals(2, $invoice->items()->count());
    }

    public function test_admin_can_create_inter_state_bulk_invoice_with_igst(): void
    {
        $invoiceNumber = 'INV-IGST-' . uniqid();

        $payload = [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => date('Y-m-d'),
            'payment_status' => 'UNPAID',
            'customer_name' => 'Meena Patel',
            'business_name' => 'Patel Saree Center',
            'customer_gstin' => '24AAACP9999P1Z1',
            'billing_line1' => 'Ring Road Textile Market',
            'billing_city' => 'Surat',
            'billing_state_code' => '24', // Gujarat
            'billing_postal_code' => '395002',
            'shipping_same_as_billing' => 1,
            'place_of_supply_state_code' => '24', // Gujarat -> Inter-state
            'items' => [
                [
                    'item_name' => 'Jaipuri Hand Block Print Bedsheet Wholesale Bale',
                    'hsn_code' => '6304',
                    'quantity' => 20,
                    'unit' => 'PCS',
                    'unit_price' => 450.00, // 450 * 20 = 9000 taxable
                    'discount' => 0,
                    'gst_rate' => 12.0, // 12% IGST = 1080 Rs = 108000 paise
                ],
            ],
        ];

        $response = $this->asAdmin()->post('/admin/bulk-invoices', $payload);
        $response->assertSessionHasNoErrors();

        $invoice = BulkInvoice::where('invoice_number', $invoiceNumber)->first();
        $this->assertNotNull($invoice);

        $this->assertEquals('INTER_STATE', $invoice->supply_type);
        $this->assertEquals(900000, $invoice->subtotal_taxable_paise);
        $this->assertEquals(0, $invoice->cgst_paise);
        $this->assertEquals(0, $invoice->sgst_paise);
        $this->assertEquals(108000, $invoice->igst_paise);
        $this->assertEquals(1008000, $invoice->grand_total_paise);
    }

    public function test_admin_can_view_show_page_and_printable_tax_invoice(): void
    {
        $invoice = BulkInvoice::create([
            'invoice_number' => 'INV-PRINT-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'payment_status' => 'PAID',
            'paid_amount_paise' => 500000,
            'seller_name' => 'Dhansree Trends',
            'seller_legal_name' => 'Dhanshree Trends',
            'seller_gstin' => '08CCUPS2872J1ZO',
            'seller_state_code' => '08',
            'seller_state_name' => 'Rajasthan',
            'customer_name' => 'Sunita Verma',
            'business_name' => 'Verma Boutiques',
            'customer_gstin' => '08BBBPV4321B1Z2',
            'billing_line1' => 'M.I. Road',
            'billing_city' => 'Jaipur',
            'billing_state_code' => '08',
            'billing_state_name' => 'Rajasthan',
            'billing_postal_code' => '302001',
            'place_of_supply_state_code' => '08',
            'place_of_supply_state_name' => 'Rajasthan',
            'supply_type' => 'INTRA_STATE',
            'subtotal_taxable_paise' => 476190,
            'cgst_paise' => 11905,
            'sgst_paise' => 11905,
            'total_tax_paise' => 23810,
            'grand_total_paise' => 500000,
            'disclaimer' => 'This is a system generated invoice and does not require a physical signature.',
        ]);

        $invoice->items()->create([
            'item_name' => 'Designer Chanderi Suit Material',
            'hsn_code' => '6204',
            'quantity' => 5,
            'unit' => 'SET',
            'unit_price_paise' => 95238,
            'taxable_amount_paise' => 476190,
            'gst_rate_bps' => 500,
            'cgst_rate_bps' => 250,
            'cgst_amount_paise' => 11905,
            'sgst_rate_bps' => 250,
            'sgst_amount_paise' => 11905,
            'total_tax_paise' => 23810,
            'line_total_paise' => 500000,
            'position' => 1,
        ]);

        // 1. Show page test
        $showResponse = $this->asAdmin()->get("/admin/bulk-invoices/{$invoice->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee($invoice->invoice_number);
        $showResponse->assertSee('Verma Boutiques');

        // 2. Printable invoice test
        $printResponse = $this->asAdmin()->get("/admin/bulk-invoices/{$invoice->id}/invoice");
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Dhansree Trends');
        $printResponse->assertSee('08CCUPS2872J1ZO');
        $printResponse->assertSee('TAX INVOICE');
        $printResponse->assertSee('ORIGINAL FOR RECIPIENT');
        $printResponse->assertSee('This is a system generated invoice and does not require a physical signature.');
    }

    public function test_admin_can_update_payment_status(): void
    {
        $invoice = BulkInvoice::create([
            'invoice_number' => 'INV-PAY-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'payment_status' => 'UNPAID',
            'paid_amount_paise' => 0,
            'seller_name' => 'Dhansree Trends',
            'seller_gstin' => '08CCUPS2872J1ZO',
            'seller_address' => 'C-197 Murlipura Scheme',
            'seller_state_code' => '08',
            'seller_state_name' => 'Rajasthan',
            'customer_name' => 'Kunal Jain',
            'billing_line1' => 'Ajmer Road',
            'billing_city' => 'Jaipur',
            'billing_state_code' => '08',
            'billing_state_name' => 'Rajasthan',
            'billing_postal_code' => '302006',
            'place_of_supply_state_code' => '08',
            'place_of_supply_state_name' => 'Rajasthan',
            'supply_type' => 'INTRA_STATE',
            'subtotal_taxable_paise' => 100000,
            'cgst_paise' => 2500,
            'sgst_paise' => 2500,
            'total_tax_paise' => 5000,
            'grand_total_paise' => 105000,
        ]);

        $response = $this->asAdmin()->post("/admin/bulk-invoices/{$invoice->id}/payment", [
            'payment_status' => 'PAID',
            'paid_amount' => 1050.00,
            'payment_method' => 'BANK_TRANSFER',
            'payment_reference' => 'UTR123456789',
        ]);

        $response->assertSessionHas('success');
        $invoice->refresh();

        $this->assertEquals('PAID', $invoice->payment_status);
        $this->assertEquals(105000, $invoice->paid_amount_paise);
        $this->assertEquals('UTR123456789', $invoice->payment_reference);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_admin_can_delete_bulk_invoice(): void
    {
        $invoice = BulkInvoice::create([
            'invoice_number' => 'INV-DEL-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'payment_status' => 'CANCELLED',
            'seller_name' => 'Dhansree Trends',
            'seller_address' => 'C-197 Murlipura Scheme',
            'seller_state_code' => '08',
            'seller_state_name' => 'Rajasthan',
            'customer_name' => 'Test Delete',
            'billing_line1' => 'Test Address',
            'billing_city' => 'Jaipur',
            'billing_state_code' => '08',
            'billing_state_name' => 'Rajasthan',
            'billing_postal_code' => '302001',
            'place_of_supply_state_code' => '08',
            'place_of_supply_state_name' => 'Rajasthan',
            'supply_type' => 'INTRA_STATE',
            'subtotal_taxable_paise' => 10000,
            'grand_total_paise' => 10500,
        ]);

        $response = $this->asAdmin()->delete("/admin/bulk-invoices/{$invoice->id}");
        $response->assertRedirect(route('admin.bulk-invoices.index'));

        $this->assertDatabaseMissing('bulk_invoices', ['id' => $invoice->id]);
    }

    public function test_admin_can_update_existing_invoice(): void
    {
        $invoice = BulkInvoice::create([
            'invoice_number' => 'INV-EDIT-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'payment_status' => 'UNPAID',
            'seller_name' => 'Dhansree Trends',
            'seller_address' => 'C-197 Murlipura Scheme',
            'seller_state_code' => '08',
            'seller_state_name' => 'Rajasthan',
            'customer_name' => 'Original Customer',
            'billing_line1' => 'Old Address',
            'billing_city' => 'Jaipur',
            'billing_state_code' => '08',
            'billing_state_name' => 'Rajasthan',
            'billing_postal_code' => '302001',
            'place_of_supply_state_code' => '08',
            'place_of_supply_state_name' => 'Rajasthan',
            'supply_type' => 'INTRA_STATE',
            'subtotal_taxable_paise' => 100000,
            'grand_total_paise' => 105000,
        ]);

        $invoice->items()->create([
            'item_name' => 'Old Item',
            'hsn_code' => '6204',
            'quantity' => 1,
            'unit' => 'PCS',
            'unit_price_paise' => 100000,
            'taxable_amount_paise' => 100000,
            'gst_rate_bps' => 500,
            'cgst_rate_bps' => 250,
            'cgst_amount_paise' => 2500,
            'sgst_rate_bps' => 250,
            'sgst_amount_paise' => 2500,
            'total_tax_paise' => 5000,
            'line_total_paise' => 105000,
            'position' => 1,
        ]);

        // 1. Can view edit page
        $editPageResponse = $this->asAdmin()->get("/admin/bulk-invoices/{$invoice->id}/edit");
        $editPageResponse->assertStatus(200);
        $editPageResponse->assertSee($invoice->invoice_number);

        // 2. Can update invoice
        $updatePayload = [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => date('Y-m-d'),
            'payment_status' => 'PAID',
            'paid_amount' => 2100.00,
            'customer_name' => 'Updated Customer Name',
            'billing_line1' => 'New Showroom Address',
            'billing_city' => 'Jaipur',
            'billing_state_code' => '08',
            'billing_postal_code' => '302001',
            'shipping_same_as_billing' => 1,
            'place_of_supply_state_code' => '08',
            'items' => [
                [
                    'item_name' => 'Updated Kurti Lot',
                    'quantity' => 4,
                    'unit_price' => 500.00, // 2000 taxable + 5% GST = 2100
                    'gst_rate' => 5.0,
                ],
            ],
        ];

        $response = $this->asAdmin()->put("/admin/bulk-invoices/{$invoice->id}", $updatePayload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.bulk-invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals('Updated Customer Name', $invoice->customer_name);
        $this->assertEquals('PAID', $invoice->payment_status);
        $this->assertEquals(200000, $invoice->subtotal_taxable_paise);
        $this->assertEquals(210000, $invoice->grand_total_paise);
        $this->assertEquals(1, $invoice->items()->count());
        $this->assertEquals('Updated Kurti Lot', $invoice->items->first()->item_name);
    }
}
