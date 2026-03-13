export type OnboardingStep = {
    key: string;
    label: string;
    description: string;
    complete: boolean;
    url: string;
    optional: boolean;
};

export type OnboardingState = {
    dismissed: boolean;
    all_complete?: boolean;
    steps?: OnboardingStep[];
};
