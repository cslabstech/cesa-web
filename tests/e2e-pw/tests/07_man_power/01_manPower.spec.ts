import { expect, test } from "../../setup";
import { ManPowerAdminPage } from "../../pages/12_manPowerAdmin";
import { ManPowerPage, type ManPowerRequestInput } from "../../pages/11_manPower";
import { ensureManPowerPipelineFixture, getManPowerRequestMetadata } from "../../utils/manPower";

test.describe("Man Power Public Flow E2E", () => {
    const buildRequestInput = (key: number, statusKebutuhan: "New Hiring" | "Replacement" = "New Hiring"): ManPowerRequestInput => ({
        applicantName: `E2E Man Power ${key}`,
        applicantEmail: `man-power+${key}@example.com`,
        applicantPosition: "HR Manager",
        division: "IT",
        businessEntity: "PT CESA",
        statusKebutuhan,
        replacementName: statusKebutuhan === "Replacement" ? `Replacement ${key}` : undefined,
        requiredPosition: "Software Engineer",
        levelPekerjaan: "Staf",
        quantity: "2",
        placement: "Jakarta",
        estimatedJoinDate: "2026-04-15",
        jobDescription: `Develop internal systems ${key}`,
        qualification: `Laravel and SQL ${key}`,
        notes: `Additional note ${key}`,
    });

    test.beforeAll(async () => {
        ensureManPowerPipelineFixture();
    });

    test("Public form validation shows feedback and keeps submit usable", async ({ page }) => {
        const manPowerPage = new ManPowerPage(page);

        await manPowerPage.gotoRequestForm();
        await manPowerPage.expectValidationFeedbackAndRecoverableSubmit();
    });

    test("Public form submission is visible from admin listing and progress page", async ({ page, adminPage }) => {
        const manPowerPage = new ManPowerPage(page);
        const adminRequestPage = new ManPowerAdminPage(adminPage);
        const key = Date.now();
        const requestInput = buildRequestInput(key);

        await manPowerPage.submitRequest(requestInput);

        const metadata = getManPowerRequestMetadata(requestInput.applicantEmail);

        await manPowerPage.assertProgressPageShowsSubmission({
            responseId: metadata.responseId,
            requiredPosition: requestInput.requiredPosition,
            requirementStatus: "Karyawan Baru",
            jobDescription: requestInput.jobDescription,
        });

        await adminRequestPage.gotoRequestListing();
        await adminRequestPage.assertRequestVisible(requestInput.applicantName);
    });

    test("Replacement submission keeps replacement details on progress page", async ({ page }) => {
        const manPowerPage = new ManPowerPage(page);
        const key = Date.now();
        const requestInput = buildRequestInput(key, "Replacement");

        await manPowerPage.submitRequest(requestInput);

        const metadata = getManPowerRequestMetadata(requestInput.applicantEmail);

        await manPowerPage.assertProgressPageShowsSubmission({
            responseId: metadata.responseId,
            requiredPosition: requestInput.requiredPosition,
            requirementStatus: "Penggantian",
            jobDescription: requestInput.jobDescription,
            replacementName: requestInput.replacementName,
        });
    });
});

test.describe("Man Power Admin Flow E2E", () => {
    test.beforeAll(async () => {
        ensureManPowerPipelineFixture();
    });

    test("Admin can approve a pending request", async ({ page, adminPage }) => {
        const manPowerPage = new ManPowerPage(page);
        const adminRequestPage = new ManPowerAdminPage(adminPage);
        const key = Date.now();
        const requestInput = buildApprovalInput(key);

        await manPowerPage.submitRequest(requestInput);
        await adminRequestPage.gotoRequestListing();
        await adminRequestPage.approveRequest(requestInput.applicantName);

        const metadata = getManPowerRequestMetadata(requestInput.applicantEmail);
        expect(metadata.status).toBe("approved");

        await manPowerPage.gotoProgressPath(metadata.progressPath);
        await expect(page.getByText(/Disetujui|Approved/i).first()).toBeVisible();
    });

    test("Approving a request creates a job posting in admin listing", async ({ page, adminPage }) => {
        const manPowerPage = new ManPowerPage(page);
        const adminRequestPage = new ManPowerAdminPage(adminPage);
        const key = Date.now();
        const requestInput = buildApprovalInput(key + 100);

        await manPowerPage.submitRequest(requestInput);
        await adminRequestPage.gotoRequestListing();
        await adminRequestPage.approveRequest(requestInput.applicantName);

        await adminRequestPage.gotoJobPostingListing();
        await adminRequestPage.assertJobPostingVisible(requestInput.requiredPosition);
    });

    test("Admin can reject and then set pending again", async ({ page, adminPage }) => {
        const manPowerPage = new ManPowerPage(page);
        const adminRequestPage = new ManPowerAdminPage(adminPage);
        const key = Date.now();
        const requestInput = buildApprovalInput(key + 1);

        await manPowerPage.submitRequest(requestInput);

        await adminRequestPage.gotoRequestListing();
        await adminRequestPage.rejectRequest(requestInput.applicantName);

        let metadata = getManPowerRequestMetadata(requestInput.applicantEmail);
        expect(metadata.status).toBe("rejected");

        await adminRequestPage.gotoRequestListing();
        await adminRequestPage.setPending(requestInput.applicantName);

        metadata = getManPowerRequestMetadata(requestInput.applicantEmail);
        expect(metadata.status).toBe("pending");

        await manPowerPage.gotoProgressPath(metadata.progressPath);
        await expect(page.getByText(/Pending/i).first()).toBeVisible();
    });
});

function buildApprovalInput(key: number): ManPowerRequestInput {
    return {
        applicantName: `E2E Man Power Admin ${key}`,
        applicantEmail: `man-power-admin+${key}@example.com`,
        applicantPosition: "Recruitment Lead",
        division: "Operations",
        businessEntity: "PT CESA",
        statusKebutuhan: "New Hiring",
        requiredPosition: `QA Engineer ${key}`,
        levelPekerjaan: "Staf",
        quantity: "1",
        placement: "Bandung",
        estimatedJoinDate: "2026-04-20",
        jobDescription: `QA position ${key}`,
        qualification: `Attention to detail ${key}`,
        notes: `Approval note ${key}`,
    };
}
