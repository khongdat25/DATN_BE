<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductModel;
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
        $products = ProductModel::with('images:product_id,id,image', 
                                        'variants:product_id,size_id,color_id,sku,stock,price,image',
                                        )->get(['id','slug','name','sold']);
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
       $flashSales = FlashSale::with(['items:id,flash_sale_id,sold,quantity_limit,discount_value,product_id',
                                        'items.product:id,name,slug,sold',
                                        'items.product.images:id,product_id,image',
                                        'items.product.variants:product_id,id,image'])
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
        $products = ProductModel::Where('status', 1)->with(['variants:product_id,image','category:id,name'])
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
                                                'variants:product_id,size_id', /*đây */
                                                'variants.size:id,name',        /*đây */
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
        $products = ProductModel::Where('id', $id)->with(['brand:id,name','image:id,product_id,image',
                                                'variants:product_id,image,price,stock,color_id,size_id',
                                                'category:id,name',
                                                'variants.color:id,name',
                                                'variants.size:id,name',
                                                'rating:product_id,rating,comment,created_at,user_id',
                                                'rating.user:id,name'
                                                ])->first();
            return response()->json(
                [
                    'success' => true,
                    'message' => 'chi tiết sp',
                    'data' => $products,
                ],200);
        }   

        /*tìm kiếm sp :8000/api/search?
        q='tên sp ở đây'
        &
        category_id='id danh mục'
        &
        gender='giới tính: male,female,both'
        &
        min_price='giá thấp nhất'
        &
        max_price='giá cao nhất'
        &
        sort= 'lọc: price_"asc/desc", sold_"asc/desc", newest/oldest */

        /*db bảng products thêm  cột gender	enum('male', 'female', 'both')	default='both'  */

    public function Search(Request $request)
    {
           $products = ProductModel::query()
            ->select(['id','name','slug','sold','category_id','brand_id'])
                ->selectSub(function ($q) {
                $q->from('product_variants')
                ->selectRaw('MIN(price)')
                ->whereColumn('product_variants.product_id', 'products.id');
            }, 'min_price');

            if ($request->filled('q')) {
                $products->where('name', 'like', '%' . $request->q . '%');
            }
            if ($request->filled('category_id')) {
                $products->where('category_id', $request->category_id);
            }
            if ($request->filled('gender')) {
                $products->whereIn('gender', [$request->gender, 'both']);
            }
            if ($request->filled('min_price')) {
                $products->having('min_price', '>=', $request->min_price);
            }
            if ($request->filled('max_price')) {
                $products->having('min_price', '<=', $request->max_price);
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
                    'message' => 'chi tiết sp',
                    'data' => $products->get()
                ],200);
        }
    /*

        if ($request->filled('category_id')){
            $products->where('category_id', $request->category_id);
        }

        
        if ($request->filled('gender')) {
            $products->whereIn('gender', [$request->gender, 'both']);
        }
        tìm theo khoảng giá tag name = 'min_price' và 'max_price' 
       if ($request->filled('min_price')) {
        $products->having('variants_min_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $products->having('variants_min_price', '<=', $request->max_price);
        }
            */

  
}
