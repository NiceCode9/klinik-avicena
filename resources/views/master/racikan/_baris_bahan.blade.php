<tr class="baris-bahan">
    <td>
        <input type="hidden" name="bahan[{{ $index }}][id]" value="{{ $baris['id'] ?? '' }}">
        <select name="bahan[{{ $index }}][obat_id]" class="form-select form-select-sm pilih-obat">
            <option value="">- Pilih obat -</option>
            @foreach ($daftarObat as $obat)
                <option value="{{ $obat->id }}" data-satuan="{{ $obat->satuan_id }}"
                    @selected((int) ($baris['obat_id'] ?? 0) === $obat->id)>{{ $obat->nama }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" step="0.001" min="0.001" name="bahan[{{ $index }}][jumlah]"
            class="form-control form-control-sm" value="{{ $baris['jumlah'] ?? '' }}">
    </td>
    <td>
        <select name="bahan[{{ $index }}][satuan_id]" class="form-select form-select-sm pilih-satuan">
            <option value="">- Pilih satuan -</option>
            @foreach ($daftarSatuan as $satuan)
                <option value="{{ $satuan->id }}"
                    @selected((int) ($baris['satuan_id'] ?? 0) === $satuan->id)>{{ $satuan->nama }}</option>
            @endforeach
        </select>
    </td>
    <td class="text-end">
        <button type="button" class="btn btn-sm btn-danger hapus-bahan">Hapus</button>
    </td>
</tr>
