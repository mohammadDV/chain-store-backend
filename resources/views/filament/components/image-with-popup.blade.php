@php
    $path = $getState();
    $imageUrl = $path;

    // Check if path starts with https:// or http://
    if ($path && !str_starts_with($path, 'https://') && !str_starts_with($path, 'http://')) {
        // Use S3 storage to get the URL
        $imageUrl = \Storage::disk('s3')->url($path);
    }

    // Escape the URL for JavaScript
    $escapedUrl = addslashes($imageUrl);
@endphp

@if($path)
    <div class="relative">
        <img
            src="{{ $imageUrl }}"
            alt="File preview"
            class="w-16 h-16 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity"
            loading="lazy"
            onclick="if (typeof window.filamentImageModalOpen === 'function') { window.filamentImageModalOpen('{{ $escapedUrl }}'); } else { window.open('{{ $imageUrl }}', '_blank'); }"
        />
    </div>
@else
    <span class="text-gray-400">-</span>
@endif

@once
    <!-- Single shared Image Modal -->
    <div
        id="filamentImageModal"
        class="hidden fixed inset-0 z-[99999] overflow-auto bg-black bg-opacity-90 flex items-center justify-center"
        onclick="if (typeof window.filamentImageModalClose === 'function') window.filamentImageModalClose();"
    >
        <div class="relative max-w-7xl max-h-full p-4" onclick="event.stopPropagation();">
            <button
                onclick="if (typeof window.filamentImageModalClose === 'function') window.filamentImageModalClose();"
                class="absolute top-4 right-4 text-white hover:text-gray-300 text-4xl font-bold bg-black bg-opacity-50 rounded-full w-12 h-12 flex items-center justify-center z-10 transition-all hover:bg-opacity-70 cursor-pointer"
            >
                &times;
            </button>
            <img
                id="filamentImageModalImg"
                src=""
                alt=""
                class="max-w-full max-h-[90vh] object-contain rounded-lg"
            />
        </div>
    </div>

    <script>
        (function() {
            if (typeof window.filamentImageModalOpen !== 'undefined') {
                return; // Already initialized
            }

            window.filamentImageModalOpen = function(imageUrl) {
                const modal = document.getElementById('filamentImageModal');
                const modalImage = document.getElementById('filamentImageModalImg');
                if (modal && modalImage) {
                    modalImage.src = imageUrl;
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            };

            window.filamentImageModalClose = function() {
                const modal = document.getElementById('filamentImageModal');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            };

            // Close on Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const modal = document.getElementById('filamentImageModal');
                    if (modal && !modal.classList.contains('hidden')) {
                        window.filamentImageModalClose();
                    }
                }
            });
        })();
    </script>
@endonce

