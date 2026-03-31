import { test } from "../../setup";
import { FormTransferAdminPage } from "../../pages/09_formTransferAdmin";
import { FormTransferPage, type FormTransferRequestInput } from "../../pages/08_formTransfer";
import {
    createPublicFormTransferFixture,
    getFormTransferRequestMetadata,
} from "../../utils/formTransfer";

test.describe("Form Transfer Public Flow E2E", () => {
    const buildRequestInput = (key: number, fixture: ReturnType<typeof createPublicFormTransferFixture>): FormTransferRequestInput => ({
        email: `form-transfer+${key}@example.com`,
        requesterName: `E2E Form Transfer ${key}`,
        division: fixture.divisionName,
        bank: "BCA",
        accountNumber: `12345${String(key).slice(-5)}`,
        accountName: `Pemohon ${key}`,
        transferAmount: "1250000",
        purpose: `Kebutuhan operasional ${key}`,
        referenceNote: `Catatan referensi ${key}`,
    });

    test("Public form validation shows feedback and keeps submit usable", async ({ page }) => {
        const fixture = createPublicFormTransferFixture(Date.now());
        const formTransferPage = new FormTransferPage(page);

        await formTransferPage.gotoRequestForm(fixture.publicPath);
        await formTransferPage.expectValidationFeedbackAndRecoverableSubmit();
    });

    test("Public form submission is visible from admin request detail", async ({ page, adminPage }) => {
        const fixture = createPublicFormTransferFixture(Date.now());
        const formTransferPage = new FormTransferPage(page);
        const adminRequestPage = new FormTransferAdminPage(adminPage);
        const requestInput = buildRequestInput(Date.now(), fixture);

        await formTransferPage.submitRequest(fixture.publicPath, requestInput);

        const metadata = getFormTransferRequestMetadata(requestInput.email);

        await formTransferPage.assertProgressPageShowsSubmission({
            requesterName: requestInput.requesterName,
            division: requestInput.division,
            uid: metadata.uid,
            purpose: requestInput.purpose,
        });

        await adminRequestPage.gotoRequestView(metadata.requestId);
        await adminRequestPage.assertRequestDetails({
            requesterName: requestInput.requesterName,
            email: requestInput.email,
            uid: metadata.uid,
        });
    });

    test("Approver can reject a submission and progress page reflects the rejected state", async ({ page }) => {
        const fixture = createPublicFormTransferFixture(Date.now());
        const formTransferPage = new FormTransferPage(page);
        const key = Date.now();
        const requestInput = buildRequestInput(key, fixture);
        const rejectionNote = `Reject note ${key}`;

        await formTransferPage.submitRequest(fixture.publicPath, requestInput);

        const metadata = getFormTransferRequestMetadata(requestInput.email);

        if (!metadata.approvalPath) {
            throw new Error(`No approval path generated for ${requestInput.email}`);
        }

        await formTransferPage.gotoApprovalPath(metadata.approvalPath);
        await formTransferPage.rejectApproval(rejectionNote);
        await formTransferPage.gotoProgressPath(metadata.progressPath);
        await formTransferPage.assertProgressPageShowsRejectedState(rejectionNote);
    });

    test("Approvers can approve until final and request becomes approved", async ({ page, adminPage }) => {
        const fixture = createPublicFormTransferFixture(Date.now());
        const formTransferPage = new FormTransferPage(page);
        const adminRequestPage = new FormTransferAdminPage(adminPage);
        const key = Date.now();
        const requestInput = buildRequestInput(key, fixture);
        const finalApprovalNote = `Approve note final ${key}`;

        await formTransferPage.submitRequest(fixture.publicPath, requestInput);

        const metadata = getFormTransferRequestMetadata(requestInput.email);

        if (metadata.approvalPaths.length === 0) {
            throw new Error(`No approval paths generated for ${requestInput.email}`);
        }

        for (const [index, approvalPath] of metadata.approvalPaths.entries()) {
            const note =
                index === metadata.approvalPaths.length - 1
                    ? finalApprovalNote
                    : `Approve note ${index + 1} ${key}`;

            await formTransferPage.gotoApprovalPath(approvalPath);
            await formTransferPage.approveApproval(note);
        }

        await formTransferPage.gotoProgressPath(metadata.progressPath);
        await formTransferPage.assertProgressPageShowsApprovedState(finalApprovalNote);

        await adminRequestPage.gotoRequestView(metadata.requestId);
        await adminRequestPage.assertRequestDetails({
            requesterName: requestInput.requesterName,
            email: requestInput.email,
            uid: metadata.uid,
            status: /disetujui|approved/i,
        });
    });
});
