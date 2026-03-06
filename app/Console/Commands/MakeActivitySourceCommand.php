<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

class MakeActivitySourceCommand extends Command
{
    protected $signature = 'make:activity-source {name? : The source name in StudlyCase (e.g. Jira)}';

    protected $description = 'Scaffold a new activity source (config, service, migration, enum case, settings property)';

    public function handle(): int
    {
        $name = $this->argument('name') ?? text('Source name (StudlyCase, e.g. Jira)');

        if (! $name) {
            $this->error('Source name is required.');

            return self::FAILURE;
        }

        $studly = Str::studly((string) $name);
        $snake = Str::snake($studly);

        $this->generateFile(
            stub: 'activity-source-config',
            destination: app_path("Data/ActivitySourceConfigs/{$studly}SourceConfig.php"),
            studly: $studly,
            snake: $snake,
        );

        $this->generateFile(
            stub: 'activity-source',
            destination: app_path("Services/ActivitySources/{$studly}ActivitySource.php"),
            studly: $studly,
            snake: $snake,
        );

        $this->generateFile(
            stub: 'activity-source-settings-migration',
            destination: database_path('settings/'.now()->format('Y_m_d_His')."_add_{$snake}_source_setting.php"),
            studly: $studly,
            snake: $snake,
        );

        $this->injectEnumCase($studly, $snake);
        $this->injectSettingsProperty($studly, $snake);

        $this->newLine();
        $this->components->info("Activity source [{$studly}] scaffolded successfully.");
        $this->newLine();
        $this->components->bulletList([
            "Implement <info>isAvailable()</info> and <info>scan()</info> in <comment>app/Services/ActivitySources/{$studly}ActivitySource.php</comment>",
            "Add config properties to <comment>app/Data/ActivitySourceConfigs/{$studly}SourceConfig.php</comment>",
            'Fill in <info>color()</info>, <info>requirements()</info>, <info>description()</info> match arms in <comment>app/Enums/ActivityEventSourceType.php</comment>',
            'Run <info>php artisan migrate</info> to apply the settings migration',
        ]);
        $this->newLine();

        return self::SUCCESS;
    }

    private function generateFile(string $stub, string $destination, string $studly, string $snake): void
    {
        $stubPath = app_path("Console/Commands/stubs/{$stub}.stub");

        if (! file_exists($stubPath)) {
            $this->error("Stub not found: {$stubPath}");

            return;
        }

        $content = (string) file_get_contents($stubPath);
        $content = str_replace('{{ NameStudly }}', $studly, $content);
        $content = str_replace('{{ nameSnake }}', $snake, $content);

        $dir = dirname($destination);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($destination)) {
            $this->warn("File already exists, skipping: {$destination}");

            return;
        }

        file_put_contents($destination, $content);
        $this->components->twoColumnDetail('<info>Created</info>', str_replace(base_path().'/', '', $destination));
    }

    private function injectEnumCase(string $studly, string $snake): void
    {
        $path = app_path('Enums/ActivityEventSourceType.php');
        $content = (string) file_get_contents($path);

        // Inject enum case after last existing case
        if (preg_match_all("/^    case \\w+ = '\\w+';/m", $content, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $insertPos = $lastMatch[1] + mb_strlen($lastMatch[0]);
            $content = mb_substr($content, 0, $insertPos)
                ."\n    case {$studly} = '{$snake}';"
                .mb_substr($content, $insertPos);
            $this->components->twoColumnDetail('<info>Modified</info>', 'app/Enums/ActivityEventSourceType.php (case)');
        } else {
            $this->warn('Could not inject enum case — please add it manually.');
        }

        // Inject match arm in color()
        $content = $this->injectMatchArm(
            content: $content,
            method: 'color',
            studly: $studly,
            arm: "            self::{$studly} => 'text-gray-400',",
        );

        // Inject match arm in requirements()
        $content = $this->injectMatchArm(
            content: $content,
            method: 'requirements',
            studly: $studly,
            arm: "            self::{$studly} => 'Requires {$snake} — <code>brew install {$snake}</code>',",
        );

        // Inject match arm in description()
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
        // Match the specific method body and inject before closing `};`
        $pattern = "/(public function {$method}\\(\\): string[^}]+)(            };)/s";

        return (string) preg_replace_callback($pattern, function (array $m) use ($arm, $studly) {
            // Only inject if the case is not already present
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

        // Inject use statement after last existing ActivitySourceConfigs use
        if (preg_match_all('/^use App\\\\Data\\\\ActivitySourceConfigs\\\\\\w+;/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            if (str_contains($content, $fqcn)) {
                $this->warn("Import {$fqcn} already exists, skipping.");
            } else {
                $lastUse = end($matches[0]);
                $insertPos = $lastUse[1] + mb_strlen($lastUse[0]);
                $content = mb_substr($content, 0, $insertPos)."\n".$use.mb_substr($content, $insertPos);
            }
        } else {
            $this->warn('Could not inject use statement — please add it manually.');
        }

        // Inject property after last existing public SourceConfig property
        if (preg_match_all('/^    public \\w+SourceConfig \\$\\w+;/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            if (str_contains($content, $property)) {
                $this->warn("Property \${$snake} already exists, skipping.");
            } else {
                $lastProp = end($matches[0]);
                $insertPos = $lastProp[1] + mb_strlen($lastProp[0]);
                $content = mb_substr($content, 0, $insertPos)."\n\n".$property.mb_substr($content, $insertPos);
            }
        } else {
            $this->warn('Could not inject property — please add it manually.');
        }

        file_put_contents($path, $content);
        $this->components->twoColumnDetail('<info>Modified</info>', 'app/Settings/ActivitySourceSettings.php');
    }
}
