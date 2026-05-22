<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimekeepingCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_timekeeping_correction_can_be_submitted()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('timekeeping.correction.store'), [
                'timekeeping_id' => 1,
                'shiftCode' => 1,
                'reason' => 2,
                'timeIn' => now()->setTime(8, 0),
                'timeOut' => now()->setTime(17, 0),
            ]);

        $response->assertRedirect(route('timekeeping'));
    }
}

