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
    href?: string;
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

export type Place = { slug: string; name: string; bbox: number[]; id?: number; top_week?: string | null };

export type Status = {
    detections: number;
    cell_days: number;
    record_days: number;
    first_day: string;
    last_day: string;
    instruments: { instrument: string; detections: number; cell_days: number; pixels_per_cell: number | null; first: string; last: string }[];
    satellites: { instrument: string; satellite: string; detections: number; last: string }[];
};
