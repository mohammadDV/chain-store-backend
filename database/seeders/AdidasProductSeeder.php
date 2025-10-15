<?php

namespace Database\Seeders;

use Domain\Product\Models\Product;
use Domain\User\Models\User;
use Illuminate\Database\Seeder;

class AdidasProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user
        $user = User::first();
        if (!$user) {
            echo "هیچ کاربری یافت نشد. لطفا ابتدا یک کاربر ایجاد کنید." . PHP_EOL;
            return;
        }

        $products = [
            // Category 3: کودک
            [
                'title' => 'ست ورزشی کودک آدیداس',
                'description' => 'ست ورزشی کامل برای کودکان شامل تی‌شرت و شلوارک با پارچه نرم و راحت.',
                'details' => json_encode([
                    'جنس' => 'پنبه و پلی‌استر',
                    'سایز' => '6-8 سال',
                    'رنگ' => 'آبی و سفید'
                ]),
                'stock' => 80,
                'points' => 30,
                'rate' => 5,
                'amount' => 750000,
                'discount' => 10,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 3,
                'color_id' => 4, // آبی
                'category_id' => 3,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 42,
                'view_count' => 165,
            ],
            [
                'title' => 'کفش ورزشی کودک آدیداس راپیدا',
                'description' => 'کفش ورزشی سبک و راحت مخصوص کودکان با طراحی جذاب و رنگارنگ.',
                'details' => json_encode([
                    'جنس' => 'مش و EVA',
                    'سایز' => '30-35',
                    'ویژگی' => 'چسبی'
                ]),
                'stock' => 60,
                'points' => 40,
                'rate' => 5,
                'amount' => 1200000,
                'discount' => 15,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 2,
                'color_id' => 3, // قرمز
                'category_id' => 3,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 55,
                'view_count' => 190,
            ],

            // Category 5: سویشرت (مردانه)
            [
                'title' => 'سویشرت مردانه آدیداس اسنشالز',
                'description' => 'سویشرت مردانه با پارچه گرم و نرم، مناسب برای فصول سرد سال.',
                'details' => json_encode([
                    'جنس' => 'کتان فرانسه',
                    'سایز' => 'XL',
                    'رنگ' => 'مشکی'
                ]),
                'stock' => 45,
                'points' => 60,
                'rate' => 4,
                'amount' => 1850000,
                'discount' => 12,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 3,
                'color_id' => 1, // مشکی
                'category_id' => 5,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 38,
                'view_count' => 145,
            ],
            [
                'title' => 'سویشرت مردانه آدیداس با کلاه',
                'description' => 'سویشرت ورزشی با کلاه و جیب کانگورویی، مناسب برای ورزش و پیاده‌روی.',
                'details' => json_encode([
                    'جنس' => 'پلی‌استر',
                    'سایز' => 'L',
                    'ویژگی' => 'ضد آب'
                ]),
                'stock' => 50,
                'points' => 55,
                'rate' => 5,
                'amount' => 2100000,
                'discount' => 8,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 2,
                'color_id' => 10, // خاکستری
                'category_id' => 5,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 32,
                'view_count' => 178,
            ],

            // Category 7: لباس زیر (مردانه)
            [
                'title' => 'ست لباس زیر مردانه آدیداس',
                'description' => 'ست لباس زیر ورزشی با پارچه تنفس‌پذیر و راحت برای فعالیت‌های روزانه.',
                'details' => json_encode([
                    'جنس' => 'کتان و الاستین',
                    'سایز' => 'L',
                    'تعداد' => '3 عددی'
                ]),
                'stock' => 100,
                'points' => 25,
                'rate' => 4,
                'amount' => 650000,
                'discount' => 20,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 4,
                'color_id' => 1, // مشکی
                'category_id' => 7,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 68,
                'view_count' => 210,
            ],
            [
                'title' => 'زیرپوش مردانه آدیداس تکنوفیت',
                'description' => 'زیرپوش ورزشی با فناوری جذب عرق برای عملکرد بهتر در ورزش.',
                'details' => json_encode([
                    'جنس' => 'پلی‌استر',
                    'سایز' => 'M',
                    'فناوری' => 'ClimaLite'
                ]),
                'stock' => 75,
                'points' => 30,
                'rate' => 5,
                'amount' => 580000,
                'discount' => 10,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 5,
                'color_id' => 2, // سفید
                'category_id' => 7,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 85,
                'view_count' => 195,
            ],

            // Category 8: کفش فوتبال (مردانه)
            [
                'title' => 'کفش فوتبال آدیداس پردیتور',
                'description' => 'کفش فوتبال حرفه‌ای با طراحی پیشرفته برای کنترل و دقت بیشتر توپ.',
                'details' => json_encode([
                    'جنس' => 'چرم مصنوعی پریمیوم',
                    'سایز' => '42',
                    'نوع' => 'زمین چمن'
                ]),
                'stock' => 35,
                'points' => 120,
                'rate' => 5,
                'amount' => 6500000,
                'discount' => 5,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 3, // قرمز
                'category_id' => 8,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 28,
                'view_count' => 320,
            ],
            [
                'title' => 'کفش فوتبال آدیداس کوپا',
                'description' => 'کفش فوتبال کلاسیک با چرم طبیعی برای لمس و راحتی عالی.',
                'details' => json_encode([
                    'جنس' => 'چرم طبیعی',
                    'سایز' => '43',
                    'نوع' => 'زمین چمن و مخلوط'
                ]),
                'stock' => 30,
                'points' => 110,
                'rate' => 5,
                'amount' => 5800000,
                'discount' => 10,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 1, // مشکی
                'category_id' => 8,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 35,
                'view_count' => 285,
            ],
            [
                'title' => 'کفش فوتسال آدیداس ایکس',
                'description' => 'کفش فوتسال سبک و سریع با کفی مخصوص سالن‌های ورزشی.',
                'details' => json_encode([
                    'جنس' => 'مش و PU',
                    'سایز' => '41',
                    'نوع' => 'سالنی'
                ]),
                'stock' => 40,
                'points' => 95,
                'rate' => 4,
                'amount' => 4200000,
                'discount' => 12,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 2,
                'color_id' => 6, // زرد
                'category_id' => 8,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 48,
                'view_count' => 240,
            ],

            // Category 9: لباس (زنانه)
            [
                'title' => 'تاپ ورزشی زنانه آدیداس',
                'description' => 'تاپ ورزشی با ساپورت داخلی و پارچه تنفس‌پذیر برای تمرینات سنگین.',
                'details' => json_encode([
                    'جنس' => 'پلی‌استر و اسپندکس',
                    'سایز' => 'M',
                    'ساپورت' => 'متوسط'
                ]),
                'stock' => 70,
                'points' => 45,
                'rate' => 5,
                'amount' => 980000,
                'discount' => 15,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 3,
                'color_id' => 7, // بنفش
                'category_id' => 9,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 52,
                'view_count' => 175,
            ],
            [
                'title' => 'شلوار لگینگ زنانه آدیداس',
                'description' => 'شلوار لگینگ با پارچه فشرده‌سازی برای راحتی و عملکرد بهتر.',
                'details' => json_encode([
                    'جنس' => 'نایلون و اسپندکس',
                    'سایز' => 'L',
                    'ویژگی' => 'فشرده‌سازی خفیف'
                ]),
                'stock' => 65,
                'points' => 50,
                'rate' => 5,
                'amount' => 1150000,
                'discount' => 10,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 2,
                'color_id' => 1, // مشکی
                'category_id' => 9,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 64,
                'view_count' => 220,
            ],
            [
                'title' => 'ست ورزشی زنانه آدیداس',
                'description' => 'ست کامل ورزشی شامل تاپ و شلوار با طراحی مدرن و رنگ‌های متنوع.',
                'details' => json_encode([
                    'جنس' => 'پلی‌استر',
                    'سایز' => 'M',
                    'تعداد قطعات' => '2 تکه'
                ]),
                'stock' => 55,
                'points' => 70,
                'rate' => 4,
                'amount' => 1650000,
                'discount' => 18,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 3,
                'color_id' => 9, // ارغوانی
                'category_id' => 9,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 46,
                'view_count' => 188,
            ],

            // Category 10: کفش ورزشی
            [
                'title' => 'کفش ورزشی آدیداس سوپراستار',
                'description' => 'کفش آیکونیک آدیداس با طراحی کلاسیک و راحتی بی‌نظیر برای استفاده روزمره.',
                'details' => json_encode([
                    'جنس' => 'چرم مصنوعی',
                    'سایز' => '42',
                    'مدل' => 'کلاسیک'
                ]),
                'stock' => 50,
                'points' => 85,
                'rate' => 5,
                'amount' => 3800000,
                'discount' => 8,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 2, // سفید
                'category_id' => 10,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 72,
                'view_count' => 350,
            ],
            [
                'title' => 'کفش دویدن آدیداس سولار بوست',
                'description' => 'کفش دویدن با فناوری Boost و زیره انعطاف‌پذیر برای دویدن طولانی.',
                'details' => json_encode([
                    'جنس' => 'مش و TPU',
                    'سایز' => '40',
                    'فناوری' => 'Boost'
                ]),
                'stock' => 45,
                'points' => 100,
                'rate' => 5,
                'amount' => 4800000,
                'discount' => 10,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 1,
                'color_id' => 4, // آبی
                'category_id' => 10,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 58,
                'view_count' => 295,
            ],
            [
                'title' => 'کفش ورزشی آدیداس اسمیت',
                'description' => 'کفش اسپرت با سبک مینیمال و طراحی تمیز، مناسب برای هر استایل.',
                'details' => json_encode([
                    'جنس' => 'چرم مصنوعی',
                    'سایز' => '39',
                    'رنگ' => 'سفید-سبز'
                ]),
                'stock' => 60,
                'points' => 80,
                'rate' => 4,
                'amount' => 3500000,
                'discount' => 12,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 2,
                'color_id' => 2, // سفید
                'category_id' => 10,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 82,
                'view_count' => 310,
            ],

            // Category 11: لباس ورزشی
            [
                'title' => 'تی‌شرت ورزشی آدیداس کلیماکول',
                'description' => 'تی‌شرت با فناوری خنک‌کننده برای کاهش دمای بدن در تمرینات سنگین.',
                'details' => json_encode([
                    'جنس' => 'پلی‌استر ریسایکل',
                    'سایز' => 'L',
                    'فناوری' => 'Climacool'
                ]),
                'stock' => 90,
                'points' => 35,
                'rate' => 4,
                'amount' => 720000,
                'discount' => 15,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 4,
                'color_id' => 5, // سبز
                'category_id' => 11,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 95,
                'view_count' => 235,
            ],
            [
                'title' => 'شلوار ورزشی آدیداس تیرو',
                'description' => 'شلوار ورزشی با سه خط کلاسیک آدیداس و پارچه قابل کشش.',
                'details' => json_encode([
                    'جنس' => 'پلی‌استر',
                    'سایز' => 'XL',
                    'جیب' => 'دارد'
                ]),
                'stock' => 75,
                'points' => 40,
                'rate' => 5,
                'amount' => 1050000,
                'discount' => 10,
                'active' => 1,
                'status' => 'completed',
                'vip' => 0,
                'priority' => 3,
                'color_id' => 1, // مشکی
                'category_id' => 11,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 88,
                'view_count' => 260,
            ],
            [
                'title' => 'گرمکن ورزشی آدیداس',
                'description' => 'گرمکن کامل شامل سویشرت و شلوار با زیپ کامل و جیب‌های کاربردی.',
                'details' => json_encode([
                    'جنس' => 'ترکیب پنبه و پلی‌استر',
                    'سایز' => 'L',
                    'ویژگی' => 'ضد باد'
                ]),
                'stock' => 40,
                'points' => 90,
                'rate' => 5,
                'amount' => 2850000,
                'discount' => 12,
                'active' => 1,
                'status' => 'completed',
                'vip' => 1,
                'priority' => 2,
                'color_id' => 10, // خاکستری
                'category_id' => 11,
                'brand_id' => 1,
                'user_id' => $user->id,
                'order_count' => 42,
                'view_count' => 198,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        echo "تعداد " . count($products) . " محصول برای برند آدیداس با موفقیت اضافه شد." . PHP_EOL;
    }
}
