<?php

namespace App\Events;

use App\Models\DotDanhGia;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EvaluationClosingSoonEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public const THREE_DAYS = '3-days';
    public const TWENTY_FOUR_HOURS = '24-hours';

    public function __construct(public readonly DotDanhGia $period, public readonly string $milestone) {}
}
