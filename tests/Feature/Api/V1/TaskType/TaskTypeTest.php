<?php

namespace Tests\Feature\Api\V1\TaskType;

use App\Models\Project;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = User::where('email', 'admin@admin.com')->first();
        $this->regularUser = User::where('email', 'user@user.com')->first();
    }

    public function test_guest_cannot_access_task_types(): void
    {
        $response = $this->getJson('/api/task-types');
        $response->assertStatus(401);
    }

    public function test_admin_can_list_task_types(): void
    {
        TaskType::create(['name' => 'Network Setup', 'type' => 'network']);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/task-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'type', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_admin_can_create_task_type(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/task-types', [
            'name' => 'Device Repair',
            'type' => 'device',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'name' => 'Device Repair',
                    'type' => 'device',
                ],
            ]);

        $this->assertDatabaseHas('task_types', [
            'name' => 'Device Repair',
            'type' => 'device',
        ]);
    }

    public function test_task_type_creation_validates_type(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/task-types', [
            'name' => 'Invalid Type Task',
            'type' => 'not_a_valid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_admin_can_show_and_update_task_type(): void
    {
        $taskType = TaskType::create(['name' => 'Focus Mode', 'type' => 'focus']);

        $showResponse = $this->actingAs($this->admin, 'sanctum')->getJson('/api/task-types/' . $taskType->id);
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Focus Mode');

        $updateResponse = $this->actingAs($this->admin, 'sanctum')->putJson('/api/task-types/' . $taskType->id, [
            'name' => 'Deep Focus',
            'type' => 'focus',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Deep Focus');
    }

    public function test_admin_can_delete_task_type(): void
    {
        $taskType = TaskType::create(['name' => 'Other Task', 'type' => 'other']);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson('/api/task-types/' . $taskType->id);
        $response->assertStatus(200);

        $this->assertSoftDeleted('task_types', ['id' => $taskType->id]);
    }

    public function test_task_creation_requires_valid_task_type_id(): void
    {
        $project = Project::create([
            'name'       => 'Test Project',
            'created_by' => $this->admin->id,
        ]);

        $workspace = Workspace::create([
            'project_id' => $project->id,
            'name'       => 'Test Workspace',
            'created_by' => $this->admin->id,
        ]);

        $status = TaskStatus::create([
            'name'  => 'Backlog',
            'stage' => 'pending',
            'order' => 1,
        ]);

        // Request missing task_type_id
        $missingResponse = $this->actingAs($this->admin, 'sanctum')->postJson('/api/tasks', [
            'project_id'   => $project->id,
            'workspace_id' => $workspace->id,
            'status_id'    => $status->id,
            'title'        => 'Task Without Type',
        ]);
        $missingResponse->assertStatus(422)
            ->assertJsonValidationErrors(['task_type_id']);

        // Request with non-existent task_type_id
        $invalidResponse = $this->actingAs($this->admin, 'sanctum')->postJson('/api/tasks', [
            'project_id'   => $project->id,
            'workspace_id' => $workspace->id,
            'status_id'    => $status->id,
            'task_type_id' => 99999,
            'title'        => 'Task With Invalid Type',
        ]);
        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['task_type_id']);

        // Request with valid task_type_id
        $taskType = TaskType::create(['name' => 'Network Task', 'type' => 'network']);

        $validResponse = $this->actingAs($this->admin, 'sanctum')->postJson('/api/tasks', [
            'project_id'   => $project->id,
            'workspace_id' => $workspace->id,
            'status_id'    => $status->id,
            'task_type_id' => $taskType->id,
            'title'        => 'Valid Task',
        ]);
        $validResponse->assertStatus(201)
            ->assertJsonPath('data.task_type_id', $taskType->id);
    }

    public function test_can_filter_task_types_by_project_id(): void
    {
        $projectA = Project::create(['name' => 'Project A for Type']);
        $projectB = Project::create(['name' => 'Project B for Type']);

        $typeA = TaskType::create(['name' => 'Type A', 'type' => 'network', 'project_id' => $projectA->id]);
        $typeB = TaskType::create(['name' => 'Type B', 'type' => 'device', 'project_id' => $projectB->id]);

        $resA = $this->actingAs($this->admin, 'sanctum')->getJson('/api/task-types?project_id=' . $projectA->id);
        $resA->assertStatus(200);
        $idsA = collect($resA->json('data'))->pluck('id');
        $this->assertTrue($idsA->contains($typeA->id));
        $this->assertFalse($idsA->contains($typeB->id));
    }
}
