<?php

namespace App\Services;

use App\Models\PayrollProcessStatus;
use Illuminate\Support\Facades\DB;

class PayrollStepsService
{
    /**
     * Mark step as processing
     */
    public static function start($cutoffId, $step)
    {
        self::updateStatus($cutoffId, $step, 'processing');
    }

    /**
     * Mark step as completed
     */
    public static function complete($cutoffId, $step)
    {
        self::updateStatus($cutoffId, $step, 'completed');
    }

    /**
     * Mark step as failed
     */
    public static function fail($cutoffId, $step, $message = null)
    {
        self::updateStatus($cutoffId, $step, 'failed');
    }

    /**
     * Mark step as skipped (treated as completed but optional override logic)
     */
    public static function skip($cutoffId, $step)
    {
        self::updateStatus($cutoffId, $step, 'skipped');
    }

    /**
     * Centralized status updater (IMPORTANT)
     */
    private static function updateStatus($cutoffId, $step, $status)
    {
        PayrollProcessStatus::updateOrCreate(
            [
                'cutoff_id' => $cutoffId,
                'step' => $step,
            ],
            [
                'status' => $status,
                'processed_at' => now(),
            ]
        );
    }

    /**
     * Get all step statuses for UI
     */
    public static function getStatuses($cutoffId)
    {
        return PayrollProcessStatus::where('cutoff_id', $cutoffId)
            ->pluck('status', 'step');
    }

    /**
     * OPTIONAL: Check if step is completed
     */
    public static function isCompleted($cutoffId, $step)
    {
        return PayrollProcessStatus::where('cutoff_id', $cutoffId)
            ->where('step', $step)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * OPTIONAL: Reset a step (useful for reprocessing SSS / Pag-IBIG)
     */
    public static function reset($cutoffId, $step)
    {
        PayrollProcessStatus::updateOrCreate(
            [
                'cutoff_id' => $cutoffId,
                'step' => $step,
            ],
            [
                'status' => 'pending',
                'processed_at' => null,
            ]
        );
    }
}
