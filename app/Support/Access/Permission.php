<?php

namespace App\Support\Access;

/**
 * Every permission in the system, as "<module>.<action>".
 * Controllers/policies check these; roles are only bundles of them.
 */
enum Permission: string
{
    case ProductsView = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsDelete = 'products.delete';

    case CategoriesView = 'categories.view';
    case CategoriesManage = 'categories.manage';

    case InventoryView = 'inventory.view';
    case InventoryManage = 'inventory.manage';

    case OrdersView = 'orders.view';
    case OrdersUpdate = 'orders.update';
    case OrdersFulfil = 'orders.fulfil';
    case OrdersRefund = 'orders.refund';
    case OrdersCancel = 'orders.cancel';

    case ShippingView = 'shipping.view';
    case ShippingManage = 'shipping.manage';

    case CustomersView = 'customers.view';
    case CustomersUpdate = 'customers.update';

    case CouponsView = 'coupons.view';
    case CouponsManage = 'coupons.manage';

    case ReviewsModerate = 'reviews.moderate';

    case BannersManage = 'banners.manage';
    case PagesManage = 'pages.manage';
    case BlogsManage = 'blogs.manage';

    case ReportsView = 'reports.view';

    case SettingsView = 'settings.view';
    case SettingsManage = 'settings.manage';

    case AdminsView = 'admins.view';
    case AdminsManage = 'admins.manage';
    case RolesManage = 'roles.manage';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
