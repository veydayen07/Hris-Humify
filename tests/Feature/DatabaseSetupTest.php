<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('migrations and database seed work', function () {
    $this->seed();
    $this->assertDatabaseHas('departments', ['name' => 'Product Design']);
});

