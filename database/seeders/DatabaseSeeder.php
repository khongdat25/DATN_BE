<?php

namespace Database\Seeders;

use App\Models\Blogs;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (Blogs::count() === 0) {
            $blogs = [
                [
                    'name' => 'Sự kiện ra mắt bộ sưu tập giới hạn "Future Walk" của SaigonShoes',
                    'slug' => Str::slug('Sự kiện ra mắt bộ sưu tập giới hạn "Future Walk" của SaigonShoes'),
                    'avatar' => 'news_featured.png',
                    'comment' => 'Chiêm ngưỡng những siêu phẩm Sneaker mang phong cách tương lai với sự kết hợp đột phá giữa nghệ thuật thiết kế đương đại.',
                    'content' => 'Chiêm ngưỡng những siêu phẩm Sneaker mang phong cách tương lai với sự kết hợp đột phá giữa nghệ thuật thiết kế đương đại. Trải nghiệm tuyệt vời cùng chất liệu siêu bền chắc và cảm giác thoải mái chưa từng có.',
                    'featuring' => true,
                    'views' => 1240,
                ],
                [
                    'name' => 'Cách vệ sinh giày Sneaker trắng luôn sáng bóng như mới tại nhà',
                    'slug' => Str::slug('Cách vệ sinh giày Sneaker trắng luôn sáng bóng như mới tại nhà'),
                    'avatar' => 'news_1.png',
                    'comment' => 'Giày trắng bị bám bẩn luôn là nỗi phiền toái lớn của cộng đồng yêu giày. Khám phá 5 bước đơn giản cực dễ dàng.',
                    'content' => 'Giày trắng bị bám bẩn luôn là nỗi phiền toái lớn của cộng đồng yêu giày. Khám phá 5 bước đơn giản cực dễ dàng bằng giấm, baking soda hoặc cồn y tế loại nhẹ giúp duy trì độ trắng sáng tự nhiên tốt nhất.',
                    'featuring' => false,
                    'views' => 954,
                ],
                [
                    'name' => 'Gợi ý các outfit phối đồ với Sneaker cực chất năng động cho mùa hè',
                    'slug' => Str::slug('Gợi ý các outfit phối đồ với Sneaker cực chất năng động cho mùa hè'),
                    'avatar' => 'news_2.png',
                    'comment' => 'Mùa hè năng động chính là thời điểm hoàn hảo nhất để bạn trình diễn các outfit thời trang đầy màu sắc của mình.',
                    'content' => 'Mùa hè năng động chính là thời điểm hoàn hảo nhất để bạn trình diễn các outfit thời trang đầy màu sắc của mình. Kết hợp giữa quần shorts, áo phông rộng và đôi Sneaker cổ thấp thời thượng.',
                    'featuring' => false,
                    'views' => 742,
                ],
                [
                    'name' => 'Đại tiệc sinh nhật SaigonShoes tròn 5 tuổi - Bùng nổ giảm giá tới 50%',
                    'slug' => Str::slug('Đại tiệc sinh nhật SaigonShoes tròn 5 tuổi - Bùng nổ giảm giá tới 50%'),
                    'avatar' => 'cat1.png',
                    'comment' => 'Mừng cột mốc sinh nhật đáng nhớ, SaigonShoes mang đến chương trình ưu đãi lớn nhất năm với hàng ngàn deal sốc.',
                    'content' => 'Mừng cột mốc sinh nhật đáng nhớ, SaigonShoes mang đến chương trình ưu đãi lớn nhất năm với hàng ngàn deal sốc áp dụng cho toàn bộ các cửa hàng chi nhánh trên toàn quốc và Website đặt hàng online.',
                    'featuring' => false,
                    'views' => 2420,
                ],
                [
                    'name' => 'Bản thảo: Top 5 xu hướng giày thể thao dự kiến lên ngôi cuối năm 2026',
                    'slug' => Str::slug('Bản thảo: Top 5 xu hướng giày thể thao dự kiến lên ngôi cuối năm 2026'),
                    'avatar' => 'news_2.png',
                    'comment' => 'Khám phá sớm các công nghệ đột phá, chất liệu đế đệm siêu đàn hồi sẽ thống trị tủ đồ của các Sneakerheads.',
                    'content' => 'Khám phá sớm các công nghệ đột phá, chất liệu đế đệm siêu đàn hồi sẽ thống trị tủ đồ của các Sneakerheads trong tương lai gần.',
                    'featuring' => false,
                    'views' => 0,
                ],
                [
                    'name' => 'Bản thảo: Hướng dẫn chi tiết cách đo size chân chuẩn để đặt giày từ xa',
                    'slug' => Str::slug('Bản thảo: Hướng dẫn chi tiết cách đo size chân chuẩn để đặt giày từ xa'),
                    'avatar' => 'news_1.png',
                    'comment' => 'Tránh hoàn toàn tình trạng đặt sai size, chật gót hoặc kích mũi giày với cách đo bàn chân bằng thước chuẩn xác.',
                    'content' => 'Tránh hoàn toàn tình trạng đặt sai size, chật gót hoặc kích mũi giày với cách đo bàn chân bằng thước chuẩn xác.',
                    'featuring' => false,
                    'views' => 0,
                ],
            ];

            foreach ($blogs as $blog) {
                Blogs::create($blog);
            }
        }
    }
}
