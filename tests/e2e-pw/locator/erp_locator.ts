import { Locator, Page } from "@playwright/test";

export class ErpLocators {

    readonly page: Page;

    /**
     *  Plugin Install/Uninstall  
     */


    readonly pluginthreeDot: Locator;
    readonly pluginName : Locator;
    readonly pluginInstallButton: Locator;
    readonly pluginUninstallButton: Locator
    readonly pluginConfirmButton : Locator;
    readonly pluginSearchInput : Locator;
    readonly pluginSuccessMessage : Locator;
    readonly pluginErrorMessage : Locator;

    /**
     * Companies
     */

    readonly allCompaniesCount: Locator;
    readonly companiesMenuLink: Locator;
    readonly companiesTable: Locator;
    readonly companiesCreateButton: Locator;
    readonly companiesNameInput: Locator;
    readonly companiesEmailInput: Locator;
    readonly companiesPhoneInput: Locator;
    readonly companiesStatusToggleOn: Locator;
    readonly companiesStatusToggleOff: Locator;
    readonly companiesSaveButton: Locator;
    readonly companiesSearchInput: Locator;
    readonly companiesRowActionsButton: Locator;
    readonly companiesEditButton: Locator;
    readonly companiesDeleteButton: Locator;
    readonly selectAllCompaniesButton: Locator;
    readonly bulkActionsButton: Locator;
    readonly forceDeleteButton: Locator;
    readonly companiesConfirmDeleteButton: Locator;
    readonly companiesStatusToggle: Locator;
    readonly companiesSuccessToast: Locator;
    readonly companiesErrorToast: Locator;
    readonly companiesFeildValidationMessage: Locator;
    readonly companiesValidationMessage: Locator;

    /**
     * Users
     */

    readonly usersMenuLink: Locator;
    readonly allUsersCount: Locator;
    readonly usersTable: Locator;
    readonly usersCreateButton: Locator;
    readonly usersInviteButton: Locator;
    readonly usersNameInput: Locator;
    readonly usersEmailInput: Locator;
    readonly usersPasswordInput: Locator;
    readonly usersPasswordConfirmationInput: Locator;
    readonly usersRoleSelect: Locator;
    readonly usersCompanySelect: Locator;
    readonly usersCompanySearchInput: Locator;
    readonly usersRoleOption: Locator;
    readonly usersCompanyOption: Locator;
    readonly usersSaveButton: Locator;
    readonly usersSearchInput: Locator;
    readonly usersRowActionsButton: Locator;
    readonly usersEditButton: Locator;
    readonly usersDeleteButton: Locator;
    readonly usersConfirmDeleteButton: Locator;
    readonly selectAllUsersButton: Locator;
    readonly usersBulkActionsButton: Locator;
    readonly usersForceDeleteButton: Locator;
    readonly usersStatusToggle: Locator;
    readonly usersCreateStatusToggle: Locator;
    readonly usersResetPasswordButton: Locator;
    readonly usersChangePasswordInput: Locator;
    readonly usersChangePasswordConfirmationInput: Locator;
    readonly usersChangePasswordSaveButton: Locator;
    readonly userMenuButton: Locator;
    readonly logoutButton: Locator;
    readonly usersSuccessToast: Locator;
    readonly usersErrorToast: Locator;
    readonly userFeildValidationMessage: Locator;
    readonly usersValidationMessage: Locator;
    readonly manageUsersEnableResetCard: Locator;
    readonly manageUsersEnableResetToggle: Locator;
    readonly manageUsersEnableInvitationToggle: Locator;
    readonly settingsSaveButton: Locator;

    /**
     * Sales - Customers, Products, Quotations
     */

    readonly salesCustomersTable: Locator;
    readonly salesCustomerCreateButton: Locator;
    readonly salesCustomerNewCreateButton: Locator;
    readonly salesCustomerNameInput: Locator;
    readonly salesCustomerEmailInput: Locator;
    readonly salesCustomerSaveButton: Locator;
    readonly salesCustomerSearchInput: Locator;
    readonly salesCustomerEditButton: Locator;
    readonly salesCustomerDeleteButton: Locator;


