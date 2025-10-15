<?php

namespace Database\Seeders;

use Domain\Product\Models\Product;
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
        if (!$user) {
            $user = User::create([
                'first_name' => 'مدیر',
                'last_name' => 'سیستم',
                'nickname' => 'admin',
                'customer_number' => User::generateCustumerNumber(),
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'mobile' => '09123456789',
                'role_id' => 1,
                'status' => 1,
            ]);
        }

        $products = [
            [
                'title' => 'کفش ورزشی نایک ایر مکس',
                'description' => 'کفش ورزشی با کیفیت بالا مناسب برای دویدن و پیاده‌روی. طراحی زیبا و راحتی بی‌نظیر.',
                'details' => json_encode([
                    'جنس' => 'مش و چرم مصنوعی',
                    'کشور سازنده' => 'ویتنام',
                    'گارانتی' => '6 ماه'
                ]),
                'stock' => 50,
                'points' => 100,
                'rate' => 5,
                'amount' => 4500000,
                'discount' => 10,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 1, // مشکی
                'category_id' => 11, // کفش ورزشی نایک
                'brand_id' => 3, // نایک
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
                    'رنگ' => 'آبی'
                ]),
                'stock' => 100,
                'points' => 50,
                'rate' => 4,
                'amount' => 850000,
                'discount' => 5,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 2,
                'color_id' => 4, // آبی
                'category_id' => 13, // لباس ورزشی آدیداس
                'brand_id' => 4, // آدیداس
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
                    'دوربین' => '50 مگاپیکسل'
                ]),
                'stock' => 30,
                'points' => 500,
                'rate' => 5,
                'amount' => 35000000,
                'discount' => 8,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 1, // مشکی
                'category_id' => 14, // گوشی موبایل سامسونگ
                'brand_id' => 5, // سامسونگ
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
                    'گارانتی' => '18 ماه'
                ]),
                'stock' => 20,
                'points' => 800,
                'rate' => 5,
                'amount' => 68000000,
                'discount' => 0,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 10, // خاکستری
                'category_id' => 16, // آیفون
                'brand_id' => 6, // اپل
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
                    'فناوری' => 'QLED'
                ]),
                'stock' => 15,
                'points' => 300,
                'rate' => 4,
                'amount' => 25000000,
                'discount' => 12,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 3,
                'color_id' => 1, // مشکی
                'category_id' => 15, // تلویزیون
                'brand_id' => 5, // سامسونگ
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
                    'حافظه' => '512 گیگابایت SSD'
                ]),
                'stock' => 10,
                'points' => 600,
                'rate' => 5,
                'amount' => 95000000,
                'discount' => 0,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 10, // خاکستری
                'category_id' => 17, // مک بوک
                'brand_id' => 6, // اپل
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
                    'رنگ' => 'سفید'
                ]),
                'stock' => 80,
                'points' => 40,
                'rate' => 4,
                'amount' => 1200000,
                'discount' => 15,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 4,
                'color_id' => 2, // سفید
                'category_id' => 18, // لباس مردانه
                'brand_id' => 7, // زارا
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
                    'طرح' => 'ساده'
                ]),
                'stock' => 60,
                'points' => 45,
                'rate' => 5,
                'amount' => 1800000,
                'discount' => 20,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 3,
                'color_id' => 1, // مشکی
                'category_id' => 19, // لباس زنانه
                'brand_id' => 7, // زارا
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
                    'مناسب برای' => 'دویدن'
                ]),
                'stock' => 40,
                'points' => 90,
                'rate' => 5,
                'amount' => 5200000,
                'discount' => 8,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 2,
                'color_id' => 2, // سفید
                'category_id' => 12, // کفش ورزشی آدیداس
                'brand_id' => 4, // آدیداس
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
                    'جیب' => 'دارد'
                ]),
                'stock' => 70,
                'points' => 35,
                'rate' => 4,
                'amount' => 950000,
                'discount' => 10,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 5,
                'color_id' => 1, // مشکی
                'category_id' => 11, // لباس ورزشی نایک
                'brand_id' => 3, // نایک
                'user_id' => $user->id,
                'order_count' => 50,
                'view_count' => 160,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
