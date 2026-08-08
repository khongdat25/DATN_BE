<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Table ai_settings
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('assistant_name')->default('SaigonShoes AI Stylist');
            $table->string('ai_model')->default('Gemini 1.5 Flash');
            $table->float('temperature')->default(0.7);
            $table->boolean('is_enabled')->default(true);
            $table->string('persona_style')->default('friendly');
            $table->string('store_address')->nullable()->default('123 Nguyễn Trãi, Phường Bến Thành, Quận 1, TP. Hồ Chí Minh');
            $table->string('hotline')->nullable()->default('0901 234 567');
            $table->text('shipping_policy')->nullable();
            $table->text('system_prompt')->nullable();
            $table->text('size_chart_guide')->nullable();
            $table->timestamps();
        });

        // Seed initial default AI setting
        DB::table('ai_settings')->insert([
            'assistant_name' => 'SaigonShoes AI Stylist',
            'ai_model' => 'Gemini 1.5 Flash',
            'temperature' => 0.7,
            'is_enabled' => true,
            'persona_style' => 'friendly',
            'store_address' => '123 Nguyễn Trãi, Phường Bến Thành, Quận 1, TP. Hồ Chí Minh',
            'hotline' => '0901 234 567',
            'shipping_policy' => 'Freeship toàn quốc cho đơn hàng từ 500.000đ. Đổi trả miễn phí trong 7 ngày.',
            'system_prompt' => "Bạn là \"SaigonShoes AI Stylist\" - Chuyên gia tư vấn thời trang giày sneaker chuyên nghiệp và thân thiện tại cửa hàng SaigonShoes.\n\nNhiệm vụ chính:\n1. Tư vấn chọn size giày chính xác theo chiều dài bàn chân (cm) và dáng chân (thon hoặc bè).\n2. Gợi ý phối đồ (Outfits / Style) chuẩn xu hướng Streetwear, Casual, Sporty.\n3. Giải đáp thắc mắc về giá sản phẩm, chương trình khuyến mãi Flash Sale và chính sách đổi trả.\n\nQuy tắc phản hồi:\n- Luôn giữ thái độ lịch sự, vui vẻ, năng động và súc tích.\n- Ưu tiên giới thiệu các mẫu giày chính hãng sẵn có tại SaigonShoes.",
            'size_chart_guide' => "• Chiều dài 22.5cm - 23.0cm: Size 36 (EU)\n• Chiều dài 23.0cm - 23.5cm: Size 37 (EU)\n• Chiều dài 23.5cm - 24.0cm: Size 38 (EU)\n• Chiều dài 24.0cm - 24.5cm: Size 39 (EU)\n• Chiều dài 24.5cm - 25.0cm: Size 40 (EU)\n• Chiều dài 25.0cm - 25.5cm: Size 41 (EU)\n• Chiều dài 25.5cm - 26.0cm: Size 42 (EU)\n• Chiều dài 26.0cm - 27.0cm: Size 43 (EU)\n* Lưu ý: Nếu bàn chân BÈ NGANG hoặc MU BÀN CHÂN DÀY, nên khuyên khách TĂNG 1 SIZE.",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Table ai_logs
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('user_name')->default('Khách vương');
            $table->string('user_email')->nullable();
            $table->string('user_phone')->nullable();
            $table->string('topic')->default('Tư vấn chung');
            $table->integer('messages_count')->default(1);
            $table->text('user_message');
            $table->text('bot_reply');
            $table->json('recommended_product_ids')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->enum('feedback', ['positive', 'negative', 'none'])->default('none');
            $table->timestamps();
        });

        // 3. Table ai_suggestions
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->string('action')->default('prompt');
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default quick suggestions
        DB::table('ai_suggestions')->insert([
            ['text' => '📐 Đo size chân', 'action' => 'widget_size_calculator', 'active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['text' => '👟 Style Streetwear', 'action' => 'prompt_streetwear', 'active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['text' => '🔥 Giày Hot giảm giá', 'action' => 'prompt_sale_shoes', 'active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['text' => '🛡️ Chính sách đổi trả size', 'action' => 'prompt_return_policy', 'active' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('ai_logs');
        Schema::dropIfExists('ai_settings');
    }
};
