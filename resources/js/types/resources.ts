import type { ActivityEventSourceType, ActivityReportStatus, EnumValue, RoundingStrategyOption, SessionSource } from './enums';

export type ProjectRepository = {
    id: string;
    project_id: string;
    local_path: string;
    name: string;
    created_at: string;
    updated_at: string;
};

export type Project = {
    id: string;
    client_id: string;
    name: string;
    color: string;
    is_active: boolean;
    daily_reference_hours: number;
    rounding: RoundingStrategyOption;
    hourly_rate: number | null;
    hourly_rate_formatted: string | null;
    daily_rate: number | null;
    daily_rate_formatted: string | null;
    created_at: string;
    updated_at: string;
    client?: Client;
    repositories?: ProjectRepository[];
};

export type Client = {
    id: string;
    name: string;
    address: {
        line_1?: string;
        line_2?: string;
        city?: string;
        zip?: string;
    } | null;
    contacts: {
        phone?: string;
        email?: string;
    } | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    projects?: Project[];
    projects_count?: number;
};

export type Session = {
    id: string;
    project_id: string;
    date: string | null;
    started_at: string;
    ended_at: string | null;
    duration_minutes: number | null;
    rounded_minutes: number | null;
    source: SessionSource;
    notes: string | null;
    description: string | null;
    is_validated: boolean;
    created_at: string;
    updated_at: string;
    project?: Project;
};

export type ActivityEvent = {
    id: string;
    project_id: string;
    project_repository_id: string | null;
    session_id: string | null;
    source_type: ActivityEventSourceType;
    type: EnumValue;
    occurred_at: string;
    occurred_at_timestamp: number;
    metadata: Record<string, unknown>;
    project_name?: string;
    repository_name?: string;
    detail: string;
    created_at: string;
    updated_at: string;
};

export type ActivityReportLine = {
    id: string;
    activity_report_id: string;
    project_id: string;
    date: string;
    minutes: number;
    days: number;
    description: string | null;
    summary: string | null;
    display_description: string | null;
    created_at: string;
    updated_at: string;
    project?: Project;
};

export type ActivityReport = {
    id: string;
    client_id: string;
    month: number;
    year: number;
    status: ActivityReportStatus;
    total_minutes: number;
    total_days: number;
    total_amount_ht: number | null;
    generated_at: string | null;
    pdf_path: string | null;
    csv_path: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    client?: Client;
    lines?: ActivityReportLine[];
    lines_count?: number;
};
