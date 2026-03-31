import { expect, type Page } from "@playwright/test";

export class FormTransferAdminPage {
    readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    async gotoRequestView(requestId: number): Promise<void> {
        await this.page.goto(`/admin/transfer-requests/${requestId}`, {
            waitUntil: "domcontentloaded",
        });

        await expect(this.page).not.toHaveURL(/\/admin\/login/);

        await expect(this.page).toHaveURL(new RegExp(`/admin/transfer-requests/${requestId}$`));
    }

    async assertRequestDetails(input: {
        requesterName: string;
        email: string;
        uid: string;
        status?: string | RegExp;
    }): Promise<void> {
        await expect(this.page.getByText(input.requesterName).first()).toBeVisible();
        await expect(this.page.getByText(input.email).first()).toBeVisible();
        await expect(this.page.getByText(input.uid).first()).toBeVisible();

        if (input.status) {
            if (typeof input.status === "string") {
                await expect(this.page.getByText(input.status).first()).toBeVisible();
            } else {
                await expect(this.page.getByText(input.status).first()).toBeVisible();
            }
        }
    }
}
