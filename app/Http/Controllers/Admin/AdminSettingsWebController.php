<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\StoreSettings;
use App\Support\Rbac\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSettingsWebController extends Controller
{
    /**
     * Store settings dashboard.
     */
    public function index(): View
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_VIEW)) {
            abort(403, 'You do not have permission to view store settings.');
        }

        $settings = StoreSettings::findOr(1, fn() => StoreSettings::first() ?? new StoreSettings(['id' => 1]));

        return view('admin.settings.index', [
            'settings' => $settings,
            'canEdit' => $admin->hasPermission(Permissions::SETTINGS_UPDATE),
        ]);
    }

    /**
     * Update store settings.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPermission(Permissions::SETTINGS_UPDATE)) {
            abort(403, 'You do not have permission to update store settings.');
        }

        $settings = StoreSettings::firstOrCreate(['id' => 1]);

        $section = $request->input('section', 'all');

        if ($section === 'general' || $section === 'all') {
            $settings->store_name = $request->input('store_name', $settings->store_name);
            $settings->support_email = $request->input('support_email', $settings->support_email);
            $settings->support_phone = $request->input('support_phone', $settings->support_phone);
            $settings->order_notification_email = $request->input('order_notification_email', $settings->order_notification_email);
            if ($request->has('featured_coupon_code')) {
                $settings->featured_coupon_code = strtoupper(trim((string) $request->input('featured_coupon_code')));
            }
        }

        if ($section === 'business' || $section === 'all') {
            $settings->legal_name = $request->input('legal_name', $settings->legal_name);
            $settings->gstin = $request->input('gstin', $settings->gstin);
            $settings->gst_registration_type = $request->input('gst_registration_type', $settings->gst_registration_type);
            $settings->address_line1 = $request->input('address_line1', $settings->address_line1);
            $settings->address_line2 = $request->input('address_line2', $settings->address_line2);
            $settings->city = $request->input('city', $settings->city);
            $settings->state_code = $request->input('state_code', $settings->state_code);
            $settings->postal_code = $request->input('postal_code', $settings->postal_code);
            if ($request->has('default_gst_rate_bps')) {
                $settings->default_gst_rate_bps = (int) $request->input('default_gst_rate_bps');
            }
            $settings->prices_include_tax = $request->boolean('prices_include_tax');
        }

        if ($section === 'whatsapp' || $section === 'all') {
            $settings->whatsapp_enabled = $request->boolean('whatsapp_enabled');
            $settings->whatsapp_phone = $request->input('whatsapp_phone', $settings->whatsapp_phone);
            $settings->whatsapp_default_message = $request->input('whatsapp_default_message', $settings->whatsapp_default_message);
            $settings->whatsapp_button_label = $request->input('whatsapp_button_label', $settings->whatsapp_button_label);
        }

        if ($section === 'payments_shipping' || $section === 'all') {
            $settings->razorpay_enabled = $request->boolean('razorpay_enabled');
            $settings->cod_enabled = $request->boolean('cod_enabled');
            if ($request->has('free_shipping_threshold_rupees')) {
                $settings->free_shipping_threshold_paise = (int) round(((float) $request->input('free_shipping_threshold_rupees')) * 100);
            }
            if ($request->has('shipping_flat_rate_rupees')) {
                $settings->shipping_flat_rate_paise = (int) round(((float) $request->input('shipping_flat_rate_rupees')) * 100);
            }
            $settings->shiprocket_enabled = $request->boolean('shiprocket_enabled');
            $settings->shiprocket_pickup_location = $request->input('shiprocket_pickup_location', $settings->shiprocket_pickup_location);
        }

        if ($section === 'social_seo' || $section === 'all') {
            $settings->instagram_url = $request->input('instagram_url', $settings->instagram_url);
            $settings->facebook_url = $request->input('facebook_url', $settings->facebook_url);
            $settings->youtube_url = $request->input('youtube_url', $settings->youtube_url);
            $settings->pinterest_url = $request->input('pinterest_url', $settings->pinterest_url);
            $settings->x_url = $request->input('x_url', $settings->x_url);
            $settings->seo_home_title = $request->input('seo_home_title', $settings->seo_home_title);
            $settings->seo_home_description = $request->input('seo_home_description', $settings->seo_home_description);
            $settings->seo_google_verification = $request->input('seo_google_verification', $settings->seo_google_verification);
            $settings->analytics_google_measurement_id = $request->input('analytics_google_measurement_id', $settings->analytics_google_measurement_id);
        }

        $settings->save();

        return back()->with('success', 'Store settings updated successfully.');
    }
}
