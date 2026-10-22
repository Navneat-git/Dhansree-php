<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\Enums\AddressLabel;
use App\Enums\AuditActorType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductImage;
use App\Models\Review;
use App\Support\Audit\AuditLogger;
use App\Support\Commerce\IndianStates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerAccountService
{
    public const MAX_ADDRESS_LIMIT = 10;

    /**
     * Customer dashboard overview stats and recent activity.
     *
     * @return array{customer: array, orderCount: int, addressCount: int, reviewCount: int, awaitingReview: int, recentOrders: array}
     */
    public function dashboard(Customer $customer): array
    {
        $orderCount = Order::where('customer_id', $customer->id)->count();
        $addressCount = CustomerAddress::where('customer_id', $customer->id)->count();
        $reviewCount = Review::where('customer_id', $customer->id)->count();

        // Items delivered but not reviewed yet
        $awaitingReview = OrderItem::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id)
                ->where('status', OrderStatus::DELIVERED)
                ->where('payment_status', '!=', PaymentStatus::REFUNDED);
        })->whereDoesntHave('review')->count();

        $recentOrders = $this->listOrders($customer->id, page: 1, perPage: 5);

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'created_at' => $customer->created_at?->toIso8601String(),
            ],
            'orderCount' => $orderCount,
            'addressCount' => $addressCount,
            'reviewCount' => $reviewCount,
            'awaitingReview' => $awaitingReview,
            'recentOrders' => $recentOrders['items'],
        ];
    }

    /**
     * Update customer profile name and phone.
     */
    public function updateProfile(Customer $customer, array $data, ?Request $request = null): Customer
    {
        $name = trim($data['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) {
            throw ValidationException::withMessages(['name' => ['Name must be between 1 and 100 characters.']]);
        }

        $phone = isset($data['phone']) ? trim($data['phone']) : null;
        if ($phone !== null && $phone !== '') {
            $phone = $this->normalizePhone($phone);
            if (!preg_match('/^\+91[6-9][0-9]{9}$/', $phone)) {
                throw ValidationException::withMessages(['phone' => ['Mobile number must be a valid 10-digit Indian number (+91).']]);
            }

            if ($phone !== $customer->phone) {
                $taken = Customer::where('phone', $phone)->where('id', '!=', $customer->id)->exists();
                if ($taken) {
                    throw new ApiException('PHONE_IN_USE', 'We could not save that mobile number. Please check it or use another.', 409);
                }
            }
        } else {
            $phone = null;
        }

        $nameChanged = $customer->name !== $name;
        $phoneChanged = $customer->phone !== $phone;

        $customer->update([
            'name' => $name,
            'phone' => $phone,
        ]);

        AuditLogger::log(
            action: 'CUSTOMER_PROFILE_UPDATED',
            actorType: AuditActorType::CUSTOMER,
            actorCustomerId: $customer->id,
            entityType: 'customer',
            entityId: $customer->id,
            metadata: [
                'nameChanged' => $nameChanged,
                'phoneChanged' => $phoneChanged,
            ],
            request: $request
        );

        return $customer;
    }

    /**
     * Customer order history.
     */
    public function listOrders(int $customerId, int $page = 1, int $perPage = 10): array
    {
        $query = Order::where('customer_id', $customerId)
            ->with(['items', 'shipments' => function ($q) {
                $q->latest('id')->limit(1);
            }])
            ->orderBy('placed_at', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Fetch primary images for all items
        $productIds = collect($paginator->items())
            ->flatMap(fn (Order $order) => $order->items->pluck('product_id'))
            ->unique()
            ->values()
            ->all();

        $images = $this->getPrimaryImages($productIds);

        $items = collect($paginator->items())->map(function (Order $order) use ($images) {
            $latestShipment = $order->shipments->first();
            return [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'placedAt' => $order->placed_at?->toIso8601String(),
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'paymentStatus' => $order->payment_status->value,
                'paymentMethod' => $order->payment_method->value,
                'itemCount' => $order->item_count,
                'totalPaise' => $order->total_paise,
                'currency' => $order->currency,
                'shipmentStatus' => $latestShipment?->status?->value ?? null,
                'items' => $order->items->take(4)->map(fn (OrderItem $item) => [
                    'productName' => $item->product_name,
                    'variantTitle' => $item->variant_title,
                    'quantity' => $item->quantity,
                    'imageUrl' => $images[$item->product_id] ?? null,
                ])->all(),
            ];
        })->all();

        return [
            'items' => $items,
            'paginator' => $paginator,
            'totalItems' => $paginator->total(),
            'totalPages' => $paginator->lastPage(),
            'currentPage' => $paginator->currentPage(),
        ];
    }

    /**
     * Customer single order detail.
     */
    public function orderDetail(int $customerId, string $orderNumber): Order
    {
        $order = Order::where('customer_id', $customerId)
            ->where('order_number', $orderNumber)
            ->with(['items.review', 'shippingAddress', 'billingAddress', 'shipments', 'payments'])
            ->first();

        if (!$order) {
            throw new ApiException('NOT_FOUND', 'We could not find that order.', 404);
        }

        return $order;
    }

    /**
     * List saved addresses for customer.
     */
    public function listAddresses(int $customerId): array
    {
        $addresses = CustomerAddress::where('customer_id', $customerId)
            ->orderBy('is_default_shipping', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $addresses->map(fn (CustomerAddress $addr) => $this->formatAddressView($addr))->all();
    }

    /**
     * Create customer address.
     */
    public function createAddress(int $customerId, array $data, ?Request $request = null): CustomerAddress
    {
        $count = CustomerAddress::where('customer_id', $customerId)->count();
        if ($count >= self::MAX_ADDRESS_LIMIT) {
            throw new ApiException(
                'ADDRESS_LIMIT_REACHED',
                'You can save up to ' . self::MAX_ADDRESS_LIMIT . ' addresses. Delete one to add another.',
                409
            );
        }

        $validated = $this->validateAddressData($data);
        $first = ($count === 0);

        $isDefaultShipping = $first || !empty($data['is_default_shipping']);
        $isDefaultBilling = $first || !empty($data['is_default_billing']);

        return DB::transaction(function () use ($customerId, $validated, $isDefaultShipping, $isDefaultBilling, $request) {
            $address = CustomerAddress::create([
                'customer_id' => $customerId,
                'label' => $validated['label'],
                'recipient_name' => $validated['recipient_name'],
                'phone' => $validated['phone'],
                'line1' => $validated['line1'],
                'line2' => $validated['line2'],
                'landmark' => $validated['landmark'],
                'city' => $validated['city'],
                'state_code' => $validated['state_code'],
                'postal_code' => $validated['postal_code'],
                'country' => 'IN',
                'is_default_shipping' => $isDefaultShipping,
                'is_default_billing' => $isDefaultBilling,
            ]);

            $this->applyDefaultRules($customerId, $address->id, $isDefaultShipping, $isDefaultBilling);

            AuditLogger::log(
                action: 'CUSTOMER_PROFILE_UPDATED',
                actorType: AuditActorType::CUSTOMER,
                actorCustomerId: $customerId,
                entityType: 'customer_address',
                entityId: $address->id,
                metadata: ['change' => 'address_added'],
                request: $request
            );

            return $address;
        });
    }

    /**
     * Update customer address.
     */
    public function updateAddress(int $customerId, int $addressId, array $data, ?Request $request = null): CustomerAddress
    {
        $address = CustomerAddress::where('customer_id', $customerId)->where('id', $addressId)->first();
        if (!$address) {
            throw new ApiException('NOT_FOUND', 'We could not find that address.', 404);
        }

        $validated = $this->validateAddressData($data);
        $isDefaultShipping = !empty($data['is_default_shipping']);
        $isDefaultBilling = !empty($data['is_default_billing']);

        return DB::transaction(function () use ($customerId, $address, $validated, $isDefaultShipping, $isDefaultBilling, $request) {
            $address->update([
                'label' => $validated['label'],
                'recipient_name' => $validated['recipient_name'],
                'phone' => $validated['phone'],
                'line1' => $validated['line1'],
                'line2' => $validated['line2'],
                'landmark' => $validated['landmark'],
                'city' => $validated['city'],
                'state_code' => $validated['state_code'],
                'postal_code' => $validated['postal_code'],
                'is_default_shipping' => $isDefaultShipping,
                'is_default_billing' => $isDefaultBilling,
            ]);

            $this->applyDefaultRules($customerId, $address->id, $isDefaultShipping, $isDefaultBilling);

            AuditLogger::log(
                action: 'CUSTOMER_PROFILE_UPDATED',
                actorType: AuditActorType::CUSTOMER,
                actorCustomerId: $customerId,
                entityType: 'customer_address',
                entityId: $address->id,
                metadata: ['change' => 'address_updated'],
                request: $request
            );

            return $address;
        });
    }

    /**
     * Delete customer address.
     */
    public function deleteAddress(int $customerId, int $addressId, ?Request $request = null): void
    {
        $address = CustomerAddress::where('customer_id', $customerId)->where('id', $addressId)->first();
        if (!$address) {
            throw new ApiException('NOT_FOUND', 'We could not find that address.', 404);
        }

        DB::transaction(function () use ($customerId, $address, $request) {
            $wasDefaultShipping = $address->is_default_shipping;
            $wasDefaultBilling = $address->is_default_billing;
            $address->delete();

            // Re-assign default if needed
            if ($wasDefaultShipping) {
                $latest = CustomerAddress::where('customer_id', $customerId)->orderBy('updated_at', 'desc')->first();
                if ($latest) {
                    $latest->update(['is_default_shipping' => true]);
                }
            }
            if ($wasDefaultBilling) {
                $latest = CustomerAddress::where('customer_id', $customerId)->orderBy('updated_at', 'desc')->first();
                if ($latest) {
                    $latest->update(['is_default_billing' => true]);
                }
            }

            AuditLogger::log(
                action: 'CUSTOMER_PROFILE_UPDATED',
                actorType: AuditActorType::CUSTOMER,
                actorCustomerId: $customerId,
                entityType: 'customer_address',
                entityId: $address->id,
                metadata: ['change' => 'address_deleted'],
                request: $request
            );
        });
    }

    private function applyDefaultRules(int $customerId, int $addressId, bool $isDefaultShipping, bool $isDefaultBilling): void
    {
        if ($isDefaultShipping) {
            CustomerAddress::where('customer_id', $customerId)
                ->where('id', '!=', $addressId)
                ->where('is_default_shipping', true)
                ->update(['is_default_shipping' => false]);
        }
        if ($isDefaultBilling) {
            CustomerAddress::where('customer_id', $customerId)
                ->where('id', '!=', $addressId)
                ->where('is_default_billing', true)
                ->update(['is_default_billing' => false]);
        }
    }

    private function validateAddressData(array $data): array
    {
        $label = strtoupper(trim($data['label'] ?? 'HOME'));
        if (!in_array($label, AddressLabel::values(), true)) {
            $label = AddressLabel::HOME->value;
        }

        $recipientName = trim($data['recipient_name'] ?? '');
        if ($recipientName === '' || mb_strlen($recipientName) > 100) {
            throw ValidationException::withMessages(['recipient_name' => ['Recipient name is required (up to 100 characters).']]);
        }

        $phone = $this->normalizePhone($data['phone'] ?? '');
        if (!preg_match('/^\+91[6-9][0-9]{9}$/', $phone)) {
            throw ValidationException::withMessages(['phone' => ['Mobile number must be a valid 10-digit Indian number (+91).']]);
        }

        $line1 = trim($data['line1'] ?? '');
        if ($line1 === '' || mb_strlen($line1) > 255) {
            throw ValidationException::withMessages(['line1' => ['Address line 1 is required (up to 255 characters).']]);
        }

        $line2 = isset($data['line2']) && trim($data['line2']) !== '' ? trim($data['line2']) : null;
        if ($line2 !== null && mb_strlen($line2) > 255) {
            throw ValidationException::withMessages(['line2' => ['Address line 2 must not exceed 255 characters.']]);
        }

        $landmark = isset($data['landmark']) && trim($data['landmark']) !== '' ? trim($data['landmark']) : null;
        if ($landmark !== null && mb_strlen($landmark) > 100) {
            throw ValidationException::withMessages(['landmark' => ['Landmark must not exceed 100 characters.']]);
        }

        $city = trim($data['city'] ?? '');
        if ($city === '' || mb_strlen($city) > 100) {
            throw ValidationException::withMessages(['city' => ['City is required (up to 100 characters).']]);
        }

        $stateCode = trim($data['state_code'] ?? '');
        if (!preg_match('/^[0-9]{2}$/', $stateCode) || !IndianStates::isValid($stateCode)) {
            throw ValidationException::withMessages(['state_code' => ['Please select a valid Indian State.']]);
        }

        $postalCode = trim($data['postal_code'] ?? '');
        if (!preg_match('/^[1-9][0-9]{5}$/', $postalCode)) {
            throw ValidationException::withMessages(['postal_code' => ['Please enter a valid 6-digit Indian PIN code.']]);
        }

        return [
            'label' => $label,
            'recipient_name' => $recipientName,
            'phone' => $phone,
            'line1' => $line1,
            'line2' => $line2,
            'landmark' => $landmark,
            'city' => $city,
            'state_code' => $stateCode,
            'postal_code' => $postalCode,
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            return '+' . $digits;
        }
        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }
        return (str_starts_with($phone, '+') ? '' : '+') . $phone;
    }

    public function formatAddressView(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label?->value ?? $address->label,
            'recipientName' => $address->recipient_name,
            'phone' => $address->phone,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'landmark' => $address->landmark,
            'city' => $address->city,
            'stateCode' => $address->state_code,
            'stateName' => IndianStates::getName($address->state_code) ?? $address->state_code,
            'postalCode' => $address->postal_code,
            'country' => $address->country ?? 'IN',
            'isDefaultShipping' => (bool) $address->is_default_shipping,
            'isDefaultBilling' => (bool) $address->is_default_billing,
            'createdAt' => $address->created_at?->toIso8601String(),
            'updatedAt' => $address->updated_at?->toIso8601String(),
        ];
    }

    private function getPrimaryImages(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $images = ProductImage::whereIn('product_id', $productIds)
            ->where('position', 0)
            ->get();

        $map = [];
        foreach ($images as $img) {
            $map[$img->product_id] = '/storage/' . ltrim($img->storage_key, '/');
        }

        return $map;
    }
}
