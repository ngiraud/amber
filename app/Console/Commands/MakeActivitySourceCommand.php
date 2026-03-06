<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Info;
use Illuminate\Support\Str;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

class MakeActivitySourceCommand extends Command
{
    protected $signature = 'make:activity-source {name? : The source name in StudlyCase (e.g. Jira)}';

    protected $description = 'Scaffold a new activity source (config, service, migration, enum case, settings property)';

    public function handle(): int
    {
        intro('Activity Source Generator');

        $name = $this->argument('name') ?? text(
            label: 'Source name',
            placeholder: 'Jira',
            required: 'Source name is required.',
            validate: fn (string $v) => preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $v)
                ? null
                : 'Use letters and numbers only (e.g. Jira, LinearApp).',
            hint: 'StudlyCase — e.g. "Jira" generates JiraSourceConfig, JiraActivitySource, …',
        );

        $studly = Str::studly((string) $name);
        $snake = Str::snake($studly);

        /** @var list<array{string, string}> $rows */
        $rows = [];

        spin(function () use ($studly, $snake, &$rows): void {
            $stubs = [
                'activity-source-config' => app_path("Data/ActivitySourceConfigs/{$studly}SourceConfig.php"),
                'activity-source' => app_path("Services/ActivitySources/{$studly}ActivitySource.php"),
                'activity-source-settings-migration' => database_path('settings/'.now()->format('Y_m_d_His')."_add_{$snake}_source_setting.php"),
            ];

            foreach ($stubs as $stub => $destination) {
                $status = $this->generateFile($stub, $destination, $studly, $snake);
                $rows[] = [$status, str_replace(base_path().'/', '', $destination)];
            }

            $this->injectEnumCase($studly, $snake);
            $rows[] = ['Modified', 'app/Enums/ActivityEventSourceType.php'];

            $this->injectSettingsProperty($studly, $snake);
            $rows[] = ['Modified', 'app/Settings/ActivitySourceSettings.php'];
        }, 'Scaffolding files…');

        table(
            headers: ['Action', 'File'],
            rows: $rows,
        );

        new Info($this->output)->render('Next steps');

        note(
            <<<TEXT
            → Implement isAvailable() and scan() in:
              app/Services/ActivitySources/{$studly}ActivitySource.php

            → Add config properties and fieldDefinitions() in:
              app/Data/ActivitySourceConfigs/{$studly}SourceConfig.php

            → Fill in color(), requirements(), description() in:
              app/Enums/ActivityEventSourceType.php

            → Run: php artisan migrate
            TEXT,
        );

        outro("Activity source [{$studly}] is ready.");

        return self::SUCCESS;
    }

    private function generateFile(string $stub, string $destination, string $studly, string $snake): string
    {
        $stubPath = app_path("Console/Commands/stubs/{$stub}.stub");

        if (! file_exists($stubPath)) {
            return '⚠ Stub missing';
        }

        if (file_exists($destination)) {
            return 'Skipped (exists)';
        }

        $content = (string) file_get_contents($stubPath);
        $content = str_replace('{{ NameStudly }}', $studly, $content);
        $content = str_replace('{{ nameSnake }}', $snake, $content);

        $dir = dirname($destination);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($destination, $content);

        return 'Created';
    }

    private function injectEnumCase(string $studly, string $snake): void
    {
        $path = app_path('Enums/ActivityEventSourceType.php');
        $content = (string) file_get_contents($path);

        if (preg_match_all("/^    case \\w+ = '\\w+';/m", $content, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $insertPos = $lastMatch[1] + mb_strlen($lastMatch[0]);
            $content = mb_substr($content, 0, $insertPos)
                ."\n    case {$studly} = '{$snake}';"
                .mb_substr($content, $insertPos);
        }

        $content = $this->injectMatchArm(
            content: $content,
            method: 'color',
            studly: $studly,
            arm: "            self::{$studly} => 'text-gray-400',",
        );

        $content = $this->injectMatchArm(
            content: $content,
            method: 'requirements',
            studly: $studly,
            arm: "            self::{$studly} => 'Requires {$snake} — <code>brew install {$snake}</code>',",
        );

        $content = $this->injectMatchArm(
            content: $content,
            method: 'description',
            studly: $studly,
            arm: "            self::{$studly} => 'Detect {$snake} activity',",
        );

        file_put_contents($path, $content);
    }

    private function injectMatchArm(string $content, string $method, string $studly, string $arm): string
    {
        $pattern = "/(public function {$method}\\(\\): string[^}]+)(            };)/s";

        return (string) preg_replace_callback($pattern, function (array $m) use ($arm, $studly) {
            if (str_contains($m[0], "self::{$studly}")) {
                return $m[0];
            }

            return $m[1].$arm."\n".$m[2];
        }, $content);
    }

    private function injectSettingsProperty(string $studly, string $snake): void
    {
        $path = app_path('Settings/ActivitySourceSettings.php');
        $content = (string) file_get_contents($path);

        $fqcn = "App\\Data\\ActivitySourceConfigs\\{$studly}SourceConfig";
        $use = "use App\\Data\\ActivitySourceConfigs\\{$studly}SourceConfig;";
        $property = "    public {$studly}SourceConfig \${$snake};";

        if (! str_contains($content, $fqcn)
            && preg_match_all('/^use App\\\\Data\\\\ActivitySourceConfigs\\\\\\w+;/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $lastUse = end($matches[0]);
            $insertPos = $lastUse[1] + mb_strlen($lastUse[0]);
            $content = mb_substr($content, 0, $insertPos)."\n".$use.mb_substr($content, $insertPos);
        }

        if (! str_contains($content, $property)
            && preg_match_all('/^    public \\w+SourceConfig \\$\\w+;/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $lastProp = end($matches[0]);
            $insertPos = $lastProp[1] + mb_strlen($lastProp[0]);
            $content = mb_substr($content, 0, $insertPos)."\n\n".$property.mb_substr($content, $insertPos);
        }

        file_put_contents($path, $content);
    }
}
