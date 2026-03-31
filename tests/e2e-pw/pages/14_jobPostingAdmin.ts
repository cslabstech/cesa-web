import { expect, type Locator, type Page } from "@playwright/test";

export type JobPostingInput = {
    title: string;
    slug: string;
    pipeline: string;
    location: string;
    jobDesk: string;
    qualifications: string;
    isPublished?: boolean;
    thumbnailPath?: string;
};

export class JobPostingAdminPage {
    readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    async gotoListing(): Promise<void> {
        await this.page.goto("/admin/job-postings", { waitUntil: "domcontentloaded" });
        await expect(this.page).toHaveURL(/job-postings/);
        await expect(this.page.getByRole("heading", { name: "Lowongan Kerja" })).toBeVisible();
    }

    async createPosting(input: JobPostingInput): Promise<void> {
        await this.page.goto("/admin/job-postings/create", { waitUntil: "domcontentloaded" });
        await expect(this.page).toHaveURL(/job-postings\/create/);

        await this.page.locator("#form\\.title").fill(input.title);
        await this.page.locator("#form\\.slug").fill(input.slug);
        await this.page.locator("#form\\.location").fill(input.location);

        await this.selectBySearch(
            this.page.locator('[wire\\:key*="rekrutmen_pipeline_id"] button.fi-select-input-btn').first(),
            input.pipeline,
        );

        await this.fillRichEditor("JOB DESK", input.jobDesk, 0);
        await this.fillRichEditor("KUALIFIKASI", input.qualifications, 1);

        if (input.thumbnailPath) {
            await this.page.locator('input[type="file"]').first().setInputFiles(input.thumbnailPath);
        }

        if (input.isPublished) {
            const publishedToggle = this.page.getByLabel("Dipublikasikan untuk Rekrutmen");
            if (!(await publishedToggle.isChecked().catch(() => false))) {
                await publishedToggle.check();
            }
        }

        await this.page.getByRole("button", { name: /^(Create|Buat)$/i }).click();
        await expect(this.page).not.toHaveURL(/job-postings\/create$/);
    }

    async assertPostingVisible(keyword: string): Promise<void> {
        const searchInput = this.tableSearchInput();
        await searchInput.fill(keyword);
        await this.page.waitForLoadState("networkidle");
        await expect(this.rowLocator(keyword).first()).toBeVisible();
    }

    private async fillRichEditor(label: string, value: string, index: number): Promise<void> {
        const contentEditable = this.page
            .locator('[contenteditable="true"]')
            .nth(index);

        if (await contentEditable.isVisible().catch(() => false)) {
            await contentEditable.click();
            await contentEditable.fill(value);

            return;
        }

        const fallbackTextarea = this.page.getByLabel(label).first();
        await fallbackTextarea.fill(value);
    }

    private async selectBySearch(trigger: Locator, value: string): Promise<void> {
        await trigger.click();
        await this.page.waitForLoadState("networkidle");

        const listbox = this.page.locator('[role="listbox"]:visible').last();
        await expect(listbox).toBeVisible();

        const searchInput = listbox.locator('input[type="search"], [role="textbox"]').first();
        if (await searchInput.isVisible().catch(() => false)) {
            await searchInput.fill(value);
            await this.page.waitForLoadState("networkidle");
        }

        const option = listbox.getByRole("option", {
            name: new RegExp(`^${this.escapeRegExp(value)}$`, "i"),
        }).first();

        await expect(option).toBeVisible();
        await option.click();
    }

    private rowLocator(keyword: string): Locator {
        return this.page.getByRole("row").filter({ hasText: keyword });
    }

    private tableSearchInput(): Locator {
        return this.page.locator('input[wire\\:model\\.live\\.debounce\\.500ms="tableSearch"]').first();
    }

    private escapeRegExp(value: string): string {
        return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }
}
