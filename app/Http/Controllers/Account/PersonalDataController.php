<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\ExportPersonalData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PersonalDataController extends Controller
{
    public function __invoke(Request $request, ExportPersonalData $export): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return response()->json($export($user), 200, [
            'Content-Disposition' => 'attachment; filename="mes-donnees-'.now()->format('Y-m-d').'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
