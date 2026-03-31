import { test as base, expect, type Page } from "@playwright/test";
import { loginAsAdmin } from "./utils/admin";

type Fixtures = {
    adminPage: Page;
};

export const test = base.extend<Fixtures>({
    adminPage: async ({ browser }, use) => {
        const context = await browser.newContext();
        const page = await context.newPage();
        await loginAsAdmin(page);

        await use(page);
        await context.close();
    },
});

export { expect };
