<div id="image-url-preview-wrapper" class="mt-4">
    <div id="image-url-preview-container" style="display: none;" class="p-4 bg-gray-50 rounded-lg border border-gray-200">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('site.image_preview') ?? 'Image Preview' }}
        </label>
        <div class="relative inline-block">
            <img
                id="image-url-preview-img"
                src=""
                alt="Image preview"
                class="max-w-full h-auto max-h-64 rounded-lg border border-gray-300 shadow-sm"
                loading="lazy"
            />
            <div id="image-url-preview-error" class="hidden text-sm text-red-600 p-2 bg-red-50 rounded border border-red-200 mt-2">
                {{ __('site.image_preview_error') ?? 'Failed to load image preview' }}
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    function findImageUrlInput() {
        // Try multiple selectors to find the image_url input field
        const selectors = [
            'input[wire\\:model*="image_url"]',
            'input[wire\\:model="data.image_url"]',
            'input[name="data[image_url]"]',
            'input[name*="image_url"]',
            'input[id*="image_url"]'
        ];

        for (const selector of selectors) {
            const input = document.querySelector(selector);
            if (input) return input;
        }

        return null;
    }

    function setupImagePreview() {
        const urlInput = findImageUrlInput();
        const previewContainer = document.getElementById('image-url-preview-container');
        const previewImg = document.getElementById('image-url-preview-img');
        const previewError = document.getElementById('image-url-preview-error');

        if (!urlInput || !previewContainer || !previewImg) {
            // Retry after a delay if elements aren't ready
            setTimeout(setupImagePreview, 500);
            return;
        }

        function updatePreview() {
            const url = (urlInput.value || '').trim();

            if (url && url.length > 0) {
                previewContainer.style.display = 'block';
                previewError.classList.add('hidden');

                // Update image source
                previewImg.src = url;
                previewImg.style.display = 'block';

                // Handle image load/error
                previewImg.onload = function() {
                    previewImg.style.display = 'block';
                    previewError.classList.add('hidden');
                };

                previewImg.onerror = function() {
                    previewImg.style.display = 'none';
                    previewError.classList.remove('hidden');
                };
            } else {
                previewContainer.style.display = 'none';
            }
        }

        // Watch for direct input changes
        urlInput.addEventListener('input', updatePreview);
        urlInput.addEventListener('change', updatePreview);
        urlInput.addEventListener('blur', updatePreview);

        // Watch for Livewire updates
        if (typeof Livewire !== 'undefined') {
            document.addEventListener('livewire:init', () => {
                Livewire.hook('morph.updated', ({ el }) => {
                    if (el.contains(urlInput) || urlInput.isEqualNode(el)) {
                        setTimeout(updatePreview, 100);
                    }
                });
            });

            // Also hook into Livewire if already initialized
            if (Livewire.hook) {
                Livewire.hook('morph.updated', ({ el }) => {
                    if (el.contains(urlInput) || urlInput.isEqualNode(el)) {
                        setTimeout(updatePreview, 100);
                    }
                });
            }
        }

        // Use MutationObserver as fallback
        const observer = new MutationObserver(() => {
            updatePreview();
        });

        observer.observe(urlInput, {
            attributes: true,
            attributeFilter: ['value'],
            childList: false,
            subtree: false
        });

        // Initial check
        setTimeout(updatePreview, 500);

        // Poll as final fallback (check every 2 seconds)
        setInterval(() => {
            const currentUrl = (urlInput.value || '').trim();
            const currentSrc = previewImg.src;
            if (currentUrl && currentUrl !== currentSrc) {
                updatePreview();
            }
        }, 2000);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupImagePreview);
    } else {
        setupImagePreview();
    }

    // Also try after a delay to catch dynamically loaded forms
    setTimeout(setupImagePreview, 1000);
})();
</script>
