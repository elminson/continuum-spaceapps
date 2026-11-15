<?php

namespace App\Fire;

/**
 * Ridge regression in plain PHP: standardized features, an unpenalized intercept,
 * and a closed-form solve of (XᵀX + λI) w = Xᵀy by Gauss-Jordan elimination.
 */
class Ridge
{
    /** @var list<float> */
    public array $weights = [];

    public float $intercept = 0.0;

    /** @var list<float> */
    private array $means = [];

    /** @var list<float> */
    private array $stds = [];

    /**
     * @param  list<list<float>>  $x  rows of features
     * @param  list<float>  $y
     */
    public static function fit(array $x, array $y, float $lambda = 1.0): self
    {
        $model = new self;
        $n = count($x);
        $p = count($x[0]);

        // Standardize columns so λ treats every feature alike.
        for ($j = 0; $j < $p; $j++) {
            $col = array_column($x, $j);
            $mean = array_sum($col) / $n;
            $var = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $col)) / max($n - 1, 1);
            $model->means[$j] = $mean;
            $model->stds[$j] = sqrt($var) ?: 1.0;
        }

        $z = array_map(fn ($row) => $model->standardize($row), $x);
        $yMean = array_sum($y) / $n;

        // Normal equations on centred data; the intercept is the mean of y.
        $a = array_fill(0, $p, array_fill(0, $p, 0.0));
        $b = array_fill(0, $p, 0.0);

        foreach ($z as $i => $row) {
            $yi = $y[$i] - $yMean;
            for ($j = 0; $j < $p; $j++) {
                $b[$j] += $row[$j] * $yi;
                for ($k = $j; $k < $p; $k++) {
                    $a[$j][$k] += $row[$j] * $row[$k];
                }
            }
        }

        for ($j = 0; $j < $p; $j++) {
            $a[$j][$j] += $lambda;
            for ($k = 0; $k < $j; $k++) {
                $a[$j][$k] = $a[$k][$j];
            }
        }

        $model->weights = self::solve($a, $b);
        $model->intercept = $yMean;

        return $model;
    }

    /** @param list<float> $row */
    public function predict(array $row): float
    {
        $z = $this->standardize($row);
        $sum = $this->intercept;

        foreach ($this->weights as $j => $w) {
            $sum += $w * $z[$j];
        }

        return $sum;
    }

    /**
     * Weights on the standardized scale: comparable across features.
     *
     * @return list<float>
     */
    public function standardizedWeights(): array
    {
        return $this->weights;
    }

    /**
     * @param  list<float>  $row
     * @return list<float>
     */
    private function standardize(array $row): array
    {
        $out = [];
        foreach ($row as $j => $v) {
            $out[$j] = ($v - $this->means[$j]) / $this->stds[$j];
        }

        return $out;
    }

    /**
     * Gauss-Jordan with partial pivoting.
     *
     * @param  list<list<float>>  $a
     * @param  list<float>  $b
     * @return list<float>
     */
    private static function solve(array $a, array $b): array
    {
        $n = count($b);

        for ($col = 0; $col < $n; $col++) {
            $pivot = $col;
            for ($r = $col + 1; $r < $n; $r++) {
                if (abs($a[$r][$col]) > abs($a[$pivot][$col])) {
                    $pivot = $r;
                }
            }
            [$a[$col], $a[$pivot]] = [$a[$pivot], $a[$col]];
            [$b[$col], $b[$pivot]] = [$b[$pivot], $b[$col]];

            $div = $a[$col][$col] ?: 1e-12;
            for ($k = 0; $k < $n; $k++) {
                $a[$col][$k] /= $div;
            }
            $b[$col] /= $div;

            for ($r = 0; $r < $n; $r++) {
                if ($r === $col || $a[$r][$col] == 0.0) {
                    continue;
                }
                $factor = $a[$r][$col];
                for ($k = 0; $k < $n; $k++) {
                    $a[$r][$k] -= $factor * $a[$col][$k];
                }
                $b[$r] -= $factor * $b[$col];
            }
        }

        return $b;
    }
}
