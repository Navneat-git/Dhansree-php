<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SocialCommerce;

use App\Http\Controllers\Controller;
use App\Models\SocialSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialCommerceSettingsController extends Controller
{
    /**
     * Display settings form.
     */
    public function index(): View
    {
        $settings = SocialSetting::allKeyed();

        return view('admin.social-commerce.settings.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Save settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'brand_name' => 'required|string|max:100',
            'tagline' => 'required|string|max:150',
            'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'gold_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'cream_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'whatsapp_number' => 'required|string|max:30',
            'whatsapp_order_cta' => 'required|string|max:100',
            'default_hashtags' => 'nullable|string',
            'meta_app_id' => 'nullable|string|max:100',
            'meta_app_secret' => 'nullable|string|max:100',
            'meta_api_version' => 'nullable|string|max:20',
            'meta_mock_mode' => 'nullable|boolean',
        ]);

        $fields = [
            'brand_name',
            'tagline',
            'primary_color',
            'gold_color',
            'cream_color',
            'whatsapp_number',
            'whatsapp_order_cta',
            'default_hashtags',
            'meta_app_id',
            'meta_app_secret',
            'meta_api_version',
            'meta_mock_mode',
        ];

        foreach ($fields as $field) {
            if ($field === 'meta_mock_mode') {
                SocialSetting::set($field, $request->boolean('meta_mock_mode') ? '1' : '0');
            } elseif ($request->has($field)) {
                SocialSetting::set($field, $request->input($field));
            }
        }

        return redirect()->back()->with('success', 'Social Commerce Studio settings updated successfully.');
    }
}
