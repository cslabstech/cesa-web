import { expect, type Locator, type Page } from "@playwright/test";

export type ManPowerRequestInput = {
    applicantName: string;
    applicantEmail: string;
    applicantPosition: string;
    division: string;
    businessEntity: string;
    statusKebutuhan: "New Hiring" | "Replacement";
    replacementName?: string;
    requiredPosition: string;
    levelPekerjaan: string;
    quantity: string;
    placement: string;
    estimatedJoinDate: string;
    jobDescription: string;
    qualification: string;
    notes: string;
};

export class ManPowerPage {
    readonly page: Page;
    readonly pageTitle: Locator;
    readonly submitButton: Locator;
    readonly progressPageTitle: Locator;
    readonly currentStatusLabel: Locator;

    constructor(page: Page) {
        this.page = page;
        this.pageTitle = page.getByRole("heading", { name: "FORM REQUEST MAN POWER" });
        this.submitButton = page.getByRole("button", { name: "Kirim Pengajuan" });
        this.progressPageTitle = page.getByRole("heading", { name: "PROGRESS REQUEST MAN POWER" });
        this.currentStatusLabel = page.getByText("Status saat ini");
    }

    async gotoRequestForm(): Promise<void> {
        await this.page.goto("/man-power", { waitUntil: "domcontentloaded" });
        await expect(this.pageTitle).toBeVisible();
        await expect(this.page).toHaveURL(/man-power/);
    }

    async fillRequestForm(input: ManPowerRequestInput): Promise<void> {
        await this.page.locator("#form\\.nama_pengaju").fill(input.applicantName);
        await this.page.locator("#form\\.email_address").fill(input.applicantEmail);
        await this.page.locator("#form\\.posisi_pengaju").fill(input.applicantPosition);
        await this.page.locator("#form\\.divisi").fill(input.division);
        await this.page.locator("#form\\.badan_usaha").fill(input.businessEntity);
        await this.page.locator("#form\\.status_kebutuhan").selectOption(input.statusKebutuhan);

        if (input.statusKebutuhan === "Replacement" && input.replacementName) {
            await this.page.locator("#form\\.nama_karyawan_replacement").fill(input.replacementName);
        }

        await this.page.locator("#form\\.posisi_dibutuhkan").fill(input.requiredPosition);
        await this.page.locator("#form\\.level_pekerjaan").selectOption({ label: input.levelPekerjaan });
        await this.page.locator("#form\\.jumlah_karyawan_dibutuhkan").fill(input.quantity);
        await this.page.locator("#form\\.lokasi_penempatan").fill(input.placement);
        await this.page.locator("#form\\.estimasi_tanggal_join").fill(input.estimatedJoinDate);
        await this.page.locator("#form\\.job_description").fill(input.jobDescription);
        await this.page.locator("#form\\.requirements_kualifikasi").fill(input.qualification);
        await this.page.locator("#form\\.keterangan").fill(input.notes);
    }

    async expectValidationFeedbackAndRecoverableSubmit(): Promise<void> {
        await this.submitButton.click();
        await expect(
            this.page.locator("input:invalid, select:invalid, textarea:invalid").first(),
        ).toBeVisible();
        await expect(this.submitButton).toBeEnabled();
    }

    async submit(): Promise<void> {
        await this.submitButton.click();
        await expect(this.page).toHaveURL(/man-power\/progress\//, { timeout: 30_000 });
        await expect(this.progressPageTitle).toBeVisible();
        await expect(this.currentStatusLabel).toBeVisible();
    }

    async submitRequest(input: ManPowerRequestInput): Promise<void> {
        await this.gotoRequestForm();
        await this.fillRequestForm(input);
        await this.submit();
    }

    async gotoProgressPath(progressPath: string): Promise<void> {
        await this.page.goto(progressPath, { waitUntil: "domcontentloaded" });
        await expect(this.progressPageTitle).toBeVisible();
    }

    async assertProgressPageShowsSubmission(input: {
        responseId: string;
        requiredPosition: string;
        requirementStatus: string;
        jobDescription: string;
        replacementName?: string;
    }): Promise<void> {
        await expect(this.page.getByText(input.responseId).first()).toBeVisible();
        await expect(this.page.getByText(input.requiredPosition).first()).toBeVisible();
        await expect(this.page.getByText(input.requirementStatus).first()).toBeVisible();
        await expect(this.page.getByText(input.jobDescription).first()).toBeVisible();

        if (input.replacementName) {
            await expect(this.page.getByText(input.replacementName).first()).toBeVisible();
        }
    }
}
