import { expect, type Locator, type Page } from "@playwright/test";
import { loginWithCredentials } from "../utils/admin";

export type FormTransferConfigurationInput = {
    name: string;
    code: string;
    uidPrefix: string;
    description?: string;
};

export type BankConfigurationInput = {
    code: string;
    name: string;
    shortName?: string;
    sortOrder?: string;
};

export type DivisionConfigurationInput = {
    formTransfer: string;
    name: string;
    description?: string;
};

export type ReferenceNoteConfigurationInput = {
    formTransfer: string;
    label: string;
    description?: string;
};

export class FormTransferConfigurationPage {
    readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    async gotoFormTransfersConfiguration(): Promise<void> {
        await this.gotoConfigurationPage("/admin/form-transfer/configurations/form-transfers");
    }

    async gotoBanksConfiguration(): Promise<void> {
        await this.gotoConfigurationPage("/admin/form-transfer/configurations/banks");
    }

    async gotoDivisionsConfiguration(): Promise<void> {
        await this.gotoConfigurationPage("/admin/form-transfer/configurations/divisions");
    }

    async gotoReferenceNotesConfiguration(): Promise<void> {
        await this.gotoConfigurationPage("/admin/form-transfer/configurations/reference-notes");
    }

    async gotoApprovalWorkflowsConfiguration(): Promise<void> {
        await this.gotoConfigurationPage("/admin/form-transfer/configurations/approval-workflows");
    }

    async createFormTransfer(input: FormTransferConfigurationInput): Promise<void> {
        await this.openCreateAction();
        await this.page.locator('input[id*="name"]').first().fill(input.name);
        await this.page.locator('input[id*="code"]').first().fill(input.code);
        await this.page.locator('input[id*="uid_prefix"]').first().fill(input.uidPrefix);

        if (input.description) {
            await this.page.locator('textarea[id*="description"]').first().fill(input.description);
        }

        await this.submitModalForm();
        await this.expectSuccessToast();
    }

    async editFormTransfer(searchKey: string, updates: Partial<FormTransferConfigurationInput>): Promise<void> {
        const row = await this.findRow(searchKey);
        await this.openRowAction(row, /edit/i);

        if (updates.name) {
            await this.page.locator('input[id*="name"]').first().fill(updates.name);
        }

        if (updates.code) {
            await this.page.locator('input[id*="code"]').first().fill(updates.code);
        }

        if (updates.uidPrefix) {
            await this.page.locator('input[id*="uid_prefix"]').first().fill(updates.uidPrefix);
        }

        if (updates.description !== undefined) {
            await this.page.locator('textarea[id*="description"]').first().fill(updates.description);
        }

        await this.submitModalForm();
        await this.expectSuccessToast();
    }

    async createBank(input: BankConfigurationInput): Promise<void> {
        await this.openCreateAction();
        await this.dialogField(/Kode Bank|Code/i).fill(input.code);
        await this.dialogField(/Nama Bank|Name/i).fill(input.name);

        if (input.shortName) {
            await this.dialogField(/Nama Singkat|Short Name/i).fill(input.shortName);
        }

        if (input.sortOrder) {
            await this.dialogField(/Urutan|Sort Order/i).fill(input.sortOrder);
        }

        await this.submitModalForm();
        await this.expectSuccessToast();
    }

    async editBank(searchKey: string, updates: Partial<BankConfigurationInput>): Promise<void> {
        const row = await this.findRow(searchKey);
        await this.openRowAction(row, /edit/i);

        if (updates.name) {
            await this.dialogField(/Nama Bank|Name/i).fill(updates.name);
        }

        if (updates.shortName !== undefined) {
            await this.dialogField(/Nama Singkat|Short Name/i).fill(updates.shortName);
        }

        if (updates.sortOrder) {
            await this.dialogField(/Urutan|Sort Order/i).fill(updates.sortOrder);
        }

        await this.submitModalForm();
        await this.expectSuccessToast();
    }

