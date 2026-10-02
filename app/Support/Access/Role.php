<?php

namespace App\Support\Access;

/**
 * Admin roles and the permissions each one bundles.
 * Edit the map here, then run `php artisan db:seed --class=RolesAndPermissionsSeeder`.
 */
enum Role: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case ProductManager = 'Product Manager';
    case OrderManager = 'Order Manager';
    case ContentManager = 'Content Manager';
    case CustomerSupport = 'Customer Support';

    /** @return list<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            // Super Admin passes every check via Gate::before (see AppServiceProvider).
            self::SuperAdmin => Permission::cases(),

            self::Admin => array_values(array_filter(
                Permission::cases(),
                fn (Permission $p) => ! in_array($p, [Permission::RolesManage, Permission::AdminsManage], true),
            )),

            self::ProductManager => [
                Permission::ProductsView, Permission::ProductsCreate,
                Permission::ProductsUpdate, Permission::ProductsDelete,
                Permission::CategoriesView, Permission::CategoriesManage,
                Permission::InventoryView, Permission::InventoryManage,
            ],

            self::OrderManager => [
                Permission::OrdersView, Permission::OrdersUpdate,
                Permission::OrdersFulfil, Permission::OrdersCancel,
                Permission::ShippingView, Permission::ShippingManage,
                Permission::InventoryView, Permission::CustomersView,
            ],

            self::ContentManager => [
                Permission::BannersManage, Permission::PagesManage,
                Permission::BlogsManage, Permission::ProductsView,
                Permission::CategoriesView,
            ],

            self::CustomerSupport => [
                Permission::CustomersView, Permission::CustomersUpdate,
                Permission::OrdersView, Permission::OrdersUpdate,
                Permission::ReviewsModerate, Permission::ShippingView,
            ],
        };
    }
}
