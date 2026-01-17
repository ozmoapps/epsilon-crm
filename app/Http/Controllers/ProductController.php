<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use Illuminate\Http\Request;

use App\Support\TenantGuard;

class ProductController extends Controller
{
    use TenantGuard;

    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $categoryId = $request->input('category_id');
        $trackStock = $request->input('track_stock');

        $products = Product::query()
            ->with(['category', 'tags'])
            ->where('tenant_id', app(\App\Services\TenantContext::class)->id())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($trackStock !== null, fn ($q) => $q->where('track_stock', $trackStock))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'search', 'type', 'categoryId', 'trackStock'));
    }

    public function create()
    {
        return view('products.create', [
            'product' => new Product(),
            'categories' => Category::where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(), // Tags assumed global or shared for now
        ]);
    }

    public function store(ProductStoreRequest $request)
    {
        $validated = $request->validated();
        
        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        // Model hook handles tenant_id
        $product = Product::create($validated);
        
        if (!empty($tags)) {
            $product->tags()->sync($tags);
        }

        return redirect()->route('products.index')
            ->with('success', 'Ürün/Hizmet başarıyla oluşturuldu.');
    }

    public function show(Product $product)
    {
        $this->checkTenant($product);

        $product->load(['category', 'tags', 'inventoryBalances.warehouse']);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $this->checkTenant($product);

        return view('products.edit', [
            'product' => $product,
            'categories' => Category::where('tenant_id', app(\App\Services\TenantContext::class)->id())->orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $this->checkTenant($product);

        $validated = $request->validated();
        
        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $product->update($validated);
        
        if (isset($request->tags)) {
             $product->tags()->sync($tags);
        }

        return redirect()->route('products.index')
            ->with('success', 'Ürün/Hizmet başarıyla güncellendi.');
    }

    public function destroy(Product $product)
    {
        $this->checkTenant($product);

        if ($product->stockMovements()->exists()) {
            return redirect()->back()->with('error', 'Bu ürünün stok hareketleri mevcut, silinemez. Pasife alabilirsiniz.');
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Kayıt silindi.');
    }
}
