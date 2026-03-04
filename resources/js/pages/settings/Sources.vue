<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputField from '@/components/InputField.vue';
import SettingsLayout from '@/components/settings/SettingsLayout.vue';
import SourceCard from '@/components/settings/SourceCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import * as sourcesRoutes from '@/routes/settings/sources';
import type { ActivitySourceSettings } from '@/types';

const props = defineProps<{
    activitySourceSettings: ActivitySourceSettings;
    sourceInfo: Record<string, { requirements: string }>;
}>();

// ── Git ────────────────────────────────────────────────────────────────────

const gitForm = useForm({
    enabled: props.activitySourceSettings.git.enabled,
    author_emails: props.activitySourceSettings.git.author_emails.join(', '),
});

function saveGit(visitOptions: Record<string, unknown> = {}): void {
    gitForm
        .transform((data) => ({
            git: {
                enabled: data.enabled,
                author_emails: data.author_emails
                    .split(',')
                    .map((e: string) => e.trim())
                    .filter(Boolean),
            },
        }))
        .submit(sourcesRoutes.update(), visitOptions);
}

// ── GitHub ─────────────────────────────────────────────────────────────────

const githubForm = useForm({
    enabled: props.activitySourceSettings.github.enabled,
    username: props.activitySourceSettings.github.username ?? '',
});

function saveGithub(visitOptions: Record<string, unknown> = {}): void {
    githubForm
        .transform((data) => ({
            github: {
                enabled: data.enabled,
                username: data.username || null,
            },
        }))
        .submit(sourcesRoutes.update(), visitOptions);
}

// ── Claude Code ────────────────────────────────────────────────────────────

const claudeCodeForm = useForm({
    enabled: props.activitySourceSettings.claude_code.enabled,
    projects_path: props.activitySourceSettings.claude_code.projects_path,
});

function saveClaudeCode(visitOptions: Record<string, unknown> = {}): void {
    claudeCodeForm
        .transform((data) => ({
            claude_code: {
                enabled: data.enabled,
                projects_path: data.projects_path,
            },
        }))
        .submit(sourcesRoutes.update(), visitOptions);
}

// ── Fswatch ────────────────────────────────────────────────────────────────

const fswatchForm = useForm({
    enabled: props.activitySourceSettings.fswatch.enabled,
    debounce_seconds: props.activitySourceSettings.fswatch.debounce_seconds,
    excluded_patterns: props.activitySourceSettings.fswatch.excluded_patterns.join('\n'),
    allowed_extensions: props.activitySourceSettings.fswatch.allowed_extensions.join(', '),
});

function saveFswatch(visitOptions: Record<string, unknown> = {}): void {
    fswatchForm
        .transform((data) => ({
            fswatch: {
                enabled: data.enabled,
                debounce_seconds: data.debounce_seconds,
                excluded_patterns: data.excluded_patterns
                    .split('\n')
                    .map((p: string) => p.trim())
                    .filter(Boolean),
                allowed_extensions: data.allowed_extensions
                    .split(',')
                    .map((e: string) => e.trim())
                    .filter(Boolean),
            },
        }))
        .submit(sourcesRoutes.update(), visitOptions);
}
</script>

