<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\WholesaleColorCode;
use App\Models\WholesalePurchase;
use App\Services\Catalog\ImageProcessor;
use App\Support\Rbac\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AdminWholesalePurchaseWebController extends Controller
{
    public function __construct(
        protected ImageProcessor $imageProcessor
    ) {}

    /**
     * Display tabular spreadsheet view of wholesale purchase entries.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'all'); // 'all', 'women_fashion', 'smart_living'
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $categoryId = $request->query('category_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = WholesalePurchase::with('category')->orderByDesc('id');

        if ($tab === WholesalePurchase::TYPE_WOMEN_FASHION) {
            $query->where('purchase_type', WholesalePurchase::TYPE_WOMEN_FASHION);
        } elseif ($tab === WholesalePurchase::TYPE_SMART_LIVING) {
            $query->where('purchase_type', WholesalePurchase::TYPE_SMART_LIVING);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('design_number', 'like', "%{$search}%")
                  ->orWhere('pcode', 'like', "%{$search}%")
                  ->orWhere('vcode', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if (!empty($dateFrom)) {
            $query->whereDate('purchase_date', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('purchase_date', '<=', $dateTo);
        }

        $purchases = $query->paginate(25)->withQueryString();

        // Metric counts
        $totalCount = WholesalePurchase::count();
        $womenCount = WholesalePurchase::where('purchase_type', WholesalePurchase::TYPE_WOMEN_FASHION)->count();
        $smartCount = WholesalePurchase::where('purchase_type', WholesalePurchase::TYPE_SMART_LIVING)->count();
        $totalStockUnits = WholesalePurchase::where('purchase_type', WholesalePurchase::TYPE_SMART_LIVING)->sum('stock_qty');

        $categories = Category::orderBy('name')->get();
        $colorCodes = WholesaleColorCode::orderBy('sort_order')->orderBy('color_name')->get();

        return view('admin.wholesale.index', [
            'purchases' => $purchases,
            'tab' => $tab,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'category_id' => $categoryId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'totalCount' => $totalCount,
            'womenCount' => $womenCount,
            'smartCount' => $smartCount,
            'totalStockUnits' => $totalStockUnits,
            'categories' => $categories,
            'colorCodes' => $colorCodes,
            'nextSmartLivingSerial' => WholesalePurchase::getNextSmartLivingSerialNumber(),
        ]);
    }

    /**
     * Show form to add a new wholesale purchase entry.
     */
    public function create(Request $request): View
    {
        $type = $request->query('type', WholesalePurchase::TYPE_WOMEN_FASHION);
        $categories = Category::orderBy('name')->get();
        $colorCodes = WholesaleColorCode::orderBy('sort_order')->orderBy('color_name')->get();
        $nextSmartLivingSerial = WholesalePurchase::getNextSmartLivingSerialNumber();

        return view('admin.wholesale.create', [
            'type' => $type,
            'categories' => $categories,
            'colorCodes' => $colorCodes,
            'nextSmartLivingSerial' => $nextSmartLivingSerial,
        ]);
    }

    /**
     * Get next Smart Living serial number JSON.
     */
    public function nextSerial(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'next_serial' => WholesalePurchase::getNextSmartLivingSerialNumber(),
        ]);
    }

    /**
     * Store new wholesale purchase.
     */
    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('purchase_type', WholesalePurchase::TYPE_WOMEN_FASHION);

        if ($type === WholesalePurchase::TYPE_SMART_LIVING) {
            $data = $request->validate([
                'purchase_type' => 'required|in:smart_living,women_fashion',
                'sku' => 'required|string|max:120',
                'product_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category_id' => 'nullable|integer',
                'cost_price' => 'nullable|numeric|min:0',
                'markup' => 'nullable|numeric|min:0',
                'retail_markup' => 'nullable|numeric|min:0',
                'stock_qty' => 'nullable|integer|min:0',
                'moq' => 'nullable|string|max:50',
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'badge' => 'nullable|string|max:50',
                'status' => 'required|string|max:50',
                'supplier' => 'nullable|string|max:150',
                'purchase_date' => 'nullable|date',
                'image' => 'nullable|file|max:10240',
                'image_url' => 'nullable|string|max:1000',
                'notes' => 'nullable|string',
            ]);

            // Auto-complete Smart Living SKU with serial if user typed base SKU (e.g. DS-JWL-BOX -> DS-JWL-BOX-062)
            $data['sku'] = WholesalePurchase::completeSmartLivingSku($data['sku']);

            // Auto calculate wholesale & retail price
            $costPrice = (float) ($data['cost_price'] ?? 0);
            $markup = (float) ($data['markup'] ?? 1);
            $retailMarkup = (float) ($data['retail_markup'] ?? 1);

            $prices = WholesalePurchase::computeSmartLivingPrices($costPrice, $markup, $retailMarkup);
            $data['wholesale_price'] = $prices['wholesale_price'];
            $data['retail_price'] = $prices['retail_price'];

            if (!empty($data['category_id'])) {
                $category = Category::find($data['category_id']);
                $data['category_name'] = $category?->name;
            }
        } else {
            // Women Fashion
            $data = $request->validate([
                'purchase_type' => 'required|in:smart_living,women_fashion',
                'brand_code' => 'nullable|string|max:50',
                'purchase_date' => 'nullable|date',
                'image' => 'nullable|file|max:10240',
                'image_url' => 'nullable|string|max:1000',
                'design_number' => 'nullable|string|max:50',
                'category_name' => 'required|string|max:100',
                'vcode' => 'nullable|string|max:50',
                'category_code' => 'nullable|string|max:20',
                'pcode' => 'nullable|string|max:80',
                'color' => 'nullable|string|max:100',
                'color_code' => 'nullable|string|max:20',
                'size' => 'nullable|string|max:50',
                'sizes' => 'nullable|array',
                'sizes.*' => 'string|max:50',
                'sku' => 'nullable|string|max:120',
                'status' => 'required|string|max:50',
                'product_name' => 'required|string|max:255',
                'thread_count' => 'nullable|string|max:100',
                'cost' => 'nullable|numeric|min:0',
                'gst_percent' => 'nullable|numeric|min:0',
                'markup' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            $brandCode = !empty($data['brand_code']) ? $data['brand_code'] : 'DS';
            $data['brand_code'] = $brandCode;

            // Auto resolve category code if not supplied
            if (empty($data['category_code'])) {
                $data['category_code'] = WholesalePurchase::resolveCategoryCode($data['category_name']);
            }

            // Auto resolve color code from master if empty
            if (empty($data['color_code']) && !empty($data['color'])) {
                $matchedColor = WholesaleColorCode::where('color_name', $data['color'])->first();
                $data['color_code'] = $matchedColor?->color_code;
            }

            // Auto generate pcode
            if (empty($data['pcode']) && !empty($data['vcode'])) {
                $data['pcode'] = WholesalePurchase::generatePcode(
                    $data['vcode'],
                    $data['purchase_date'] ?? null,
                    $data['design_number'] ?? null
                );
            }

            // Auto calculate Total Cost and Selling Price
            $cost = (float) ($data['cost'] ?? 0);
            $gstPercent = (float) ($data['gst_percent'] ?? 5.0);
            $markup = (float) ($data['markup'] ?? 1.0);

            $costs = WholesalePurchase::computeWomenCosts($cost, $gstPercent, $markup);
            $data['gst_amount'] = $costs['gst_amount'];
            $data['total_cost'] = $costs['total_cost'];
            $data['selling_price'] = $costs['selling_price'];
        }

        // Process image upload
        if ($request->hasFile('image')) {
            try {
                $processed = $this->imageProcessor->process($request->file('image'));
                $filename = Str::uuid()->toString() . '.webp';
                $storageKey = 'wholesale/' . date('Y/m') . '/' . $filename;

                Storage::disk('public')->put('media/' . $storageKey, $processed['data']);

                $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
                if ($extraDir) {
                    $targetFile = rtrim($extraDir, '/') . '/' . ltrim($storageKey, '/');
                    @mkdir(dirname($targetFile), 0775, true);
                    @file_put_contents($targetFile, $processed['data']);
                }

                $data['image_path'] = $storageKey;
                $data['image_url'] = url('/media/' . $storageKey);
            } catch (Throwable $e) {
                return back()->withInput()->with('error', 'Image processing failed: ' . $e->getMessage());
            }
        } elseif (!empty($data['image_url'])) {
            $data['image_url'] = trim($data['image_url']);
        }

        $adminUser = $request->attributes->get('admin_user');
        $data['created_by'] = $adminUser?->id;

        // Multi-size bulk creation for Women Fashion
        if ($type === WholesalePurchase::TYPE_WOMEN_FASHION) {
            $sizes = $request->input('sizes', []);
            if (!is_array($sizes) || empty($sizes)) {
                $sizes = !empty($data['size']) ? [$data['size']] : [];
            }
            $sizes = array_values(array_filter(array_map('trim', $sizes)));

            if (count($sizes) > 1) {
                $createdEntries = [];
                unset($data['sizes']);

                foreach ($sizes as $sz) {
                    $itemData = $data;
                    $itemData['size'] = $sz;
                    $itemData['sku'] = WholesalePurchase::generateWomenSku(
                        $data['brand_code'],
                        $data['pcode'] ?? '',
                        $data['category_code'] ?? '',
                        $data['color_code'] ?? '',
                        $sz
                    );
                    $createdEntries[] = WholesalePurchase::create($itemData);
                }

                $sizeList = implode(', ', $sizes);
                return redirect()
                    ->route('admin.wholesale.index', ['tab' => WholesalePurchase::TYPE_WOMEN_FASHION])
                    ->with('success', "Added " . count($createdEntries) . " wholesale entries for sizes ({$sizeList}) with individual SKUs.");
            } elseif (count($sizes) === 1) {
                $data['size'] = $sizes[0];
            }

            if (empty($data['sku'])) {
                $data['sku'] = WholesalePurchase::generateWomenSku(
                    $data['brand_code'],
                    $data['pcode'] ?? '',
                    $data['category_code'] ?? '',
                    $data['color_code'] ?? '',
                    $data['size'] ?? ''
                );
            }
        }

        unset($data['sizes']);
        $purchase = WholesalePurchase::create($data);

        return redirect()
            ->route('admin.wholesale.index', ['tab' => $purchase->purchase_type])
            ->with('success', "Wholesale product '{$purchase->product_name}' (SKU: {$purchase->sku}) saved successfully.");
    }

    /**
     * Show edit form.
     */
    public function edit(WholesalePurchase $purchase): View
    {
        $categories = Category::orderBy('name')->get();
        $colorCodes = WholesaleColorCode::orderBy('sort_order')->orderBy('color_name')->get();

        $nextSmartLivingSerial = WholesalePurchase::getNextSmartLivingSerialNumber();

        return view('admin.wholesale.edit', [
            'purchase' => $purchase,
            'type' => $purchase->purchase_type,
            'categories' => $categories,
            'colorCodes' => $colorCodes,
            'nextSmartLivingSerial' => $nextSmartLivingSerial,
        ]);
    }

    /**
     * Update existing wholesale purchase.
     */
    public function update(Request $request, WholesalePurchase $purchase): RedirectResponse
    {
        $type = $purchase->purchase_type;

        if ($type === WholesalePurchase::TYPE_SMART_LIVING) {
            $data = $request->validate([
                'sku' => 'required|string|max:120',
                'product_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category_id' => 'nullable|integer',
                'cost_price' => 'nullable|numeric|min:0',
                'markup' => 'nullable|numeric|min:0',
                'retail_markup' => 'nullable|numeric|min:0',
                'stock_qty' => 'nullable|integer|min:0',
                'moq' => 'nullable|string|max:50',
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'badge' => 'nullable|string|max:50',
                'status' => 'required|string|max:50',
                'supplier' => 'nullable|string|max:150',
                'purchase_date' => 'nullable|date',
                'image' => 'nullable|file|max:10240',
                'image_url' => 'nullable|string|max:1000',
                'notes' => 'nullable|string',
            ]);

            // Auto-complete SKU if modified without serial
            $data['sku'] = WholesalePurchase::completeSmartLivingSku($data['sku']);

            $costPrice = (float) ($data['cost_price'] ?? 0);
            $markup = (float) ($data['markup'] ?? 1);
            $retailMarkup = (float) ($data['retail_markup'] ?? 1);

            $prices = WholesalePurchase::computeSmartLivingPrices($costPrice, $markup, $retailMarkup);
            $data['wholesale_price'] = $prices['wholesale_price'];
            $data['retail_price'] = $prices['retail_price'];

            if (!empty($data['category_id'])) {
                $category = Category::find($data['category_id']);
                $data['category_name'] = $category?->name;
            }
        } else {
            // Women Fashion
            $data = $request->validate([
                'brand_code' => 'nullable|string|max:50',
                'purchase_date' => 'nullable|date',
                'image' => 'nullable|file|max:10240',
                'image_url' => 'nullable|string|max:1000',
                'design_number' => 'nullable|string|max:50',
                'category_name' => 'required|string|max:100',
                'vcode' => 'nullable|string|max:50',
                'category_code' => 'nullable|string|max:20',
                'pcode' => 'nullable|string|max:80',
                'color' => 'nullable|string|max:100',
                'color_code' => 'nullable|string|max:20',
                'size' => 'nullable|string|max:50',
                'sku' => 'nullable|string|max:120',
                'status' => 'required|string|max:50',
                'product_name' => 'required|string|max:255',
                'thread_count' => 'nullable|string|max:100',
                'cost' => 'nullable|numeric|min:0',
                'gst_percent' => 'nullable|numeric|min:0',
                'markup' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            $brandCode = !empty($data['brand_code']) ? $data['brand_code'] : ($purchase->brand_code ?: 'DS');
            $data['brand_code'] = $brandCode;

            if (empty($data['category_code'])) {
                $data['category_code'] = WholesalePurchase::resolveCategoryCode($data['category_name']);
            }

            if (empty($data['color_code']) && !empty($data['color'])) {
                $matchedColor = WholesaleColorCode::where('color_name', $data['color'])->first();
                $data['color_code'] = $matchedColor?->color_code;
            }

            if (empty($data['pcode']) && !empty($data['vcode'])) {
                $data['pcode'] = WholesalePurchase::generatePcode(
                    $data['vcode'],
                    $data['purchase_date'] ?? null,
                    $data['design_number'] ?? null
                );
            }

            if (empty($data['sku'])) {
                $data['sku'] = WholesalePurchase::generateWomenSku(
                    $data['brand_code'],
                    $data['pcode'] ?? '',
                    $data['category_code'] ?? '',
                    $data['color_code'] ?? '',
                    $data['size'] ?? ''
                );
            }

            $cost = (float) ($data['cost'] ?? 0);
            $gstPercent = (float) ($data['gst_percent'] ?? 5.0);
            $markup = (float) ($data['markup'] ?? 1.0);

            $costs = WholesalePurchase::computeWomenCosts($cost, $gstPercent, $markup);
            $data['gst_amount'] = $costs['gst_amount'];
            $data['total_cost'] = $costs['total_cost'];
            $data['selling_price'] = $costs['selling_price'];
        }

        if ($request->hasFile('image')) {
            try {
                $processed = $this->imageProcessor->process($request->file('image'));
                $filename = Str::uuid()->toString() . '.webp';
                $storageKey = 'wholesale/' . date('Y/m') . '/' . $filename;

                Storage::disk('public')->put('media/' . $storageKey, $processed['data']);

                $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
                if ($extraDir) {
                    $targetFile = rtrim($extraDir, '/') . '/' . ltrim($storageKey, '/');
                    @mkdir(dirname($targetFile), 0775, true);
                    @file_put_contents($targetFile, $processed['data']);
                }

                $data['image_path'] = $storageKey;
                $data['image_url'] = url('/media/' . $storageKey);
            } catch (Throwable $e) {
                return back()->withInput()->with('error', 'Image processing failed: ' . $e->getMessage());
            }
        } elseif ($request->filled('image_url')) {
            $data['image_url'] = trim($request->input('image_url'));
        }

        $purchase->update($data);

        return redirect()
            ->route('admin.wholesale.index', ['tab' => $purchase->purchase_type])
            ->with('success', "Wholesale entry '{$purchase->product_name}' updated successfully.");
    }

    /**
     * Delete a wholesale purchase.
     */
    public function destroy(WholesalePurchase $purchase): RedirectResponse
    {
        $name = $purchase->product_name;
        $type = $purchase->purchase_type;
        $purchase->delete();

        return redirect()
            ->route('admin.wholesale.index', ['tab' => $type])
            ->with('success', "Entry '{$name}' deleted successfully.");
    }

    /**
     * Export entries matching current filters to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $tab = $request->query('tab', 'all');
        $query = WholesalePurchase::with('category')->orderByDesc('id');

        if ($tab === WholesalePurchase::TYPE_WOMEN_FASHION) {
            $query->where('purchase_type', WholesalePurchase::TYPE_WOMEN_FASHION);
            $filename = 'dhansree_wholesale_women_fashion_' . date('Y-m-d') . '.csv';
            $headers = [
                'ID', 'SKU', 'Brand Code', 'Purchase Date', 'Product Title', 'Design Number',
                'Category Name', 'Category Code', 'vcode', 'pcode', 'Color', 'Color Code',
                'Size', 'Thread Count', 'Cost', 'GST %', 'GST Amount', 'Total Cost',
                'Markup', 'Calculated Selling Price', 'Status', 'Image URL'
            ];
        } elseif ($tab === WholesalePurchase::TYPE_SMART_LIVING) {
            $query->where('purchase_type', WholesalePurchase::TYPE_SMART_LIVING);
            $filename = 'dhansree_wholesale_smart_living_' . date('Y-m-d') . '.csv';
            $headers = [
                'ID', 'SKU', 'Product Name', 'Category', 'Wholesale Price', 'MOQ',
                'Stock Qty', 'Brand', 'Model', 'Description', 'Badge', 'Status',
                'Supplier', 'Cost Price', 'Markup', 'Retail Markup', 'Retail Price',
                'Purchase Date', 'Image URL'
            ];
        } else {
            $filename = 'dhansree_wholesale_all_' . date('Y-m-d') . '.csv';
            $headers = [
                'ID', 'Type', 'SKU', 'Product Name', 'Category', 'Cost Price / Cost',
                'Markup', 'Wholesale / Total Cost', 'Selling / Retail Price', 'Stock / Size',
                'Brand', 'Supplier', 'Status', 'Purchase Date', 'Image URL'
            ];
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('design_number', 'like', "%{$search}%")
                  ->orWhere('pcode', 'like', "%{$search}%")
                  ->orWhere('vcode', 'like', "%{$search}%");
            });
        }

        $records = $query->get();

        return response()->streamDownload(function () use ($headers, $records, $tab) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            foreach ($records as $r) {
                if ($tab === WholesalePurchase::TYPE_WOMEN_FASHION) {
                    fputcsv($out, [
                        $r->id,
                        $r->sku,
                        $r->brand_code,
                        $r->purchase_date?->format('Y-m-d') ?? '',
                        $r->product_name,
                        $r->design_number,
                        $r->category_name,
                        $r->category_code,
                        $r->vcode,
                        $r->pcode,
                        $r->color,
                        $r->color_code,
                        $r->size,
                        $r->thread_count,
                        $r->cost,
                        $r->gst_percent,
                        $r->gst_amount,
                        $r->total_cost,
                        $r->markup,
                        $r->selling_price,
                        $r->status,
                        $r->effective_image_url,
                    ]);
                } elseif ($tab === WholesalePurchase::TYPE_SMART_LIVING) {
                    fputcsv($out, [
                        $r->id,
                        $r->sku,
                        $r->product_name,
                        $r->category?->name ?? $r->category_name,
                        $r->wholesale_price,
                        $r->moq,
                        $r->stock_qty,
                        $r->brand,
                        $r->model,
                        $r->description,
                        $r->badge,
                        $r->status,
                        $r->supplier,
                        $r->cost_price,
                        $r->markup,
                        $r->retail_markup,
                        $r->retail_price,
                        $r->purchase_date?->format('Y-m-d') ?? '',
                        $r->effective_image_url,
                    ]);
                } else {
                    fputcsv($out, [
                        $r->id,
                        $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? 'Women Fashion' : 'Smart Living',
                        $r->sku,
                        $r->product_name,
                        $r->category?->name ?? $r->category_name,
                        $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->cost : $r->cost_price,
                        $r->markup,
                        $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->total_cost : $r->wholesale_price,
                        $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->selling_price : $r->retail_price,
                        $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->size : $r->stock_qty,
                        $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->brand_code : $r->brand,
                        $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->vcode : $r->supplier,
                        $r->status,
                        $r->purchase_date?->format('Y-m-d') ?? '',
                        $r->effective_image_url,
                    ]);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Return TSV stream for instant clipboard copying to Google Sheets / Excel.
     */
    public function copyTsv(Request $request): Response
    {
        $tab = $request->query('tab', 'all');
        $query = WholesalePurchase::with('category')->orderByDesc('id');

        if ($tab === WholesalePurchase::TYPE_WOMEN_FASHION) {
            $query->where('purchase_type', WholesalePurchase::TYPE_WOMEN_FASHION);
            $headers = [
                'ID', 'SKU', 'Brand Code', 'Purchase Date', 'Product Title', 'Design Number',
                'Category Name', 'Category Code', 'vcode', 'pcode', 'Color', 'Color Code',
                'Size', 'Thread Count', 'Cost', 'GST %', 'GST Amount', 'Total Cost',
                'Markup', 'Calculated Selling Price', 'Status', 'Image URL'
            ];
        } elseif ($tab === WholesalePurchase::TYPE_SMART_LIVING) {
            $query->where('purchase_type', WholesalePurchase::TYPE_SMART_LIVING);
            $headers = [
                'ID', 'SKU', 'Product Name', 'Category', 'Wholesale Price', 'MOQ',
                'Stock Qty', 'Brand', 'Model', 'Description', 'Badge', 'Status',
                'Supplier', 'Cost Price', 'Markup', 'Retail Markup', 'Retail Price',
                'Purchase Date', 'Image URL'
            ];
        } else {
            $headers = [
                'ID', 'Type', 'SKU', 'Product Name', 'Category', 'Cost Price / Cost',
                'Markup', 'Wholesale / Total Cost', 'Selling / Retail Price', 'Stock / Size',
                'Brand', 'Supplier', 'Status', 'Purchase Date', 'Image URL'
            ];
        }

        $records = $query->get();
        $tsv = implode("\t", $headers) . "\n";

        foreach ($records as $r) {
            if ($tab === WholesalePurchase::TYPE_WOMEN_FASHION) {
                $row = [
                    $r->id,
                    $r->sku,
                    $r->brand_code,
                    $r->purchase_date?->format('Y-m-d') ?? '',
                    $r->product_name,
                    $r->design_number,
                    $r->category_name,
                    $r->category_code,
                    $r->vcode,
                    $r->pcode,
                    $r->color,
                    $r->color_code,
                    $r->size,
                    $r->thread_count,
                    $r->cost,
                    $r->gst_percent,
                    $r->gst_amount,
                    $r->total_cost,
                    $r->markup,
                    $r->selling_price,
                    $r->status,
                    $r->effective_image_url,
                ];
            } elseif ($tab === WholesalePurchase::TYPE_SMART_LIVING) {
                $row = [
                    $r->id,
                    $r->sku,
                    $r->product_name,
                    $r->category?->name ?? $r->category_name,
                    $r->wholesale_price,
                    $r->moq,
                    $r->stock_qty,
                    $r->brand,
                    $r->model,
                    $r->description,
                    $r->badge,
                    $r->status,
                    $r->supplier,
                    $r->cost_price,
                    $r->markup,
                    $r->retail_markup,
                    $r->retail_price,
                    $r->purchase_date?->format('Y-m-d') ?? '',
                    $r->effective_image_url,
                ];
            } else {
                $row = [
                    $r->id,
                    $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? 'Women Fashion' : 'Smart Living',
                    $r->sku,
                    $r->product_name,
                    $r->category?->name ?? $r->category_name,
                    $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->cost : $r->cost_price,
                    $r->markup,
                    $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->total_cost : $r->wholesale_price,
                    $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->selling_price : $r->retail_price,
                    $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->size : $r->stock_qty,
                    $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->brand_code : $r->brand,
                    $r->purchase_type === WholesalePurchase::TYPE_WOMEN_FASHION ? $r->vcode : $r->supplier,
                    $r->status,
                    $r->purchase_date?->format('Y-m-d') ?? '',
                    $r->effective_image_url,
                ];
            }

            $sanitized = array_map(function ($val) {
                $s = (string) ($val ?? '');
                return str_replace(["\t", "\r", "\n"], ' ', $s);
            }, $row);

            $tsv .= implode("\t", $sanitized) . "\n";
        }

        return response($tsv, 200, [
            'Content-Type' => 'text/tab-separated-values; charset=UTF-8',
        ]);
    }

    /**
     * Store new color code in master.
     */
    public function storeColor(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'color_name' => 'required|string|max:100|unique:wholesale_color_codes,color_name',
            'color_code' => 'required|string|max:20',
            'hex_code' => 'nullable|string|max:30',
        ]);

        $data['color_code'] = strtoupper(trim($data['color_code']));
        $data['sort_order'] = WholesaleColorCode::max('sort_order') + 1;

        $created = WholesaleColorCode::create($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'color' => $created,
            ]);
        }

        return back()->with('success', "Color '{$created->color_name}' ({$created->color_code}) added to Master List.");
    }

    /**
     * Delete color code from master.
     */
    public function deleteColor(WholesaleColorCode $color): RedirectResponse
    {
        $name = $color->color_name;
        $color->delete();

        return back()->with('success', "Color '{$name}' removed from Master List.");
    }
}
