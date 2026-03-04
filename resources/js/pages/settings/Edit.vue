<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';
import InputField from '@/components/InputField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import * as settingsRoutes from '@/routes/settings';
import * as activityRoutes from '@/routes/settings/activity';
import * as generalRoutes from '@/routes/settings/general';
import * as sourcesRoutes from '@/routes/settings/sources';
import type { ActivitySettings, ActivitySourceSettings, GeneralSettings, LocaleOption } from '@/types';

const props = defineProps<{
    tab: 'general' | 'activity' | 'sources';
    generalSettings?: GeneralSettings;
    activitySettings?: ActivitySettings;
    activitySourceSettings?: ActivitySourceSettings;
    timezones?: string[];
    locales?: LocaleOption[];
}>();

const ROUNDING_OPTIONS = [
    { value: 15, label: 'Quarter hour (15 min)' },
    { value: 30, label: 'Half hour (30 min)' },
    { value: 60, label: 'Hour (60 min)' },
];

// ── General ────────────────────────────────────────────────────────────────

const generalForm = useForm({
    company_name: props.generalSettings?.company_name ?? '',
    company_address: props.generalSettings?.company_address ?? '',
    default_hourly_rate: props.generalSettings?.default_hourly_rate ?? null,
    default_daily_rate: props.generalSettings?.default_daily_rate ?? null,
    default_daily_reference_hours: props.generalSettings?.default_daily_reference_hours ?? 7,
    default_rounding_strategy: props.generalSettings?.default_rounding_strategy ?? 15,
    timezone: props.generalSettings?.timezone ?? '',
    locale: props.generalSettings?.locale ?? '',
});

function submitGeneral(): void {
    generalForm.submit(generalRoutes.update());
}

// ── Activity ───────────────────────────────────────────────────────────────

const activityForm = useForm({
    idle_timeout_minutes: props.activitySettings?.idle_timeout_minutes ?? 30,
    untracked_threshold_minutes: props.activitySettings?.untracked_threshold_minutes ?? 15,
    scan_interval_minutes: props.activitySettings?.scan_interval_minutes ?? 2,
    block_end_padding_minutes: props.activitySettings?.block_end_padding_minutes ?? 15,
});

function submitActivity(): void {
    activityForm.submit(activityRoutes.update());
}

// ── Sources ────────────────────────────────────────────────────────────────

const gitForm = useForm({
    enabled: props.activitySourceSettings?.git.enabled ?? true,
    author_emails: props.activitySourceSettings?.git.author_emails?.join(', ') ?? '',
});

const githubForm = useForm({
    enabled: props.activitySourceSettings?.github.enabled ?? true,
    username: props.activitySourceSettings?.github.username ?? '',
});

const claudeCodeForm = useForm({
    enabled: props.activitySourceSettings?.claude_code.enabled ?? true,
    projects_path: props.activitySourceSettings?.claude_code.projects_path ?? '',
});

