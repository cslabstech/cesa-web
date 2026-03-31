import { expect, test } from "@playwright/test";
import type { BrowserContext, Page } from "@playwright/test";
import { FormTransferAdminPage } from "../../pages/09_formTransferAdmin";
import { FormTransferPage } from "../../pages/08_formTransfer";
import {
    createManagedTransferRequestFixture,
    createPublicFormTransferFixture,
} from "../../utils/formTransfer";
import { loginWithCredentials } from "../../utils/admin";

test.describe("Form Transfer Additional E2E", () => {
    test("Public index lists active forms and links to the public form page", async ({ page }) => {
        const fixture = createPublicFormTransferFixture(Date.now());

        await page.goto("/transfer-requests");
        await expect(page.getByRole("heading", { name: "DAFTAR FORM TRANSFER" })).toBeVisible();
        await expect(page.getByRole("link", { name: new RegExp(fixture.name, "i") })).toBeVisible();

        await page.getByRole("link", { name: new RegExp(fixture.name, "i") }).click();
        await expect(page).toHaveURL(new RegExp(fixture.publicPath.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")));
        await expect(page.getByRole("heading", { name: /FORM PENGAJUAN TRANSFER/i })).toBeVisible();
    });

    test("Public progress page attachment links are downloadable", async ({ page, request, baseURL }) => {
        const fixture = createManagedTransferRequestFixture({
            key: Date.now(),
            state: "pending",
            withAttachments: true,
        });
        const formTransferPage = new FormTransferPage(page);

        await formTransferPage.gotoProgressPath(fixture.progressPath);

        const links = page.locator('a[href*="transfer-requests/files/"]');
        await expect(links.first()).toBeVisible();

        const response = await request.get(`${baseURL}${fixture.invoiceDownloadPath}`);

        expect(response.ok()).toBeTruthy();
        expect(response.headers()["content-disposition"] || "").toContain(".txt");
        await expect(await response.text()).toContain("Invoice fixture");
    });

});

test.describe("Form Transfer Admin Detail Additional E2E", () => {
    test.describe.configure({ mode: "serial" });

    let adminContext: BrowserContext;
    let adminPage: Page;

    test.beforeAll(async ({ browser, baseURL }) => {
        adminContext = await browser.newContext({
            baseURL: baseURL ?? "http://web-cesa.test",
        });
        adminPage = await adminContext.newPage();

        await loginWithCredentials(adminPage, {
            email: "admin@example.com",
            password: "admin123",
        });
    });

    test.afterAll(async () => {
        await adminContext.close();
    });

    test("Admin request detail can resend pending approver notification", async () => {
        const fixture = createManagedTransferRequestFixture({
            key: Date.now(),
            state: "pending",
            withAttachments: false,
        });
        const adminRequestPage = new FormTransferAdminPage(adminPage);

        await adminRequestPage.gotoRequestView(fixture.requestId);
        await adminPage.getByRole("button", { name: /Kirim ulang ke approver pending/i }).click();
        await adminPage.getByRole("dialog").getByRole("button", { name: /Konfirmasi/i }).click();

        await expect(adminPage.locator("h3.fi-no-notification-title, .fi-toast-message-success").first()).toContainText(/Notifikasi dikirim ulang/i);
    });

    test("Admin request detail can resend requester email", async () => {
        const fixture = createManagedTransferRequestFixture({
            key: Date.now(),
            state: "pending",
            withAttachments: false,
        });
        const adminRequestPage = new FormTransferAdminPage(adminPage);

        await adminRequestPage.gotoRequestView(fixture.requestId);
        await adminPage.getByRole("button", { name: /Kirim ulang email pengaju/i }).click();
        await adminPage.getByRole("dialog").getByRole("button", { name: /Konfirmasi/i }).click();

        await expect(adminPage.locator("h3.fi-no-notification-title, .fi-toast-message-success").first()).toContainText(/Email dikirim ulang/i);
    });

    test("Admin request detail can download PDF", async () => {
        const fixture = createManagedTransferRequestFixture({
            key: Date.now(),
            state: "pending",
            withAttachments: false,
        });
        const adminRequestPage = new FormTransferAdminPage(adminPage);

        await adminRequestPage.gotoRequestView(fixture.requestId);

        const [download] = await Promise.all([
            adminPage.waitForEvent("download"),
            adminPage.getByRole("button", { name: /Unduh PDF/i }).click(),
        ]);

        expect(download.suggestedFilename()).toContain("pengajuan-transfer");
    });

    test("Admin request detail can update realization status", async () => {
        const fixture = createManagedTransferRequestFixture({
            key: Date.now(),
            state: "approved",
            withAttachments: false,
        });
        const adminRequestPage = new FormTransferAdminPage(adminPage);

        await adminRequestPage.gotoRequestView(fixture.requestId);
        await adminPage.getByRole("button", { name: /Realisasi Transfer|Realisasikan Transfer|Edit Realisasi/i }).click();
        await adminPage.getByLabel(/Status Realisasi/i).selectOption("cancelled");
        await adminPage.getByLabel(/Catatan Realisasi/i).fill("Cancelled by E2E");
        await adminPage
            .getByRole("dialog")
            .getByRole("button", { name: /Realisasi Transfer|Realisasikan Transfer|Edit Realisasi|Save|Simpan|Kirim/i })
            .last()
            .click();

        await expect(adminPage.getByText(/Dibatalkan|Cancelled/i).first()).toBeVisible();
        await expect(adminPage.getByText("Cancelled by E2E").first()).toBeVisible();
    });
});
