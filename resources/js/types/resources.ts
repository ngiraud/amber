export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

export type RoundingStrategyOption = {
    value: number;
    label: string;
};

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

export type SessionSource = {
    value: number;
    label: string;
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

export interface EnumValue {
    value: string;
    label: string;
}

export interface ActivityEventSourceType extends EnumValue {
    color: string;
}

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

export type GeneralSettings = {
    company_name: string | null;
    company_address: string | null;
    default_hourly_rate: number | null;
    default_daily_rate: number | null;
    default_daily_reference_hours: number;
    default_rounding_strategy: string;
    timezone: string | null;
    locale: string | null;
};

export type ActivitySettings = {
    idle_timeout_minutes: number;
    untracked_threshold_minutes: number;
    scan_interval_minutes: number;
    block_end_padding_minutes: number;
};

export type SourceFieldDefinition = {
    name: string;
    type: 'text' | 'number' | 'textarea' | 'email-list' | 'string-list';
    label: string;
    hint: string;
    placeholder?: string;
    min?: number;
    max?: number;
    rows?: number;
    separator?: string;
};

export type SourceDefinition = {
    value: string;
    label: string;
    color: string;
    description: string;
    requirements: string;
    fields: SourceFieldDefinition[];
    config: {
        enabled: boolean;
        [key: string]: unknown;
    };
};

export type LocaleOption = { value: string; label: string };

export type ActivityReportStatus = {
    value: number;
    label: string;
};

export type ActivityReportStep = 'collecting_context' | 'building_lines' | 'generating_files' | 'completed' | 'failed';

export type ActivityReportLine = {
    id: string;
    activity_report_id: string;
    project_id: string;
    date: string;
    minutes: number;
    days: number;
    description: string | null;
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

export type ActivityReportProgressPayload = {
    reportId: string;
    step: ActivityReportStep;
    message?: string | null;
};
