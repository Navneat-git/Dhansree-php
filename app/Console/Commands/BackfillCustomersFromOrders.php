<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AddressLabel;
use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderAddress;
use Illuminate\Console\Command;

class BackfillCustomersFromOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:backfill-from-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill customer records from past guest orders where customer_id is null';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $orders = Order::whereNull('customer_id')->orderBy('placed_at', 'asc')->get();

        if ($orders->isEmpty()) {
            $this->info('No orders with null customer_id found. All orders are linked to customer accounts!');
            return Command::SUCCESS;
        }

        $this->info("Found {$orders->count()} order(s) without customer account. Processing...");

        $createdCount = 0;
        $linkedCount = 0;

        foreach ($orders as $order) {
            $email = strtolower(trim((string) $order->customer_email));
            $phone = trim((string) $order->customer_phone);
            $name = trim((string) $order->customer_name);

            if (!$email) {
                $this->warn("Order #{$order->order_number} has no customer email. Skipping.");
                continue;
            }

            // Find existing customer by email or phone
            $customer = Customer::where('email', $email)->first();
            if (!$customer && $phone) {
                $customer = Customer::where('phone', $phone)->first();
            }

            if (!$customer) {
                $customer = Customer::create([
                    'name' => $name ?: 'Customer',
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'status' => CustomerStatus::ACTIVE,
                    'email_verified_at' => null,
                ]);
                $createdCount++;
                $this->line("  ✓ Created customer account: {$customer->name} ({$customer->email})");
            } else {
                $updates = [];
                if (empty($customer->phone) && $phone) {
                    $updates['phone'] = $phone;
                }
                if ((empty($customer->name) || $customer->name === 'Customer') && $name) {
                    $updates['name'] = $name;
                }
                if (!empty($updates)) {
                    $customer->update($updates);
                }
            }

            // Link order to customer
            $order->update(['customer_id' => $customer->id]);
            $linkedCount++;

            // Check if customer has addresses; if not, create from order's shipping address
            if ($customer->addresses()->count() === 0) {
                $shipping = OrderAddress::where('order_id', $order->id)
                    ->where('type', 'SHIPPING')
                    ->first();

                if ($shipping) {
                    try {
                        CustomerAddress::create([
                            'customer_id' => $customer->id,
                            'label' => AddressLabel::HOME,
                            'recipient_name' => $shipping->recipient_name ?: $customer->name,
                            'phone' => $shipping->phone ?: $customer->phone,
                            'line1' => $shipping->line1,
                            'line2' => $shipping->line2,
                            'landmark' => $shipping->landmark,
                            'city' => $shipping->city,
                            'state_code' => $shipping->state_code,
                            'postal_code' => $shipping->postal_code,
                            'country' => $shipping->country ?: 'IN',
                            'is_default_shipping' => true,
                            'is_default_billing' => true,
                        ]);
                        $this->line("    + Created default address for customer #{$customer->id}");
                    } catch (\Throwable $e) {
                        $this->warn("    ! Could not create address: {$e->getMessage()}");
                    }
                }
            }
        }

        $this->info("Completed: {$createdCount} customer(s) created, {$linkedCount} order(s) linked to customer accounts.");
        return Command::SUCCESS;
    }
}