<template>
    <SettingsLayout active-tab="sources">
        <div class="flex flex-col gap-4">
            <!-- Git -->
            <SourceCard
                v-model:enabled="gitForm.enabled"
                title="Git"
                description="Detect commits and branch activity from local repositories"
                :requirements="sourceInfo['git']?.requirements ?? ''"
                source-value="git"
                @update:enabled="saveGit({ preserveScroll: true })"
            >
                <form class="flex flex-col gap-4" @submit.prevent="saveGit()">
                    <InputField label="Author emails" :error="gitForm.errors.author_emails" hint="Comma-separated emails used in git commits">
                        <Input v-model="gitForm.author_emails" type="text" placeholder="me@example.com, work@example.com" />
                    </InputField>
                    <div>
                        <Button type="submit" size="sm" :disabled="gitForm.processing">
                            {{ gitForm.processing ? 'Saving…' : 'Save' }}
                        </Button>
                    </div>
                </form>
            </SourceCard>

            <!-- GitHub -->
            <SourceCard
                v-model:enabled="githubForm.enabled"
                title="GitHub"
                description="Detect pull requests, reviews, and issue activity"
                :requirements="sourceInfo['github']?.requirements ?? ''"
                source-value="github"
                @update:enabled="saveGithub({ preserveScroll: true })"
            >
                <form class="flex flex-col gap-4" @submit.prevent="saveGithub()">
                    <InputField label="GitHub username" :error="githubForm.errors.username" hint="Used to filter your GitHub activity">
                        <Input v-model="githubForm.username" type="text" placeholder="octocat" />
                    </InputField>
                    <div>
                        <Button type="submit" size="sm" :disabled="githubForm.processing">
                            {{ githubForm.processing ? 'Saving…' : 'Save' }}
                        </Button>
                    </div>
                </form>
            </SourceCard>

            <!-- Claude Code -->
            <SourceCard
                v-model:enabled="claudeCodeForm.enabled"
                title="Claude Code"
                description="Detect Claude Code sessions and conversation history"
                :requirements="sourceInfo['claude-code']?.requirements ?? ''"
                source-value="claude-code"
                @update:enabled="saveClaudeCode({ preserveScroll: true })"
            >
                <form class="flex flex-col gap-4" @submit.prevent="saveClaudeCode()">
                    <InputField
                        label="Projects path"
                        :error="claudeCodeForm.errors.projects_path"
                        hint="Path to your Claude Code projects directory (default: ~/.claude/projects)"
                    >
                        <Input v-model="claudeCodeForm.projects_path" type="text" placeholder="~/.claude/projects" class="font-mono text-xs" />
                    </InputField>
                    <div>
                        <Button type="submit" size="sm" :disabled="claudeCodeForm.processing">
                            {{ claudeCodeForm.processing ? 'Saving…' : 'Save' }}
                        </Button>
                    </div>
                </form>
            </SourceCard>

            <!-- File Watcher -->
            <SourceCard
                v-model:enabled="fswatchForm.enabled"
                title="File Watcher"
                description="Detect file changes in real-time — restart required on toggle"
                :requirements="sourceInfo['fswatch']?.requirements ?? ''"
                source-value="fswatch"
                @update:enabled="saveFswatch({ preserveScroll: true })"
            >
                <form class="flex flex-col gap-4" @submit.prevent="saveFswatch()">
                    <InputField
                        label="Debounce (seconds)"
                        :error="fswatchForm.errors.debounce_seconds"
                        hint="Delay before recording a file change event"
                    >
                        <Input v-model.number="fswatchForm.debounce_seconds" type="number" min="1" max="30" class="max-w-32" />
                    </InputField>

                    <InputField
                        label="Excluded patterns"
                        :error="fswatchForm.errors.excluded_patterns"
                        hint="One regex pattern per line — matching paths are ignored"
                    >
                        <Textarea v-model="fswatchForm.excluded_patterns" rows="6" class="font-mono text-xs" />
                    </InputField>

                    <InputField
                        label="Allowed extensions"
                        :error="fswatchForm.errors.allowed_extensions"
                        hint="Comma-separated extensions to track without dot (e.g. php, ts, vue)"
                    >
                        <Textarea v-model="fswatchForm.allowed_extensions" rows="3" class="font-mono text-xs" />
                    </InputField>

                    <div>
                        <Button type="submit" size="sm" :disabled="fswatchForm.processing">
                            {{ fswatchForm.processing ? 'Saving…' : 'Save' }}
                        </Button>
                    </div>
                </form>
            </SourceCard>
        </div>
    </SettingsLayout>
</template>
