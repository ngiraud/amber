import type { ActivitySourceCategory } from './enums';
import type { Project } from './resources';

export type CurrentActivity = {
    project: Project;
    since: string;
};

export type TimelineDay = {
    date: string;
    total_minutes: number;
    projects: {
        id: string;
        name: string | undefined;
        color: string | undefined;
        minutes: number;
    }[];
};

export type ProjectBreakdown = {
    id: string | null;
    name: string | null;
    color: string | null;
    minutes: number;
    percentage: number;
};

export type TimelineMonthStats = {
    month_total_minutes: number;
    month_worked_days: number;
    month_avg_minutes_per_day: number;
    month_avg_minutes_per_week: number;
    current_week_total_minutes: number | null;
    month_project_breakdown: ProjectBreakdown[];
};

export type ProjectStats = {
    worked_days: number;
    total_minutes: number;
    avg_minutes_per_day: number;
    first_date: string | null;
    last_date: string | null;
};

export type ClientProjectBreakdown = {
    id: string;
    name: string;
    color: string;
    minutes: number;
    days: number;
    percentage: number;
};

export type ClientStats = {
    projects_count: number;
    active_projects_count: number;
    worked_days: number;
    total_minutes: number;
    avg_minutes_per_day: number;
    first_date: string | null;
    last_date: string | null;
    project_breakdown: ClientProjectBreakdown[];
};

export type SessionStats = {
    total_minutes: number;
    session_count: number;
    avg_session_minutes: number;
    first_started_at: string | null;
    last_ended_at: string | null;
};

export type WeekStats = {
    label: string;
    start_date: string;
    end_date: string;
    total_minutes: number;
    worked_days: number;
    avg_minutes_per_day: number;
    project_breakdown: ProjectBreakdown[];
};

export type SourceFieldDefinition = {
    name: string;
    type: 'text' | 'number' | 'textarea' | 'email-list' | 'string-list' | 'folder-path';
    label: string;
    hint: string;
    placeholder?: string;
    min?: number;
    max?: number;
    rows?: number;
    separator?: string;
};

export type SourceInstallationInstruction = {
    label?: string;
    command: string;
};

export type SourceDefinition = {
    value: string;
    label: string;
    color: string;
    description: string;
    requirements: string;
    installation_instructions: SourceInstallationInstruction[];
    fields: SourceFieldDefinition[];
    config: {
        enabled: boolean;
        [key: string]: unknown;
    };
};

export type CategoryWithSources = {
    category: ActivitySourceCategory;
    sources: SourceDefinition[];
};

export type ActivityReportProgressPayload = {
    reportId: string;
    step: string;
    message?: string | null;
};
