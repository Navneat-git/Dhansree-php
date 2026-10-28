<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\StoreSettings;
use App\Support\Rbac\Permissions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

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

        $this->ensureStoreSettingsColumns();

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

        $this->ensureStoreSettingsColumns();

        $settings = StoreSettings::firstOrCreate(['id' => 1]);

        $section = $request->input('section', 'all');

        try {
            if ($section === 'general' || $section === 'all') {
                $settings->store_name = $request->input('store_name', $settings->store_name);
                $settings->support_email = $request->input('support_email', $settings->support_email);
                $settings->support_phone = $request->input('support_phone', $settings->support_phone);
                $settings->order_notification_email = $request->input('order_notification_email', $settings->order_notification_email);
                if ($request->has('featured_coupon_code') && Schema::hasColumn('store_settings', 'featured_coupon_code')) {
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

            if ($section === 'payments_shipping' || $section === 'shipping_payments' || $section === 'all') {
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
                $cleanUrl = function (?string $val): ?string {
                    $v = trim((string) $val);
                    return $v !== '' ? $v : null;
                };

                if ($request->has('instagram_url')) {
                    $settings->instagram_url = $cleanUrl($request->input('instagram_url'));
                }
                if ($request->has('facebook_url')) {
                    $settings->facebook_url = $cleanUrl($request->input('facebook_url'));
                }
                if ($request->has('youtube_url')) {
                    $settings->youtube_url = $cleanUrl($request->input('youtube_url'));
                }
                if ($request->has('pinterest_url')) {
                    $settings->pinterest_url = $cleanUrl($request->input('pinterest_url'));
                }
                if ($request->has('x_url')) {
                    $settings->x_url = $cleanUrl($request->input('x_url'));
                }
                $settings->seo_home_title = $request->has('seo_home_title') ? $request->input('seo_home_title') : $settings->seo_home_title;
                $settings->seo_home_description = $request->has('seo_home_description') ? $request->input('seo_home_description') : $settings->seo_home_description;

                if ($request->has('seo_google_verification') && Schema::hasColumn('store_settings', 'seo_google_verification')) {
                    $val = trim((string) $request->input('seo_google_verification'));
                    $settings->seo_google_verification = $val !== '' ? $val : null;
                }

                if ($request->has('analytics_google_measurement_id') && Schema::hasColumn('store_settings', 'analytics_google_measurement_id')) {
                    $val = strtoupper(trim((string) $request->input('analytics_google_measurement_id')));
                    $settings->analytics_google_measurement_id = $val !== '' ? $val : null;
                }

                if ($request->has('google_ads_id') && Schema::hasColumn('store_settings', 'google_ads_id')) {
                    $raw = trim((string) $request->input('google_ads_id'));
                    if ($raw !== '') {
                        if (preg_match('/(AW-\d+)/i', $raw, $m)) {
                            $settings->google_ads_id = strtoupper(trim($m[1]));
                        } else {
                            $settings->google_ads_id = strtoupper(trim(strip_tags($raw)));
                        }
                    } else {
                        $settings->google_ads_id = null;
                    }
                }

                if ($request->has('gtm_container_id') && Schema::hasColumn('store_settings', 'gtm_container_id')) {
                    $val = strtoupper(trim((string) $request->input('gtm_container_id')));
                    $settings->gtm_container_id = $val !== '' ? $val : null;
                }

                if ($request->has('ads_meta_pixel_id') && Schema::hasColumn('store_settings', 'ads_meta_pixel_id')) {
                    $raw = trim((string) $request->input('ads_meta_pixel_id'));
                    if ($raw !== '') {
                        if (preg_match('/fbq\s*\(\s*[\'"]init[\'"]\s*,\s*[\'"](\d+)[\'"]\s*\)/i', $raw, $m)) {
                            $settings->ads_meta_pixel_id = trim($m[1]);
                        } elseif (preg_match('/id=(\d+)/i', $raw, $m)) {
                            $settings->ads_meta_pixel_id = trim($m[1]);
                        } elseif (preg_match('/\b\d{14,18}\b/', $raw, $m)) {
                            $settings->ads_meta_pixel_id = trim($m[0]);
                        } else {
                            $settings->ads_meta_pixel_id = trim(strip_tags($raw));
                        }
                    } else {
                        $settings->ads_meta_pixel_id = null;
                    }
                }

                if ($request->has('facebook_domain_verification') && Schema::hasColumn('store_settings', 'facebook_domain_verification')) {
                    $raw = trim((string) $request->input('facebook_domain_verification'));
                    if ($raw !== '') {
                        if (preg_match('/content=[\'"]([^\'"]+)[\'"]/i', $raw, $matches)) {
                            $settings->facebook_domain_verification = trim($matches[1]);
                        } else {
                            $settings->facebook_domain_verification = trim(strip_tags($raw));
                        }
                    } else {
                        $settings->facebook_domain_verification = null;
                    }
                }
            }

            $settings->save();

            // Map active tab for UI
            $tabName = match ($section) {
                'payments_shipping' => 'shipping_payments',
                default => $section,
            };

            return back()
                ->with('success', 'Store settings updated successfully.')
                ->with('active_tab', $tabName);
        } catch (Throwable $e) {
            Log::error('Failed to update store settings: ' . $e->getMessage(), ['exception' => $e]);

            return back()
                ->with('error', 'Failed to update store settings: ' . $e->getMessage())
                ->withInput()
                ->with('active_tab', $section);
        }
    }

    /**
     * Ensure tracking, verification and SEO columns exist in store_settings table.
     */
    private function ensureStoreSettingsColumns(): void
    {
        try {
            $table = 'store_settings';
            $columns = [
                'seo_google_verification' => fn(Blueprint $t) => $t->string('seo_google_verification', 100)->nullable(),
                'analytics_google_measurement_id' => fn(Blueprint $t) => $t->string('analytics_google_measurement_id', 40)->nullable(),
                'google_ads_id' => fn(Blueprint $t) => $t->string('google_ads_id', 64)->nullable()->default('AW-18539949937'),
                'gtm_container_id' => fn(Blueprint $t) => $t->string('gtm_container_id', 64)->nullable(),
                'ads_meta_pixel_id' => fn(Blueprint $t) => $t->string('ads_meta_pixel_id', 64)->nullable(),
                'facebook_domain_verification' => fn(Blueprint $t) => $t->string('facebook_domain_verification', 100)->nullable()->default('jaur6cwxsewazavl4qh5goy7ylgewz'),
                'featured_coupon_code' => fn(Blueprint $t) => $t->string('featured_coupon_code', 64)->nullable()->default('FESTIVE10'),
            ];

            $missing = [];
            foreach ($columns as $column => $definition) {
                if (!Schema::hasColumn($table, $column)) {
                    $missing[$column] = $definition;
                }
            }

            if (!empty($missing)) {
                Schema::table($table, function (Blueprint $t) use ($missing) {
                    foreach ($missing as $col => $def) {
                        $def($t);
                    }
                });
            }
        } catch (Throwable $e) {
            Log::warning('Could not auto-add missing columns to store_settings: ' . $e->getMessage());
        }
    }
}
