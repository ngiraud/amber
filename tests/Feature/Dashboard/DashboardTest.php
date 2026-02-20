<?php

declare(strict_types=1);

test('home redirects to clients index', function () {
    $this->get(route('home'))
        ->assertRedirectToRoute('clients.index');
});
