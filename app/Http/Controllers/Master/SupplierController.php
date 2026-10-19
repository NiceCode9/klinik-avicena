<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanSupplierRequest;
use App\Models\Master\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.supplier,lihat', only: ['index']),
            new Middleware('izin:master.supplier,tambah', only: ['create', 'store']),
            new Middleware('izin:master.supplier,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.supplier,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.supplier.index', [
            'data' => Supplier::orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.supplier.create');
    }

    public function store(SimpanSupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        return redirect()->route('supplier.index')->with('sukses', 'Data supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('master.supplier.edit', ['supplier' => $supplier]);
    }

    public function update(SimpanSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()->route('supplier.index')->with('sukses', 'Data supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('supplier.index')->with('sukses', 'Data supplier berhasil dihapus.');
    }
}
