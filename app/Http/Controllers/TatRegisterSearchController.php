<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchTatRegisterRequest;
use App\Models\TatRegister;
use App\Support\Pagination;
use Illuminate\View\View;

class TatRegisterSearchController extends Controller
{
    public function __invoke(SearchTatRegisterRequest $request): View
    {
        $criteria = $request->validated();
        $mode = $criteria['mode'] ?? null;
        $searched = $request->filled('mode') || $request->filled('nik') || $request->filled('nama') || $request->filled('tanggal_lahir');
        $records = null;
        $perPage = Pagination::resolve($request);

        if ($searched && $mode) {
            $query = TatRegister::query()
                ->with('bnnk')
                ->when($request->user()->isBnnk(), fn ($query) => $query->where('bnnk_id', $request->user()->bnnk_id));

            if ($mode === 'nik') {
                $query->where('nik', $criteria['nik']);
            }

            if ($mode === 'name_dob') {
                $query
                    ->whereRaw('LOWER(nama) = ?', [mb_strtolower($criteria['nama'], 'UTF-8')])
                    ->whereDate('tanggal_lahir', $criteria['tanggal_lahir']);
            }

            $records = $query
                ->orderByDesc('tanggal_pelaksanaan')
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('tat-registers.search', compact('records', 'mode', 'searched', 'perPage'));
    }
}
