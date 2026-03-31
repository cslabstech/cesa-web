import { test } from "@playwright/test";
import type { BrowserContext, Page } from "@playwright/test";
import { FormTransferConfigurationPage } from "../../pages/10_formTransferConfiguration";
import {
    createPublicFormTransferFixture,
    createFormTransferWorkflowFixture,
} from "../../utils/formTransfer";
import { loginWithCredentials } from "../../utils/admin";

test.describe("Form Transfer Admin Configuration E2E", () => {
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

    test("Create form transfer configuration from admin page", async () => {
        const configPage = new FormTransferConfigurationPage(adminPage);
        const key = Date.now();
        const originalName = `E2E Config Form Transfer ${key}`;

        await configPage.gotoFormTransfersConfiguration();
        await configPage.createFormTransfer({
            name: originalName,
            code: `E2E-CONFIG-${key}`,
            uidPrefix: `CFG${String(key).slice(-4)}`,
            description: "Created from admin configuration E2E",
        });
        await configPage.gotoFormTransfersConfiguration();
        await configPage.assertRecordVisible(originalName);
    });

    test("Create bank configuration from admin page", async () => {
        const configPage = new FormTransferConfigurationPage(adminPage);
        const key = Date.now();
        const originalName = `E2E Bank ${key}`;
        const code = `E2EB${String(key).slice(-4)}`;

        await configPage.gotoBanksConfiguration();
        await configPage.createBank({
            code,
            name: originalName,
        });
        await configPage.gotoBanksConfiguration();
        await configPage.assertRecordVisible(originalName);
    });

    test("Create division configuration from admin page", async () => {
        const fixture = createPublicFormTransferFixture(Date.now());
        const configPage = new FormTransferConfigurationPage(adminPage);
        const key = Date.now();
        const originalName = `E2E Config Division ${key}`;

        await configPage.gotoDivisionsConfiguration();
        await configPage.createDivision({
            formTransfer: fixture.name,
            name: originalName,
            description: "Created from admin configuration E2E",
        });
        await configPage.gotoDivisionsConfiguration();
        await configPage.assertRecordVisible(originalName);
    });

    test("Create reference note configuration from admin page", async () => {
        const fixture = createPublicFormTransferFixture(Date.now());
        const configPage = new FormTransferConfigurationPage(adminPage);
        const key = Date.now();
        const originalLabel = `E2E Config Reference ${key}`;

        await configPage.gotoReferenceNotesConfiguration();
        await configPage.createReferenceNote({
            formTransfer: fixture.name,
            label: originalLabel,
            description: "Created from admin configuration E2E",
        });
        await configPage.gotoReferenceNotesConfiguration();
        await configPage.assertRecordVisible(originalLabel);
    });

    test("Approval workflow configuration listing shows seeded workflow", async () => {
        const workflowFixture = createFormTransferWorkflowFixture(Date.now());
        const configPage = new FormTransferConfigurationPage(adminPage);

        await configPage.gotoApprovalWorkflowsConfiguration();
        await configPage.assertRecordVisible(workflowFixture.formTransferName);
        await configPage.assertRecordVisible(workflowFixture.divisionName);
    });
});
