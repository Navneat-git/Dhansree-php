<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Services\Customer\CustomerAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerAccountController extends Controller
{
    public function __construct(
        private readonly CustomerAccountService $accountService
    ) {}

    /**
     * Customer account overview dashboard.
     */
    public function dashboard(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $data = $this->accountService->dashboard($customer);

        return view('store.account.dashboard', $data);
    }

    /**
     * Customer order history page.
     */
    public function orders(Request $request): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $page = max(1, (int) $request->query('page', 1));
        $data = $this->accountService->listOrders($customer->id, page: $page, perPage: 10);

        return view('store.account.orders', $data);
    }

    /**
     * Customer single order details.
     */
    public function orderDetail(string $orderNumber): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $order = $this->accountService->orderDetail($customer->id, $orderNumber);

        return view('store.account.order-detail', [
            'order' => $order,
        ]);
    }

    /**
     * Customer address book list.
     */
    public function addresses(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $addresses = $this->accountService->listAddresses($customer->id);

        return view('store.account.addresses', [
            'addresses' => $addresses,
            'maxLimit' => CustomerAccountService::MAX_ADDRESS_LIMIT,
        ]);
    }

    /**
     * Save new customer address.
     */
    public function storeAddress(Request $request): JsonResponse|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $address = $this->accountService->createAddress($customer->id, $request->all(), $request);

        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::success($this->accountService->formatAddressView($address), status: 201);
        }

        return redirect()->route('storefront.account.addresses')
            ->with('success', 'Address added successfully.');
    }

    /**
     * Update existing address.
     */
    public function updateAddress(Request $request, int $id): JsonResponse|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $address = $this->accountService->updateAddress($customer->id, $id, $request->all(), $request);

        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::success($this->accountService->formatAddressView($address));
        }

        return redirect()->route('storefront.account.addresses')
            ->with('success', 'Address updated successfully.');
    }

    /**
     * Remove saved address.
     */
    public function deleteAddress(Request $request, int $id): JsonResponse|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $this->accountService->deleteAddress($customer->id, $id, $request);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true], 204);
        }

        return redirect()->route('storefront.account.addresses')
            ->with('success', 'Address deleted successfully.');
    }

    /**
     * Customer profile details view.
     */
    public function profile(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return view('store.account.profile', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update customer profile name & phone.
     */
    public function updateProfile(Request $request): JsonResponse|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $updated = $this->accountService->updateProfile($customer, $request->all(), $request);

        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::success([
                'name' => $updated->name,
                'email' => $updated->email,
                'phone' => $updated->phone,
            ]);
        }

        return redirect()->route('storefront.account.profile')
            ->with('success', 'Profile updated successfully.');
    }
}
