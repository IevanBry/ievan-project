<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Storage;

class ProjectController extends Controller
{
    public function index()
    {
        $query = Project::query();

        $sortField = request("sort_field", "created_at");
        $sortDirection = request("sort_direction", "desc");

        if(request("name")){
            $query->where("name","like","%" . request("name") . "%");
        }

        if(request("status")){
            $query->where("status", request("status"));
        }

        $projects = $query->orderBy($sortField, $sortDirection)
        ->paginate(10);
        
        return inertia("Project/index", [
            "projects" => ProjectResource::collection($projects),
            "queryParams" => request()->query() ?: null,
            'Success'=> session('Success'), 
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia("Project/Create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();

        $image = $data['image'] ?? null;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        if($image){
            $data['image_path'] = $image->store('project/' .Str::random(), 'public');
        }
        Project::create($data);

        return to_route('project.index')
        ->with('Success', 'Project was created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $query = $project->tasks();
        $sortField = request("sort_field", "created_at");
        $sortDirection = request("sort_direction", "desc");

        if(request("name")){
            $query->where("name", "like", "%" . request("name"). "%");
        }

        if(request("status")){
            $query->where("status", request("status"));
        }
        $tasks = $query->orderBy($sortField, $sortDirection)->paginate(10);

        return inertia('Project/Show', [
            'project' => new ProjectResource($project),
            'tasks' => TaskResource::collection($tasks),
            'queryParams' => request()->query ?: null,
        ]); 
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        return inertia('Project/Edit', [
            'project' => new ProjectResource($project),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $name = $project->name;
        $data = $request->validated();
        $image = $data['image'] ?? null;
        $data['updated_by'] = Auth::id();
        if($image) {
            if($project->image_path) {
                Storage::disk('public')->delete($project->image_path);
            }
            $data['image_path'] = $image->store('project/' . Str::random(), 'public');
        }
        $project->update($data);
        return to_route('project.index')
        ->with('Success', "Project \"$name\" was updated");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $name = $project->name;
        $project->delete();
        if($project->image_path)
        {
            Storage::disk('public')->delete($project->image_path);
        }
        return to_route('project.index')
            ->with('Success', "Project \"$name\" was deleted");
    }
}
