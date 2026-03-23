<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CustomerPanelProvider;
use Webkul\Analytic\AnalyticServiceProvider;
use Webkul\Chatter\ChatterServiceProvider;
use Webkul\Field\FieldServiceProvider;
use Webkul\Partner\PartnerServiceProvider;
use Webkul\PluginManager\PluginManagerServiceProvider;
use Webkul\Security\SecurityServiceProvider;
use Webkul\Support\SupportServiceProvider;
use Webkul\TableViews\TableViewsServiceProvider;

return [
    // App Providers
    AppServiceProvider::class,
    AdminPanelProvider::class,
    CustomerPanelProvider::class,

    // Core Plugins — included with `php artisan erp:install`, no separate install needed
    AnalyticServiceProvider::class,
    ChatterServiceProvider::class,
    FieldServiceProvider::class,
    PartnerServiceProvider::class,
    SecurityServiceProvider::class,
    SupportServiceProvider::class,
    TableViewsServiceProvider::class,
    PluginManagerServiceProvider::class,

    // Installable Plugins — each requires `php artisan [module]:install`
    // Webkul\Accounting\AccountingServiceProvider::class,
    // Webkul\Account\AccountServiceProvider::class,
    // Webkul\Blog\BlogServiceProvider::class,
    // Webkul\Contact\ContactServiceProvider::class,
    // Webkul\Employee\EmployeeServiceProvider::class,
    // Webkul\FullCalendar\FullCalendarServiceProvider::class,
    // Webkul\Inventory\InventoryServiceProvider::class,
    // Webkul\Invoice\InvoiceServiceProvider::class,
    // Webkul\Payment\PaymentServiceProvider::class,
    // Webkul\Product\ProductServiceProvider::class,
    // Webkul\Project\ProjectServiceProvider::class,
    // Webkul\Purchase\PurchaseServiceProvider::class,
    // Webkul\Recruitment\RecruitmentServiceProvider::class,
    // Webkul\Sale\SaleServiceProvider::class,
    // Webkul\TimeOff\TimeOffServiceProvider::class,
    // Webkul\Timesheet\TimesheetServiceProvider::class,
    // Webkul\Website\WebsiteServiceProvider::class,
];
