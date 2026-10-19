<tr class="baris-parameter">
    <td>
        <input type="hidden" name="parameter[{{ $index }}][id]" value="{{ $baris['id'] ?? '' }}">
        <input type="text" name="parameter[{{ $index }}][nama_parameter]" maxlength="150"
            class="form-control form-control-sm" value="{{ $baris['nama_parameter'] ?? '' }}">
    </td>
    <td>
        <input type="text" name="parameter[{{ $index }}][satuan]" maxlength="50"
            class="form-control form-control-sm" value="{{ $baris['satuan'] ?? '' }}">
    </td>
    <td>
        <input type="number" step="0.001" name="parameter[{{ $index }}][nilai_rujukan_min]"
            class="form-control form-control-sm" value="{{ $baris['nilai_rujukan_min'] ?? '' }}">
    </td>
    <td>
        <input type="number" step="0.001" name="parameter[{{ $index }}][nilai_rujukan_max]"
            class="form-control form-control-sm" value="{{ $baris['nilai_rujukan_max'] ?? '' }}">
    </td>
    <td>
        <input type="text" name="parameter[{{ $index }}][keterangan_rujukan]" maxlength="255"
            class="form-control form-control-sm" placeholder="mis. Negatif / &lt; 200"
            value="{{ $baris['keterangan_rujukan'] ?? '' }}">
    </td>
    <td class="text-end">
        <button type="button" class="btn btn-sm btn-danger hapus-parameter">Hapus</button>
    </td>
</tr>
