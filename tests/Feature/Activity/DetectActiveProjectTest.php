<?php

declare(strict_types=1);

use App\Actions\Activity\DetectActiveProject;
use App\Models\ProjectRepository;

pest()->group('actions', 'activity');

describe('DetectActiveProject', function () {
    it('returns null ids when filePath is null', function () {
        $result = DetectActiveProject::make()->handle(null);

        expect($result)->toBe(['project_id' => null, 'project_repository_id' => null]);
    });

    it('returns null ids when no repository matches the path', function () {
        ProjectRepository::factory()->create(['local_path' => '/Users/foo/code/other-project']);

        $result = DetectActiveProject::make()->handle('/Users/foo/code/my-project/src/app.php');

        expect($result)->toBe(['project_id' => null, 'project_repository_id' => null]);
    });

    it('resolves project from a file path under a repository local_path', function () {
        $repo = ProjectRepository::factory()->create(['local_path' => '/Users/foo/code/my-project']);

        $result = DetectActiveProject::make()->handle('/Users/foo/code/my-project/src/app.php');

        expect($result['project_id'])->toBe($repo->project_id)
            ->and($result['project_repository_id'])->toBe($repo->id);
    });

    it('picks the most specific (longest) matching repository', function () {
        ProjectRepository::factory()->create(['local_path' => '/Users/foo/code']);
        $specific = ProjectRepository::factory()->create(['local_path' => '/Users/foo/code/my-project']);

        $result = DetectActiveProject::make()->handle('/Users/foo/code/my-project/src/app.php');

        expect($result['project_repository_id'])->toBe($specific->id);
    });
});
