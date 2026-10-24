<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Layar Antrean</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; background: #0f172a; color: #fff; }
        .wrap { padding: 24px; }
        h1 { text-align: center; margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
        .card { background: #1e293b; border-radius: 12px; padding: 24px; text-align: center; }
        .poli { font-size: 22px; font-weight: 600; margin-bottom: 12px; color: #94a3b8; }
        .nomor { font-size: 64px; font-weight: 800; color: #38bdf8; }
        .status { font-size: 14px; color: #cbd5e1; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>ANTREAN KLINIK AVICENA</h1>
        <div class="grid" id="layar-grid"></div>
    </div>
    <script>
        const url = @json(route('antrean.json'));
        async function refresh() {
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                const grid = document.getElementById('layar-grid');
                grid.innerHTML = '';
                // group by poli not in json; show all items as cards
                (data.items || []).forEach(function (item) {
                    const card = document.createElement('div');
                    card.className = 'card';
                    card.innerHTML =
                        '<div class="poli">Antrean</div>' +
                        '<div class="nomor">' + item.nomor + '</div>' +
                        '<div class="status">' + item.status + '</div>';
                    grid.appendChild(card);
                });
            } catch (e) { /* ignore */ }
        }
        refresh();
        setInterval(refresh, 5000);
    </script>
</body>
</html>