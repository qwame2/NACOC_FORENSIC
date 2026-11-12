                    <!-- SEARCH WARRANT DOCUMENT UPLOAD SECTION (Conditional on Operation Type = Search Warrant) -->
                    <div id="searchWarrantUploadSection" class="hidden bg-white p-6 rounded-2xl border border-blue-200/90 shadow-sm space-y-4 relative z-40">
                        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 border border-blue-200/90 flex items-center justify-center font-bold shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Search Warrant Document Upload</h3>
                                    <p class="text-xs text-slate-500">Attach the signed Search Warrant copy authorized for this operation (PDF, JPG, PNG, WEBP, DOC, DOCX &bull; Max 10 MB)</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-[10px] font-mono-code font-bold uppercase self-start sm:self-center">Search Warrant</span>
                        </div>

                        <div id="searchWarrantDropzone"
                            onclick="document.getElementById('searchWarrantDocumentInput')?.click()"
                            ondragover="handleSearchWarrantDragOver(event, this)"
                            ondragleave="handleSearchWarrantDragLeave(event, this)"
                            ondrop="handleSearchWarrantDrop(event)"
                            class="border-2 border-dashed border-blue-200 hover:border-blue-400 bg-blue-50/30 hover:bg-blue-50/60 rounded-2xl p-5 text-center cursor-pointer transition-all">
                            <input type="file"
                                id="searchWarrantDocumentInput"
                                name="search_warrant_document"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                class="hidden"
                                onchange="handleSearchWarrantFileSelect(this.files && this.files[0])">

                            <div id="searchWarrantEmptyState" class="space-y-2 py-2">
                                <div class="w-11 h-11 mx-auto rounded-xl bg-white border border-blue-200 text-blue-600 flex items-center justify-center shadow-2xs">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <div class="text-xs font-extrabold text-slate-800">Click to select or drag &amp; drop Search Warrant Document</div>
                                <div class="text-[11px] text-slate-500">Accepted formats: PDF, JPG, PNG, WEBP, DOC, DOCX</div>
                            </div>

                            <div id="searchWarrantFilePreview" class="hidden flex items-center justify-between gap-3 bg-white border border-blue-200 rounded-xl px-4 py-3 text-left shadow-2xs" onclick="event.stopPropagation()">
                                <div class="flex items-center space-x-3 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div id="searchWarrantFileName" class="text-xs font-extrabold text-slate-900 truncate"></div>
                                        <div id="searchWarrantFileSize" class="text-[10px] font-mono-code text-slate-500"></div>
                                    </div>
                                </div>
                                <button type="button" onclick="clearSearchWarrantFile()"
                                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl text-[11px] font-bold transition-colors cursor-pointer shrink-0">
                                    Remove
                                </button>
                            </div>
                        </div>

                        <p id="searchWarrantErrorMsg" class="hidden text-xs font-bold text-red-600"></p>
                    </div>

                    <script>
                        window.__stagedSearchWarrantFile = null;

                        window.toggleSearchWarrantUploadSection = function(operationTypeVal) {
                            const section = document.getElementById('searchWarrantUploadSection');
                            if (!section) return;
                            const isSearchWarrant = String(operationTypeVal || '').trim().toLowerCase() === 'search warrant';
                            if (isSearchWarrant) {
                                section.classList.remove('hidden');
                            } else {
                                section.classList.add('hidden');
                                window.clearSearchWarrantFile();
                            }
                        };

                        window.handleSearchWarrantDragOver = function(e, el) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (el) el.classList.add('border-blue-500', 'bg-blue-50');
                        };

                        window.handleSearchWarrantDragLeave = function(e, el) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (el) el.classList.remove('border-blue-500', 'bg-blue-50');
                        };

                        window.handleSearchWarrantDrop = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const dropzone = document.getElementById('searchWarrantDropzone');
                            if (dropzone) dropzone.classList.remove('border-blue-500', 'bg-blue-50');
                            const droppedFile = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                            if (droppedFile) {
                                window.handleSearchWarrantFileSelect(droppedFile);
                            }
                        };

                        window.handleSearchWarrantFileSelect = function(file) {
                            const errEl = document.getElementById('searchWarrantErrorMsg');
                            if (errEl) {
                                errEl.textContent = '';
                                errEl.classList.add('hidden');
                            }
                            if (!file) {
                                window.clearSearchWarrantFile();
                                return;
                            }

                            const allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
                            const ext = (file.name.split('.').pop() || '').toLowerCase();
                            if (!allowedExts.includes(ext)) {
                                window.clearSearchWarrantFile();
                                if (errEl) {
                                    errEl.textContent = 'Unsupported file format. Allowed formats: PDF, JPG, PNG, WEBP, DOC, DOCX.';
                                    errEl.classList.remove('hidden');
                                }
                                return;
                            }

                            if (file.size > 10 * 1024 * 1024) {
                                window.clearSearchWarrantFile();
                                if (errEl) {
                                    errEl.textContent = 'File exceeds 10 MB maximum upload limit.';
                                    errEl.classList.remove('hidden');
                                }
                                return;
                            }

                            window.__stagedSearchWarrantFile = file;

                            const emptyState = document.getElementById('searchWarrantEmptyState');
                            const previewEl = document.getElementById('searchWarrantFilePreview');
                            const nameEl = document.getElementById('searchWarrantFileName');
                            const sizeEl = document.getElementById('searchWarrantFileSize');

                            if (nameEl) nameEl.textContent = file.name;
                            if (sizeEl) {
                                const kb = Math.max(1, Math.round(file.size / 1024));
                                sizeEl.textContent = kb >= 1024 ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' : kb + ' KB';
                            }
                            if (emptyState) emptyState.classList.add('hidden');
                            if (previewEl) previewEl.classList.remove('hidden');
                        };

                        window.clearSearchWarrantFile = function() {
                            window.__stagedSearchWarrantFile = null;
                            const input = document.getElementById('searchWarrantDocumentInput');
                            if (input) input.value = '';
                            const emptyState = document.getElementById('searchWarrantEmptyState');
                            const previewEl = document.getElementById('searchWarrantFilePreview');
                            const errEl = document.getElementById('searchWarrantErrorMsg');
                            if (emptyState) emptyState.classList.remove('hidden');
                            if (previewEl) previewEl.classList.add('hidden');
                            if (errEl) {
                                errEl.textContent = '';
                                errEl.classList.add('hidden');
                            }
                        };

                        window.uploadStagedSearchWarrantFile = async function(caseData) {
                            const opTypeVal = (document.getElementById('operationTypeSelect')?.value || '').trim().toLowerCase();
                            if (opTypeVal !== 'search warrant') {
                                return null;
                            }

                            const fileInput = document.getElementById('searchWarrantDocumentInput');
                            const fileToUpload = window.__stagedSearchWarrantFile || (fileInput && fileInput.files && fileInput.files[0]);
                            if (!fileToUpload) {
                                return null;
                            }

                            const caseTarget = (caseData && caseData.case && (caseData.case.id || caseData.case.case_number))
                                || (caseData && (caseData.id || caseData.case_number));
                            if (!caseTarget) {
                                return null;
                            }

                            const formData = new FormData();
                            formData.append('search_warrant_document', fileToUpload);
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                            if (csrfToken) {
                                formData.append('_token', csrfToken);
                            }

                            const res = await fetch('/cases/' + encodeURIComponent(caseTarget) + '/upload-search-warrant', {
                                method: 'POST',
                                headers: {
                                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            if (!res.ok) {
                                let errMsg = 'Search Warrant document upload failed.';
                                try {
                                    const errData = await res.json();
                                    if (errData && errData.message) errMsg = errData.message;
                                } catch (e) {}
                                throw new Error(errMsg);
                            }

                            const result = await res.json();
                            window.clearSearchWarrantFile();
                            return result;
                        };
                    </script>

