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

export type AppSettings = {
    git_author_emails?: string[];
    company_name?: string | null;
    company_address?: string | null;
    default_hourly_rate?: number | null;
    default_daily_rate?: number | null;
    default_daily_reference_hours?: number | null;
    default_rounding_strategy?: number | null;
};
