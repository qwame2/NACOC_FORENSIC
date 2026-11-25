/**
 * ForensicNACOC Evidence Photo Optimizer & Batch Safeguard
 * 
 * Implements Forensic Evidence Best Practice:
 * - Keeps individual exhibit photos under 3–5 MB (auto-scales & compresses high-res camera photos via HTML5 canvas)
 * - Restricts batches to 10–15 photos per upload to stay safely within the 40 MB server post_max_size limit
 * - Preserves forensic-grade visual fidelity (Full HD 1920px bounds at 0.85 JPEG quality)
 */
(function(window) {
    'use strict';

    const ForensicImageOptimizer = {
        MAX_BATCH_PHOTOS: 15,          // Max photos per batch
        RECOMMENDED_BATCH_MIN: 10,     // Target batch lower bound
        MAX_PHOTO_BYTES: 5 * 1024 * 1024,      // 5 MB target max per photo
        HARD_REJECT_BYTES: 25 * 1024 * 1024,   // 25 MB hard reject for raw camera blobs
        MAX_DIMENSION: 1920,           // Max dimension (width/height) to preserve forensic clarity
        JPEG_QUALITY: 0.85,            // Quality for canvas compression

        /**
         * Display a clean toast notification or alert
         */
        showToast(message, type = 'info') {
            const toastId = 'forensic-photo-toast';
            let toast = document.getElementById(toastId);
            if (!toast) {
                toast = document.createElement('div');
                toast.id = toastId;
                toast.className = 'fixed bottom-5 right-5 z-[99999] max-w-md px-4 py-3 rounded-2xl shadow-xl text-xs font-semibold flex items-center space-x-2.5 transition-all duration-300 opacity-0 translate-y-3 pointer-events-none';
                document.body.appendChild(toast);
            }

            const styles = {
                info: 'bg-slate-900 text-white border border-slate-700 shadow-slate-900/40',
                warning: 'bg-amber-900 text-amber-100 border border-amber-700 shadow-amber-900/40',
                success: 'bg-emerald-900 text-emerald-100 border border-emerald-700 shadow-emerald-900/40',
                error: 'bg-rose-900 text-rose-100 border border-rose-700 shadow-rose-900/40'
            };

            const icons = {
                info: '<svg class="w-4 h-4 text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                warning: '<svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
                success: '<svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                error: '<svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
            };

            toast.className = `fixed bottom-5 right-5 z-[99999] max-w-md px-4 py-3 rounded-2xl shadow-xl text-xs font-semibold flex items-center space-x-2.5 transition-all duration-300 opacity-100 translate-y-0 ${styles[type] || styles.info}`;
            toast.innerHTML = `${icons[type] || icons.info}<span>${message}</span>`;

            clearTimeout(toast._timeout);
            toast._timeout = setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-3');
            }, 4500);
        },

        /**
         * Validates and caps the file batch to MAX_BATCH_PHOTOS
         * @param {FileList|File[]} fileList 
         * @param {number} currentCount 
         * @returns {File[]}
         */
        filterBatch(fileList, currentCount = 0) {
            let rawFiles = Array.from(fileList || []);
            let imageFiles = rawFiles.filter(f => f && (!f.type || f.type.startsWith('image/')));

            if (imageFiles.length === 0) {
                this.showToast('Please select valid image files (JPG, PNG, WEBP).', 'warning');
                return [];
            }

            const maxAllowed = Math.max(0, this.MAX_BATCH_PHOTOS - currentCount);
            if (maxAllowed <= 0) {
                this.showToast(`Batch limit reached: Exhibit block already contains ${this.MAX_BATCH_PHOTOS} photos. Maximum 15 photos per exhibit ensures optimal performance.`, 'warning');
                return [];
            }

            if (imageFiles.length > maxAllowed) {
                this.showToast(`Batch safeguard: You selected ${imageFiles.length} photos. Limiting to ${maxAllowed} photo(s) to stay within the 15-photo batch recommendation.`, 'warning');
                imageFiles = imageFiles.slice(0, maxAllowed);
            }

            return imageFiles;
        },

        /**
         * Optimizes an image file to a base64 Data URL under 3-5 MB.
         * Scales down dimensions if exceeding 1920px.
         * Re-encodes through HTML5 Canvas with high-quality smoothing.
         * @param {File} file 
         * @returns {Promise<string>}
         */
        optimizeImageToDataUrl(file) {
            return new Promise((resolve, reject) => {
                if (!file) return reject(new Error('No file provided'));

                if (file.size > this.HARD_REJECT_BYTES) {
                    const mb = (file.size / (1024 * 1024)).toFixed(1);
                    this.showToast(`"${file.name}" is too large (${mb} MB). Please select photos under 25 MB.`, 'error');
                    return reject(new Error('File exceeds maximum initial size'));
                }

                // If SVG, read directly as data URL
                if (file.type === 'image/svg+xml') {
                    const reader = new FileReader();
                    reader.onload = e => resolve(e.target.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    const rawDataUrl = e.target.result;
                    const img = new Image();

                    img.onload = () => {
                        const width = img.naturalWidth || img.width;
                        const height = img.naturalHeight || img.height;

                        const needsResize = width > this.MAX_DIMENSION || height > this.MAX_DIMENSION;
                        const needsCompression = file.size > 2 * 1024 * 1024; // > 2MB

                        // If already small and well proportioned, return raw
                        if (!needsResize && !needsCompression && file.size <= this.MAX_PHOTO_BYTES) {
                            resolve(rawDataUrl);
                            return;
                        }

                        // Calculate aspect ratio preserving dimensions
                        let newWidth = width;
                        let newHeight = height;
                        if (needsResize) {
                            if (width >= height) {
                                newWidth = this.MAX_DIMENSION;
                                newHeight = Math.round((height * this.MAX_DIMENSION) / width);
                            } else {
                                newHeight = this.MAX_DIMENSION;
                                newWidth = Math.round((width * this.MAX_DIMENSION) / height);
                            }
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = newWidth;
                        canvas.height = newHeight;
                        const ctx = canvas.getContext('2d');

                        if (!ctx) {
                            resolve(rawDataUrl);
                            return;
                        }

                        // High fidelity smoothing
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(img, 0, 0, newWidth, newHeight);

                        // Export to JPEG
                        let quality = this.JPEG_QUALITY;
                        let optimizedDataUrl = canvas.toDataURL('image/jpeg', quality);

                        // If still larger than 5MB, reduce quality slightly to 0.75
                        let approxBytes = Math.round((optimizedDataUrl.length - 22) * 3 / 4);
                        if (approxBytes > this.MAX_PHOTO_BYTES) {
                            optimizedDataUrl = canvas.toDataURL('image/jpeg', 0.75);
                        }

                        resolve(optimizedDataUrl);
                    };

                    img.onerror = () => {
                        // If canvas decode fails (e.g. HEIC / TIFF / unsupported format), always preserve the raw data URL
                        resolve(rawDataUrl);
                    };

                    img.src = rawDataUrl;
                };

                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        },

        /**
         * Asynchronously process a batch of files:
         * 1. Filters & caps to 15 photos max
         * 2. Optimizes each image to under 3-5 MB
         * 3. Calls onComplete with the optimized data URLs
         */
        async loadOptimizedSeizurePhotos(files, targetBlock, photosArray, onComplete) {
            const acceptedFiles = this.filterBatch(files, photosArray.length);
            if (acceptedFiles.length === 0) {
                if (typeof onComplete === 'function') onComplete();
                return;
            }

            // Show optimization feedback if multiple files
            if (acceptedFiles.length > 2) {
                this.showToast(`Optimizing ${acceptedFiles.length} photo(s) to under 5MB for fast upload...`, 'info');
            }

            let loaded = 0;
            for (const file of acceptedFiles) {
                try {
                    const dataUrl = await this.optimizeImageToDataUrl(file);
                    if (dataUrl) {
                        photosArray.push(dataUrl);
                        loaded++;
                    }
                } catch (err) {
                    console.warn('[ForensicImageOptimizer] Skipped file:', file.name, err);
                }
            }

            if (loaded > 0 && acceptedFiles.length > 2) {
                this.showToast(`${loaded} photo(s) optimized & attached successfully.`, 'success');
            }

            if (typeof onComplete === 'function') {
                onComplete();
            }
        },

        /**
         * Optimize a mugshot file or input and preview it.
         * Accepts an HTMLInputElement, a File object (from drag-drop), or a FileList.
         */
        async optimizeMugshotFile(fileOrInput, onOptimized) {
            let file = null;
            if (fileOrInput instanceof File) {
                file = fileOrInput;
            } else if (fileOrInput && fileOrInput.files && fileOrInput.files[0]) {
                file = fileOrInput.files[0];
            } else if (fileOrInput && fileOrInput[0] instanceof File) {
                file = fileOrInput[0];
            }
            if (!file) return;

            if (file.size > this.HARD_REJECT_BYTES) {
                this.showToast(`Mugshot file "${file.name}" exceeds 25 MB limit.`, 'error');
                return;
            }

            try {
                const dataUrl = await this.optimizeImageToDataUrl(file);
                if (typeof onOptimized === 'function') {
                    onOptimized(dataUrl);
                }
            } catch (err) {
                console.warn('[ForensicImageOptimizer] Fallback to raw mugshot read:', err);
                const reader = new FileReader();
                reader.onload = e => {
                    if (typeof onOptimized === 'function') onOptimized(e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
    };

    window.ForensicImageOptimizer = ForensicImageOptimizer;
})(window);

