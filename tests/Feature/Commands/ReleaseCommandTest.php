<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;

pest()->group('commands');

/** @var array<string> */
const RELEASE_TYPE_CHOICES = [
    'Major — v1.0.0',
    'Minor — v0.2.0',
    'Patch — v0.1.1',
    'major',
    'minor',
    'patch',
];

beforeEach(function (): void {
    $this->changelogPath = tempnam(sys_get_temp_dir(), 'changelog');
    config(['changelog.path' => $this->changelogPath]);

    file_put_contents($this->changelogPath, implode("\n", [
        '# Changelog',
        '',
        '## [Unreleased]',
        '',
        '### Added',
        '- Some feature',
        '',
        '---',
        '',
        '## [0.1.0] — 2026-01-01',
        '',
        '### Added',
        '- Foundation',
        '',
    ]));
});

afterEach(function (): void {
    @unlink($this->changelogPath);
});

/**
 * Array commands are Symfony-escaped: ['git', 'tag'] becomes "'git' 'tag'".
 * String commands are passed as-is.
 *
 * @param  array<string, mixed>  $overrides
 */
function fakeReleaseProcesses(array $overrides = []): void
{
    Process::fake(array_merge([
        "'git' 'rev-parse'*" => Process::result('main'),
        "'git' 'status'*" => Process::result(''),
        "'git' 'fetch'*" => Process::result(''),
        "'git' 'rev-list'*" => Process::result('0'),
        'git describe*' => Process::result('v0.1.0'),
        "'git' 'log'*" => Process::result(''),
        "'git' 'add'*" => Process::result(''),
        "'git' 'commit'*" => Process::result(''),
        "'git' 'push'*" => Process::result(''),
        "'git' 'tag'*" => Process::result(''),
    ], $overrides));
}

describe('preflight checks', function (): void {
    it('fails when not on main branch', function (): void {
        fakeReleaseProcesses(["'git' 'rev-parse'*" => Process::result('feature/foo')]);

        $this->artisan('release')->assertFailed();
    });

    it('fails when behind origin', function (): void {
        fakeReleaseProcesses(["'git' 'rev-list'*" => Process::result('2')]);

        $this->artisan('release')->assertFailed();
    });

    it('blocks when uncommitted changes are declined', function (): void {
        fakeReleaseProcesses(["'git' 'status'*" => Process::result('M app/Models/User.php')]);

        $this->artisan('release')
            ->expectsConfirmation('Uncommitted changes detected. Release anyway?', 'no')
            ->assertFailed();
    });

    it('continues when uncommitted changes are accepted', function (): void {
        fakeReleaseProcesses(["'git' 'status'*" => Process::result('M app/Models/User.php')]);

        $this->artisan('release')
            ->expectsConfirmation('Uncommitted changes detected. Release anyway?', 'yes')
            ->expectsConfirmation('Have you run `composer test:all` and did all checks pass?', 'yes')
            ->expectsChoice('Select release type', 'patch', RELEASE_TYPE_CHOICES)
            ->expectsConfirmation('Create and push tag v0.1.1?', 'no')
            ->assertSuccessful();
    });
})->group('preflight');

describe('test suite', function (): void {
    it('fails when confirmation is declined', function (): void {
        fakeReleaseProcesses();

        $this->artisan('release')
            ->expectsConfirmation('Have you run `composer test:all` and did all checks pass?', 'no')
            ->assertFailed();
    });
})->group('tests');

describe('version selection', function (): void {
    it('cancels when release is not confirmed', function (): void {
        fakeReleaseProcesses();

        $this->artisan('release')
            ->expectsConfirmation('Have you run `composer test:all` and did all checks pass?', 'yes')
            ->expectsChoice('Select release type', 'patch', RELEASE_TYPE_CHOICES)
            ->expectsConfirmation('Create and push tag v0.1.1?', 'no')
            ->assertSuccessful();

        Process::assertNotRan("'git' 'tag'*");
    });
})->group('version');

describe('publish', function (): void {
    it('creates the tag for the selected version', function (string $type, string $expectedVersion): void {
        fakeReleaseProcesses();

        $this->artisan('release')
            ->expectsConfirmation('Have you run `composer test:all` and did all checks pass?', 'yes')
            ->expectsChoice('Select release type', $type, RELEASE_TYPE_CHOICES)
            ->expectsConfirmation("Create and push tag v{$expectedVersion}?", 'yes')
            ->assertSuccessful();

        Process::assertRan(fn ($p) => $p->command === ['git', 'tag', "v{$expectedVersion}"]);
        Process::assertRan(fn ($p) => $p->command === ['git', 'push', 'origin', "v{$expectedVersion}", '--quiet']);
    })->with([
        'patch' => ['patch', '0.1.1'],
        'minor' => ['minor', '0.2.0'],
        'major' => ['major', '1.0.0'],
    ]);

    it('replaces [Unreleased] in changelog with the new version', function (): void {
        fakeReleaseProcesses();

        $this->artisan('release')
            ->expectsConfirmation('Have you run `composer test:all` and did all checks pass?', 'yes')
            ->expectsChoice('Select release type', 'patch', RELEASE_TYPE_CHOICES)
            ->expectsConfirmation('Create and push tag v0.1.1?', 'yes')
            ->assertSuccessful();

        $changelog = (string) file_get_contents($this->changelogPath);

        expect($changelog)
            ->not->toContain('## [Unreleased]')
            ->toContain('## [0.1.1]');
    });
})->group('publish');
