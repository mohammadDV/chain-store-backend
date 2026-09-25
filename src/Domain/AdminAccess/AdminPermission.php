<?php

namespace Domain\AdminAccess;

final class AdminPermission
{
    public const USERS_VIEW = 'users.view';

    public const USERS_CREATE = 'users.create';

    public const USERS_UPDATE = 'users.update';

    public const USERS_DELETE = 'users.delete';

    public const USERS_MANAGE_PERMISSIONS = 'users.manage_permissions';

    public const SETTINGS_VIEW = 'settings.view';

    public const SETTINGS_UPDATE = 'settings.update';

    public const TICKETS_VIEW = 'tickets.view';

    public const TICKETS_UPDATE = 'tickets.update';

    public const TICKETS_DELETE = 'tickets.delete';

    public const TICKET_SUBJECTS_VIEW = 'ticket_subjects.view';

    public const TICKET_SUBJECTS_CREATE = 'ticket_subjects.create';

    public const TICKET_SUBJECTS_UPDATE = 'ticket_subjects.update';

    public const TICKET_SUBJECTS_DELETE = 'ticket_subjects.delete';

    public const TICKET_MESSAGES_VIEW = 'ticket_messages.view';

    public const TICKET_MESSAGES_CREATE = 'ticket_messages.create';

    public const TICKET_MESSAGES_UPDATE = 'ticket_messages.update';

    public const TICKET_MESSAGES_DELETE = 'ticket_messages.delete';

    public const WALLETS_VIEW = 'wallets.view';

    public const WALLETS_UPDATE = 'wallets.update';

    public const WALLET_TRANSACTIONS_VIEW = 'wallet_transactions.view';

    public const WITHDRAWALS_VIEW = 'withdrawals.view';

    public const WITHDRAWALS_APPROVE = 'withdrawals.approve';

    public const WITHDRAWALS_REJECT = 'withdrawals.reject';

    public const TRANSACTIONS_VIEW = 'transactions.view';

    public const TRANSACTIONS_UPDATE = 'transactions.update';

    public const POSTS_VIEW = 'posts.view';

    public const POSTS_CREATE = 'posts.create';

    public const POSTS_UPDATE = 'posts.update';

    public const POSTS_DELETE = 'posts.delete';

    public const NOTIFICATIONS_VIEW = 'notifications.view';

    public const COSTS_VIEW = 'costs.view';

    public const COSTS_CREATE = 'costs.create';

    public const COSTS_UPDATE = 'costs.update';

    public const COSTS_DELETE = 'costs.delete';

    public const COST_CATEGORIES_VIEW = 'cost_categories.view';

    public const COST_CATEGORIES_CREATE = 'cost_categories.create';

    public const COST_CATEGORIES_UPDATE = 'cost_categories.update';

    public const COST_CATEGORIES_DELETE = 'cost_categories.delete';

    public const DISCOUNTS_VIEW = 'discounts.view';

    public const DISCOUNTS_CREATE = 'discounts.create';

    public const DISCOUNTS_UPDATE = 'discounts.update';

    public const DISCOUNTS_DELETE = 'discounts.delete';

    public const BRANDS_VIEW = 'brands.view';

    public const BRANDS_CREATE = 'brands.create';

    public const BRANDS_UPDATE = 'brands.update';

    public const BRANDS_DELETE = 'brands.delete';

    public const PRODUCTS_VIEW = 'products.view';

    public const PRODUCTS_CREATE = 'products.create';

    public const PRODUCTS_UPDATE = 'products.update';

    public const PRODUCTS_DELETE = 'products.delete';

    public const PRODUCTS_AUTO_UPDATE = 'products.auto_update';

    public const CATEGORIES_VIEW = 'categories.view';

    public const CATEGORIES_CREATE = 'categories.create';

    public const CATEGORIES_UPDATE = 'categories.update';

    public const CATEGORIES_DELETE = 'categories.delete';

    public const BANNERS_VIEW = 'banners.view';

    public const BANNERS_CREATE = 'banners.create';

    public const BANNERS_UPDATE = 'banners.update';

    public const BANNERS_DELETE = 'banners.delete';

    public const ORDERS_VIEW = 'orders.view';

    public const ORDERS_UPDATE = 'orders.update';

    public const ORDERS_DELETE = 'orders.delete';

    public const ORDERS_CHANGE_STATUS = 'orders.change_status';

    public const ORDERS_REFUND = 'orders.refund';

    public const REVIEWS_VIEW = 'reviews.view';

    public const REVIEWS_UPDATE = 'reviews.update';

