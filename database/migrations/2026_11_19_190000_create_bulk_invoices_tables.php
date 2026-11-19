<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bulk_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->date('invoice_date')->index();
            $table->date('due_date')->nullable();
            $table->string('payment_terms', 50)->default('Due on Receipt');
            $table->string('payment_status', 30)->default('UNPAID')->index(); // UNPAID, PAID, PARTIALLY_PAID, CANCELLED
            $table->string('payment_method', 50)->nullable(); // Bank Transfer (NEFT/RTGS), UPI, Cheque, Cash
            $table->string('payment_reference', 100)->nullable(); // Transaction UTR / Ref No.
            $table->unsignedBigInteger('paid_amount_paise')->default(0);
            $table->dateTime('paid_at')->nullable();

            // Seller (Dhansree Trends) details snapshot
            $table->string('seller_name', 150)->default('Dhansree Trends');
            $table->string('seller_legal_name', 150)->default('Dhanshree Trends');
            $table->string('seller_gstin', 20)->default('08CCUPS2872J1ZO');
            $table->string('seller_pan', 20)->nullable();
            $table->text('seller_address')->nullable();
            $table->string('seller_city', 100)->default('Jaipur');
            $table->char('seller_state_code', 2)->default('08');
            $table->string('seller_state_name', 100)->default('Rajasthan');
            $table->string('seller_postal_code', 12)->default('302039');
            $table->string('seller_phone', 30)->default('+919887776777');
            $table->string('seller_email', 100)->default('info@dhansree.com');

            // Buyer / Customer details
            $table->string('customer_name', 150);
            $table->string('business_name', 150)->nullable();
            $table->string('customer_gstin', 20)->nullable()->index();
            $table->string('customer_pan', 20)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->string('customer_email', 100)->nullable();

            // Billing address
            $table->string('billing_line1', 255);
            $table->string('billing_line2', 255)->nullable();
            $table->string('billing_city', 100);
            $table->char('billing_state_code', 2);
            $table->string('billing_state_name', 100);
            $table->string('billing_postal_code', 12);
            $table->string('billing_country', 50)->default('India');

            // Shipping address
            $table->boolean('shipping_same_as_billing')->default(true);
            $table->string('shipping_recipient_name', 150)->nullable();
            $table->string('shipping_line1', 255)->nullable();
            $table->string('shipping_line2', 255)->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->char('shipping_state_code', 2)->nullable();
            $table->string('shipping_state_name', 100)->nullable();
            $table->string('shipping_postal_code', 12)->nullable();
            $table->string('shipping_country', 50)->default('India');

            // Tax & Supply type
            $table->char('place_of_supply_state_code', 2);
            $table->string('place_of_supply_state_name', 100);
            $table->string('supply_type', 30)->default('INTRA_STATE'); // INTRA_STATE, INTER_STATE
            $table->boolean('reverse_charge')->default(false);

            // Calculations in paise
            $table->unsignedBigInteger('subtotal_taxable_paise')->default(0);
            $table->unsignedBigInteger('total_discount_paise')->default(0);
            $table->unsignedBigInteger('shipping_charges_paise')->default(0);
            $table->unsignedSmallInteger('shipping_gst_rate_bps')->default(0);
            $table->unsignedBigInteger('packaging_charges_paise')->default(0);
            $table->unsignedBigInteger('cgst_paise')->default(0);
            $table->unsignedBigInteger('sgst_paise')->default(0);
            $table->unsignedBigInteger('igst_paise')->default(0);
            $table->unsignedBigInteger('total_tax_paise')->default(0);
            $table->bigInteger('round_off_paise')->default(0);
            $table->unsignedBigInteger('grand_total_paise')->default(0);

            // Bank details for wholesale customer payment transfer
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_ifsc_code', 30)->nullable();
            $table->string('bank_branch', 100)->nullable();
            $table->string('bank_upi_id', 100)->nullable();

            // Legal & Disclaimers
            $table->text('customer_notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('disclaimer')->nullable();

            $table->unsignedBigInteger('created_by_admin_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('bulk_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_invoice_id')->constrained('bulk_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->string('hsn_code', 20)->default('6204');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit', 20)->default('PCS');
            $table->unsignedBigInteger('unit_price_paise'); // Wholesale price per unit
            $table->unsignedBigInteger('discount_paise')->default(0);
            $table->unsignedBigInteger('taxable_amount_paise');
            $table->unsignedSmallInteger('gst_rate_bps')->default(500); // 500 = 5%, 1200 = 12%, 1800 = 18%
            $table->unsignedSmallInteger('cgst_rate_bps')->default(0);
            $table->unsignedBigInteger('cgst_amount_paise')->default(0);
            $table->unsignedSmallInteger('sgst_rate_bps')->default(0);
            $table->unsignedBigInteger('sgst_amount_paise')->default(0);
            $table->unsignedSmallInteger('igst_rate_bps')->default(0);
            $table->unsignedBigInteger('igst_amount_paise')->default(0);
            $table->unsignedBigInteger('total_tax_paise')->default(0);
            $table->unsignedBigInteger('line_total_paise');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_invoice_items');
        Schema::dropIfExists('bulk_invoices');
    }
};
