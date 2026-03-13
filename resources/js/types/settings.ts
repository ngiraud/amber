import type { Appearance } from './ui';

export type GeneralSettings = {
    company_name: string | null;
    company_address: string | null;
    default_hourly_rate: number | null;
    default_daily_rate: number | null;
    default_daily_reference_hours: number;
    default_rounding_strategy: number;
    timezone: string | null;
    locale: string | null;
    theme: Appearance;
    open_at_login: boolean;
};

export type ActivitySettings = {
    idle_timeout_minutes: number;
    scan_interval_minutes: number;
    block_end_padding_minutes: number;
    manual_session_reminder_minutes: number;
};

export type AiSettings = {
    enabled: boolean;
    provider: string | null;
    api_key: string | null;
    summary_language: string;
};
