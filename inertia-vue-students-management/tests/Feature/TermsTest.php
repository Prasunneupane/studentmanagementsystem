<?php

use App\Models\Terms;

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('it can create a term', function () {
    $response = $this->post('/terms', [
        'name' => 'Term 1',
        'academic_year_id' => 1,
        'term_number' => 1,
        'start_date' => '2024-01-01',
        'end_date' => '2024-06-30',
    ]);

    // ek nindra sutera

    $response->assertStatus(201);
    $this->assertDatabaseHas('tbl_terms', [
        'name' => 'Term 1',
        'academic_year_id' => 1,
        'term_number' => 1,
    ]);
});

test('it can update a term', function () {
    $term = Terms::factory()->create();

    $response = $this->put("/terms/{$term->id}", [
        'name' => 'Updated Term',
        'academic_year_id' => 1,
        'term_number' => 2,
        'start_date' => '2024-07-01',
        'end_date' => '2024-12-31',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('tbl_terms', [
        'id' => $term->id,
        'name' => 'Updated Term',
        'academic_year_id' => 1,
        'term_number' => 2,
    ]);
});