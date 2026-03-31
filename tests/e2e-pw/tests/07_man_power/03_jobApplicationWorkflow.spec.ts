import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { expect, test } from "../../setup";
import { RekrutmenPipelineAdminPage } from "../../pages/13_rekrutmenPipelineAdmin";
import { JobPostingAdminPage } from "../../pages/14_jobPostingAdmin";
import { JobApplicationAdminPage } from "../../pages/15_jobApplicationAdmin";
import { getJobApplicationMetadata, getJobPostingMetadata } from "../../utils/rekrutmenWorkflow";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const thumbnailPath = path.resolve(
    __dirname,
    "../../../../public/images/logo.png",
);

const pdfBuffer = Buffer.from("%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF", "utf8");

test.describe("Rekrutmen Candidate Workflow E2E", () => {
    test("Admin can create pipeline and job posting, then candidate enters first stage on board", async ({
        adminPage,
        request,
    }) => {
        const pipelinePage = new RekrutmenPipelineAdminPage(adminPage);
        const postingPage = new JobPostingAdminPage(adminPage);
        const applicationPage = new JobApplicationAdminPage(adminPage);
        const key = Date.now();
        const pipelineName = `E2E Hiring Pipeline ${key}`;
        const stageOne = `CV Screening ${key}`;
        const stageTwo = `HR Interview ${key}`;
        const postingTitle = `Backend Developer ${key}`;
        const postingSlug = `backend-developer-${key}`;
        const candidateEmail = `candidate-${key}@example.com`;
        const candidateName = `Budi Santoso ${key}`;

        await pipelinePage.gotoListing();
        await pipelinePage.createPipeline({
            name: pipelineName,
            description: `Pipeline ${key}`,
            stages: [stageOne, stageTwo],
        });
        await pipelinePage.gotoListing();
        await pipelinePage.assertPipelineVisible(pipelineName);

        await postingPage.gotoListing();
        await postingPage.createPosting({
            title: postingTitle,
            slug: postingSlug,
            pipeline: pipelineName,
            location: "Jakarta",
            jobDesk: `Build APIs ${key}`,
            qualifications: `Laravel ${key}`,
            isPublished: true,
            thumbnailPath,
        });
        await postingPage.gotoListing();
        await postingPage.assertPostingVisible(postingTitle);

        const applyResponse = await request.post(`/api/jobs/${postingSlug}/apply`, {
            multipart: {
                full_name: candidateName,
                email: candidateEmail,
                gender: "male",
                birth_date: "1995-01-10",
                marital_status: "single",
                address_ktp: "Jl. KTP No. 1, Jakarta",
                address_domicile: "Jl. Domisili No. 2, Bekasi",
                whatsapp_number: "081200000001",
                active_phone: "081200000002",
                emergency_contact_name: "Bunga",
                emergency_contact_relation: "Adik Kandung",
                emergency_contact_phone: "081200000003",
                photo: {
                    name: "photo.png",
                    mimeType: "image/png",
                    buffer: fs.readFileSync(thumbnailPath),
                },
                resume: {
                    name: "resume.pdf",
                    mimeType: "application/pdf",
                    buffer: pdfBuffer,
                },
            },
        });

        expect(applyResponse.status()).toBe(201);

        const postingMetadata = getJobPostingMetadata(postingSlug);
        const applicationMetadata = getJobApplicationMetadata(candidateEmail);

        expect(applicationMetadata.currentStageId).toBe(postingMetadata.firstStageId);
        expect(applicationMetadata.historyCount).toBe(1);

        await applicationPage.gotoListing();
        await applicationPage.assertCandidateVisible(candidateName.toUpperCase());

        await applicationPage.gotoBoard(postingMetadata.id);
        await applicationPage.assertCandidateInBoardColumn(
            candidateName.toUpperCase(),
            stageOne,
        );
    });

    test("Admin can move candidate on board, accept candidate, and reject another candidate with recorded history", async ({
        adminPage,
        request,
    }) => {
        const pipelinePage = new RekrutmenPipelineAdminPage(adminPage);
        const postingPage = new JobPostingAdminPage(adminPage);
        const applicationPage = new JobApplicationAdminPage(adminPage);
        const key = Date.now();
        const pipelineName = `E2E Accept Pipeline ${key}`;
        const stageOne = `CV Screening ${key}`;
        const stageTwo = `HR Interview ${key}`;
        const postingTitle = `QA Engineer ${key}`;
        const postingSlug = `qa-engineer-${key}`;
        const candidateEmail = `accept-${key}@example.com`;
        const candidateName = `Accept Candidate ${key}`;
        const rejectedCandidateEmail = `reject-${key}@example.com`;
        const rejectedCandidateName = `Reject Candidate ${key}`;

        await pipelinePage.gotoListing();
        await pipelinePage.createPipeline({
            name: pipelineName,
            stages: [stageOne, stageTwo],
        });
        await pipelinePage.gotoListing();
        await pipelinePage.assertPipelineVisible(pipelineName);

        await postingPage.gotoListing();
        await postingPage.createPosting({
            title: postingTitle,
            slug: postingSlug,
            pipeline: pipelineName,
            location: "Bandung",
            jobDesk: `QA APIs ${key}`,
            qualifications: `Automation ${key}`,
            isPublished: true,
        });

        const applyResponse = await request.post(`/api/jobs/${postingSlug}/apply`, {
            multipart: {
                full_name: candidateName,
                email: candidateEmail,
                gender: "female",
                birth_date: "1996-02-11",
                marital_status: "single",
                address_ktp: "Jl. KTP No. 3, Bandung",
                address_domicile: "Jl. Domisili No. 4, Bandung",
                whatsapp_number: "081200000011",
                active_phone: "081200000012",
                emergency_contact_name: "Sari",
                emergency_contact_relation: "Kakak",
                emergency_contact_phone: "081200000013",
                photo: {
                    name: "photo.png",
                    mimeType: "image/png",
                    buffer: fs.readFileSync(thumbnailPath),
                },
                resume: {
                    name: "resume.pdf",
                    mimeType: "application/pdf",
                    buffer: pdfBuffer,
                },
            },
        });

        expect(applyResponse.status()).toBe(201);

        const rejectResponse = await request.post(`/api/jobs/${postingSlug}/apply`, {
            multipart: {
                full_name: rejectedCandidateName,
                email: rejectedCandidateEmail,
                gender: "male",
                birth_date: "1994-03-12",
                marital_status: "married",
                address_ktp: "Jl. KTP No. 5, Surabaya",
                address_domicile: "Jl. Domisili No. 6, Surabaya",
                whatsapp_number: "081200000021",
                active_phone: "081200000022",
                emergency_contact_name: "Rina",
                emergency_contact_relation: "Istri",
                emergency_contact_phone: "081200000023",
                photo: {
                    name: "photo.png",
                    mimeType: "image/png",
                    buffer: fs.readFileSync(thumbnailPath),
                },
                resume: {
                    name: "resume.pdf",
                    mimeType: "application/pdf",
                    buffer: pdfBuffer,
                },
            },
        });

        expect(rejectResponse.status()).toBe(201);

        const postingMetadata = getJobPostingMetadata(postingSlug);

        await applicationPage.gotoBoard(postingMetadata.id);
        await applicationPage.moveCandidateToBoardColumn(candidateName.toUpperCase(), stageTwo);

        let applicationMetadata = getJobApplicationMetadata(candidateEmail);
        expect(applicationMetadata.currentStageName).toBe(stageTwo);
        expect(applicationMetadata.historyCount).toBe(2);

        await applicationPage.gotoListing();
        await applicationPage.markCandidateAsHired(candidateName.toUpperCase(), "Accepted by E2E");

        applicationMetadata = getJobApplicationMetadata(candidateEmail);
        expect(applicationMetadata.status).toBe("hired");
        expect(applicationMetadata.lastHistoryStatus).toBe("hired");
        expect(applicationMetadata.historyCount).toBe(3);
        expect(applicationMetadata.lastHistoryNotes).toBe("Accepted by E2E");

        await applicationPage.gotoListing();
        await applicationPage.markCandidateAsRejected(rejectedCandidateName.toUpperCase(), "Rejected by E2E");

        const rejectedApplicationMetadata = getJobApplicationMetadata(rejectedCandidateEmail);
        expect(rejectedApplicationMetadata.status).toBe("rejected");
        expect(rejectedApplicationMetadata.lastHistoryStatus).toBe("rejected");
        expect(rejectedApplicationMetadata.historyCount).toBe(2);
        expect(rejectedApplicationMetadata.lastHistoryNotes).toBe("Rejected by E2E");
    });
});