    async createDivision(input: DivisionConfigurationInput): Promise<void> {
        await this.openCreateAction();
        await this.selectBySearch(
            this.page.getByRole("dialog").locator("button.fi-select-input-btn").first(),
            input.formTransfer,
        );
        await this.dialogField(/Nama Divisi|Name/i).fill(input.name);

        if (input.description) {
            await this.dialogField(/Deskripsi|Description/i).fill(input.description);
        }

        await this.submitModalForm();
        await this.expectSuccessToast();
    }

    async editDivision(searchKey: string, updates: Partial<DivisionConfigurationInput>): Promise<void> {
        const row = await this.findRow(searchKey);
        await this.openRowAction(row, /edit/i);

        if (updates.name) {
            await this.dialogField(/Nama Divisi|Name/i).fill(updates.name);
        }

        if (updates.description !== undefined) {
            await this.dialogField(/Deskripsi|Description/i).fill(updates.description);
        }

        await this.submitModalForm();
        await this.expectSuccessToast();
    }

    async createReferenceNote(input: ReferenceNoteConfigurationInput): Promise<void> {
        await this.openCreateAction();
        await this.selectBySearch(
            this.page.getByRole("dialog").locator("button.fi-select-input-btn").first(),
            input.formTransfer,
        );
        await this.dialogField(/Label/i).fill(input.label);

        if (input.description) {
            await this.dialogField(/Deskripsi|Description/i).fill(input.description);
        }

        await this.submitModalForm();
        await this.expectSuccessToast();
    }

    async editReferenceNote(searchKey: string, updates: Partial<ReferenceNoteConfigurationInput>): Promise<void> {
        const row = await this.findRow(searchKey);
        await this.openRowAction(row, /edit/i);

        if (updates.label) {
            await this.dialogField(/Label/i).fill(updates.label);
        }

        if (updates.description !== undefined) {
            await this.dialogField(/Deskripsi|Description/i).fill(updates.description);
        }

        await this.submitModalForm();
        await this.expectSuccessToast();
    }

    async deleteRecord(searchKey: string): Promise<void> {
        const row = await this.findRow(searchKey);
        await this.openRowAction(row, /delete|hapus/i);
        await this.page.getByRole("dialog").getByRole("button", { name: /delete|hapus/i }).last().click();
        await this.expectSuccessToast();
    }

    async assertRecordVisible(recordName: string): Promise<void> {
        await this.searchRecord(recordName);
        await expect(this.rowLocator(recordName).first()).toBeVisible();
    }

    async assertRecordNotVisible(recordName: string): Promise<void> {
        await this.searchRecord(recordName);
        await expect(this.rowLocator(recordName)).toHaveCount(0);
    }

