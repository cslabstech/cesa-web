export async function loginWithCredentials(
    page: any,
    credentials: { email: string; password: string }
) {
    await page.goto("/admin/login");
    await page.fill('input[type="email"]', credentials.email);
    await page.fill('input[type="password"]', credentials.password);
    await page.press('input[type="password"]', "Enter");
    await page.waitForNavigation();

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
