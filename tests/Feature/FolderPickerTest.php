<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

pest()->group('controllers', 'folder-picker');

it('returns the selected folder path', function () {
    Http::fake([
        '*/dialog/open' => Http::response(['result' => ['/Users/me/code/my-repo']]),
    ]);

    $this->get(route('folder-picker'))
        ->assertSuccessful()
        ->assertJson(['path' => '/Users/me/code/my-repo']);
});

it('returns null when the user cancels the dialog', function () {
    Http::fake([
        '*/dialog/open' => Http::response(['result' => []]),
    ]);

    $this->get(route('folder-picker'))
        ->assertSuccessful()
        ->assertJson(['path' => null]);
});
