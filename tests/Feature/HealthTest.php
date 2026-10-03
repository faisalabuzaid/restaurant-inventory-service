<?php

it('serves the health endpoint', function () {
    $this->get('/up')->assertOk();
});

it('renders unknown API routes as JSON', function () {
    $this->getJson('/api/does-not-exist')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});
