<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Http\Requests\ProcessImportRequest;
use App\Http\Requests\UploadImportFileRequest;
use App\Models\Wallet;
use App\Services\ImportService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService,
        private readonly PlanService $planService,
    ) {}

    public function index(Request $request): Response
    {
        abort_if(! $this->planService->canExportImport($request->user()), HttpStatus::Forbidden->value);

        $user = $request->user();

        return Inertia::render('Import/Index', [
            'wallets' => $user->wallets()->orderBy('name')->get(['id', 'name']),
            'categories' => $user->categories()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function template(Request $request): HttpResponse
    {
        abort_if(! $this->planService->canExportImport($request->user()), HttpStatus::Forbidden->value);

        $spreadsheet = $this->importService->generateTemplate();

        ob_start();
        (new Xlsx($spreadsheet))->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="spendly-import-template.xlsx"',
        ]);
    }

    public function preview(UploadImportFileRequest $request): JsonResponse
    {
        abort_if(! $this->planService->canExportImport($request->user()), HttpStatus::Forbidden->value);

        $path = $request->file('file')->storeAs(
            'csv-imports',
            uniqid('import_', true).'.xlsx',
            'local'
        );

        try {
            return response()->json($this->importService->preview($path));
        } catch (RuntimeException $runtimeException) {
            // Delete the uploaded file and return the validation error
            Storage::delete($path);

            return response()->json(['message' => $runtimeException->getMessage()], HttpStatus::UnprocessableEntity->value);
        }
    }

    public function process(ProcessImportRequest $request): RedirectResponse
    {
        abort_if(! $this->planService->canExportImport($request->user()), HttpStatus::Forbidden->value);

        $data = $request->validated();
        $user = $request->user();
        /** @var Wallet $wallet */
        $wallet = $user->wallets()->findOrFail($data['wallet_id']);

        ['created' => $created, 'errors' => $errors, 'month' => $month] = $this->importService->process($data, $user, $wallet);

        $message = $created.' transaction(s) importée(s)'.($errors > 0 ? sprintf(', %d ignorée(s)', $errors) : '').'.';

        return redirect(sprintf('/wallets/%s/budget?month=%s', $wallet->id, $month))
            ->with('success', $message);
    }
}
