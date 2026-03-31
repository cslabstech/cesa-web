import { expect, type Locator, type Page } from "@playwright/test";

export type FormTransferRequestInput = {
    email: string;
    requesterName: string;
    division: string;
    bank: string;
    accountNumber: string;
    accountName: string;
    transferAmount: string;
    purpose: string;
    referenceNote: string;
};

export class FormTransferPage {
    readonly page: Page;

    readonly pageTitle: Locator;
    readonly submitButton: Locator;
    readonly validationSummaryTitle: Locator;
    readonly progressPageTitle: Locator;
    readonly currentStatusLabel: Locator;
    readonly approvalFlowHeading: Locator;

    constructor(page: Page) {
        this.page = page;
        this.pageTitle = page.getByRole("heading", { name: /FORM PENGAJUAN TRANSFER/i });
        this.submitButton = page.getByRole("button", { name: "Kirim Pengajuan" });
        this.validationSummaryTitle = page.getByText("Validasi Gagal");
        this.progressPageTitle = page.getByRole("heading", { name: "PANTAU PROGRES PERMINTAAN" });
        this.currentStatusLabel = page.getByText("Status saat ini");
        this.approvalFlowHeading = page.getByRole("heading", { name: "ALUR PERSETUJUAN" });
    }

    async gotoRequestForm(publicPath: string): Promise<void> {
        const candidates = [
            process.env.FORM_TRANSFER_PUBLIC_BASE_URL
                ? `${process.env.FORM_TRANSFER_PUBLIC_BASE_URL}${publicPath}`
                : `http://web-cesa.test${publicPath}`,
            publicPath,
        ];

        let lastError: unknown;

        for (const url of candidates) {
            try {
                await this.page.goto(url, { waitUntil: "domcontentloaded" });
                await expect(this.pageTitle).toBeVisible({ timeout: 15_000 });
                await expect(this.page).toHaveURL(/transfer-requests/);

                return;
            } catch (error) {
                lastError = error;
            }
        }

        throw lastError instanceof Error
            ? lastError
            : new Error("Unable to open the form transfer public form.");
    }

    async fillRequestForm(input: FormTransferRequestInput): Promise<void> {
        await this.page.locator("#form\\.email").fill(input.email);
        await this.page.locator("#form\\.requester_name").fill(input.requesterName);
        await this.selectBySearch(this.divisionTrigger(), input.division);
        await this.selectBySearch(this.bankTrigger(), input.bank);
        await this.page.locator("#form\\.account_number").fill(input.accountNumber);
        await this.page.locator("#form\\.account_name").fill(input.accountName);
        await this.page.locator("#form\\.transfer_amount").fill(input.transferAmount);
        await this.page.locator("#form\\.purpose").fill(input.purpose);
        await this.page.locator("#form\\.submission_status").selectOption("baru");
        await this.page.locator("#form\\.reference_note").fill(input.referenceNote);
    }

    async expectValidationFeedbackAndRecoverableSubmit(): Promise<void> {
        await this.submitButton.click();
        await expect(
            this.page.locator("input:invalid, select:invalid, textarea:invalid").first()
        ).toBeVisible();
        await expect(this.submitButton).toBeEnabled();
    }

    async submit(): Promise<void> {
        await this.submitButton.click();
        await expect(this.page).toHaveURL(/transfer-requests\/progress\//, { timeout: 30_000 });
        await expect(this.progressPageTitle).toBeVisible();
        await expect(this.currentStatusLabel).toBeVisible();
        await expect(this.approvalFlowHeading).toBeVisible();
    }

    async submitRequest(publicPath: string, input: FormTransferRequestInput): Promise<void> {
        await this.gotoRequestForm(publicPath);
        await this.fillRequestForm(input);
        await this.submit();
    }

    async gotoApprovalPath(approvalPath: string): Promise<void> {
        await this.page.goto(approvalPath, { waitUntil: "domcontentloaded" });
        await expect(
            this.page.getByRole("heading", { name: /PERSETUJUAN PENGAJUAN TRANSFER/i })
        ).toBeVisible();
    }

    async approveApproval(note: string): Promise<void> {
        await this.page.locator("#form\\.comments").fill(note);
        await this.page.getByRole("button", { name: "Setujui" }).click();
        await expect(
            this.page.getByText("Tahap ini sudah diproses. Anda dapat menutup halaman ini.")
        ).toBeVisible();
    }

    async rejectApproval(note: string): Promise<void> {
        await this.page.locator("#form\\.comments").fill(note);
        await this.page.getByRole("button", { name: "Tolak" }).click();
        await expect(
            this.page.getByText("Tahap ini sudah diproses. Anda dapat menutup halaman ini.")
        ).toBeVisible();
    }

    async gotoProgressPath(progressPath: string): Promise<void> {
        await this.page.goto(progressPath, { waitUntil: "domcontentloaded" });
        await expect(this.progressPageTitle).toBeVisible();
    }

    async assertProgressPageShowsSubmission(input: {
        requesterName: string;
        division: string;
        uid: string;
        purpose: string;
    }): Promise<void> {
        await expect(this.page.getByText(input.requesterName).first()).toBeVisible();
        await expect(this.page.getByText(`(${input.division})`).first()).toBeVisible();
        await expect(this.page.getByText(input.uid).first()).toBeVisible();
        await expect(this.page.getByText(input.purpose).first()).toBeVisible();
        await expect(
            this.page.locator("span.rounded-full").filter({ hasText: /pengajuan baru|menunggu|disetujui|ditolak/i }).first()
        ).toBeVisible();
    }

    async assertProgressPageShowsRejectedState(note: string): Promise<void> {
        await expect(
            this.page.locator("span.rounded-full").filter({ hasText: /ditolak|rejected/i }).first()
        ).toBeVisible();
        await expect(this.page.getByText(note).first()).toBeVisible();
    }

    async assertProgressPageShowsApprovedState(note: string): Promise<void> {
        await expect(
            this.page.locator("span.rounded-full").filter({ hasText: /disetujui|approved/i }).first()
        ).toBeVisible();
        await expect(this.page.getByText(note).first()).toBeVisible();
    }

    private divisionTrigger(): Locator {
        return this.page
            .locator(
                '[wire\\:key*="form.division_id"] button.fi-select-input-btn, [wire\\:key*="division_id"] button.fi-select-input-btn'
            )
            .first();
    }

    private bankTrigger(): Locator {
        return this.page
            .locator(
                '[wire\\:key*="form.bank_id"] button.fi-select-input-btn, [wire\\:key*="bank_id"] button.fi-select-input-btn'
            )
            .first();
    }

    private async selectBySearch(trigger: Locator, value: string): Promise<void> {
        await trigger.click();

        const searchInput = this.page
            .locator('.fi-dropdown-panel[role="listbox"]:visible input[type="search"], input.fi-select-input-search:visible')
            .first();

        if (await searchInput.isVisible().catch(() => false)) {
            await searchInput.fill(value);
        }

        const option = this.page
            .locator('[role="option"]:visible, .fi-select-input-option:visible, li.fi-select-input-option:visible')
            .filter({ hasText: new RegExp(`^${this.escapeRegExp(value)}$`, "i") })
            .first();

        await expect(option).toBeVisible();
        await option.click();
    }

    private escapeRegExp(value: string): string {
        return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }
}
