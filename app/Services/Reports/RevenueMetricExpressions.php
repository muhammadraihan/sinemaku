<?php

namespace App\Services\Reports;

class RevenueMetricExpressions
{
    private string $audience;
    private string $gross;
    private string $net;

    public function __construct(string $audience, string $gross, string $net)
    {
        $this->audience = $audience;
        $this->gross = $gross;
        $this->net = $net;
    }

    public function atpGross(): string
    {
        return "CASE WHEN {$this->audience} > 0 THEN {$this->gross} / {$this->audience} ELSE 0 END";
    }

    public function atpNet(): string
    {
        return "CASE WHEN {$this->audience} > 0 THEN {$this->net} / {$this->audience} ELSE 0 END";
    }
}