    readonly salesProductsTable: Locator;
    readonly salesProductNewCreateButton: Locator;
    readonly salesProductNameInput: Locator;
    readonly salesProductCategorySelect: Locator;
    readonly salesProductPriceInput: Locator;
    readonly salesProductUomSelect: Locator;
    readonly salesProductSaveButton: Locator;
    readonly salesProductCreateButton: Locator;
    readonly salesProductEditButton: Locator;
    readonly salesProductDeleteButton: Locator;

    readonly salesQuotationCreateButton: Locator;
    readonly salesQuotationEditButton: Locator;
    readonly salesQuotationCustomerSelect: Locator;
    readonly salesQuotationPaymentTermSelect: Locator;
    readonly salesQuotationAddProductButton: Locator;
    readonly salesQuotationProductSelectInput: Locator;
    readonly salesQuotationQuantityInput: Locator;
    readonly salesQuotationSaveButton: Locator;
    readonly salesQuotationDeleteButton: Locator;
    readonly salesQuotationConfirmButton: Locator;
    readonly salesQuotationSendButton: Locator;
    readonly salesQuotationSendSubmitButton: Locator;
    readonly salesQuotationSentRadio: Locator;
    readonly salesQuotationCreateInvoiceButton: Locator;
    readonly salesQuotationInvoiceSubmitButton: Locator;
    readonly salesQuotationDeliveriesTable: Locator;
    readonly salesQuotationDeliveryEditButton: Locator;
    readonly salesDeliveryValidateButton: Locator;
    readonly salesDeliveryNoBackorderButton: Locator;
    readonly salesInvoicesTable: Locator;

    readonly salesSearchInput: Locator;
    readonly salesRowActionsButton: Locator;
    readonly salesEditAction: Locator;
    readonly salesDeleteAction: Locator;
    readonly salesConfirmDeleteButton: Locator;

    readonly salesSelectSearchInput: Locator;
    readonly salesSelectOption: Locator;
    readonly salesSuccessToast: Locator;
    readonly salesValidationMessage: Locator;

