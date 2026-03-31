import { expect, type Locator, type Page } from "@playwright/test";

export type RekrutmenPipelineInput = {
    name: string;
    description?: string;
    stages: string[];
};

export class RekrutmenPipelineAdminPage {
    readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    async gotoListing(): Promise<void> {
        await this.page.goto("/admin/rekrutmen-pipelines", { waitUntil: "domcontentloaded" });
        await expect(this.page).toHaveURL(/rekrutmen-pipelines/);
        await expect(this.page.getByRole("heading", { name: "Pipeline Rekrutmen" })).toBeVisible();
        await expect(this.page.locator("table, div.fi-ta-empty-state").first()).toBeVisible();
    }

    async createPipeline(input: RekrutmenPipelineInput): Promise<void> {
        await this.page.goto("/admin/rekrutmen-pipelines/create", { waitUntil: "domcontentloaded" });
        await expect(this.page).toHaveURL(/rekrutmen-pipelines\/create/);

        await this.pipelineNameInput().fill(input.name);

        if (input.description) {
            await this.page.getByLabel("Deskripsi").fill(input.description);
        }

        await this.fillStages(input.stages);
        await this.submitCreateForm();
    }

    async editPipeline(searchKey: string, updates: RekrutmenPipelineInput): Promise<void> {
        await this.gotoListing();
        await this.searchPipeline(searchKey);

        const row = this.rowLocator(searchKey).first();
        await expect(row).toBeVisible();
        await row.getByRole("link", { name: /Ubah|Edit/i }).first().click();

        await expect(this.page).toHaveURL(/rekrutmen-pipelines\/\d+\/edit/);
        await this.pipelineNameInput().fill(updates.name);

        if (updates.description !== undefined) {
            await this.page.getByLabel("Deskripsi").fill(updates.description);
        }

        await this.syncStages(updates.stages);
        await this.submitEditForm();
    }

    async deletePipeline(searchKey: string): Promise<void> {
        await this.gotoListing();
        await this.searchPipeline(searchKey);

        const row = this.rowLocator(searchKey).first();
        await expect(row).toBeVisible();
        await row.locator("a,button").filter({ hasText: /Hapus|Delete/i }).first().click();
        await this.page.getByRole("dialog").getByRole("button", { name: /Hapus|Delete/i }).last().click();
    }

    async assertPipelineVisible(keyword: string): Promise<void> {
        await this.searchPipeline(keyword);
        await expect(this.rowLocator(keyword).first()).toBeVisible();
    }

    async assertPipelineNotVisible(keyword: string): Promise<void> {
        await this.searchPipeline(keyword);
        await expect(this.rowLocator(keyword)).toHaveCount(0);
    }

    private async fillStages(stages: string[]): Promise<void> {
        for (const [index, stage] of stages.entries()) {
            if (index > 0) {
                await this.page.getByRole("button", { name: /Tambah Tahap|Add stage/i }).click();
            }

            await this.stageNameInput(index).fill(stage);
        }
    }

    private async syncStages(stages: string[]): Promise<void> {
        const currentStageCount = Math.max((await this.stageNameInputs().count()) - 1, 0);

        for (let index = currentStageCount; index < stages.length; index++) {
            await this.page.getByRole("button", { name: /Tambah Tahap|Add stage/i }).click();
        }

        for (const [index, stage] of stages.entries()) {
            await this.stageNameInput(index).fill(stage);
        }
    }

    private async submitCreateForm(): Promise<void> {
        await this.page.getByRole("button", { name: /^(Create|Buat)$/i }).click();
        await expect(this.page).not.toHaveURL(/rekrutmen-pipelines\/create$/);
    }

    private async submitEditForm(): Promise<void> {
        const primarySaveButton = this.page.getByRole("button", { name: /^(Save|Simpan)$/i });

        if (await primarySaveButton.isVisible().catch(() => false)) {
            await primarySaveButton.click();
        } else {
            await this.page
                .locator("a,button")
                .filter({ hasText: /Save|Simpan|Save changes|Simpan perubahan/i })
                .first()
                .click();
        }

        await expect(this.page).toHaveURL(/rekrutmen-pipelines\/\d+\/edit/);
    }

    private async searchPipeline(keyword: string): Promise<void> {
        const searchInput = this.page.locator('input[wire\\:model\\.live\\.debounce\\.500ms="tableSearch"]').first();
        await searchInput.fill(keyword);
        await this.page.waitForLoadState("networkidle");
    }

    private rowLocator(keyword: string): Locator {
        return this.page.getByRole("row").filter({ hasText: keyword });
    }

    private pipelineNameInput(): Locator {
        return this.page.getByLabel("Nama").first();
    }

    private stageNameInputs(): Locator {
        return this.page.getByLabel("Nama");
    }

    private stageNameInput(index: number): Locator {
        return this.page.getByLabel("Nama").nth(index + 1);
    }
}
