<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\ProductModel;
use App\Models\Variant;
use App\Models\Banners;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Brand;
use Carbon\Carbon;

class ProductApi extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /*demo test gọi toàn bộ sp */
    public function index()
    {
        $products = ProductModel::with([
            'images:product_id,id,image', 
            'variants:product_id,size_id,color_id,sku,stock,price,image',
            'brand:id,name',
            'category:id,name'
        ])->get(['id','slug','name','sold','brand_id','category_id',]);
        
        return response()->json(
        [
            'success' => true,
            'message' => 'test all datas',
            'data' => $products,
            
        ],200);
    }

    /*gọi banner*/

    public function Banner(){
        $banners = Banners::take(3)->get(['id','name', 'image']);
        return response()->json(
        [
            'success' => true,
            'message' => 'Hero Banner',
            'data' => $banners,
        ],200);
    }

    public function getCategories(){
        $category = Category::get(['id','name']);
        return response()->json(
        [
            'success' => true,
            'message' => 'lấy categories',
            'data' => $category,
        ],200);
    }
     public function getBrands(){
        $brand = Brand::get(['id','name']);
        return response()->json(
        [
            'success' => true,
            'message' => 'lấy brands',
            'data' => $brand,
        ],200);
    }

     public function HotCategories(){
        $categories = Category::withCount('products')->take(6)->get(['name','img']);
        return response()->json(
        [
            'success' => true,
            'message' => 'Danh Mục Nổi Bật',
            'data' => $categories,
        ],200);
    }

    /*gọi sp flashsale*/ 

     public function FlashSale(){
        $now = Carbon::now();
       $flashSales = Flashsale::with(['items:id,flash_sale_id,sold,quantity_limit,discount_value,product_id',
                                        'items.product:id,name,slug,sold',
                                        'items.product.images:id,product_id,image',
                                        'items.product.variants:product_id,id,image,price,sale'])
                                                ->where('status', 1)
                                                ->whereDate('start_time', '<=', now())
                                                ->whereDate('end_time', '>=', now())
                                                ->take(5)
                                                ->get();
        return response()->json(
        [
            'success' => true,
            'message' => 'Flash Sale',
            'data' => $flashSales,
        ],200);
    }

    /*gọi sp bán chạy*/

     public function BestSelling(){
        $products = ProductModel::Where('status', 1)->with(['variants:product_id,image,price,sale','category:id,name'])
                                        ->orderBy('sold', 'desc')
                                        ->take(5)
                                        ->get(['id','name','slug','sold','category_id']);
        return response()->json(
        [
            'success' => true,
            'message' => 'Sản phẩm bán chạy',
            'data' => $products,
        ],200);
    }

   /*gọi sp nổi bật, điều kiện trong bảng brand có is_feature khác 0*/
    
     public function HotProduct(){
       $products = ProductModel::whereHas('brand', function ($q) {$q->where('is_featured', 1);})
                                        ->with(['brand:id,name',
                                                'variants:product_id,size_id,price,sale', 
                                                'variants.size:id,name',       
                                                'category:id,name'
                                                ])
                                        ->orderBy('sold', 'desc')
                                        ->take(5)
                                        ->get(['id','name','slug','sold','category_id','brand_id']);
        return response()->json(
        [
            'success' => true,
            'message' => 'Sản Phẩm Nổi Bật',
            'data' => $products,
        ],200);
    }

    /* lấy chi tiết sp theo id*/

     public function Detail($id){
         $product = ProductModel::Where('id', $id)->with(['brand:id,name','images:id,product_id,image',
                                                 'variants:product_id,image,price,sale,stock,color_id,size_id',
                                                 'category:id,name',
                                                 'variants.color:id,name',
                                                 'variants.size:id,name',
                                                 'rating:product_id,rating,comment,created_at,user_id',
                                                 'rating.user:id,name'
                                                 ])->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'no product found'], 404);
            }
        $related = ProductModel::Where('id','!=' , $id)
                                   ->where('category_id', $product->category_id)
                                    ->limit(4)
                                    ->with([
                                        'images:id,product_id,image', 
                                        'variants:product_id,size_id,color_id,sku,stock,price,sale,image',
                                        'brand:id,name',
                                        'category:id,name'
                                    ])
                                    ->get(['id', 'slug', 'name', 'sold', 'brand_id', 'category_id',]);
        $related = $related->sortBy(fn($item) => $item->min_price)->values();
        $related->each(fn($item) => $item->setRelation('variants', $item->variants->take(1)));
            return response()->json(
                [
                    'success' => true,
                    'message' => 'chi tiết sp',
                    'data' =>  ['product' => $product, 'related' => $related ]
                ],200);
        }   

        /*tìm kiếm sp :8000/api/search?
        q='tên sp ở đây'
        &
        category_id='id danh mục'
        &
        min_price='giá thấp nhất'
        &
        max_price='giá cao nhất'
        &
        size = 'nhập size vào'
        &
        sort= 'lọc: price_"asc/desc", sold_"asc/desc", newest/oldest */

    public function Search(Request $request)
    {
           $products = ProductModel::query()
            ->select(['id','name','slug','sold','category_id','brand_id'])
            ->withMin('variants as min_price', 'price');
          if (!$product) {
            return response()->json(['success' => false, 'message' => 'không có sp'], 404);
            }
            if ($request->filled('q')) {
                $products->where('name', 'like', '%' . $request->q . '%');
            }
            if ($request->filled('category_id')) {
                $products->where('category_id', $request->category_id);
            }
            if ($request->filled('brand_id')) {
                $products->where('brand_id', $request->brand_id);
            }
            if ($request->filled('min_price') && $request->filled('max_price')) {

            $products->havingBetween('min_price', [$request->min_price, $request->max_price]);
            } 
            else {
                if ($request->filled('min_price')) {
                $products->having('min_price', '>=', $request->min_price);
                }
                if ($request->filled('max_price')) {
                $products->having('min_price', '<=', $request->max_price);
                }
            }

            if ($request->filled('size')) {
                $products->wherehas('variants.size', 
                            function ($query) use ($request) {
                            $query->where('name', $request->size);
                        });
            }

            if ($request->filled('sort')) {
                if ($request->sort == 'price_asc') {
                    $products->orderBy('min_price', 'asc');
                }
                if ($request->sort == 'price_desc') {
                    $products->orderBy('min_price', 'desc');
                }
                if ($request->sort == 'sold_desc') {
                    $products->orderBy('sold', 'desc');
                }
                 if ($request->sort == 'sold_asc') {
                    $products->orderBy('sold', 'asc');
                }
                if ($request->sort == 'newest') {
                    $products->orderBy('id', 'desc');
                }if ($request->sort == 'oldest') {
                    $products->orderBy('id', 'asc');
                }
            }
        return response()->json(
                [
                    'success' => true,
                    'message' => 'tìm kiếm sp thành công',
                    'data' => $products->get()
                ],200);
        }

    function admin_product(Request $request){
        $products = ProductModel::query()
        ->with
        ([
            'images:product_id,id,image', 
            'variants:product_id,size_id,color_id,sku,stock,price,sale,image',
            'variants.color:id,name',
            'variants.size:id,name',
            'brand:id,name',
            'category:id,name'
        ])
        ->withCount('variants')
        ->withSum('variants as total_stock', 'stock')
        ->withMin('variants as min_price', 'price')
        ->withMax('variants as max_price', 'price');

        if($request->filled('q')) {
                $products->where('name', 'like', '%' . $request->q . '%');
        }
        if ($request->filled('category_id')) {
                $products->where('category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
                $products->where('brand_id', $request->brand_id);
        }
         if ($request->filled('status')) {
                $products->where('status', $request->status);
            }

        return response()->json(
        [
            'success' => true,
            'message' => 'all product for admin product page',
            'data' => $products->get(),
            
        ],200);
    }

    function product_delete($id){
        $product = ProductModel::find($id);
         if (!$product) {
        return response()->json(['success' => false, 'message' => 'no product found'], 404);
        }
         $product->images()->delete();
        $product->variants()->delete();
        $product->delete();
         return response()->json(
        [
            'success' => true,
            'message' => 'product deleted',
        ],200);
    }

    function variant_delete(Variant $v){
        $v->delete();
         return response()->json([
            'success' => true,
            'message' => 'variant deleted',
        ],200);
    }

    function product_add(Request $request){
    $request->validate([
        'name'        => 'required|string',
        'category_id' => 'required|integer',
        'brand_id'    => 'required|integer',
        'variants'    => 'required|array',
    ]);
        DB::beginTransaction();

    try {
        $product = ProductModel::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'category_id' => $request->category_id,
            'brand_id'    => $request->brand_id,
            'description' =>$request->description,
            'status'      => 1,
            'sold'        => 0,
        ]);
        //tạo sku tự động p1
                $word = Str::slug($request->name, ' ');
                $words = explode(' ', $word);
                $productCode = '';
                    foreach ($words as $w) {
                        if (!empty($w)) {
                            $productCode .= mb_substr($w, 0, 1, 'UTF-8'); 
                        }
                    }
                

    foreach ($request->variants as $variant) {
        //tạo sku tự động p2
            $colorCode = $variant['color_code'] ?? 'CLR' . $variant['color_id'];
            $sizeCode  = $variant['size_code'] ?? 'SZ' . $variant['size_id'];
            $autoSku = strtoupper($productCode . '-' . $colorCode . '-' . $sizeCode . Str::random(4));

            Variant::create([
                'product_id' => $product->id,
                'size_id'    => $variant['size_id'],
                'color_id'   => $variant['color_id'],
                'stock'      => $variant['stock'] ?? 0,
                'sku'         => $autoSku,
                'price'      => $variant['price'],
                'image'      => $variant['image'] ?? null,
                'status'     => $variant['status'] ?? null,
            ]);
        } DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data'    => $product->load('variants')
        ], 201);
        } 
        catch (\Exception $e) {
        DB::rollBack();
         return response()->json([
            'success' => false,
            'message' => 'failed :(',
            'error'   => $e->getMessage()
        ], 500);}
    }

    function product_edit(Request $request, $id) {
    $request->validate([
        'name'        => 'required|string',
        'category_id' => 'required|integer',
        'brand_id'    => 'required|integer',
        'variants'    => 'required|array',
    ]);
    $product = ProductModel::find($id);
    if (!$product) {
        return response()->json(['success' => false, 'message' => 'Không tìm thấy sản phẩm!'], 404);
    }

    DB::beginTransaction();

    try {
        $product->update([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'category_id' => $request->category_id,
            'brand_id'    => $request->brand_id,
            'description' =>$request->description,
        ]);
        $word = Str::slug($request->name, ' ');
        $words = explode(' ', $word);
        $productCode = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $productCode .= strtoupper(substr($w, 0, 1)); 
            }
        }
        $keepVariantIds = []; 

        foreach ($request->variants as $variant) {

            $colorCode = $variant['color_code'] ?? 'CLR' . $variant['color_id'];
            $sizeCode  = $variant['size_code'] ?? 'SZ' . $variant['size_id'];

            if (isset($variant['id']) && !empty($variant['id'])) {
                $existingVariant = Variant::find($variant['id']);
                if ($existingVariant) {
                    $existingVariant->update([
                        'size_id'  => $variant['size_id'],
                        'color_id' => $variant['color_id'],
                        'stock'    => $variant['stock'] ?? 0,
                        'price'    => $variant['price'],
                        'sale'    => $variant['sale'] ?? null,
                        'image'    => $variant['image'] ?? $existingVariant->image,
                        'status'   => $variant['status'] ?? $existingVariant->status,
                    ]);
                    $keepVariantIds[] = $existingVariant->id;
                }
            } else {
                $autoSku = strtoupper($productCode . '-' . $colorCode . '-' . $sizeCode . '-' . Str::random(4));
                $newVariant = Variant::create([
                    'product_id' => $product->id,
                    'size_id'    => $variant['size_id'],
                    'color_id'   => $variant['color_id'],
                    'stock'      => $variant['stock'] ?? 0,
                    'sku'        => $autoSku,
                    'price'      => $variant['price'],
                    'sale'      => $variant['sale'] ?? null,
                    'image'      => $variant['image'] ?? null,
                    'status'     => $variant['status'] ?? null,
                ]);
                $keepVariantIds[] = $newVariant->id;
            }
        }
        Variant::where('product_id', $product->id)
            ->whereNotIn('id', $keepVariantIds)
            ->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sản phẩm, danh mục và thương hiệu thành công!',
            'data'    => $product->load('variants')
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Cập nhật thất bại!',
            'error'   => $e->getMessage()
        ], 500);
    }
}
}
