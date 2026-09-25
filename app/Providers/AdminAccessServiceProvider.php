<?php

namespace App\Providers;

use App\Policies\BannerPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ColorPolicy;
use App\Policies\CostCategoryPolicy;
use App\Policies\CostPolicy;
use App\Policies\DiscountPolicy;
use App\Policies\InventoryTransactionPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PostPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\SettingPolicy;
use App\Policies\TicketMessagePolicy;
use App\Policies\TicketPolicy;
use App\Policies\TicketSubjectPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use App\Policies\WalletPolicy;
use App\Policies\WalletTransactionPolicy;
use App\Policies\WithdrawalTransactionPolicy;
use Domain\AdminAccess\Services\AdminAccessService;
use Domain\Brand\Models\Banner;
use Domain\Brand\Models\Brand;
use Domain\Cost\Models\Cost;
use Domain\Cost\Models\CostCategory;
use Domain\Notification\Models\Notification;
use Domain\Payment\Models\Transaction;
use Domain\Post\Models\Post;
use Domain\Product\Models\Category;
use Domain\Product\Models\Color;
use Domain\Product\Models\Discount;
use Domain\Product\Models\InventoryTransaction;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Review\Models\Review;
use Domain\Setting\Models\Setting;
use Domain\Ticket\Models\Ticket;
use Domain\Ticket\Models\TicketMessage;
use Domain\Ticket\Models\TicketSubject;
use Domain\User\Models\User;
use Domain\Wallet\Models\Wallet;
use Domain\Wallet\Models\WalletTransaction;
use Domain\Wallet\Models\WithdrawalTransaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AdminAccessServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public const POLICIES = [
        User::class => UserPolicy::class,
        Setting::class => SettingPolicy::class,
        Ticket::class => TicketPolicy::class,
        TicketSubject::class => TicketSubjectPolicy::class,
        TicketMessage::class => TicketMessagePolicy::class,
        Wallet::class => WalletPolicy::class,
        WalletTransaction::class => WalletTransactionPolicy::class,
        WithdrawalTransaction::class => WithdrawalTransactionPolicy::class,
        Transaction::class => TransactionPolicy::class,
        Post::class => PostPolicy::class,
        Notification::class => NotificationPolicy::class,
        Cost::class => CostPolicy::class,
        CostCategory::class => CostCategoryPolicy::class,
        Discount::class => DiscountPolicy::class,
        Brand::class => BrandPolicy::class,
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,
        Banner::class => BannerPolicy::class,
        Order::class => OrderPolicy::class,
        Review::class => ReviewPolicy::class,
        InventoryTransaction::class => InventoryTransactionPolicy::class,
        Color::class => ColorPolicy::class,
    ];

    public function register(): void
    {
        $this->app->singleton(AdminAccessService::class);
    }

    public function boot(): void
    {
        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Gate::before(function ($user, string $ability) {
            if (! $user instanceof User) {
                return null;
            }

            if (app(AdminAccessService::class)->isSuperAdmin($user)) {
                return true;
            }

            return null;
        });
    }
}
