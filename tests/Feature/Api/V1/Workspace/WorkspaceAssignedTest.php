<?php

namespace Tests\Feature\Api\V1\Workspace;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceAssignedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
    }

    public function test_user_can_see_all_workspaces_and_filter_by_project(): void
    {
        $userA = User::factory()->create();
        $userA->assignRole('admin');

        $userB = User::factory()->create();
        $userB->assignRole('admin');

        $projectA = Project::create([
            'name'       => 'Project A',
            'created_by' => $userA->id,
        ]);

        $projectB = Project::create([
            'name'       => 'Project B',
            'created_by' => $userB->id,
        ]);

        // Workspace A belongs to User A
        $workspaceA = Workspace::create([
            'project_id' => $projectA->id,
            'name'       => 'Workspace Alpha',
            'created_by' => $userA->id,
        ]);
        $workspaceA->users()->attach($userA->id, ['role' => 'creator']);

        // Workspace B belongs to User B
        $workspaceB = Workspace::create([
            'project_id' => $projectB->id,
            'name'       => 'Workspace Beta',
            'created_by' => $userB->id,
        ]);
        $workspaceB->users()->attach($userB->id, ['role' => 'creator']);

        // User A requests workspaces -> gets all workspaces
        $responseA = $this->actingAs($userA, 'sanctum')->getJson('/api/workspaces');
        $responseA->assertStatus(200);

        $workspaceIdsA = collect($responseA->json('data'))->pluck('id');
        $this->assertTrue($workspaceIdsA->contains($workspaceA->id));
        $this->assertTrue($workspaceIdsA->contains($workspaceB->id));

        // Filter by project_id
        $filteredResponse = $this->actingAs($userA, 'sanctum')->getJson('/api/workspaces?project_id=' . $projectA->id);
        $filteredResponse->assertStatus(200);
        $filteredIds = collect($filteredResponse->json('data'))->pluck('id');
        $this->assertTrue($filteredIds->contains($workspaceA->id));
        $this->assertFalse($filteredIds->contains($workspaceB->id));
    }

    public function test_user_can_see_all_projects_and_their_workspaces(): void
    {
        $userA = User::factory()->create();
        $userA->assignRole('admin');

        $userB = User::factory()->create();
        $userB->assignRole('admin');

        $projectA = Project::create([
            'name'       => 'Project Alpha',
            'created_by' => $userA->id,
        ]);

        $projectB = Project::create([
            'name'       => 'Project Beta',
            'created_by' => $userB->id,
        ]);

        $workspaceA = Workspace::create([
            'project_id' => $projectA->id,
            'name'       => 'Workspace Alpha',
            'created_by' => $userA->id,
        ]);

        // User A requests projects -> gets all projects and their workspaces
        $responseA = $this->actingAs($userA, 'sanctum')->getJson('/api/projects');
        $responseA->assertStatus(200);

        $projectIdsA = collect($responseA->json('data'))->pluck('id');
        $this->assertTrue($projectIdsA->contains($projectA->id));
        $this->assertTrue($projectIdsA->contains($projectB->id));

        $projectAData = collect($responseA->json('data'))->firstWhere('id', $projectA->id);
        $this->assertNotEmpty($projectAData['workspaces']);
    }

    public function test_user_only_sees_created_tasks_when_querying_another_project(): void
    {
        $status = TaskStatus::create(['name' => 'To Do', 'stage' => 'pending', 'order' => 1]);

        $project1 = Project::create(['name' => 'Project 1']);
        $project2 = Project::create(['name' => 'Project 2']);

        $workspace2 = Workspace::create([
            'project_id' => $project2->id,
            'name'       => 'Workspace in Project 2',
        ]);

        $user1 = User::factory()->create(['project_id' => $project1->id]);
        $user1->assignRole('admin');

        $user2 = User::factory()->create(['project_id' => $project2->id]);
        $user2->assignRole('admin');

        // Task in Project 2 created by User 2
        $taskProject2Other = Task::create([
            'title'        => 'Task by User 2 in Project 2',
            'project_id'   => $project2->id,
            'workspace_id' => $workspace2->id,
            'created_by'   => $user2->id,
            'status_id'    => $status->id,
            'position'     => 1,
        ]);

        // Task in Project 2 created by User 1
        $taskProject2User1 = Task::create([
            'title'        => 'Task by User 1 in Project 2',
            'project_id'   => $project2->id,
            'workspace_id' => $workspace2->id,
            'created_by'   => $user1->id,
            'status_id'    => $status->id,
            'position'     => 2,
        ]);

        // When User 1 queries Project 2, they should ONLY see their own task ($taskProject2User1)
        $response = $this->actingAs($user1, 'sanctum')->getJson('/api/tasks?project_id=' . $project2->id);
        $response->assertStatus(200);

        $taskIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($taskIds->contains($taskProject2User1->id));
        $this->assertFalse($taskIds->contains($taskProject2Other->id));
    }
}
