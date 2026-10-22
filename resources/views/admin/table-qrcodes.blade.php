<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR Codes des Tables</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@media print { .no-print { display: none; } }</style>
</head>
<body class="p-8 bg-gray-50">
    <div class="no-print mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">QR Codes des Tables</h1>
        <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded">
            🖨️ Imprimer
        </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        @foreach($tables as $table)
            <div class="bg-white p-4 rounded shadow text-center break-inside-avoid">
                <h2 class="font-bold mb-2">Table {{ $table->table_number }}</h2>
                <div class="flex justify-center"
                     data-qr="{{ route('qr.login', $table->qr_token) }}"
                     id="qr-{{ $table->id }}"></div>
                <p class="text-xs text-gray-500 mt-2 break-all">
                    {{ route('qr.login', $table->qr_token) }}
                </p>
            </div>
        @endforeach
    </div>

    <script>
        document.querySelectorAll('[data-qr]').forEach(el => {
            new QRCode(el, {
                text: el.dataset.qr,
                width: 180,
                height: 180,
            });
        });
    </script>
</body>
</html>