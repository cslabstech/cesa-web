import { expect, type Locator, type Page } from "@playwright/test";

export class JobApplicationAdminPage {
    readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    async gotoListing(): Promise<void> {
        await this.page.goto("/admin/job-applications", { waitUntil: "domcontentloaded" });
        await expect(this.page).toHaveURL(/job-applications$/);
        await expect(this.page.getByRole("heading", { name: "Lamaran Kerja" })).toBeVisible();
    }

    async gotoBoard(jobPostingId?: number): Promise<void> {
        const query = jobPostingId ? `?job_posting_id=${jobPostingId}` : "";
        await this.page.goto(`/admin/job-applications/board${query}`, { waitUntil: "domcontentloaded" });
        await expect(this.page).toHaveURL(/job-applications\/board/);
        await expect(this.page.getByRole("heading", { name: /Job Application Pipeline|Pipeline Lamaran Kandidat/i })).toBeVisible();
    }

    async assertCandidateVisible(keyword: string): Promise<void> {
        await this.searchCandidate(keyword);
        await expect(this.rowLocator(keyword).first()).toBeVisible();
    }

    async markCandidateAsHired(keyword: string, notes: string): Promise<void> {
        await this.gotoListing();
        await this.searchCandidate(keyword);
        await this.openEditPage(keyword);
        await this.submitDecisionWithNotes(/Accept Candidate|Terima Kandidat/i, notes);
        await this.gotoListing();
        await this.searchCandidate(keyword);
    }

    async markCandidateAsRejected(keyword: string, notes: string): Promise<void> {
        await this.gotoListing();
        await this.searchCandidate(keyword);
        await this.openEditPage(keyword);
        await this.submitDecisionWithNotes(/Reject Candidate|Tolak Kandidat/i, notes);
        await this.gotoListing();
        await this.searchCandidate(keyword);
    }

    async assertCandidateInBoardColumn(candidateName: string, columnLabel: string): Promise<void> {
        const column = this.columnLocator(columnLabel);
        await expect(column).toBeVisible();
        await expect(column.locator(`[data-card-id]`).filter({ hasText: candidateName }).first()).toBeVisible();
    }

    async moveCandidateToBoardColumn(candidateName: string, targetColumnLabel: string): Promise<void> {
        const card = this.page.locator('.flowforge-card').filter({ hasText: candidateName }).first();
        const targetColumn = this.columnLocator(targetColumnLabel).locator('[data-column-id]').first();

        await expect(card).toBeVisible();
        await expect(targetColumn).toBeVisible();
        await card.dragTo(targetColumn);
        await expect(targetColumn.locator('.flowforge-card').filter({ hasText: candidateName }).first()).toBeVisible();
    }

    private async searchCandidate(keyword: string): Promise<void> {
        const searchInput = this.tableSearchInput();
        await searchInput.fill(keyword);
        await this.page.waitForLoadState("networkidle");
    }

    private async openEditPage(keyword: string): Promise<void> {
        const row = this.rowLocator(keyword).first();
        await expect(row).toBeVisible();

        const editAction = row.locator("a,button").filter({ hasText: /Ubah|Edit/i }).first();
        await expect(editAction).toBeVisible();
        await editAction.click();
        await expect(this.page).toHaveURL(/job-applications\/\d+\/edit/);
    }

    private rowLocator(keyword: string): Locator {
        return this.page.getByRole("row").filter({ hasText: keyword });
    }

    private async submitDecisionWithNotes(actionLabel: RegExp, notes: string): Promise<void> {
        let notesField = this.page.locator('textarea[id*="mountedActionSchema"][id$=".notes"]:visible').last();

        if (!(await notesField.isVisible().catch(() => false))) {
            const pageLevelAction = this.page
                .locator("button:visible")
                .filter({ hasText: actionLabel })
                .first();

            await expect(pageLevelAction).toBeVisible();
            await pageLevelAction.click();
            notesField = this.page.locator('textarea[id*="mountedActionSchema"][id$=".notes"]:visible').last();
        }

        await expect(notesField).toBeVisible();
        await notesField.fill(notes);

        let submitButton = this.page
            .locator("button:visible")
            .filter({ hasText: /Kirim|Submit/i })
            .last();

        if (!(await submitButton.isVisible().catch(() => false))) {
            submitButton = this.page
                .locator("button:visible")
                .filter({ hasText: /Accept Candidate|Terima Kandidat|Reject Candidate|Tolak Kandidat/i })
                .last();
        }

        await expect(submitButton).toBeVisible();
        await submitButton.click();
        await expect(notesField).not.toBeVisible({ timeout: 10000 });
        await this.page.waitForLoadState("networkidle");
    }

    private tableSearchInput(): Locator {
        return this.page.locator('input[wire\\:model\\.live\\.debounce\\.500ms="tableSearch"]').first();
    }

    private columnLocator(label: string): Locator {
        return this.page.locator(".flowforge-column").filter({ hasText: label }).first();
    }
}
