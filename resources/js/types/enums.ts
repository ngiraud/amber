export interface EnumValue {
    value: string;
    label: string;
}

export type RoundingStrategyOption = {
    value: number;
    label: string;
};

export type SessionSource = {
    value: number;
    label: string;
};

export interface ActivityEventSourceType extends EnumValue {
    color: string;
}

export interface ActivityReportStatus {
    value: number;
    label: string;
    variant: 'default' | 'secondary' | 'outline' | 'destructive';
    shouldDisplayBadge: boolean;
}

export interface ActivityReportStep {
    value: string;
    label: string;
    shouldDisplayStep: boolean;
}

export type ActivitySourceCategory = {
    value: string;
    label: string;
    description: string;
    display_layout: 'grid-2' | 'full-width';
};

export type AiProviderOption = {
    value: string;
    label: string;
    model: string;
    requiresApiKey: boolean;
};

export type LocaleOption = { value: string; label: string };
