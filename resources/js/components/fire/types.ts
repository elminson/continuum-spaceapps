export type Month = {
    month: string;
    modis: number | null;
    viirs: number | null;
    value: number | null;
    low: number | null;
    high: number | null;
    estimated: boolean;
};

export type AlertRow = {
    week_start: string;
    observed: number;
    typical: number;
    times: number | null;
    score: number;
    severity: string;
    area?: string;
    slug?: string;
};

export type Fit = {
    ratio: number;
    low: number;
    high: number;
    days: number;
    validation: {
        train_days: number;
        test_days: number;
        mae: number;
        naive_mae: number;
        improvement: number;
        correlation: number;
        overlap_from: string;
        overlap_to: string;
    } | null;
    months: { month: number; ratio: number; low: number; high: number; days: number }[];
};