    private async gotoConfigurationPage(path: string): Promise<void> {
        await this.page.goto(path, { waitUntil: "domcontentloaded" });

        if (this.page.url().includes("/admin/login")) {
            await loginWithCredentials(this.page, {
                email: "admin@example.com",
                password: "admin123",
            });
            await this.page.goto(path, { waitUntil: "domcontentloaded" });
        }

        await expect(this.page).toHaveURL(new RegExp(path.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")));
        await expect(this.page.locator("table, div.fi-ta-empty-state").first()).toBeVisible();
    }

    private async openCreateAction(): Promise<void> {
        await this.page
            .locator("a,button")
            .filter({ hasText: /create|new|add|tambah|baru|buat/i })
            .first()
            .click();
    }

    private async submitModalForm(): Promise<void> {
        const dialogSubmit = this.page
            .getByRole("dialog")
            .getByRole("button", { name: /create|save|submit|simpan|buat|update/i })
            .last();

        if (await dialogSubmit.isVisible().catch(() => false)) {
            await dialogSubmit.click();
            return;
        }

        await this.page
            .getByRole("button", { name: /create|save|submit|simpan|buat|update/i })
            .first()
            .click();
    }

    private async searchRecord(keyword: string): Promise<void> {
        const searchInput = this.page.locator(".fi-input.fi-input-has-inline-prefix").nth(1);
        await searchInput.fill(keyword);
        await this.page.waitForLoadState("networkidle");
    }

    private rowLocator(recordName: string): Locator {
        return this.page.getByRole("row").filter({ hasText: recordName });
    }

    private async findRow(recordName: string): Promise<Locator> {
        await this.searchRecord(recordName);
        const row = this.rowLocator(recordName).first();

        await expect(row).toBeVisible();

        return row;
    }

    private async openRowAction(row: Locator, label: RegExp): Promise<void> {
        const actionsButton = row.getByRole("button", { name: /actions/i }).first();

        if (await actionsButton.isVisible().catch(() => false)) {
            await actionsButton.click();

            const menuAction = this.page.getByRole("menuitem", { name: label }).first();
            if (await menuAction.isVisible().catch(() => false)) {
                await menuAction.click();
                return;
            }
        }

        const inlineAction = row.locator("a.fi-ac-btn-action, button.fi-ac-btn-action").filter({ hasText: label }).first();
        if (await inlineAction.isVisible().catch(() => false)) {
            await inlineAction.click();
            return;
        }

        const rowLink = row.getByRole("link").first();
        if (await rowLink.isVisible().catch(() => false)) {
            await rowLink.click();

            const pageAction = this.page
                .locator("a,button")
                .filter({ hasText: label })
                .first();

            if (await pageAction.isVisible().catch(() => false)) {
                await pageAction.click();
                return;
            }
        }

        const menuAction = this.page.getByRole("menuitem", { name: label }).first();
        if (await menuAction.isVisible().catch(() => false)) {
            await menuAction.click();
            return;
        }

        await row.locator("div.fi-ta-text-item").first().click();

        const fallbackAction = this.page
            .locator("a.fi-ac-btn-action, button.fi-ac-btn-action, a, button")
            .filter({ hasText: label })
            .first();

        await fallbackAction.click();
    }

    private async selectBySearch(trigger: Locator, value: string): Promise<void> {
        await trigger.click();

        const searchInput = this.page
            .locator('.fi-dropdown-panel[role="listbox"]:visible input.fi-input[aria-label="Search"], input[type="search"]:visible')
            .last();

        if (await searchInput.isVisible().catch(() => false)) {
            await searchInput.fill(value);
        }

        const option = this.page
            .locator('[role="option"]:visible, .fi-select-input-option:visible, li.fi-select-input-option:visible')
            .filter({ hasText: new RegExp(this.escapeRegExp(value), "i") })
            .first();

        await expect(option).toBeVisible();
        await option.click();
    }

    private async selectFieldByLabel(label: RegExp, value: string): Promise<void> {
        const trigger = this.page
            .locator("label")
            .filter({ hasText: label })
            .first()
            .locator("..")
            .locator("button.fi-select-input-btn")
            .first();

        await this.selectBySearch(trigger, value);
    }

    private field(label: RegExp): Locator {
        return this.page.getByLabel(label).first();
    }

    private dialogField(label: RegExp): Locator {
        return this.page.getByRole("dialog").last().getByLabel(label).first();
    }

    private async fillFieldByLabel(label: RegExp, value: string): Promise<void> {
        const labelElement = this.page.locator("label").filter({ hasText: label }).first();
        await expect(labelElement).toBeVisible();

        const fieldId = await labelElement.getAttribute("for");

        if (fieldId) {
            await this.page.locator(`[id="${fieldId}"]`).first().fill(value);
            return;
        }

        await this.page.locator("input, textarea").filter({ has: labelElement }).first().fill(value);
    }

    private async expectSuccessToast(): Promise<void> {
        await expect(this.page.locator("h3.fi-no-notification-title, .fi-toast-message-success").first()).toBeVisible();
    }

    private escapeRegExp(value: string): string {
        return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }
}