const fswatchForm = useForm({
    enabled: props.activitySourceSettings?.fswatch.enabled ?? true,
    debounce_seconds: props.activitySourceSettings?.fswatch.debounce_seconds ?? 3,
    excluded_patterns: props.activitySourceSettings?.fswatch.excluded_patterns?.join('\n') ?? '',
    allowed_extensions: props.activitySourceSettings?.fswatch.allowed_extensions?.join(', ') ?? '',
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

function onGitToggle(enabled: boolean): void {
    gitForm.enabled = enabled;
    saveGit({ preserveScroll: true });
}

function onGithubToggle(enabled: boolean): void {
    githubForm.enabled = enabled;
    saveGithub({ preserveScroll: true });
}

function onClaudeCodeToggle(enabled: boolean): void {
    claudeCodeForm.enabled = enabled;
    saveClaudeCode({ preserveScroll: true });
}

function onFswatchToggle(enabled: boolean): void {
    fswatchForm.enabled = enabled;
    saveFswatch({ preserveScroll: true });
}

// ── Source test ────────────────────────────────────────────────────────────

type TestResult = { loading: boolean; available?: boolean; tested: boolean };

const testResults = reactive<Record<string, TestResult>>({
    git: { loading: false, tested: false },
    github: { loading: false, tested: false },
    'claude-code': { loading: false, tested: false },
    fswatch: { loading: false, tested: false },
});

function getCsrfToken(): string {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function testSource(sourceValue: string): Promise<void> {
    const result = testResults[sourceValue];
    result.loading = true;
    result.tested = false;

    try {
        const response = await fetch(sourcesRoutes.test(sourceValue).url, {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
        });
        const data = await response.json();
        result.available = data.available;
        result.tested = true;
    } finally {
        result.loading = false;
    }
}
</script>

<template>
    <AppLayout title="Settings">
        <template #header>
            <PageHeader title="Settings" />
        </template>

        <div class="max-w-2xl">
            <!-- Tab navigation -->
            <nav class="mb-6 flex gap-1 rounded-lg border bg-muted p-1">
                <Link
                    :href="settingsRoutes.general().url"
                    class="flex-1 rounded-md px-3 py-1.5 text-center text-sm font-medium transition-colors"
                    :class="tab === 'general' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                >
                    General
                </Link>
                <Link
                    :href="settingsRoutes.activity().url"
                    class="flex-1 rounded-md px-3 py-1.5 text-center text-sm font-medium transition-colors"
                    :class="tab === 'activity' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                >
                    Activity
                </Link>
                <Link
                    :href="settingsRoutes.sources().url"
                    class="flex-1 rounded-md px-3 py-1.5 text-center text-sm font-medium transition-colors"
                    :class="tab === 'sources' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                >
                    Sources
                </Link>
            </nav>

            <!-- GENERAL TAB -->
            <form v-if="tab === 'general'" class="flex flex-col gap-8" @submit.prevent="submitGeneral">
                <section class="flex flex-col gap-4">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Preferences</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Timezone" :error="generalForm.errors.timezone">
                            <select
                                v-model="generalForm.timezone"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="">— system default —</option>
                                <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                            </select>
                        </InputField>

                        <InputField label="Language" :error="generalForm.errors.locale">
                            <select
                                v-model="generalForm.locale"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="">— system default —</option>
                                <option v-for="locale in locales" :key="locale.value" :value="locale.value">
                                    {{ locale.label }}
                                </option>
                            </select>
                        </InputField>
                    </div>
                </section>

                <Separator />

                <section class="flex flex-col gap-4">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Company</h2>

                    <InputField label="Company name" :error="generalForm.errors.company_name">
                        <Input v-model="generalForm.company_name" type="text" />
                    </InputField>

                    <InputField label="Company address" :error="generalForm.errors.company_address">
                        <Textarea v-model="generalForm.company_address" rows="2" />
                    </InputField>
                </section>

                <Separator />

                <section class="flex flex-col gap-4">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Billing defaults</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Hourly rate (€)" :error="generalForm.errors.default_hourly_rate">
                            <Input v-model.number="generalForm.default_hourly_rate" type="number" min="0" step="0.01" placeholder="0.00" />
                        </InputField>

                        <InputField label="Daily rate (€)" :error="generalForm.errors.default_daily_rate">
                            <Input v-model.number="generalForm.default_daily_rate" type="number" min="0" step="0.01" placeholder="0.00" />
                        </InputField>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField label="Daily reference hours" :error="generalForm.errors.default_daily_reference_hours">
                            <Input v-model.number="generalForm.default_daily_reference_hours" type="number" min="1" max="24" />
                        </InputField>

                        <InputField label="Default rounding" :error="generalForm.errors.default_rounding_strategy">
                            <select
                                v-model.number="generalForm.default_rounding_strategy"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option v-for="option in ROUNDING_OPTIONS" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </InputField>
                    </div>
                </section>

                <div class="pt-2">
                    <Button type="submit" :disabled="generalForm.processing">
                        {{ generalForm.processing ? 'Saving…' : 'Save' }}
                    </Button>
                </div>
            </form>

            <!-- ACTIVITY TAB -->
            <form v-else-if="tab === 'activity'" class="flex flex-col gap-8" @submit.prevent="submitActivity">
                <section class="flex flex-col gap-4">
                    <h2 class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">Timing</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <InputField
                            label="Idle timeout (min)"
                            :error="activityForm.errors.idle_timeout_minutes"
                            hint="Minutes of inactivity before a session is considered idle"
                        >
                            <Input v-model.number="activityForm.idle_timeout_minutes" type="number" min="1" max="120" />
                        </InputField>

                        <InputField
                            label="Untracked threshold (min)"
                            :error="activityForm.errors.untracked_threshold_minutes"
                            hint="Minimum activity length before prompting to track"
                        >
                            <Input v-model.number="activityForm.untracked_threshold_minutes" type="number" min="1" max="120" />
                        </InputField>

                        <InputField
                            label="Scan interval (min)"
                            :error="activityForm.errors.scan_interval_minutes"
                            hint="How often activity sources are scanned"
                        >
                            <Input v-model.number="activityForm.scan_interval_minutes" type="number" min="1" max="30" />
                        </InputField>

                        <InputField
                            label="Block end padding (min)"
                            :error="activityForm.errors.block_end_padding_minutes"
                            hint="Minutes added after the last event when reconstructing sessions"
                        >
                            <Input v-model.number="activityForm.block_end_padding_minutes" type="number" min="0" max="60" />
                        </InputField>
                    </div>
                </section>

                <div class="pt-2">
                    <Button type="submit" :disabled="activityForm.processing">
                        {{ activityForm.processing ? 'Saving…' : 'Save' }}
                    </Button>
                </div>
            </form>

            <!-- SOURCES TAB -->
            <div v-else-if="tab === 'sources'" class="flex flex-col gap-4">
                <!-- Git -->
                <div class="rounded-lg border">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">Git</p>
                            <p class="text-xs text-muted-foreground">Detect commits and branch activity from local repositories</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge variant="outline">requires git</Badge>
                            <Button variant="ghost" size="sm" :disabled="testResults.git.loading" @click="testSource('git')">
                                {{ testResults.git.loading ? 'Testing…' : 'Test' }}
                            </Button>
                            <Badge v-if="testResults.git.tested" :variant="testResults.git.available ? 'default' : 'destructive'">
                                {{ testResults.git.available ? 'OK' : 'Unavailable' }}
                            </Badge>
                            <Switch :modelValue="gitForm.enabled" @update:checked="onGitToggle" />
                        </div>
                    </div>
                    <div v-if="gitForm.enabled" class="border-t px-4 py-4">
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
                    </div>
                </div>

                <!-- GitHub -->
                <div class="rounded-lg border">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">GitHub</p>
                            <p class="text-xs text-muted-foreground">Detect pull requests, reviews, and issue activity</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge variant="outline">GitHub API</Badge>
                            <Button variant="ghost" size="sm" :disabled="testResults.github.loading" @click="testSource('github')">
                                {{ testResults.github.loading ? 'Testing…' : 'Test' }}
                            </Button>
                            <Badge v-if="testResults.github.tested" :variant="testResults.github.available ? 'default' : 'destructive'">
                                {{ testResults.github.available ? 'OK' : 'Unavailable' }}
                            </Badge>
                            <Switch :checked="githubForm.enabled" @update:checked="onGithubToggle" />
                        </div>
                    </div>
                    <div v-if="githubForm.enabled" class="border-t px-4 py-4">
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
                    </div>
                </div>

                <!-- Claude Code -->
                <div class="rounded-lg border">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">Claude Code</p>
                            <p class="text-xs text-muted-foreground">Detect Claude Code sessions and conversation history</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge variant="outline">~/.claude</Badge>
                            <Button variant="ghost" size="sm" :disabled="testResults['claude-code'].loading" @click="testSource('claude-code')">
                                {{ testResults['claude-code'].loading ? 'Testing…' : 'Test' }}
                            </Button>
                            <Badge
                                v-if="testResults['claude-code'].tested"
                                :variant="testResults['claude-code'].available ? 'default' : 'destructive'"
                            >
                                {{ testResults['claude-code'].available ? 'OK' : 'Unavailable' }}
                            </Badge>
                            <Switch :checked="claudeCodeForm.enabled" @update:checked="onClaudeCodeToggle" />
                        </div>
                    </div>
                    <div v-if="claudeCodeForm.enabled" class="border-t px-4 py-4">
                        <form class="flex flex-col gap-4" @submit.prevent="saveClaudeCode()">
                            <InputField
                                label="Projects path"
                                :error="claudeCodeForm.errors.projects_path"
                                hint="Path to your Claude Code projects directory (default: ~/.claude/projects)"
                            >
                                <Input
                                    v-model="claudeCodeForm.projects_path"
                                    type="text"
                                    placeholder="~/.claude/projects"
                                    class="font-mono text-xs"
                                />
                            </InputField>
                            <div>
                                <Button type="submit" size="sm" :disabled="claudeCodeForm.processing">
                                    {{ claudeCodeForm.processing ? 'Saving…' : 'Save' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- File Watcher -->
                <div class="rounded-lg border">
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">File Watcher</p>
                            <p class="text-xs text-muted-foreground">Detect file changes in real-time — restart required on toggle</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge variant="outline">requires fswatch</Badge>
                            <Button variant="ghost" size="sm" :disabled="testResults.fswatch.loading" @click="testSource('fswatch')">
                                {{ testResults.fswatch.loading ? 'Testing…' : 'Test' }}
                            </Button>
                            <Badge v-if="testResults.fswatch.tested" :variant="testResults.fswatch.available ? 'default' : 'destructive'">
                                {{ testResults.fswatch.available ? 'OK' : 'Unavailable' }}
                            </Badge>
                            <Switch :checked="fswatchForm.enabled" @update:checked="onFswatchToggle" />
                        </div>
                    </div>
                    <div v-if="fswatchForm.enabled" class="border-t px-4 py-4">
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
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
