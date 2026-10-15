<?php

namespace App\Http\Controllers;

use App\Enums\AuditLogAction;
use App\Models\{AuditLog, Bnnk, User};
use App\Services\AuditLogService;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(Request $request): View
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'user_id' => $request->input('user_id'),
            'action' => $request->input('action'),
            'bnnk_id' => $request->input('bnnk_id'),
            'entity_type' => $request->input('entity_type'),
        ];

        $perPage = Pagination::resolve($request);

        $logs = AuditLog::query()
            ->with(['user', 'bnnk'])
            ->when(filled($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->when(is_numeric($filters['user_id']), fn ($query) => $query->where('user_id', (int) $filters['user_id']))
            ->when(array_key_exists($filters['action'], AuditLogAction::options()), fn ($query) => $query->where('action', $filters['action']))
            ->when(is_numeric($filters['bnnk_id']), fn ($query) => $query->where('bnnk_id', (int) $filters['bnnk_id']))
            ->when(in_array($filters['entity_type'], ['TatRegister', 'User'], true), fn ($query) => $query->where('entity_type', $filters['entity_type']))
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,
            'perPage' => $perPage,
            'filters' => $filters,
            'actions' => AuditLogAction::options(),
            'users' => User::query()->with('bnnk')->orderBy('name')->get(),
            'bnnks' => Bnnk::query()->orderBy('name')->get(),
            'entityTypes' => [
                'TatRegister' => 'Register TAT',
                'User' => 'User',
            ],
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load(['user', 'bnnk']);

        return view('audit-logs.show', [
            'auditLog' => $auditLog,
            'changes' => $this->auditLogService->presentChanges($auditLog),
            'metadata' => $this->auditLogService->presentMetadata($auditLog),
        ]);
    }
}