    constructor(page: Page) {
        this.page = page;

        /**
         *  Plugin Install/Uninstall  
         */


        this.pluginthreeDot = page.locator('button[title="Actions"], button[title="Aksi"], button[aria-label="Actions"], button[aria-label="Aksi"]');
        this.pluginName = page.locator('.fi-size-lg.fi-font-semibold.fi-ta-text-item.fi-ta-text.fi-inline');
        this.pluginInstallButton = page.getByRole('menuitem', { name: /Install/i }).or(page.getByRole('button', { name: /Install/i }));
        this.pluginUninstallButton = page.getByRole('menuitem', { name: /Uninstall/i }).or(page.getByRole('button', { name: /Uninstall/i }));
        this.pluginConfirmButton = page.getByRole('dialog').getByRole('button', { name: /Install Plugin|Uninstall Plugin|Install|Uninstall|Confirm|Konfirmasi/i });
        this.pluginSearchInput = page.locator('input[placeholder*="Search"], input[placeholder*="Cari"], input[aria-label*="Search"], input[aria-label*="Cari"]').first();
        this.pluginSuccessMessage = page.locator('h3.fi-no-notification-title');
        this.pluginErrorMessage = page.locator('.fi-toast-message-error');

        /**
         * Companies
         */

        this.allCompaniesCount = page.locator('span.fi-badge-label-ctn').nth(0);
        this.companiesMenuLink = page.getByRole("link", { name: /companies/i });
        this.companiesTable = page.locator("table");
        this.companiesCreateButton = page.locator('a[href*="/admin/companies/create"], button[id="key-bindings-1"]').first();
        this.companiesNameInput = page.locator('input[id="form.name"]').first();
        this.companiesEmailInput = page.locator('input[id="form.email"]').first();
        this.companiesPhoneInput = page.locator('input[id="form.phone"]').first();
        this.companiesStatusToggleOn = page.locator('button[aria-checked="true"]');
        this.companiesStatusToggleOff = page.locator('button[aria-checked="false"]');
        this.companiesSaveButton = page.locator('button[type="submit"]').nth(1);
        this.companiesSearchInput = page.locator('.fi-input.fi-input-has-inline-prefix').nth(1);
        this.companiesRowActionsButton = page.locator('button[title="Actions"], button[title="Aksi"], button[aria-label="Actions"], button[aria-label="Aksi"]').first();
        this.companiesEditButton = page.getByRole("menuitem", { name: /Edit|Ubah/i }).or(page.getByRole("link", { name: /Edit|Ubah/i }));
        this.companiesDeleteButton = page.getByRole("menuitem", { name: /Delete|Hapus/i }).or(page.getByRole("button", { name: /Delete|Hapus/i }));
        this.selectAllCompaniesButton = page.locator('input[aria-label="Select/deselect all items for bulk actions."]');
        this.bulkActionsButton = page.locator('button.fi-ac-btn-group').nth(1);
        this.forceDeleteButton = page.getByRole("menuitem", { name: /Force delete|Hapus paksa/i }).or(page.getByRole("button", { name: /Force delete|Hapus paksa/i }));
        this.companiesConfirmDeleteButton = page.getByRole("dialog").getByRole("button", { name: /Delete|Hapus|Force delete|Hapus paksa/i }).first();
        this.companiesStatusToggle = page.locator('button[role="switch"], input[type="checkbox"]').first();
        this.companiesSuccessToast = page.locator("h3.fi-no-notification-title, .fi-toast-message-success").first();
        this.companiesErrorToast = page.locator(".fi-toast-message-error, .fi-input-wrp-error").first();
        this.companiesFeildValidationMessage = page.locator(".fi-fo-field-wrp-error-message", { hasText: /Company name already exists. Please use a unique name./ });
        this.companiesValidationMessage = page.locator(".fi-fo-field-wrp-error-message, .text-danger, .invalid-feedback");

        /**
         * Users
         */

        this.usersMenuLink = page.getByRole("link", { name: /users/i });
        this.allUsersCount = page.locator('span.fi-badge-label-ctn').nth(0);
        this.usersTable = page.locator("table");
        this.usersCreateButton = page.locator('a[href*="/admin/users/create"], button[id="key-bindings-1"]').first();
        this.usersInviteButton = page.locator("a,button").filter({ hasText: /invite user|undang pengguna|user invitation|invite/i }).first();
        this.usersNameInput = page.locator('input[id="form.name"]');
        this.usersEmailInput = page.locator('input[id="form.email"]');
        this.usersPasswordInput = page.locator('input[id="form.password"]');
        this.usersPasswordConfirmationInput = page.locator('input[id="form.password_confirmation"]');
        this.usersRoleSelect = page.locator('div.fi-select-input-value-ctn').nth(0);
        this.usersCompanySelect = page.locator('div.fi-select-input-value-ctn').nth(5);
        this.usersCompanySearchInput = page.locator('input[type="search"], input[placeholder*="Search"], input[placeholder*="search"]');
        this.usersRoleOption = page.locator('[role="option"], .fi-select-option, li').filter({ hasText: /./ });
        this.usersCompanyOption = page.locator('[role="option"], .fi-select-option, li').filter({ hasText: /./ });
        this.usersSaveButton = page.locator('button[x-data="filamentFormButton"]');
        this.usersSearchInput = page.locator('.fi-input.fi-input-has-inline-prefix').nth(1);
        this.usersRowActionsButton = page.locator('button[title="Actions"], button[title="Aksi"], button[aria-label="Actions"], button[aria-label="Aksi"]').first();
        this.usersEditButton = page.getByRole("menuitem", { name: /Edit|Ubah/i }).or(page.getByRole("link", { name: /Edit|Ubah/i })).first();
        this.usersDeleteButton = page.getByRole("menuitem", { name: /Delete|Hapus/i }).or(page.getByRole("button", { name: /Delete|Hapus/i }));
        this.usersConfirmDeleteButton = page.getByRole('dialog').getByRole('button', { name: /Delete|Hapus|Force delete|Hapus paksa/i }).first();
        this.selectAllUsersButton = page.locator('input[aria-label="Select/deselect all items for bulk actions."]');
        this.usersBulkActionsButton = page.locator('button.fi-ac-btn-group').nth(1);
        this.usersForceDeleteButton = page.getByRole("menuitem", { name: /Force delete|Hapus paksa/i }).or(page.getByRole("button", { name: /Force delete|Hapus paksa/i }));
        this.usersStatusToggle = page.locator('button[role="switch"], input[type="checkbox"]').first();
        this.usersCreateStatusToggle = page.locator('button.fi-fo-toggle');
        this.usersResetPasswordButton = page.locator("button,a").filter({ hasText: /Change Password|Ubah Kata Sandi|Reset Password/i }).first();
        this.usersChangePasswordInput = page.locator('input[id="mountedActionsData.0.new_password"], input[name="mountedActionsData.0.new_password"]').first();
        this.usersChangePasswordConfirmationInput = page.locator('input[id="mountedActionsData.0.new_password_confirmation"], input[name="mountedActionsData.0.new_password_confirmation"]').first();
        this.usersChangePasswordSaveButton = page.getByRole('dialog').getByRole('button', { name: /Submit|Simpan|Kirim/i }).first();
        this.userMenuButton = page.locator('button[aria-label="User menu"]');
        this.logoutButton = page.locator('button, a').filter({ hasText: /Log out|Logout|Keluar/i }).first();
        this.usersSuccessToast = page.locator("h3.fi-no-notification-title, .fi-toast-message-success").first();
        this.usersErrorToast = page.locator(".fi-toast-message-error, .fi-input-wrp-error").first();
        this.userFeildValidationMessage = page.locator(".fi-fo-field-wrp-error-message", { hasText: /The email has already been taken./ });
        this.usersValidationMessage = page.locator(".fi-fo-field-wrp-error-message, .text-danger, .invalid-feedback");
        this.manageUsersEnableResetCard = page
            .locator("div,section,li,fieldset")
            .filter({ hasText: /Enable Reset Password|Aktifkan Atur Ulang Kata Sandi|Allow users to reset their password|Izinkan pengguna menyetel ulang kata sandinya/i })
            .first();
        this.manageUsersEnableResetToggle = page.locator('button[aria-labelledby*="enable_reset_password"], input[id="data.enable_reset_password"]').first();
        this.manageUsersEnableInvitationToggle = page.locator('button[aria-labelledby*="enable_user_invitation"], input[id="data.enable_user_invitation"]').first();
        this.settingsSaveButton = page.getByRole("button", { name: /Save changes|save|update|submit/i }).first();

        /**
         * Sales - Customers, Products, Quotations
         */

        this.salesCustomersTable = page.locator("div.fi-ta-content-grid, div.fi-ta-empty-state, table");
        this.salesCustomerNewCreateButton = page.locator('a[href*="/customers/create"], button[id="key-bindings-1"]').first();
        this.salesCustomerNameInput = page.locator('input[id="form.name"]').first();
        this.salesCustomerEmailInput = page.locator('input[id="form.email"]').first();
        this.salesCustomerCreateButton = page.locator('button[id="key-bindings-1"]').first();
        this.salesCustomerSaveButton = page.locator('button[id="key-bindings-2"]').first();
        this.salesCustomerDeleteButton = page.getByRole("menuitem", { name: /Delete|Hapus/i }).or(page.getByRole("button", { name: /Delete|Hapus/i })).first();
        this.salesCustomerSearchInput = page.locator('.fi-input.fi-input-has-inline-prefix').nth(1);
        this.salesCustomerEditButton = page.getByRole('menuitem', { name: /Edit|Ubah/i }).or(page.getByRole('link', { name: /Edit|Ubah/i })).first();

        this.salesProductsTable = page.locator("table, div.fi-ta-empty-state");
        this.salesProductNewCreateButton = page.locator('a[href*="/products/create"], button[id="key-bindings-1"]').first();
        this.salesProductNameInput = page.locator('input[id="form.name"]').first();
        this.salesProductCategorySelect = page.locator('input[id="form.category_id"], [role="combobox"][aria-label*="Category"], [role="combobox"][aria-labelledby*="Category"]').first();
        this.salesProductPriceInput = page.locator('input[id="form.price"]').first();
        this.salesProductUomSelect = page.locator('input[id="form.uom_id"], [role="combobox"][aria-label*="UOM"], [role="combobox"][aria-labelledby*="UOM"]').first();
        this.salesProductCreateButton = page.locator('button[id="key-bindings-1"]').first();
        this.salesProductEditButton = page.getByRole('menuitem', { name: /Edit|Ubah/i }).or(page.getByRole('link', { name: /Edit|Ubah/i }));
        this.salesProductSaveButton = page.locator('button[id="key-bindings-2"]').first();
        this.salesProductDeleteButton = page.getByRole('menuitem', { name: /Delete|Hapus/i }).or(page.getByRole('button', { name: /Delete|Hapus/i }));

        this.salesQuotationCreateButton = page.locator('a[href*="/quotations/create"], button[id="key-bindings-1"]').first();
        this.salesQuotationEditButton = page.getByRole('menuitem', { name: /Edit|Ubah/i }).or(page.getByRole('link', { name: /Edit|Ubah/i })).first();
        this.salesQuotationCustomerSelect = page.locator('[wire\\:key$="form.partner_id"] button.fi-select-input-btn').first();
        this.salesQuotationPaymentTermSelect = page.locator('[wire\\:key$="form.payment_term_id"] button.fi-select-input-btn').first();
        this.salesQuotationAddProductButton = page.getByRole("button", { name: /Add Product/i }).first();
        this.salesQuotationProductSelectInput = page.locator('[wire\\:key*=".form.products."][wire\\:key*=".product_id."] button.fi-select-input-btn');
        this.salesQuotationQuantityInput = page.locator('input[id^="form.products."][id$=".product_qty"]');
        this.salesQuotationDeleteButton = page.getByRole('menuitem', { name: /Delete|Hapus/i }).or(page.getByRole('button', { name: /Delete|Hapus/i })).first();
        this.salesQuotationSaveButton = page.getByRole('button', { name: /^(Create|Save changes|Submit)$/i }).first();
        this.salesQuotationConfirmButton = page.getByRole("button", { name: /Confirm/i }).first();
        this.salesQuotationSendButton = page.getByRole("button", { name: /Send by Email|Send/i }).first();
        this.salesQuotationSendSubmitButton = page.getByRole("dialog").getByRole("button", { name: /Send|Submit/i }).first(); 
        this.salesQuotationSentRadio = page.getByRole("radio", { name: /Quotation Sent/i });
        this.salesQuotationCreateInvoiceButton = page.getByRole("button", { name: /Create Invoice/i }).first();
        this.salesQuotationInvoiceSubmitButton = page.getByRole("dialog").getByRole("button", { name: /Create Invoice/i }).first();
        this.salesQuotationDeliveriesTable = page.locator("table, div.fi-ta-empty-state");
        this.salesQuotationDeliveryEditButton = page.getByRole('table').getByRole('link', { name: /Edit|Ubah/i });
        this.salesDeliveryValidateButton = page.getByRole("button", { name: /Validate/i }).first();
        this.salesDeliveryNoBackorderButton = page.getByRole("button", { name: /No Backorder/i }).first();
        this.salesInvoicesTable = page.locator("table, div.fi-ta-empty-state");

        this.salesSearchInput = page.locator(".fi-input.fi-input-has-inline-prefix").nth(1);
        this.salesRowActionsButton = page.getByRole('button', { name: /Actions|Aksi/i });
        this.salesEditAction = page.getByRole("menuitem", { name: /Edit|Ubah/i }).first();
        this.salesDeleteAction = page.getByRole("menuitem", { name: /Delete|Hapus/i }).first();
        this.salesConfirmDeleteButton = page.getByRole("dialog").getByRole("button", { name: /Delete|Hapus|Force delete|Hapus paksa/i }).first();

        this.salesSelectSearchInput = page.locator('.fi-dropdown-panel[role="listbox"]:visible input.fi-input[aria-label="Search"]').last();
        this.salesSelectOption = page.locator('.fi-dropdown-panel[role="listbox"]:visible [role="option"]');
        this.salesSuccessToast = page.locator("h3.fi-no-notification-title, .fi-toast-message-success").first();
        this.salesValidationMessage = page.locator(".fi-fo-field-wrp-error-message, .text-danger, .invalid-feedback");
    }
}