    public const REVIEWS_DELETE = 'reviews.delete';

    public const REVIEWS_APPROVE = 'reviews.approve';

    public const INVENTORY_TRANSACTIONS_VIEW = 'inventory_transactions.view';

    public const INVENTORY_TRANSACTIONS_CREATE = 'inventory_transactions.create';

    public const COLORS_VIEW = 'colors.view';

    public const COLORS_CREATE = 'colors.create';

    public const COLORS_UPDATE = 'colors.update';

    public const COLORS_DELETE = 'colors.delete';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::grouped()))));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function grouped(): array
    {
        return [
            'users' => [
                self::USERS_VIEW,
                self::USERS_CREATE,
                self::USERS_UPDATE,
                self::USERS_DELETE,
                self::USERS_MANAGE_PERMISSIONS,
            ],
            'settings' => [
                self::SETTINGS_VIEW,
                self::SETTINGS_UPDATE,
            ],
            'tickets' => [
                self::TICKETS_VIEW,
                self::TICKETS_UPDATE,
                self::TICKETS_DELETE,
                self::TICKET_SUBJECTS_VIEW,
                self::TICKET_SUBJECTS_CREATE,
                self::TICKET_SUBJECTS_UPDATE,
                self::TICKET_SUBJECTS_DELETE,
                self::TICKET_MESSAGES_VIEW,
                self::TICKET_MESSAGES_CREATE,
                self::TICKET_MESSAGES_UPDATE,
                self::TICKET_MESSAGES_DELETE,
            ],
            'wallets' => [
                self::WALLETS_VIEW,
                self::WALLETS_UPDATE,
                self::WALLET_TRANSACTIONS_VIEW,
                self::WITHDRAWALS_VIEW,
                self::WITHDRAWALS_APPROVE,
                self::WITHDRAWALS_REJECT,
                self::TRANSACTIONS_VIEW,
                self::TRANSACTIONS_UPDATE,
            ],
            'posts' => [
                self::POSTS_VIEW,
                self::POSTS_CREATE,
                self::POSTS_UPDATE,
                self::POSTS_DELETE,
            ],
            'notifications' => [
                self::NOTIFICATIONS_VIEW,
            ],
            'costs' => [
                self::COSTS_VIEW,
                self::COSTS_CREATE,
                self::COSTS_UPDATE,
                self::COSTS_DELETE,
                self::COST_CATEGORIES_VIEW,
                self::COST_CATEGORIES_CREATE,
                self::COST_CATEGORIES_UPDATE,
                self::COST_CATEGORIES_DELETE,
            ],
            'discounts' => [
                self::DISCOUNTS_VIEW,
                self::DISCOUNTS_CREATE,
                self::DISCOUNTS_UPDATE,
                self::DISCOUNTS_DELETE,
            ],
            'brands' => [
                self::BRANDS_VIEW,
                self::BRANDS_CREATE,
                self::BRANDS_UPDATE,
                self::BRANDS_DELETE,
            ],
            'products' => [
                self::PRODUCTS_VIEW,
                self::PRODUCTS_CREATE,
                self::PRODUCTS_UPDATE,
                self::PRODUCTS_DELETE,
                self::PRODUCTS_AUTO_UPDATE,
            ],
            'categories' => [
                self::CATEGORIES_VIEW,
                self::CATEGORIES_CREATE,
                self::CATEGORIES_UPDATE,
                self::CATEGORIES_DELETE,
            ],
            'banners' => [
                self::BANNERS_VIEW,
                self::BANNERS_CREATE,
                self::BANNERS_UPDATE,
                self::BANNERS_DELETE,
            ],
            'orders' => [
                self::ORDERS_VIEW,
                self::ORDERS_UPDATE,
                self::ORDERS_DELETE,
                self::ORDERS_CHANGE_STATUS,
                self::ORDERS_REFUND,
            ],
            'reviews' => [
                self::REVIEWS_VIEW,
                self::REVIEWS_UPDATE,
                self::REVIEWS_DELETE,
                self::REVIEWS_APPROVE,
            ],
            'inventory_transactions' => [
                self::INVENTORY_TRANSACTIONS_VIEW,
                self::INVENTORY_TRANSACTIONS_CREATE,
            ],
            'colors' => [
                self::COLORS_VIEW,
                self::COLORS_CREATE,
                self::COLORS_UPDATE,
                self::COLORS_DELETE,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function brandScopedModules(): array
    {
        return [
            'brands',
            'products',
            'categories',
            'banners',
            'orders',
            'reviews',
            'inventory_transactions',
            'colors',
        ];
    }
}
