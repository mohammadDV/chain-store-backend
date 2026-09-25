<?php

namespace Database\Seeders;

use Domain\Product\Models\Product;
use Domain\Product\Services\StockService;
use Domain\User\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure we have a user for products
        $user = User::first();
        if (! $user) {
            $user = User::create([
                'first_name' => 'مدیر',
                'last_name' => 'سیستم',
                'nickname' => 'admin',
                'customer_number' => User::generateCustumerNumber(),
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'mobile' => '09123456789',
                'role_id' => 1,
                'level' => 3,
                'status' => 1,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('admin');
        }

        $products = [
            [
                'title' => 'کفش ورزشی نایک ایر مکس',
                'description' => 'کفش ورزشی با کیفیت بالا مناسب برای دویدن و پیاده‌روی. طراحی زیبا و راحتی بی‌نظیر.',
                'details' => json_encode([
                    'جنس' => 'مش و چرم مصنوعی',
                    'کشور سازنده' => 'ویتنام',
                    'گارانتی' => '6 ماه',
                ]),
                'points' => 100,
                'rate' => 5,
                'amount' => 4500000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 1,
                'category_id' => 1,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 25,
                'view_count' => 150,
            ],
            [
                'title' => 'تی‌شرت ورزشی آدیداس',
                'description' => 'تی‌شرت ورزشی با پارچه تنفس‌پذیر مناسب برای فعالیت‌های ورزشی و روزمره.',
                'details' => json_encode([
                    'جنس' => 'پلی‌استر',
                    'سایز' => 'L',
                    'رنگ' => 'آبی',
                ]),
                'points' => 50,
                'rate' => 4,
                'amount' => 850000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 2,
                'color_id' => 4,
                'category_id' => 2,
                'brand_id' => 2,
                'user_id' => $user->id,
                'order_count' => 45,
                'view_count' => 200,
            ],
            [
                'title' => 'گوشی سامسونگ گلکسی S23',
                'description' => 'گوشی هوشمند سامسونگ با پردازنده قدرتمند، دوربین عالی و نمایشگر AMOLED.',
                'details' => json_encode([
                    'حافظه داخلی' => '256 گیگابایت',
                    'رم' => '8 گیگابایت',
                    'دوربین' => '50 مگاپیکسل',
                ]),
                'points' => 500,
                'rate' => 5,
                'amount' => 35000000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 1,
                'category_id' => 3,
                'brand_id' => 3,
                'user_id' => $user->id,
                'order_count' => 15,
                'view_count' => 300,
            ],
            [
                'title' => 'آیفون 15 پرو مکس',
                'description' => 'جدیدترین آیفون اپل با تراشه A17 Pro، سیستم دوربین حرفه‌ای و قاب تیتانیوم.',
                'details' => json_encode([
                    'حافظه' => '512 گیگابایت',
                    'رنگ' => 'تیتانیوم طبیعی',
                    'گارانتی' => '18 ماه',
                ]),
                'points' => 800,
                'rate' => 5,
                'amount' => 68000000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 10,
                'category_id' => 5,
                'brand_id' => 4,
                'user_id' => $user->id,
                'order_count' => 8,
                'view_count' => 450,
            ],
            [
                'title' => 'تلویزیون سامسونگ 55 اینچ',
                'description' => 'تلویزیون هوشمند 4K با کیفیت تصویر عالی و امکانات هوشمند.',
                'details' => json_encode([
                    'سایز' => '55 اینچ',
                    'کیفیت' => '4K UHD',
                    'فناوری' => 'QLED',
                ]),
                'points' => 300,
                'rate' => 4,
                'amount' => 25000000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 3,
                'color_id' => 1,
                'category_id' => 4,
                'brand_id' => 3,
                'user_id' => $user->id,
                'order_count' => 12,
                'view_count' => 180,
            ],
            [
                'title' => 'مک‌بوک پرو 14 اینچ',
                'description' => 'لپ‌تاپ حرفه‌ای اپل با تراشه M3 Pro، مناسب برای کارهای سنگین و گرافیکی.',
                'details' => json_encode([
                    'پردازنده' => 'M3 Pro',
                    'رم' => '16 گیگابایت',
                    'حافظه' => '512 گیگابایت SSD',
                ]),
                'points' => 600,
                'rate' => 5,
                'amount' => 95000000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 10,
                'category_id' => 6,
                'brand_id' => 4,
                'user_id' => $user->id,
                'order_count' => 5,
                'view_count' => 250,
            ],
            [
                'title' => 'پیراهن مردانه زارا',
                'description' => 'پیراهن مردانه رسمی با پارچه باکیفیت، مناسب برای محیط کار و مهمانی.',
                'details' => json_encode([
                    'جنس' => 'پنبه',
                    'سایز' => 'XL',
                    'رنگ' => 'سفید',
                ]),
                'points' => 40,
                'rate' => 4,
                'amount' => 1200000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 4,
                'color_id' => 2,
                'category_id' => 7,
                'brand_id' => 5,
                'user_id' => $user->id,
                'order_count' => 35,
                'view_count' => 120,
            ],
            [
                'title' => 'مانتو زنانه زارا',
                'description' => 'مانتو زنانه شیک و مدرن با طراحی روز، مناسب برای استفاده روزمره.',
                'details' => json_encode([
                    'جنس' => 'ویسکوز',
                    'سایز' => 'M',
                    'طرح' => 'ساده',
                ]),
                'points' => 45,
                'rate' => 5,
                'amount' => 1800000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 3,
                'color_id' => 1,
                'category_id' => 8,
                'brand_id' => 5,
                'user_id' => $user->id,
                'order_count' => 40,
                'view_count' => 220,
            ],
            [
                'title' => 'کفش ورزشی آدیداس اولترا بوست',
                'description' => 'کفش دویدن با فناوری Boost برای انرژی بیشتر و راحتی بی‌نظیر در دویدن.',
                'details' => json_encode([
                    'جنس کفی' => 'Boost',
                    'وزن' => '310 گرم',
                    'مناسب برای' => 'دویدن',
                ]),
                'points' => 90,
                'rate' => 5,
                'amount' => 5200000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 2,
                'color_id' => 2,
                'category_id' => 1,
                'brand_id' => 2,
                'user_id' => $user->id,
                'order_count' => 30,
                'view_count' => 280,
            ],
            [
                'title' => 'شلوار ورزشی نایک',
                'description' => 'شلوار ورزشی راحت با پارچه قابل کشش، مناسب برای ورزش و استفاده روزمره.',
                'details' => json_encode([
                    'جنس' => 'پلی‌استر و اسپندکس',
                    'سایز' => 'L',
                    'جیب' => 'دارد',
                ]),
                'points' => 35,
                'rate' => 4,
                'amount' => 950000,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 5,
                'color_id' => 1,
                'category_id' => 2,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 50,
                'view_count' => 160,
            ],
        ];

        foreach ($products as $product) {
            $categoryId = $product['category_id'];
            unset($product['category_id']);

            $created = Product::create($product);

            $created->categories()->attach($categoryId);

            $size = $created->sizes()->create([
                'title' => 'Default',
                'code' => 'default',
                'status' => 1,
                'priority' => 1,
            ]);
            app(StockService::class)->setQuantity((int) $size->getKey(), 10);
        }
    }
}
