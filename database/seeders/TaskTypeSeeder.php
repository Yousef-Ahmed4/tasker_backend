<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\TaskType;
use Illuminate\Database\Seeder;

class TaskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::pluck('id')->all();
        $firstProjectId = $projects[0] ?? null;
        $secondProjectId = $projects[1] ?? $firstProjectId;
        $thirdProjectId = $projects[2] ?? $firstProjectId;

        $types = [
            [
                'name'       => 'Network Issue',
                'type'       => 'network',
                'project_id' => $firstProjectId,
            ],
            [
                'name'       => 'Device Maintenance',
                'type'       => 'device',
                'project_id' => $firstProjectId,
            ],
            [
                'name'       => 'Focus Work',
                'type'       => 'focus',
                'project_id' => $secondProjectId,
            ],
            [
                'name'       => 'General Tasks',
                'type'       => 'other',
                'project_id' => $thirdProjectId,
            ],
        ];

        foreach ($types as $type) {
            TaskType::updateOrCreate(
                ['name' => $type['name'], 'project_id' => $type['project_id']],
                $type
            );
        }
    }
}
