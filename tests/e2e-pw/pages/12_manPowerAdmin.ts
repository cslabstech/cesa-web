import { expect, type Page } from "@playwright/test";

export class ManPowerAdminPage {
    readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    async gotoRequestListing(): Promise<void> {
        await this.page.goto("/admin/request-man-powers", { waitUntil: "domcontentloaded" });
        await expect(this.page).toHaveURL(/request-man-powers/);
        await expect(this.page.getByRole("heading", { name: "Permintaan Tenaga Kerja" })).toBeVisible();
        await expect(this.page.locator("table, div.fi-ta-empty-state").first()).toBeVisible();
    }

    async searchRequest(keyword: string): Promise<void> {
        const searchInput = this.tableSearchInput();
        await searchInput.fill(keyword);
        await this.page.waitForLoadState("networkidle");
    }

    async assertRequestVisible(keyword: string): Promise<void> {
        await this.searchRequest(keyword);
        await expect(this.rowLocator(keyword).first()).toBeVisible();
    }

    async approveRequest(keyword: string): Promise<void> {
        await this.searchRequest(keyword);
        await this.openRowAction(keyword, /Setujui/i);
        await this.expectRowStatus(keyword, /Disetujui|Approved/i);
    }

    async rejectRequest(keyword: string): Promise<void> {
        await this.searchRequest(keyword);
        await this.openRowAction(keyword, /Tolak/i);
        await this.page.getByRole("dialog").getByRole("button", { name: /Tolak|Delete|Konfirmasi/i }).last().click();
        await this.expectRowStatus(keyword, /Ditolak|Rejected/i);
    }

    async setPending(keyword: string): Promise<void> {
        await this.searchRequest(keyword);
        await this.openRowAction(keyword, /Set Pending/i);
        await this.page.getByRole("dialog").getByRole("button", { name: /Set Pending|Konfirmasi/i }).last().click();
        await this.expectRowStatus(keyword, /Pending/i);
    }

    async gotoJobPostingListing(): Promise<void> {
        await this.page.goto("/admin/job-postings", { waitUntil: "domcontentloaded" });
        await expect(this.page).toHaveURL(/job-postings/);
        await expect(this.page.getByRole("heading", { name: "Lowongan Kerja" })).toBeVisible();
        await expect(this.page.locator("table, div.fi-ta-empty-state").first()).toBeVisible();
    }

    async assertJobPostingVisible(keyword: string): Promise<void> {
        const searchInput = this.tableSearchInput();
        await searchInput.fill(keyword);
        await this.page.waitForLoadState("networkidle");
        await expect(this.rowLocator(keyword).first()).toBeVisible();
    }

    private async openRowAction(keyword: string, label: RegExp): Promise<void> {
        const row = this.rowLocator(keyword).first();
        await expect(row).toBeVisible();

        const actionsButton = row.getByRole("button", { name: /Actions/i }).first();

        if (await actionsButton.isVisible().catch(() => false)) {
            await actionsButton.click();

            const menuAction = this.page.getByRole("menuitem", { name: label }).first();
            if (await menuAction.isVisible().catch(() => false)) {
                await menuAction.click();
                return;
            }
        }

        await row.locator("a.fi-ac-btn-action, button.fi-ac-btn-action, a, button").filter({ hasText: label }).first().click();
    }

    private async expectRowStatus(keyword: string, status: RegExp): Promise<void> {
        const row = this.rowLocator(keyword).first();
        await expect(row).toBeVisible();
        await expect(row).toContainText(status);
    }

    private rowLocator(keyword: string) {
        return this.page.getByRole("row").filter({ hasText: keyword });
    }

    private tableSearchInput() {
        return this.page.locator('input[wire\\:model\\.live\\.debounce\\.500ms="tableSearch"]').first();
    }
}
