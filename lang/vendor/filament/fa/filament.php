<?php

return [
    'pages' => [
        'dashboard' => [
            'title' => 'داشبورد',
        ],
    ],
    'resources' => [
        'user' => [
            'label' => 'کاربر',
            'plural_label' => 'کاربران',
        ],
        'ticket' => [
            'label' => 'تیکت',
            'plural_label' => 'تیکت‌ها',
        ],
        'ticket_message' => [
            'label' => 'پیام تیکت',
            'plural_label' => 'پیام‌های تیکت',
        ],
        'ticket_subject' => [
            'label' => 'موضوع تیکت',
            'plural_label' => 'موضوعات تیکت',
        ],
    ],
    'components' => [
        'actions' => [
            'modal' => [
                'heading' => 'تایید عملیات',
                'description' => 'آیا از انجام این عملیات اطمینان دارید؟',
                'actions' => [
                    'confirm' => [
                        'label' => 'تایید',
                    ],
                    'cancel' => [
                        'label' => 'انصراف',
                    ],
                ],
            ],
        ],
        'pagination' => [
            'label' => 'صفحه‌بندی',
            'overview' => [
                'text' => 'نمایش :first تا :last از :total نتیجه',
                'text_showing' => 'نمایش :first تا :last از :total نتیجه',
                'text_showing_total' => 'نمایش :first تا :last از :total نتیجه',
            ],
            'fields' => [
                'records_per_page' => [
                    'label' => 'تعداد در هر صفحه',
                    'options' => [
                        'all' => 'همه',
                    ],
                ],
            ],
            'actions' => [
                'first' => [
                    'label' => 'اولین',
                ],
                'go_to_page' => [
                    'label' => 'برو به صفحه :page',
                ],
                'last' => [
                    'label' => 'آخرین',
                ],
                'next' => [
                    'label' => 'بعدی',
                ],
                'previous' => [
                    'label' => 'قبلی',
                ],
            ],
        ],
        'tables' => [
            'empty' => [
                'heading' => 'هیچ رکوردی یافت نشد',
                'description' => 'هیچ رکوردی برای نمایش وجود ندارد.',
            ],
            'selection_indicator' => [
                'selected_count' => '1 رکورد انتخاب شده',
                'selected_count_text' => ':count رکورد انتخاب شده',
            ],
            'filters' => [
                'actions' => [
                    'remove_all' => [
                        'label' => 'حذف همه فیلترها',
                    ],
                ],
                'indicator' => [
                    'count' => '1 فیلتر اعمال شده',
                    'count_text' => ':count فیلتر اعمال شده',
                ],
            ],
            'reorder_indicator' => [
                'title' => 'مرتب‌سازی',
            ],
            'search' => [
                'placeholder' => 'جستجو...',
            ],
            'columns' => [
                'text' => [
                    'search' => [
                        'placeholder' => 'جستجو...',
                    ],
                ],
            ],
            'actions' => [
                'view' => [
                    'label' => 'مشاهده',
                ],
                'edit' => [
                    'label' => 'ویرایش',
                ],
                'delete' => [
                    'label' => 'حذف',
                ],
            ],
            'bulk_actions' => [
                'delete' => [
                    'label' => 'حذف انتخاب شده‌ها',
                ],
            ],
        ],
        'forms' => [
            'actions' => [
                'create' => [
                    'label' => 'ایجاد',
                ],
                'create_another' => [
                    'label' => 'ایجاد و ایجاد دیگری',
                ],
                'save' => [
                    'label' => 'ذخیره',
                ],
                'save_and_close' => [
                    'label' => 'ذخیره و بستن',
                ],
                'save_and_create_another' => [
                    'label' => 'ذخیره و ایجاد دیگری',
                ],
                'edit' => [
                    'label' => 'ویرایش',
                ],
                'delete' => [
                    'label' => 'حذف',
                ],
                'view' => [
                    'label' => 'مشاهده',
                ],
            ],
            'fields' => [
                'boolean' => [
                    'true' => 'بله',
                    'false' => 'خیر',
                ],
                'select' => [
                    'actions' => [
                        'create_option' => [
                            'modal' => [
                                'heading' => 'ایجاد گزینه جدید',
                                'description' => 'آیا می‌خواهید گزینه جدیدی ایجاد کنید؟',
                            ],
                        ],
                    ],
                    'boolean' => [
                        'true' => 'بله',
                        'false' => 'خیر',
                    ],
                    'placeholder' => 'انتخاب کنید...',
                ],
                'text_input' => [
                    'placeholder' => 'متن را وارد کنید...',
                ],
                'textarea' => [
                    'placeholder' => 'متن را وارد کنید...',
                ],
                'date_picker' => [
                    'placeholder' => 'تاریخ را انتخاب کنید...',
                ],
                'file_upload' => [
                    'placeholder' => 'فایل را انتخاب کنید...',
                    'actions' => [
                        'upload' => [
                            'label' => 'آپلود',
                        ],
                        'upload_new' => [
                            'label' => 'آپلود جدید',
                        ],
                    ],
                    'error' => [
                        'max_size' => 'حجم فایل نمی‌تواند بیشتر از :size باشد.',
                        'min_size' => 'حجم فایل نمی‌تواند کمتر از :size باشد.',
                    ],
                    'hint' => [
                        'max_size' => 'حداکثر حجم فایل: :size',
                        'min_size' => 'حداقل حجم فایل: :size',
                    ],
                ],
                'tags_input' => [
                    'placeholder' => 'تگ جدید...',
                ],
                'wizard' => [
                    'actions' => [
                        'previous_step' => [
                            'label' => 'قبلی',
                        ],
                        'next_step' => [
                            'label' => 'بعدی',
                        ],
                    ],
                ],
            ],
        ],
        'notifications' => [
            'actions' => [
                'close' => [
                    'label' => 'بستن',
                ],
                'mark_as_read' => [
                    'label' => 'علامت‌گذاری به عنوان خوانده شده',
                ],
            ],
        ],
        'modals' => [
            'actions' => [
                'close' => [
                    'label' => 'بستن',
                ],
            ],
        ],
        'common' => [
            'status' => [
                'pending' => 'در انتظار',
                'approved' => 'تایید شده',
                'rejected' => 'رد شده',
                'completed' => 'تکمیل شده',
                'cancelled' => 'لغو شده',
                'active' => 'فعال',
                'inactive' => 'غیرفعال',
            ],
            'actions' => [
                'confirm' => 'تایید',
                'cancel' => 'انصراف',
                'save' => 'ذخیره',
                'edit' => 'ویرایش',
                'delete' => 'حذف',
                'view' => 'مشاهده',
                'create' => 'ایجاد',
            ],
            'fields' => [
                'title' => 'عنوان',
                'description' => 'توضیحات',
                'status' => 'وضعیت',
                'created_at' => 'تاریخ ایجاد',
                'updated_at' => 'تاریخ بروزرسانی',
            ],
        ],
    ],
    'pages' => [
        'auth' => [
            'login' => [
                'title' => 'ورود',
                'heading' => 'ورود به سیستم',
                'form' => [
                    'email' => [
                        'label' => 'ایمیل',
                    ],
                    'password' => [
                        'label' => 'رمز عبور',
                    ],
                    'remember' => [
                        'label' => 'مرا به خاطر بسپار',
                    ],
                    'actions' => [
                        'authenticate' => [
                            'label' => 'ورود',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
