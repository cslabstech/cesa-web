export async function loginWithCredentials(
    page: any,
    credentials: { email: string; password: string }
) {
    await page.goto("/admin/login", { waitUntil: "domcontentloaded" });

    if (!page.url().includes("/admin/login")) {
        return credentials;
    }

    await page.fill('input[type="email"]', credentials.email);
    await page.fill('input[type="password"]', credentials.password);
    await page.getByRole("button", { name: /Masuk|Sign in/i }).click();
    await page.waitForLoadState("networkidle");

    if (page.url().includes("/admin/login")) {
        await page.waitForURL((url: URL) => !url.pathname.includes("/admin/login"), {
            timeout: 10_000,
        });
    }

    return credentials;
}

export async function loginAsAdmin(page:any) {
    /**
     * Admin credentials.
     */
    const adminCredentials = {
        email: "admin@example.com",
        password: "admin123",
    };

    /**
     * Authenticate the admin user.
     */
    return loginWithCredentials(page, adminCredentials);
}
