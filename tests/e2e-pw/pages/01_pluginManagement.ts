import { Page, expect } from '@playwright/test';
import { ErpLocators } from '../locator/erp_locator';

export class PluginManagementPage {

    /**
     * Page and Locators
     */
    readonly page: Page;
    readonly erpLocators: ErpLocators;

    constructor(page: Page) {
        this.page = page

        this.erpLocators = new ErpLocators(page);
    }

    /**
     * Navigate to Plugin Management Page
     */
    async gotoPluginManagementPage() {
        await this.page.goto('/admin/plugins');
        await expect(this.page).toHaveURL(/.*admin/);
        await expect(this.erpLocators.pluginSearchInput).toBeVisible();
    }

    /**
     * Install all plugins
     */
    async installAllPlugins() {
        const pluginNames = (await this.erpLocators.pluginName.allInnerTexts())
            .map((name) => name.trim())
            .filter(Boolean);

        for (const pluginName of pluginNames) {
            await this.installPluginByName(pluginName);
        }
    }

    /**
     * Uninstall all plugins
     */
    async uninstallAllPlugins() {
        const pluginNames = (await this.erpLocators.pluginName.allInnerTexts())
            .map((name) => name.trim())
            .filter(Boolean);

        for (const pluginName of pluginNames) {
            await this.openPluginActions(pluginName);

            if (await this.erpLocators.pluginUninstallButton.first().isVisible()) {
                await this.erpLocators.pluginUninstallButton.first().click();
                await expect(this.erpLocators.pluginConfirmButton).toBeVisible();
                await this.erpLocators.pluginConfirmButton.click();
                await expect(this.erpLocators.pluginSuccessMessage).toBeVisible();
                await this.page.goto('/admin/plugins');
                await this.page.waitForLoadState("networkidle");
            }
        }
    }

    /**
     * Install plugin by name if not installed
     */
    async installPluginByName(pluginName: string) {
        await this.openPluginActions(pluginName);

        if (await this.erpLocators.pluginUninstallButton.first().isVisible()) {
            return;
        }

        await this.erpLocators.pluginInstallButton.first().click();
        await expect(this.erpLocators.pluginConfirmButton).toBeVisible();
        await this.erpLocators.pluginConfirmButton.click();
        await expect(this.erpLocators.pluginSuccessMessage).toBeVisible();
        await this.page.goto('/admin/plugins');
        await this.page.waitForLoadState("networkidle");
    }

    private async openPluginActions(pluginName: string): Promise<void> {
        await this.erpLocators.pluginSearchInput.fill(pluginName);

        const exactName = new RegExp(`^${this.escapeRegExp(pluginName)}$`, 'i');
        const pluginLabel = this.erpLocators.pluginName.filter({ hasText: exactName }).first();

        await expect(pluginLabel).toBeVisible();

        // Walk up the DOM from the plugin label to find the nearest ancestor card
        // that contains an Actions button (supports both EN and ID locales).
        const pluginCard = pluginLabel.locator(
            'xpath=ancestor::*[.//button[@title="Actions" or @title="Aksi" or @aria-label="Actions" or @aria-label="Aksi"]][1]'
        );
        const actionButton = pluginCard.locator(
            'button[title="Actions"], button[title="Aksi"], button[aria-label="Actions"], button[aria-label="Aksi"]'
        ).first();

        await expect(actionButton).toBeVisible();
        await actionButton.click();
    }

    private escapeRegExp(value: string): string {
        return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // /**
    //  * Install Accounting Plugin
    //  */
    // async AccountingInstall() {
    //     await this.erpLocators.pluginSearchInput.fill('Accounting');
    //     await this.page.waitForTimeout(2000);
    //     await this.erpLocators.pluginthreeDot.click();
    //     if (await this.erpLocators.pluginUninstallButton.isVisible()) {
    //         console.log('Accounting Plugin is already installed.');
    //         return;
    //     }else {
    //     await this.erpLocators.pluginInstallButton.click();
    //     await this.page.waitForTimeout(3000); // Wait for 3 seconds to allow installation to complete
    //     await this.erpLocators.pluginConfirmButton.click();
    //     console.log(`Installing Plugin: Accounting`);
    //     await expect(this.erpLocators.pluginSuccessMessage).toBeVisible();
    //     }
    // }

}
