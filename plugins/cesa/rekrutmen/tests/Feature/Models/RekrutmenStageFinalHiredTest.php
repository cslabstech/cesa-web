<?php

namespace Cesa\Rekrutmen\Tests\Feature\Models;

use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use InvalidArgumentException;

class RekrutmenStageFinalHiredTest extends RekrutmenTestCase
{
    public function test_hired_stage_is_forced_to_last_position(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Warehouse Hiring Flow',
        ]);

        $hiredStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Hired',
            'order_column'          => 1,
        ]);

        RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview User',
            'order_column'          => 2,
        ]);

        $hiredStage->refresh();

        $this->assertSame('Hired', $hiredStage->name);
        $this->assertSame(3, (int) $hiredStage->order_column);
    }

    public function test_new_stage_can_be_inserted_before_hired_stage(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Insert Before Hired',
        ]);

        RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Interview HR',
            'order_column'          => 1,
        ]);

        $hiredStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Hired',
            'order_column'          => 2,
        ]);

        $medicalStage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Medical Checkup',
            'order_column'          => 2,
        ]);

        $hiredStage->refresh();

        $this->assertSame(2, (int) $medicalStage->order_column);
        $this->assertSame(3, (int) $hiredStage->order_column);
    }

    public function test_hired_stage_name_is_locked_to_hired(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Locked Hired Name',
        ]);

        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Hired',
            'order_column'          => 1,
        ]);

        $stage->update([
            'name' => 'Final Hired',
        ]);

        $stage->refresh();

        $this->assertSame('Hired', $stage->name);
    }

    public function test_hired_stage_cannot_be_deleted(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Protected Hired Stage',
        ]);

        $stage = RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Hired',
            'order_column'          => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('rekrutmen::filament/resources/rekrutmen-pipeline.errors.final_hired_stage_locked'));

        $stage->delete();
    }

    public function test_pipeline_cannot_have_duplicate_hired_stage(): void
    {
        $pipeline = RekrutmenPipeline::query()->create([
            'name' => 'Unique Hired Stage',
        ]);

        RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'Hired',
            'order_column'          => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('rekrutmen::filament/resources/rekrutmen-pipeline.errors.duplicate_final_hired_stage'));

        RekrutmenStage::query()->create([
            'rekrutmen_pipeline_id' => $pipeline->id,
            'name'                  => 'HIRED',
            'order_column'          => 2,
        ]);
    }
}
