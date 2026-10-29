<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription #RX-{{ str_pad((string) $prescription->id, 5, '0', STR_PAD_LEFT) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        body { background: #f4f6f9; padding: 2rem 1rem; }
        @media print {
            body { background: #fff; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-center gap-2 mb-3 no-print">
            <button type="button" class="btn btn-teal" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print prescription
            </button>
            <a href="{{ route('prescriptions.show', $prescription) }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Back to details
            </a>
        </div>

        @include('prescriptions.partials.document')
    </div>

    <script>
        window.addEventListener('load', function () {
            if (new URLSearchParams(window.location.search).get('auto') === '1') {
                window.print();
            }
        });
    </script>
</body>
</html>
