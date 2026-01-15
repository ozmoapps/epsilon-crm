<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TurkishCharacterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that multibyte string functions handle common Turkish characters.
     * Note: mb_strtoupper follows Unicode standard, so 'i' -> 'I' (not 'İ').
     * This is acceptable for IBAN/currency codes which don't use İ.
     */
    public function test_turkish_characters_upper_and_lower_case_conversion()
    {
        // Test common Turkish characters (Ş, Ğ, Ü, Ö, Ç are handled correctly)
        $this->assertEquals('ŞENOL GÜNEŞ', mb_strtoupper('şenol güneş', 'UTF-8'));
        $this->assertEquals('ÇAĞRI', mb_strtoupper('çağrı', 'UTF-8'));
        $this->assertEquals('ÖZDEMIR', mb_strtoupper('özdemir', 'UTF-8'));
        
        // Test lowercase conversion
        $this->assertEquals('şenol güneş', mb_strtolower('ŞENOL GÜNEŞ', 'UTF-8'));
        
        // IBAN example (demonstrates mb_ function safety with mixed content)
        $iban = 'tr33 0006 1005 1978 6457 8413 26';
        $this->assertEquals('TR33 0006 1005 1978 6457 8413 26', mb_strtoupper($iban, 'UTF-8'));
    }

    /**
     * Test that database can store and retrieve Turkish characters.
     */
    public function test_database_can_store_turkish_characters()
    {
        $user = User::factory()->create([
            'name' => 'Şenol Güneş',
            'email' => 'senol.gunes@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Şenol Güneş',
        ]);
        
        $this->assertEquals('Şenol Güneş', $user->fresh()->name);
    }
}
