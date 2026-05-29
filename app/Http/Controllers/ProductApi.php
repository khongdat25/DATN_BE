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
                                                'variants:product_id,image',
                                                'category:id,name'
                                                ])
                                        ->orderBy('sold', 'desc')
                                        ->take(5)
                                        ->get(['id','name','slug','sold','category_id','brand_id']);
        return response()->json(
        [
            'success' => true,
            'message' => 'Sản Phẩm Nổi Bật theo collab',
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
     /*  DELL ĐỤNG VÔ OK
     
     public function sale(){
        $products = ProductModel::where('sale', '!=', 0)->
                                orderBy('sale_price', 'desc')
                                ->take(8)
                                ->get(['id','name','slug','price','img','stock']);
        return response()->json(
        [
            'success' => true,
            'message' => 'Sản Phẩm Nổi Bật',
            'data' => $products,
        ],200);
    }

    public function Collection(){
        $collections = Collection::take(8)->get('id','name','img','brand');
        return response()->json([
            'success' => true,
            'message' => 'Bộ sưu tập',
            'data' => $collections,
        ],200);
    }

    public function SaleBanner(){
        $salebanner = Banner::where('sale' , true)->take(2)->get(['id','name','img']);
          return response()->json([
            'success' => true,
            'message' => 'khuyến mãi nhỏ',
            'data' => $collections,
        ],200);
    }

    public function Reviews(){
    $Reviews =  Comment::where('featuring', true)->take(3)->get(['id','name','avatar','comment']);
    return response()->json([
        'success' => true,
            'message' => 'Reviews',
            'data' => $Reviews,
    ],200);
    }

    public function BlogsAndNews(){
    $data =  Blogs::where('featuring', true)->take(3)->get(['id','name','avatar','comment']);
    return response()->json([
        'success' => true,
            'message' => 'Blogs and News',
            'data' => $data,
    ],200);
    }

*/




     


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
