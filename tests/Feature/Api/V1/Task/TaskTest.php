<?php

namespace Tests\Feature\Api\V1\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Workspace $workspace;
    protected TaskStatus $pendingStatus;
    protected TaskStatus $workingStatus;
    protected TaskStatus $completedStatus;
    protected TaskType $taskType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        $this->project = Project::create([
            'name'       => 'SaaS Platform',
            'created_by' => $this->user->id,
        ]);

        $this->workspace = Workspace::create([
            'project_id' => $this->project->id,
            'name'       => 'Sprint Workspace',
            'created_by' => $this->user->id,
        ]);

        $this->pendingStatus = TaskStatus::create([
            'name'  => 'To Do',
            'stage' => 'pending',
            'order' => 1,
        ]);

        $this->workingStatus = TaskStatus::create([
            'name'  => 'In Progress',
            'stage' => 'working',
            'order' => 2,
        ]);

        $this->completedStatus = TaskStatus::create([
            'name'  => 'Done',
            'stage' => 'completed',
            'order' => 3,
        ]);

        $this->taskType = TaskType::create([
            'name' => 'General Maintenance',
            'type' => 'device',
        ]);
    }

    public function test_guest_cannot_access_tasks(): void
    {
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);
    }

    public function test_auto_position_assigns_incrementing_order(): void
    {
        // Create first task without explicit position
        $response1 = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', [
                'project_id'   => $this->project->id,
                'workspace_id' => $this->workspace->id,
                'status_id'    => $this->pendingStatus->id,
                'task_type_id' => $this->taskType->id,
                'title'        => 'First Task',
                'priority'     => 'Normal',
            ]);

        $response1->assertStatus(201);
        $this->assertEquals(1, $response1->json('data.position'));

        // Create second task without explicit position
        $response2 = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', [
                'project_id'   => $this->project->id,
                'workspace_id' => $this->workspace->id,
                'status_id'    => $this->pendingStatus->id,
                'task_type_id' => $this->taskType->id,
                'title'        => 'Second Task',
                'priority'     => 'High',
            ]);

        $response2->assertStatus(201);
        $this->assertEquals(2, $response2->json('data.position'));
    }

    public function test_working_at_timestamp_is_set_when_status_transitions_to_working(): void
    {
        $task = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'title'        => 'Initial Pending Task',
            'position'     => 1,
        ]);

        $this->assertNull($task->working_at);

        // Update status to 'In Progress' (stage = working)
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/tasks/' . $task->id, [
                'status_id' => $this->workingStatus->id,
            ]);

        $response->assertStatus(200);
        $updatedTask = Task::find($task->id);

        $this->assertNotNull($updatedTask->working_at);
        $this->assertNull($updatedTask->completed_at);
    }

    public function test_completed_at_and_actual_minutes_are_calculated_when_stage_becomes_completed(): void
    {
        // Create task with working_at set to 45 minutes ago
        $workingTime = Carbon::now()->subMinutes(45);

        $task = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->workingStatus->id,
            'title'        => 'Task Near Completion',
            'position'     => 1,
            'working_at'   => $workingTime,
        ]);

        // Transition to 'Done' (stage = completed)
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/tasks/' . $task->id, [
                'status_id' => $this->completedStatus->id,
            ]);

        $response->assertStatus(200);
        $updatedTask = Task::find($task->id);

        $this->assertNotNull($updatedTask->completed_at);
        $this->assertNotNull($updatedTask->actual_minutes);
        // Actual minutes should be around 45 minutes
        $this->assertEquals(45, $updatedTask->actual_minutes);
    }

    public function test_task_soft_delete(): void
    {
        $task = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'title'        => 'Task To Soft Delete',
            'position'     => 1,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/tasks/' . $task->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task deleted successfully.',
            ]);

        // Task should not appear in regular query
        $this->assertNull(Task::find($task->id));

        // But should exist in trashed
        $this->assertNotNull(Task::withTrashed()->find($task->id));
        $this->assertNotNull(Task::withTrashed()->find($task->id)->deleted_at);
    }

    public function test_subtask_creation_with_parent_task_id(): void
    {
        $parentTask = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'title'        => 'Parent Feature Task',
            'position'     => 1,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', [
                'project_id'     => $this->project->id,
                'workspace_id'   => $this->workspace->id,
                'status_id'      => $this->pendingStatus->id,
                'task_type_id'   => $this->taskType->id,
                'parent_task_id' => $parentTask->id,
                'title'          => 'Subtask 1: Write Unit Tests',
                'priority'       => 'Urgent',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'parent_task_id' => $parentTask->id,
                    'title'          => 'Subtask 1: Write Unit Tests',
                ],
            ]);

        $this->assertEquals(1, $parentTask->subtasks()->count());
    }

    public function test_task_creation_with_attachments_uploaded_in_store_endpoint(): void
    {
        Storage::fake('public');

        $file1 = UploadedFile::fake()->create('requirements.pdf', 1024, 'application/pdf');
        $file2 = UploadedFile::fake()->image('design_mockup.png');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', [
                'project_id'   => $this->project->id,
                'workspace_id' => $this->workspace->id,
                'status_id'    => $this->pendingStatus->id,
                'task_type_id' => $this->taskType->id,
                'title'        => 'Task with Attachments',
                'priority'     => 'High',
                'attachments'  => [$file1, $file2],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'attachments' => [
                        '*' => [
                            'id',
                            'file',
                            'path',
                            'file_path',
                            'type',
                            'size',
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data['attachments']);
        $this->assertEquals('requirements.pdf', $data['attachments'][0]['file']);
        $this->assertStringStartsWith(Task::ATTACHMENT_PATH . '/', $data['attachments'][0]['path']);
        $this->assertNotEmpty($data['attachments'][0]['file_path']);
        $this->assertStringContainsString('/storage/' . $data['attachments'][0]['path'], $data['attachments'][0]['file_path']);

        Storage::disk('public')->assertExists($data['attachments'][0]['path']);
        Storage::disk('public')->assertExists($data['attachments'][1]['path']);

        $task = Task::find($data['id']);
        $this->assertEquals(2, $task->attachments()->count());
    }

    public function test_task_service_store_attachments_uses_task_attachment_path(): void
    {
        Storage::fake('public');

        $task = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'title'        => 'Task For Service Test',
            'position'     => 1,
        ]);

        $taskService = app(\App\Services\Task\TaskService::class);
        $file = UploadedFile::fake()->create('service_doc.txt', 128);

        $attachments = $taskService->storeAttachments($task, $file, $this->user->id);

        $this->assertCount(1, $attachments);
        $attachment = $attachments->first();

        $this->assertStringStartsWith(Task::ATTACHMENT_PATH . '/', $attachment->path);
        Storage::disk('public')->assertExists($attachment->path);
        $this->assertNotNull($attachment->getFilePath());
    }

    public function test_task_index_filters_root_tasks_and_loads_children_with_pagination(): void
    {
        $parentTask = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Parent Main Task',
            'position'     => 1,
        ]);

        $childTask = Task::create([
            'created_by'     => $this->user->id,
            'project_id'     => $this->project->id,
            'workspace_id'   => $this->workspace->id,
            'status_id'      => $this->pendingStatus->id,
            'task_type_id'   => $this->taskType->id,
            'parent_task_id' => $parentTask->id,
            'title'          => 'Child Subtask Item',
            'position'       => 2,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tasks?per_page=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'children',
                    ],
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        $rootTaskIds = collect($response->json('data'))->pluck('id');
        // Parent task must be present in the root list
        $this->assertTrue($rootTaskIds->contains($parentTask->id));
        // Child task must NOT be present in root list (filtered by whereNull parent_task_id)
        $this->assertFalse($rootTaskIds->contains($childTask->id));

        // But child task must be loaded inside parent task's 'children'
        $parentInResponse = collect($response->json('data'))->firstWhere('id', $parentTask->id);
        $this->assertNotEmpty($parentInResponse['children']);
        $this->assertEquals($childTask->id, $parentInResponse['children'][0]['id']);
    }

    public function test_task_show_loads_creator_project_and_children(): void
    {
        $parentTask = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Show Detailed Task',
            'position'     => 1,
        ]);

        $childTask = Task::create([
            'created_by'     => $this->user->id,
            'project_id'     => $this->project->id,
            'workspace_id'   => $this->workspace->id,
            'status_id'      => $this->pendingStatus->id,
            'task_type_id'   => $this->taskType->id,
            'parent_task_id' => $parentTask->id,
            'title'          => 'Child Under Show Task',
            'position'       => 2,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tasks/' . $parentTask->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'id'      => $parentTask->id,
                    'creator' => [
                        'id'    => $this->user->id,
                        'name'  => $this->user->name,
                        'email' => $this->user->email,
                    ],
                    'project' => [
                        'id'   => $this->project->id,
                        'name' => $this->project->name,
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.children'));
        $this->assertEquals($childTask->id, $response->json('data.children.0.id'));
    }

    public function test_task_trashed_and_restore_endpoints(): void
    {
        $task = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Task To Delete And Restore',
            'position'     => 1,
        ]);

        // Soft delete via DELETE /api/tasks/{task}
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/tasks/' . $task->id)
            ->assertStatus(200);

        // Get trashed via GET /api/tasks/trashed
        $trashedResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tasks/trashed');

        $trashedResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'pagination',
            ]);

        $trashedIds = collect($trashedResponse->json('data'))->pluck('id');
        $this->assertTrue($trashedIds->contains($task->id));

        // Restore via POST /api/tasks/{id}/restore
        $restoreResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks/' . $task->id . '/restore');

        $restoreResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task restored successfully.',
                'data'    => [
                    'id' => $task->id,
                ],
            ]);

        $this->assertNotNull(Task::find($task->id));
        $this->assertNull(Task::find($task->id)->deleted_at);
    }

    public function test_task_index_filters_by_single_status_id(): void
    {
        $pendingTask = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Pending Task Item',
            'position'     => 1,
        ]);

        $workingTask = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->workingStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Working Task Item',
            'position'     => 2,
        ]);

        $responsePending = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tasks?status_id=' . $this->pendingStatus->id);

        $responsePending->assertStatus(200);
        $pendingIds = collect($responsePending->json('data'))->pluck('id');
        $this->assertTrue($pendingIds->contains($pendingTask->id));
        $this->assertFalse($pendingIds->contains($workingTask->id));

        $responseWorking = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tasks?status_id=' . $this->workingStatus->id);

        $responseWorking->assertStatus(200);
        $workingIds = collect($responseWorking->json('data'))->pluck('id');
        $this->assertTrue($workingIds->contains($workingTask->id));
        $this->assertFalse($workingIds->contains($pendingTask->id));
    }

    public function test_task_index_filters_by_multiple_status_ids_using_array_and_comma_separated(): void
    {
        $pendingTask = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Pending Task Array Test',
            'position'     => 1,
        ]);

        $workingTask = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->workingStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Working Task Array Test',
            'position'     => 2,
        ]);

        $completedTask = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->completedStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Completed Task Array Test',
            'position'     => 3,
        ]);

        // Array syntax: ?status_id[]=X&status_id[]=Y
        $responseArray = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tasks?status_id[]={$this->pendingStatus->id}&status_id[]={$this->completedStatus->id}");

        $responseArray->assertStatus(200);
        $arrayIds = collect($responseArray->json('data'))->pluck('id');
        $this->assertTrue($arrayIds->contains($pendingTask->id));
        $this->assertTrue($arrayIds->contains($completedTask->id));
        $this->assertFalse($arrayIds->contains($workingTask->id));

        // Comma-separated syntax: ?status_id=X,Y
        $responseComma = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tasks?status_id={$this->workingStatus->id},{$this->completedStatus->id}");

        $responseComma->assertStatus(200);
        $commaIds = collect($responseComma->json('data'))->pluck('id');
        $this->assertTrue($commaIds->contains($workingTask->id));
        $this->assertTrue($commaIds->contains($completedTask->id));
        $this->assertFalse($commaIds->contains($pendingTask->id));

        // Alias: ?status_ids[]=X
        $responseAlias = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tasks?status_ids[]={$this->completedStatus->id}");

        $responseAlias->assertStatus(200);
        $aliasIds = collect($responseAlias->json('data'))->pluck('id');
        $this->assertTrue($aliasIds->contains($completedTask->id));
        $this->assertFalse($aliasIds->contains($pendingTask->id));
        $this->assertFalse($aliasIds->contains($workingTask->id));
    }

    public function test_trashed_tasks_can_be_filtered_by_status_id(): void
    {
        $task1 = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->pendingStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Trashed Pending Task',
            'position'     => 1,
        ]);

        $task2 = Task::create([
            'created_by'   => $this->user->id,
            'project_id'   => $this->project->id,
            'workspace_id' => $this->workspace->id,
            'status_id'    => $this->workingStatus->id,
            'task_type_id' => $this->taskType->id,
            'title'        => 'Trashed Working Task',
            'position'     => 2,
        ]);

        $task1->delete();
        $task2->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tasks/trashed?status_id=' . $this->pendingStatus->id);

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($task1->id));
        $this->assertFalse($ids->contains($task2->id));
    }
}
