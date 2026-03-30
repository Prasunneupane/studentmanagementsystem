<?php

use App\Models\Classes;

it('gets all active classes from the database', function () {
   // Fake return data
    $fakeData = collect([
        (object)['id' => 1, 'name' => 'Class 1'],
        (object)['id' => 2, 'name' => 'Class 2'],
    ]);

    // Mock the query builder chain
    $mock = Mockery::mock('alias:' . Classes::class);

    $mock->shouldReceive('select')->with(['id','name'])->andReturnSelf();
    $mock->shouldReceive('where')->with('is_active', true)->andReturnSelf();
    $mock->shouldReceive('orderBy')->with('id')->andReturnSelf();
    $mock->shouldReceive('get')->andReturn($fakeData);

    // Act
    $result = Classes::select(['id','name'])
        ->where('is_active', true)
        ->orderBy('id')
        ->get();

    // Assert
    expect($result)->toBe($fakeData);
});
