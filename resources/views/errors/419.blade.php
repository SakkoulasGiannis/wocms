<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Η σελίδα έχει λήξει - Session Expired</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            <!-- Icon -->
            <div class="mx-auto w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <!-- Title -->
            <h1 class="text-2xl font-bold text-gray-900 mb-3">
                Η σελίδα έχει λήξει
            </h1>

            <!-- Message -->
            <p class="text-gray-600 mb-6">
                Το session σας έχει λήξει για λόγους ασφαλείας.
                Η σελίδα θα ανανεωθεί αυτόματα σε <span id="countdown" class="font-semibold text-blue-600">3</span> δευτερόλεπτα.
            </p>

            <!-- Manual Refresh Button -->
            <button onclick="location.reload()"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Ανανέωση τώρα
            </button>

            <!-- Info -->
            <p class="text-xs text-gray-500 mt-6">
                Για την ασφάλειά σας, οι φόρμες λήγουν μετά από 8 ώρες αδράνειας.
            </p>
        </div>

        <!-- Technical Details (hidden by default) -->
        <details class="mt-4 text-center">
            <summary class="text-sm text-gray-500 cursor-pointer hover:text-gray-700">
                Τεχνικές λεπτομέρειες
            </summary>
            <div class="mt-2 text-xs text-gray-600 bg-white rounded-lg p-4">
                <p><strong>Error:</strong> 419 - CSRF Token Mismatch / Session Expired</p>
                <p class="mt-2">
                    Αυτό συμβαίνει όταν:
                </p>
                <ul class="list-disc list-inside text-left mt-2 space-y-1">
                    <li>Η σελίδα παραμένει ανοιχτή για πολλή ώρα</li>
                    <li>Το session έχει λήξει στον server</li>
                    <li>Τα cookies έχουν διαγραφεί</li>
                </ul>
            </div>
        </details>
    </div>

    <script>
        // Auto-refresh countdown
        let seconds = 3;
        const countdownEl = document.getElementById('countdown');

        const countdown = setInterval(() => {
            seconds--;
            if (countdownEl) {
                countdownEl.textContent = seconds;
            }

            if (seconds <= 0) {
                clearInterval(countdown);
                location.reload();
            }
        }, 1000);

        // Also reload if user presses any key
        document.addEventListener('keypress', () => {
            clearInterval(countdown);
            location.reload();
        });
    </script>
</body>
</html>
