<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Native\Desktop\Dialog;

class FolderPickerController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $path = Dialog::new()
            ->folders()
            ->button('Select Folder')
            ->open();

        return response()->json(['path' => $path]);
    }
}
