<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    "accepted"         => ":attribute باید پذیرفته شده باشد.",
    "accepted_if"      => ":attribute باید پذیرفته شده باشد وقتی :other برابر :value است.",
    "active_url"       => "آدرس :attribute معتبر نیست",
    "after"            => ":attribute باید تاریخی بعد از :date باشد.",
    'after_or_equal'   => ':attribute باید تاریخی بعد از :date، یا مطابق با آن باشد.',
    "alpha"            => ":attribute باید فقط شامل حروف الفبا باشد.",
    "alpha_dash"       => ":attribute باید فقط شامل حروف الفبا، اعداد، خط تیره و زیرخط باشد.",
    "alpha_num"        => ":attribute باید فقط شامل حروف الفبا و اعداد باشد.",
    "array"            => ":attribute باید آرایه باشد.",
    "before"           => ":attribute باید تاریخی قبل از :date باشد.",
    'before_or_equal' => ':attribute باید تاریخی قبل از :date، یا مطابق با آن باشد.',
    "between"          => [
        "numeric" => ":attribute باید بین :min و :max باشد.",
        "file"    => ":attribute باید بین :min و :max کیلوبایت باشد.",
        "string"  => ":attribute باید بین :min و :max کاراکتر باشد.",
        "array"   => ":attribute باید بین :min و :max آیتم باشد.",
    ],
    "boolean"          => "فیلد :attribute فقط می‌تواند true و یا false باشد",
    "confirmed"        => ":attribute با تاییدیه مطابقت ندارد.",
    "current_password" => "رمز عبور اشتباه است.",
    "date"             => ":attribute یک تاریخ معتبر نیست.",
    'date_equals'      => ':attribute باید یک تاریخ برابر با تاریخ :date باشد.',
    "date_format"      => ":attribute با الگوی :format مطابقت ندارد.",
    "declined"         => ":attribute باید رد شده باشد.",
    'declined_if'      => ':attribute باید رد شده باشد وقتی :other برابر :value است.',
    "different"        => ":attribute و :other باید متفاوت باشند.",
    "digits"           => ":attribute باید :digits رقم باشد.",
    "digits_between"   => ":attribute باید بین :min و :max رقم باشد.",
    'dimensions'       => 'dimensions مربوط به فیلد :attribute اشتباه است.',
    'distinct'         => ':attribute مقدار تکراری دارد.',
    "email"            => ":attribute باید یک ایمیل معتبر باشد.",
    'ends_with'        => ':attribute باید با یکی از این موارد پایان یابد: :values.',
    "enum"             => ":attribute انتخاب شده معتبر نیست.",
    "exists"           => ":attribute انتخاب شده، معتبر نیست.",
    'file' 	       => 'فیلد :attribute باید فایل باشد.',
    "filled"           => "فیلد :attribute الزامی است",
    'gt' => [
        'numeric' => ':attribute باید بیشتر از :value باشد.',
        'file'    => ':attribute باید بیشتر از :value کیلوبایت باشد.',
        'string'  => ':attribute باید بیشتر از :value کاراکتر باشد.',
        'array'   => ':attribute باید بیشتر از :value ایتم باشد.',
    ],
    'gte' => [
        'numeric' => ':attribute باید بیشتر یا برابر :value باشد.',
        'file'    => ':attribute باید بیشتر یا برابر :value کیلوبایت باشد.',
        'string'  => ':attribute باید بیشتر یا برابر :value کاراکتر باشد.',
        'array'   => ':attribute باید :value ایتم یا بیشتر را داشته باشد.',
    ],
    "image"            => ":attribute باید تصویر باشد.",
    "in"               => ":attribute انتخاب شده، معتبر نیست.",
    "in_array"         => "فیلد :attribute در :other وجود ندارد.",
    "integer"          => ":attribute باید عدد صحیح باشد.",
    "ip"               => ":attribute باید IP آدرس معتبر باشد.",
    'ipv4'             => ':attribute باید یک ادرس درست IPv4 باشد.',
    'ipv6'             => ':attribute باید یک ادرس درست IPv6 باشد.',
    'json'             => ':attribute باید یک رشته JSON معتبر باشد.',
    'lt' => [
        'numeric' => ':attribute باید کمتر از :value باشد.',
        'file'    => ':attribute باید کمتر از :value کیلوبایت باشد.',
        'string'  => ':attribute باید کمتر از :value کاراکتر باشد.',
        'array'   => ':attribute باید :value ایتم یا کمتر را داشته باشد.',
    ],
    'lte' => [
        'numeric' => ':attribute باید کمتر یا برابر :value باشد.',
        'file'    => ':attribute باید کمتر یا برابر :value کیلوبایت باشد.',
        'string'  => ':attribute باید کمتر یا برابر :value کاراکتر باشد.',
        'array'   => ':attribute باید :value ایتم یا کمتر را داشته باشد.',
    ],
    "mac_address"      => ":attribute باید یک آدرس MAC معتبر باشد.",
    "max"              => [
        "numeric" => ":attribute نباید بزرگتر از :max باشد.",
        "file"    => ":attribute نباید بزرگتر از :max کیلوبایت باشد.",
        "string"  => ":attribute نباید بیشتر از :max کاراکتر باشد.",
        "array"   => ":attribute نباید بیشتر از :max آیتم باشد.",
    ],
    "max_digits"       => ":attribute نباید بیشتر از :max رقم داشته باشد.",
    "mimes"            => ":attribute باید فایلی از این نوع‌ها باشد: :values.",
    'mimetypes'        => ':attribute باید تایپ ان از نوع: :values باشد.',
    "min"              => [
        "numeric" => ":attribute نباید کوچکتر از :min باشد.",
        "file"    => ":attribute نباید کوچکتر از :min کیلوبایت باشد.",
        "string"  => ":attribute نباید کمتر از :min کاراکتر باشد.",
        "array"   => ":attribute نباید کمتر از :min آیتم باشد.",
    ],
    "min_digits"       => ":attribute باید حداقل :min رقم داشته باشد.",
    "multiple_of"      => ":attribute باید مضربی از :value باشد.",
    "not_in"           => ":attribute انتخاب شده، معتبر نیست.",
    'not_regex'        => 'فرمت :attribute معتبر نیست.',
    "numeric"          => ":attribute باید عدد باشد.",
    'password'         => [
        'letters' => ':attribute باید حداقل شامل یک حرف باشد.',
        'mixed' => ':attribute باید حداقل شامل یک حرف بزرگ و یک حرف کوچک باشد.',
        'numbers' => ':attribute باید حداقل شامل یک عدد باشد.',
        'symbols' => ':attribute باید حداقل شامل یک نماد باشد.',
        'uncompromised' => ':attribute در نشت داده‌های اخیر ظاهر شده است. لطفا یک :attribute متفاوت انتخاب کنید.',
    ],
    'present'          => ':attribute باید وجود داشته باشد.',
    "prohibited"       => "فیلد :attribute ممنوع است.",
    'prohibited_if'    => 'فیلد :attribute زمانی که :other برابر :value است ممنوع است.',
    'prohibited_unless' => 'فیلد :attribute زمانی که :other در :values وجود دارد ممنوع است.',
    'prohibits'         => 'فیلد :attribute زمانی که :other وجود دارد ممنوع است.',
    "regex"            => "فرمت :attribute معتبر نیست.",
    "required"         => "فیلد :attribute الزامی است",
    "required_array_keys" => "فیلد :attribute باید شامل آیتم‌های :values باشد.",
    "required_if"      => "فیلد :attribute زمانی که :other برابر :value است الزامی است.",
    "required_if_accepted" => "فیلد :attribute زمانی که :other پذیرفته شده است الزامی است.",
    'required_unless'  => 'فیلد :attribute زمانی که :other در :values وجود دارد الزامی است.',
    "required_with"    => ":attribute الزامی است زمانی که :values موجود است.",
    "required_with_all" => ":attribute الزامی است زمانی که :values موجود است.",
    "required_without" => ":attribute الزامی است زمانی که :values موجود نیست.",
    "required_without_all" => ":attribute الزامی است زمانی که :values موجود نیست.",
    "same"             => ":attribute و :other باید مطابقت داشته باشند.",
    "size"             => [
        "numeric" => ":attribute باید برابر با :size باشد.",
        "file"    => ":attribute باید برابر با :size کیلوبایت باشد.",
        "string"  => ":attribute باید برابر با :size کاراکتر باشد.",
        "array"   => ":attribute باسد شامل :size آیتم باشد.",
    ],
    'starts_with'      => ':attribute باید با یکی از این موارد شروع شود: :values.',
    "string"           => ":attribute باید رشته باشد.",
    "timezone"         => "فیلد :attribute باید یک منطقه صحیح باشد.",
    "unique"           => ":attribute قبلا انتخاب شده است.",
    'uploaded'         => 'بارگذاری فایل :attribute موفقیت آمیز نبود.',
    "url"              => "فرمت آدرس :attribute اشتباه است.",
    'uuid'             => ':attribute باید یک فرمت درست UUID باشد.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'send_date' => [
            'required' => 'تاریخ ارسال الزامی است',
            'date' => 'فرمت تاریخ ارسال نامعتبر است',
            'after_or_equal' => 'تاریخ ارسال نمی‌تواند قبل از امروز باشد',
            'before_or_equal' => 'تاریخ ارسال نمی‌تواند بعد از تاریخ دریافت باشد',
        ],
        'receive_date' => [
            'required' => 'برای پروژه‌های ارسالی، تاریخ دریافت الزامی است',
            'date' => 'فرمت تاریخ دریافت نامعتبر است',
            'after_or_equal' => 'تاریخ دریافت نمی‌تواند قبل از تاریخ ارسال باشد',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */
    'attributes' => [
        "path_type" => "نوع سفر",
        "type" => "نوع",
        "weight" => "وزن",
        "dimensions" => "ابعاد",
        "project_count" => "تعداد پروژه",
        "priod" => "مدت بسته",
        "amount" => "مبلغ",
        "nickname" => "نام مستعار",
        "customer_number" => "شماره مشتری",
        "name" => "نام",
        "username" => "نام کاربری",
        "email" => "پست الکترونیکی",
        "first_name" => "نام",
        "last_name" => "نام خانوادگی",
        "family" => "نام خانوادگی",
        "password" => "رمز عبور",
        "password_confirmation" => "تاییدیه ی رمز عبور",
        "city" => "شهر",
        "country" => "کشور",
        "address" => "نشانی",
        "phone" => "تلفن",
        "mobile" => "تلفن همراه",
        "age" => "سن",
        "sex" => "جنسیت",
        "gender" => "جنسیت",
        "day" => "روز",
        "month" => "ماه",
        "year" => "سال",
        "hour" => "ساعت",
        "minute" => "دقیقه",
        "second" => "ثانیه",
        "title" => "عنوان",
        "text" => "متن",
        "content" => "محتوا",
        "description" => "توضیحات",
        "excerpt" => "گلچین کردن",
        "date" => "تاریخ",
        "time" => "زمان",
        "available" => "موجود",
        "size" => "اندازه",
		"file" => "فایل",
		"category_id" => "دسته بندی",
		"tags" => "هشتگ",
		"video" => "ویدئو",
        'pre_title'                     => 'توضیح کوتاه قبل از عنوان',
		"image"     => "تصویر",
		"summary"   => "سوتیتر",
		"fullname"  => "نام کامل",
		"message"  => "پیام",
		"g-recaptcha-response"  => "کپچا",
		"privacy_policy"  => "قوانین و مقررات",
        'send_date' => 'تاریخ ارسال',
        'receive_date' => 'تاریخ دریافت',
        'confirmation_code' => 'کد تایید',
    ],
];
