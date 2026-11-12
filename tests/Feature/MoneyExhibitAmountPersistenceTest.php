<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoneyExhibitAmountPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'unit' => 'Evidence Management',
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function single_suspect_case_with_money_exhibit_persists_amount_in_exhibits_table(): void
    {
        $payload = [
            'case_number' => 'NCC-2026-TEST01',
            'title' => 'Republic vs Test Single Money',
            'unit_name' => 'Evidence Management',
            'suspect_docket_mode' => 'SINGLE',
            'suspect_name' => 'Kofi Money Suspect',
            'has_seizures' => true,
            'exhibit_category' => 'Money',
            'seizure_name' => 'Ghana Cedi (GHS ₵)',
            'commodity_name' => 'Ghana Cedi (GHS ₵)',
            'seizure_name_currency' => 'Ghana Cedi (GHS ₵)',
            'seizure_quantity' => '75,000.00',
            'amount' => '75,000.00',
            'seizures' => [
                [
                    'item_number' => 1,
                    'exhibit_category' => 'Money',
                    'commodity_name' => 'Ghana Cedi (GHS ₵)',
                    'seizure_name' => 'Ghana Cedi (GHS ₵)',
                    'seizure_name_currency' => 'Ghana Cedi (GHS ₵)',
                    'seizure_quantity' => '75,000.00',
                    'amount' => '75,000.00',
                    'associated_person' => 'Kofi Money Suspect',
                    'seizure_location' => 'Tema Port',
                    'seizure_officer' => 'Officer Mensah',
                ],
            ],
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_name' => 'Kofi Money Suspect',
                        'gender' => 'Male',
                    ],
                ],
                'seizures' => [
                    [
                        'item_number' => 1,
                        'exhibit_category' => 'Money',
                        'commodity_name' => 'Ghana Cedi (GHS ₵)',
                        'seizure_name' => 'Ghana Cedi (GHS ₵)',
                        'seizure_quantity' => '75,000.00',
                        'amount' => '75,000.00',
                        'associated_person' => 'Kofi Money Suspect',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/cases', $payload);
        $response->assertStatus(200);

        $exhibit = Exhibit::where('commodity_name', 'Ghana Cedi (GHS ₵)')->first();
        $this->assertNotNull($exhibit, 'Exhibit should be created in exhibits table');
        $this->assertEquals('Money', $exhibit->exhibit_category);
        $this->assertEquals('75,000.00', $exhibit->amount, 'Amount must be saved in the amount column');
    }

    #[Test]
    public function single_suspect_case_money_exhibit_falls_back_to_quantity_or_value_when_amount_key_not_explicit(): void
    {
        $payload = [
            'case_number' => 'NCC-2026-TEST02',
            'title' => 'Republic vs Test Fallback Money',
            'unit_name' => 'Evidence Management',
            'suspect_docket_mode' => 'SINGLE',
            'suspect_name' => 'Kwame Currency',
            'has_seizures' => true,
            'exhibit_category' => 'Money',
            'seizure_name' => 'US Dollars (USD $)',
            'commodity_name' => 'US Dollars (USD $)',
            'seizure_quantity' => '12000',
            'seizures' => [
                [
                    'item_number' => 1,
                    'exhibit_category' => 'Money',
                    'commodity_name' => 'US Dollars (USD $)',
                    'seizure_quantity' => '12000',
                    'associated_person' => 'Kwame Currency',
                ],
            ],
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_name' => 'Kwame Currency',
                        'gender' => 'Male',
                    ],
                ],
                'seizures' => [
                    [
                        'item_number' => 1,
                        'exhibit_category' => 'Money',
                        'commodity_name' => 'US Dollars (USD $)',
                        'seizure_quantity' => '12000',
                        'associated_person' => 'Kwame Currency',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/cases', $payload);
        $response->assertStatus(200);

        $exhibit = Exhibit::where('commodity_name', 'US Dollars (USD $)')->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('12000', $exhibit->amount, 'Amount column should populate from seizure_quantity for Money category');
    }

    #[Test]
    public function unassigned_money_exhibit_persists_amount_in_exhibits_table(): void
    {
        $payload = [
            'seizures' => [
                [
                    'exhibit_category' => 'Money',
                    'commodity_name' => 'Euros (EUR €)',
                    'seizure_quantity' => '5,400',
                    'amount' => '5,400',
                    'seizure_location' => 'Kotoka Int Airport',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/Evidence_Management/exhibits/store-unassigned', $payload);
        $response->assertStatus(200);

        $exhibit = Exhibit::where('commodity_name', 'Euros (EUR €)')->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('Money', $exhibit->exhibit_category);
        $this->assertEquals('5,400', $exhibit->amount, 'Amount column must be populated for unassigned money exhibit');
    }

    #[Test]
    public function multiple_suspect_case_with_money_exhibits_persists_amount(): void
    {
        $payload = [
            'case_number' => 'NCC-2026-TEST03',
            'title' => 'Republic vs Multi Money Case',
            'unit_name' => 'Evidence Management',
            'suspect_docket_mode' => 'MULTIPLE',
            'suspect_name' => 'Suspect One',
            'has_seizures' => true,
            'exhibit_category' => 'Money',
            'seizure_name' => 'British Pounds (GBP £)',
            'commodity_name' => 'British Pounds (GBP £)',
            'amount' => '150,000',
            'seizure_quantity' => '150,000',
            'seizures' => [
                [
                    'item_number' => 1,
                    'exhibit_category' => 'Money',
                    'commodity_name' => 'British Pounds (GBP £)',
                    'amount' => '150,000',
                    'seizure_quantity' => '150,000',
                    'associated_person' => 'Suspect One',
                ],
            ],
            'docket_data' => [
                'suspects' => [
                    [
                        'suspect_name' => 'Suspect One',
                        'gender' => 'Male',
                    ],
                    [
                        'suspect_name' => 'Suspect Two',
                        'gender' => 'Female',
                    ],
                ],
                'seizures' => [
                    [
                        'item_number' => 1,
                        'exhibit_category' => 'Money',
                        'commodity_name' => 'British Pounds (GBP £)',
                        'amount' => '150,000',
                        'seizure_quantity' => '150,000',
                        'associated_person' => 'Suspect One',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/cases', $payload);
        $response->assertStatus(200);

        $exhibit = Exhibit::where('commodity_name', 'British Pounds (GBP £)')->first();
        $this->assertNotNull($exhibit);
        $this->assertEquals('150,000', $exhibit->amount);
    }
}
