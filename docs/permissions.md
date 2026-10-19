# Hak Akses & Manajemen Menu

## Konsep
Dua lapis keamanan:
1. **Izin menu (RBAC)** — "boleh membuka fitur ini?" → `sys_peran` × `sys_menu` × {lihat, tambah, ubah, hapus} di `sys_hak_akses`. Ditegakkan middleware `izin`.
2. **Izin data (Policy)** — "boleh menyentuh baris ini?" (pemilik, data terkunci, status).

Menyembunyikan menu/tombol hanya kenyamanan UI. **Yang mengamankan adalah middleware & Policy di server.**

## Alur komponen
```
config/menu.php ──(php artisan menu:sinkron)──► sys_menu
                                                   ▲
UI Hak Akses (admin) ─────────────► sys_hak_akses ─┘ (peran × menu × aksi)
                                         │
IzinService::untukPeran()  (cache 1 jam per peran, dilupakan saat hak disimpan)
   ├─ middleware izin:<kode>,<aksi>   → 403 jika tidak boleh
   ├─ @izin('<kode>','<aksi>')        → Blade
   └─ IzinService::pohonMenu()        → sidebar AdminLTE
```

## Menambah menu / fitur baru (checklist)
1. Tambahkan item di `config/menu.php` (`kode` unik, `nama`, `rute` = NAMA route, `ikon` bi-*).
2. Jalankan `php artisan menu:sinkron` (admin otomatis mendapat akses penuh).
3. Pasang middleware di controller:
```php
class PasienController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:pendaftaran.pasien,lihat',  only: ['index', 'show']),
            new Middleware('izin:pendaftaran.pasien,tambah', only: ['create', 'store']),
            new Middleware('izin:pendaftaran.pasien,ubah',   only: ['edit', 'update']),
            new Middleware('izin:pendaftaran.pasien,hapus',  only: ['destroy']),
        ];
    }
}
```
4. Tambahkan ke `HakAksesAwalSeeder` untuk peran yang relevan (L/T/U/H), atau atur lewat UI Hak Akses.
5. Tombol di Blade: `@izin('pendaftaran.pasien','tambah') ... @endizin`.

## Aturan
- `kode` menu = kontrak dengan kode. Jangan diganti setelah dipakai; kalau perlu, buat kode baru + migrasi hak akses.
- Salah ketik aksi (mis. `liat`) melempar exception (disengaja) — bukan menolak diam-diam.
- Tambah/ubah/hapus otomatis mensyaratkan `lihat` (UI Hak Akses memaksa ini).
- Peran `admin` tidak bisa diedit lewat UI dan selalu mendapat seluruh menu aktif setiap `menu:sinkron`.
- Menu yang dihapus dari config **dinonaktifkan**, bukan dihapus (hak lama aman).
- **Endpoint pencarian/lookup** (dropdown dokter, autocomplete ICD-10/obat/pasien) dilindungi izin fitur yang memakainya, BUKAN izin menu master. Contoh: dropdown dokter di Pendaftaran Lab Mandiri → `izin:pendaftaran.lab_mandiri,tambah`.
- Cache izin: `IzinService::lupakan($peranId)` dipanggil setiap hak berubah. Jangan memakai `Cache::flush()` (Redis dipakai bersama queue).

## Hak awal per peran (`HakAksesAwalSeeder`)
L = lihat, T = tambah, U = ubah, H = hapus. Admin: semua menu penuh.
| Peran | Menu → hak |
|---|---|
| resepsionis | dasbor L · pendaftaran.pasien LTU · pendaftaran.kunjungan LTU |
| perawat | dasbor L · pendaftaran.pasien LTU · pendaftaran.kunjungan LTU · pendaftaran.ttv LTU |
| dokter | dasbor L · pendaftaran.pasien L · erm.pemeriksaan LTU · erm.resep LTU · erm.rujukan_lab LT · lab.hasil L · master.icd10 L |
| petugas_lab | dasbor L · pendaftaran.pasien LT · pendaftaran.lab_mandiri LT · lab.antrean LU · lab.hasil LTU · master.pemeriksaan_lab L |
| apoteker | dasbor L · apotek.resep LU · apotek.penjualan LTU · apotek.stok L · apotek.penerimaan LTU · apotek.retur_supplier LTU · apotek.retur_pasien LTU · apotek.opname LTU · master.obat LTU · master.racikan LTU · master.supplier LTU |
| kasir | dasbor L · kasir.tagihan L · kasir.pembayaran LT · kasir.laporan L |

Tidak ada peran selain admin yang diberi hak `hapus` (H) secara default — data medis/transaksi tidak dihapus.

## Policy (izin data) yang perlu dibuat
| Policy | Aturan |
|---|---|
| `PemeriksaanPolicy` | ubah hanya oleh dokter pembuat selama `dikunci = 0`; lihat oleh semua dokter |
| `LabPermintaanPolicy` | ubah hasil hanya jika `dikunci = 0` |
| `ResepPolicy` | apoteker memvalidasi/menyerahkan hanya status yang sah |
| `TagihanPolicy` | pembayaran hanya jika kunjungan `kasir`; tagihan lunas tidak bisa diubah |
| `PasienPolicy` | `petugas_lab` boleh `create`, tidak boleh `update` |

## Pengujian izin
Untuk tiap controller: tes bahwa (a) peran dengan hak → 200/302, (b) peran tanpa hak → 403, (c) pengguna nonaktif → 403, (d) setelah hak diubah lewat UI, cache terbarui.
