import { test } from "../../setup";
import { RekrutmenPipelineAdminPage } from "../../pages/13_rekrutmenPipelineAdmin";

test.describe("Rekrutmen Pipeline Admin Configuration E2E", () => {
    test("Admin can create, edit, and delete a recruitment pipeline", async ({ adminPage }) => {
        const pipelinePage = new RekrutmenPipelineAdminPage(adminPage);
        const key = Date.now();
        const initialName = `E2E Pipeline ${key}`;
        const updatedName = `E2E Pipeline Updated ${key}`;

        await pipelinePage.gotoListing();
        await pipelinePage.createPipeline({
            name: initialName,
            description: `Pipeline description ${key}`,
            stages: ["CV Screening", "HR Interview"],
        });

        await pipelinePage.gotoListing();
        await pipelinePage.assertPipelineVisible(initialName);

        await pipelinePage.editPipeline(initialName, {
            name: updatedName,
            description: `Updated pipeline ${key}`,
            stages: ["CV Screening", "User Interview", "Offering"],
        });

        await pipelinePage.gotoListing();
        await pipelinePage.assertPipelineVisible(updatedName);

        await pipelinePage.deletePipeline(updatedName);

        await pipelinePage.gotoListing();
        await pipelinePage.assertPipelineNotVisible(updatedName);
    });
});
