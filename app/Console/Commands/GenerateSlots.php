<?php

namespace App\Console\Commands;

use App\Models\Hospital;
use App\Services\SlotGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Materialises slots for booking (PRD §8). Idempotent; safe on a daily schedule.
 */
#[Signature('slots:generate {--hospital= : Hospital ID (default: all active)} {--days= : Days ahead (default: booking window)}')]
#[Description('Generate bookable appointment slots from active schedules')]
class GenerateSlots extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SlotGenerator $generator): int
    {
        $query = Hospital::where('is_active', true);

        if ($this->option('hospital') !== null) {
            $query->where('id', (int) $this->option('hospital'));
        }

        $days = $this->option('days') !== null ? (int) $this->option('days') : null;
        $total = 0;

        foreach ($query->get() as $hospital) {
            $created = $generator->generateForHospital($hospital, $days);
            $this->info("{$hospital->name}: {$created} slots created.");
            $total += $created;
        }

        $this->info("Done. {$total} slots created.");

        return self::SUCCESS;
    }
}
