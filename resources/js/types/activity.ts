import type { ActivitySourceCategory } from './enums';

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

export type CategoryWithSources = {
    category: ActivitySourceCategory;
    sources: SourceDefinition[];
};

export type ActivityReportProgressPayload = {
    reportId: string;
    step: string;
    message?: string | null;
};
