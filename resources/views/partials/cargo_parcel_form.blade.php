{{-- 
    CARGO / PARCEL FORENSIC REGISTRATION FORM PARTIAL
    Renders conditionally when Operation Type is 'Cargo/Parcel'
    Frontend UI only (Sections A through F)
--}}

<div id="cargoParcelFormContainer" class="hidden space-y-6">

    <!-- EXECUTIVE SWITCHER & MODE BANNER -->
    <div class="bg-gradient-to-r from-amber-900 via-slate-900 to-slate-950 p-6 rounded-2xl border border-amber-500/30 shadow-xl text-white relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2 mb-2">
                    <span class="px-2.5 py-0.5 bg-amber-500 text-slate-950 text-[10px] font-extrabold font-mono-code rounded-md uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        CARGO / PARCEL MODE ACTIVE
                    </span>
                    <span class="text-xs text-amber-300/60">&bull;</span>
                    <span class="text-xs font-bold text-amber-300 font-mono-code">AIRPORT, SEAPORT, COURIER &amp; POSTAL INTERDICTION</span>
                </div>
                <h2 class="text-2xl font-extrabold tracking-tight text-white flex items-center gap-2">
                    <span>Cargo &amp; Parcel Forensic Registration Docket</span>
                </h2>
                <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    Dedicated protocol for logging seized consignments, mail, courier parcels, luggage, and freight containers prior to and during forensic physical examination.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="px-3.5 py-2 bg-slate-900/90 border border-amber-500/40 rounded-xl text-right">
                    <div class="text-[9px] font-mono-code uppercase text-amber-400 font-bold">Auto Parcel Ref</div>
                    <div id="bannerParcelRef" class="text-xs font-extrabold font-mono-code text-white">PRC-2026-0000</div>
                </div>

                <button type="button" onclick="switchBackToStandardDocket()" 
                    class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl border border-white/20 shadow-xs transition-all flex items-center gap-2 cursor-pointer hover:border-amber-400">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                    </svg>
                    <span>Switch to Standard Arrest Form</span>
                </button>
            </div>
        </div>
    </div>

    <!-- CARGO / PARCEL MAIN FORM -->
    <form id="cargoParcelForm" autocomplete="off" onsubmit="event.preventDefault(); handleCargoParcelSubmit();" class="space-y-6 text-xs font-medium">

        <!-- ========================================================================= -->
        <!-- SECTION A: PARCEL REGISTRATION                                            -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6">
            <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-600 text-white flex items-center justify-center font-bold shadow-md shadow-amber-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Section A: Parcel Registration &amp; Identification</h3>
                        <p class="text-xs text-slate-500">Intake logging, automated reference generation, classification, and photographic documentation</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 text-[10px] font-mono-code font-bold rounded-lg border border-amber-200 uppercase tracking-wider">
                        Initial Intake
                    </span>
                </div>
            </div>

            <!-- Top Row: Parcel Reference, Receipt Date/Time, Category, Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 2. Unique Parcel Reference Number (Generated Automatically) -->
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                        <span>Parcel Reference Number</span>
                        <span class="text-[9px] font-mono-code text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded font-bold uppercase">Automated</span>
                    </label>
                    <div class="relative flex items-center group">
                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-amber-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                        </span>
                        <input type="text" id="parcelReferenceNumber" name="parcel_reference_number" readonly
                            class="w-full pl-10 pr-3.5 py-3 bg-slate-50 border border-slate-300 rounded-xl font-mono-code font-extrabold text-slate-900 text-xs shadow-inner cursor-not-allowed">
                    </div>
                </div>

                <!-- 3. Date & Time Received by Forensic Department -->
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Date &amp; Time Received <span class="text-red-600">*</span>
                    </label>
                    <input type="datetime-local" id="parcelDateTimeReceived" name="parcel_date_time_received" required
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20 transition-all shadow-2xs">
                </div>

                <!-- 4. Parcel Category -->
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Parcel Category <span class="text-red-600">*</span>
                    </label>
                    <select id="parcelCategory" name="parcel_category" required onchange="handleParcelCategoryChange(this.value)"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20 transition-all shadow-2xs cursor-pointer">
                        <option value="" disabled selected>Select Category...</option>
                        <option value="Cargo">Cargo (Commercial Air / Sea Freight)</option>
                        <option value="Courier Parcel">Courier Parcel (DHL, FedEx, UPS, EMS)</option>
                        <option value="Postal Parcel">Postal Parcel (Ghana Post / Universal Mail)</option>
                        <option value="Luggage">Luggage / Accompanied Passenger Baggage</option>
                        <option value="Container">Container (Maritime / Intermodal ISO)</option>
                        <option value="Diplomatic Pouch">Diplomatic / Pouch Consignment</option>
                        <option value="Other">Other / Unclassified Consignment</option>
                    </select>

                    <!-- Conditional Other Input Box -->
                    <div id="parcelCategoryOtherContainer" class="hidden mt-2">
                        <label class="block text-amber-900 font-bold text-[10px] uppercase mb-1">
                            Specify Other Consignment Category <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="parcelCategoryOther" name="parcel_category_other"
                            placeholder="e.g. Diplomatic Pouch, Military Freight, Ship Stores..."
                            class="w-full px-3.5 py-2.5 bg-amber-50/60 border border-amber-300 rounded-xl text-slate-900 font-bold text-xs outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20 shadow-2xs">
                    </div>
                </div>

                <!-- 7. Current Status -->
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Current Status <span class="text-red-600">*</span>
                    </label>
                    <select id="parcelStatus" name="parcel_status" required
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20 transition-all shadow-2xs cursor-pointer">
                        <option value="Received" selected>Received (Intake Logged)</option>
                        <option value="Pending Examination">Pending Examination</option>
                        <option value="Under Examination">Under Examination</option>
                        <option value="Stored">Stored in Evidence Vault</option>
                        <option value="Transferred">Transferred to External Lab / Court</option>
                        <option value="Closed">Closed / Case Completed</option>
                    </select>
                </div>
            </div>

            <!-- Second Row: Parcel Description & Number of Packages -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <!-- 5. Parcel Description -->
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Parcel Description (Container / Outer Form) <span class="text-red-600">*</span>
                    </label>
                    <div class="relative flex items-center group">
                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-amber-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 01-2-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </span>
                        <input type="text" id="parcelDescription" name="parcel_description" required
                            placeholder="e.g. Corrugated cardboard carton reinforced with brown adhesive tape and customs sticker"
                            class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20 transition-all shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                    </div>
                </div>

                <!-- 6. Number of packages received -->
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Number of Packages Received <span class="text-red-600">*</span>
                    </label>
                    <div class="relative flex items-center group">
                        <span class="absolute left-3.5 text-slate-400 group-focus-within:text-amber-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <input type="number" id="parcelPackagesCount" name="parcel_packages_count" min="1" value="1" required
                            class="w-full pl-10 pr-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-extrabold font-mono-code outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20 transition-all shadow-2xs">
                    </div>
                </div>
            </div>

            <!-- 1. Photographs of the parcel, labels, packaging, and seals -->
            <div class="pt-2">
                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                    <span>Photographs: Parcel, Labels, Packaging &amp; Intact Seals</span>
                    <span class="text-[9px] font-mono-code text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Multi-File JPG, PNG, WEBP</span>
                </label>
                <div class="border-2 border-dashed border-slate-300 hover:border-amber-500 rounded-2xl p-4 bg-slate-50/70 hover:bg-amber-50/20 transition-all cursor-pointer relative"
                    onclick="document.getElementById('parcelIntakePhotos').click()">
                    <input type="file" id="parcelIntakePhotos" multiple accept="image/*" class="hidden" onchange="previewParcelPhotos(event, 'parcelIntakePhotosPreview')">
                    <div class="flex flex-col items-center justify-center py-4 text-center">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-2 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <p class="font-extrabold text-slate-800 text-xs">Upload Parcel Intake Photographs</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Capture all sides of outer package, barcode/airway labels, origin stickers, and seal serials</p>
                    </div>
                </div>
                <!-- Thumbnail Preview Grid -->
                <div id="parcelIntakePhotosPreview" class="flex flex-wrap gap-3 mt-3 empty:hidden"></div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION B: SEIZURE AND ORIGIN DETAILS                                     -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-5">
            <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Section B: Seizure Circumstances &amp; Origin Details</h3>
                        <p class="text-xs text-slate-500">Originating location, interdiction unit, seizing officers, related docket reference and transport custody</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-mono-code font-bold rounded-lg border border-blue-200 uppercase tracking-wider">
                    Chain of Origin
                </span>
            </div>

            <!-- Row 1: Seizure Date/Time, Seizing Unit, Related Reference -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Seizure Date &amp; Time <span class="text-red-600">*</span>
                    </label>
                    <input type="datetime-local" id="parcelSeizureDateTime" name="seizure_date_time" required
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Seizing Unit / Tactical Team <span class="text-red-600">*</span>
                    </label>
                    <select id="seizingUnit" name="seizing_unit" required onchange="handleSeizingUnitChange(this.value)"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs cursor-pointer">
                        <option value="" disabled selected>Select Seizing Unit...</option>
                        <option value="Airport Interdiction Unit (AIU)">Airport Interdiction Unit (AIU - KIA)</option>
                        <option value="Port Control Unit (PCU)">Port Control Unit (PCU - Tema Port)</option>
                        <option value="Joint Port Control Unit (JPCU)">Joint Port Control Unit (JPCU - Takoradi)</option>
                        <option value="Courier Profiling Taskforce">Courier Profiling Taskforce (EMS / DHL Sheds)</option>
                        <option value="Special Operations Unit">Special Operations Unit (SOU)</option>
                        <option value="Canine Unit (K9)">Canine Unit (K9 Narcotics Sniffer Team)</option>
                        <option value="Customs Division (GRA)">Customs Division (GRA / CEPS Joint Ops)</option>
                        <option value="Border Security Command">Border Security Command (Aflao / Paga)</option>
                        <option value="Other">Other Special Narcotics Taskforce</option>
                    </select>

                    <!-- Conditional Other Input Box -->
                    <div id="seizingUnitOtherContainer" class="hidden mt-2">
                        <label class="block text-blue-900 font-bold text-[10px] uppercase mb-1">
                            Specify Other Seizing Unit / Taskforce <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="seizingUnitOther" name="seizing_unit_other"
                            placeholder="e.g. Marine Police Unit, Joint INTERPOL Taskforce, Financial Intelligence Command..."
                            class="w-full px-3.5 py-2.5 bg-blue-50/60 border border-blue-300 rounded-xl text-slate-900 font-bold text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-2xs">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                        <span>Related Docket / Operation Ref</span>
                        <span class="text-[9px] font-mono-code text-slate-400">Optional</span>
                    </label>
                    <input type="text" id="relatedOperationRef" name="related_operation_ref"
                        placeholder="e.g. OPS-HAWK-2026 / CR-4820-26"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono-code font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                </div>
            </div>

            <!-- Row 2: Location Breakdown: Region, District, Town/Facility, GPS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Region of Seizure <span class="text-red-600">*</span>
                    </label>
                    <select id="seizureRegion" name="seizure_region" required
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs cursor-pointer">
                        <option value="Greater Accra" selected>Greater Accra Region</option>
                        <option value="Ashanti">Ashanti Region</option>
                        <option value="Western">Western Region</option>
                        <option value="Eastern">Eastern Region</option>
                        <option value="Central">Central Region</option>
                        <option value="Volta">Volta Region</option>
                        <option value="Northern">Northern Region</option>
                        <option value="Upper East">Upper East Region</option>
                        <option value="Upper West">Upper West Region</option>
                        <option value="Bono">Bono Region</option>
                        <option value="Bono East">Bono East Region</option>
                        <option value="Ahafo">Ahafo Region</option>
                        <option value="Oti">Oti Region</option>
                        <option value="Savannah">Savannah Region</option>
                        <option value="North East">North East Region</option>
                        <option value="Western North">Western North Region</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        District / Municipality <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="seizureDistrict" name="seizure_district" required
                        placeholder="e.g. Ayawaso West / Tema Metro"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Town / Facility / Terminal <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="seizureTownFacility" name="seizure_town_facility" required
                        placeholder="e.g. KIA Cargo Village - Shed 4"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                        <span>GPS Coordinates</span>
                        <span class="text-[9px] font-mono-code text-slate-400">Lat, Long</span>
                    </label>
                    <div class="relative flex items-center group">
                        <input type="text" id="seizureGps" name="seizure_gps"
                            placeholder="e.g. 5.6052° N, 0.1668° W"
                            class="w-full pl-3.5 pr-9 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono-code font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs text-xs">
                        <button type="button" onclick="getCurrentGpsCoords()" title="Capture current GPS"
                            class="absolute right-2 p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3A8.994 8.994 0 0013 3.06V1h-2v2.06A8.994 8.994 0 003.06 11H1v2h2.06A8.994 8.994 0 0011 20.94V23h2v-2.06A8.994 8.994 0 0020.94 13H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Row 3: Seizing Officer Name, Service Number, Rank -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Seizing Officer Full Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="seizingOfficerName" name="seizing_officer_name" required
                        placeholder="e.g. Det. Insp. Kwabena Boateng"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Officer Service / Staff ID <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="seizingOfficerServiceId" name="seizing_officer_service_id" required
                        placeholder="e.g. NC-78401"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-mono-code font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Officer Rank / Designation <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="seizingOfficerRank" name="seizing_officer_rank" required
                        placeholder="e.g. Senior Narcotics Control Officer"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                </div>
            </div>

            <!-- Row 4: Transporting Officer & Method of Transportation -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Transporting Officer &amp; Escort Detail <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="transportingOfficer" name="transporting_officer" required
                        placeholder="e.g. Officer E. Mensah (Staff ID NC-4102) with 2 Tactical Escorts"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Method of Transportation &amp; Vehicle Plate <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="transportMethod" name="transport_method" required
                        placeholder="e.g. Secured NACOC Evidence Van (Reg. GV-492-24)"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 font-bold outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs">
                </div>
            </div>

            <!-- Row 5: Seizure Circumstances Narrative -->
            <div>
                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                    Seizure Circumstances &amp; Incident Narrative <span class="text-red-600">*</span>
                </label>
                <textarea id="seizureCircumstances" name="seizure_circumstances" rows="3" required
                    placeholder="Provide a comprehensive operational summary: e.g. Parcel flagged during routine dual-view X-ray cargo profiling showing high-density organic mass concealed beneath industrial machine spare parts. K9 sniffer dog alerted positively. No physical owner came forward during courier clearance window..."
                    class="w-full p-3.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-medium outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all shadow-2xs resize-y min-h-[85px]"></textarea>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION C: PHYSICAL PARCEL DETAILS (BEFORE OPENING)                       -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-5">
            <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold shadow-md shadow-purple-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Section C: Physical Parcel Characterization (Pre-Opening)</h3>
                        <p class="text-xs text-slate-500">Exterior waybills, sender/recipient records, visible markings, seals, tamper assessment and initial condition</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-purple-50 text-purple-700 text-[10px] font-mono-code font-bold rounded-lg border border-purple-200 uppercase tracking-wider">
                    Exterior Inspection
                </span>
            </div>

            <!-- Row 1: External Tracking Number, Carrier, Origin & Destination -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Air Waybill / Tracking / Bill of Lading
                    </label>
                    <input type="text" id="parcelTrackingNumber" name="tracking_number"
                        placeholder="e.g. AWB-074-93821092"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Carrier / Courier Company
                    </label>
                    <input type="text" id="parcelCarrier" name="carrier_name"
                        placeholder="e.g. DHL Express / Emirates SkyCargo"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Origin Stated on Packaging <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="parcelOriginStated" name="origin_stated" required
                        placeholder="e.g. Sao Paulo, Brazil (GRU)"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Destination Stated on Packaging <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="parcelDestinationStated" name="destination_stated" required
                        placeholder="e.g. Accra, Ghana (ACC / Kotoka)"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                </div>
            </div>

            <!-- Two-Column Cards: Sender / Consignor Details vs Recipient / Consignee Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- SENDER -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center space-x-2 text-slate-900 font-extrabold text-xs">
                        <span>Sender / Consignor Details (as Stated on Label)</span>
                    </div>
                    <div>
                        <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Sender Name / Entity</label>
                        <input type="text" id="senderName" name="sender_name" placeholder="e.g. Santos Logistics Ltda"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-semibold outline-none focus:border-purple-600">
                    </div>
                    <div>
                        <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Sender Address &amp; Country</label>
                        <input type="text" id="senderAddress" name="sender_address" placeholder="e.g. Rua das Flores 102, Sao Paulo"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-semibold outline-none focus:border-purple-600">
                    </div>
                    <div>
                        <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Sender Phone / Contact</label>
                        <input type="text" id="senderPhone" name="sender_phone" placeholder="e.g. +55 11 98765-4321"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-semibold font-mono-code outline-none focus:border-purple-600">
                    </div>
                </div>

                <!-- RECIPIENT -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center space-x-2 text-slate-900 font-extrabold text-xs">
                        <span>Recipient / Consignee Details (as Stated on Label)</span>
                    </div>
                    <div>
                        <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Recipient Name / Entity</label>
                        <input type="text" id="recipientName" name="recipient_name" placeholder="e.g. K. Adjei Commercial Trading"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-semibold outline-none focus:border-purple-600">
                    </div>
                    <div>
                        <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Delivery Address &amp; City</label>
                        <input type="text" id="recipientAddress" name="recipient_address" placeholder="e.g. Plot 42 Ring Road Central, Accra"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-semibold outline-none focus:border-purple-600">
                    </div>
                    <div>
                        <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Recipient Phone / Contact</label>
                        <input type="text" id="recipientPhone" name="recipient_phone" placeholder="e.g. +233 24 555 0192"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-semibold font-mono-code outline-none focus:border-purple-600">
                    </div>
                </div>
            </div>

            <!-- Row 3: Packaging Type, Colour, Markings, Dimensions & Intake Weight -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Packaging Material &amp; Colour <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="packagingMaterialColor" name="packaging_material_color" required
                        placeholder="e.g. Brown 5-ply corrugated carton box"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Markings, Labels &amp; Stencils
                    </label>
                    <input type="text" id="visibleMarkings" name="visible_markings"
                        placeholder="e.g. 'FRAGILE', barcode label, red arrow sticker"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Outer Dimensions (L x W x H)
                    </label>
                    <input type="text" id="parcelDimensions" name="parcel_dimensions"
                        placeholder="e.g. 45 cm x 30 cm x 25 cm"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Gross Measured Intake Weight <span class="text-red-600">*</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="number" step="0.001" id="intakeGrossWeight" name="intake_gross_weight" required
                            placeholder="e.g. 14.850"
                            class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-extrabold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                        <select id="intakeWeightUnit" name="intake_weight_unit"
                            class="px-3 py-3 bg-slate-100 border border-slate-300 rounded-xl font-bold text-slate-800 text-xs outline-none cursor-pointer">
                            <option value="kg" selected>kg</option>
                            <option value="g">g</option>
                            <option value="lbs">lbs</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 4: Seals: Number & Type of Seals + Seal Serial Numbers -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Number &amp; Type of Security Seals <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="sealsCountAndType" name="seals_count_and_type" required
                        placeholder="e.g. 2 High-Security Cable Seals &amp; 1 Tamper-Evident Void Tape"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Seal Numbers / Serial Codes <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="sealSerialNumbers" name="seal_serial_numbers" required
                        placeholder="e.g. GH-NC-902144, GH-NC-902145"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 text-xs outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-600/20 transition-all shadow-2xs">
                </div>
            </div>

            <!-- Row 5: Visible Irregularities & Tampering Checkboxes -->
            <div>
                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-2">
                    Visible Damage, Leakage, Odour &amp; Irregularities (Check all that apply)
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="irregularities[]" value="Intact No Damage" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">No damage (Pristine)</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="irregularities[]" value="Punctured or Torn" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Torn / Punctured</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="irregularities[]" value="Secondary Re-Taped" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Secondary re-taping</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="irregularities[]" value="Liquid Staining or Leakage" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Leakage / Staining</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="irregularities[]" value="Chemical Pungent Odour" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Pungent / Solvent odor</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="irregularities[]" value="Seal Broken or Missing" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Seal broken / missing</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="irregularities[]" value="X-Ray Density Anomaly" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">X-Ray density anomaly</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="irregularities[]" value="K9 Positive Alert" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">K9 sniffer alert</span>
                    </label>
                </div>
            </div>

            <!-- Row 6: Initial Condition Assessment -->
            <div>
                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-2">
                    Initial Condition Classification <span class="text-red-600">*</span>
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-emerald-500 transition-all flex items-center space-x-3 group">
                        <input type="radio" name="initial_condition" value="Intact" checked class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <div>
                            <div class="font-extrabold text-slate-900 text-xs group-hover:text-emerald-700">Intact</div>
                            <div class="text-[10px] text-slate-500">All seals &amp; packaging pristine</div>
                        </div>
                    </label>

                    <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-amber-500 transition-all flex items-center space-x-3 group">
                        <input type="radio" name="initial_condition" value="Damaged" class="text-amber-600 focus:ring-amber-500 w-4 h-4">
                        <div>
                            <div class="font-extrabold text-slate-900 text-xs group-hover:text-amber-700">Damaged</div>
                            <div class="text-[10px] text-slate-500">Crushed or torn in transit</div>
                        </div>
                    </label>

                    <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-blue-500 transition-all flex items-center space-x-3 group">
                        <input type="radio" name="initial_condition" value="Unsealed" class="text-blue-600 focus:ring-blue-500 w-4 h-4">
                        <div>
                            <div class="font-extrabold text-slate-900 text-xs group-hover:text-blue-700">Unsealed</div>
                            <div class="text-[10px] text-slate-500">No security seal attached</div>
                        </div>
                    </label>

                    <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-red-500 transition-all flex items-center space-x-3 group">
                        <input type="radio" name="initial_condition" value="Suspected Tampering" class="text-red-600 focus:ring-red-500 w-4 h-4">
                        <div>
                            <div class="font-extrabold text-slate-900 text-xs group-hover:text-red-700">Suspected Tampering</div>
                            <div class="text-[10px] text-slate-500">Secondary cuts or seal breach</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION D: FORENSIC RECEIPT AND ACCEPTANCE                                -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-5">
            <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-white text-emerald-600 border border-slate-200/90 flex items-center justify-center font-bold shadow-md shadow-slate-200/60">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Section D: Forensic Receipt, Handover &amp; Acceptance</h3>
                        <p class="text-xs text-slate-500">Accountability checkpoint between seizing enforcement agency and Forensic Department custody</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider">
                    Custody Intake
                </span>
            </div>

            <!-- Officers & Timing -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Submitting Officer Name &amp; ID <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="submittingOfficer" name="submitting_officer" required
                        placeholder="e.g. Officer Daniel Asare (ID: NC-6029)"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Receiving Forensic Officer &amp; Staff ID <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="receivingForensicOfficer" name="receiving_forensic_officer" required
                        value="{{ auth()->user()->fullname ?? 'Senior Forensic Chemist' }} (ID: {{ auth()->user()->service_id ?? 'FS-101' }})"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Actual Receipt Date &amp; Time <span class="text-red-600">*</span>
                    </label>
                    <input type="datetime-local" id="actualReceiptDateTime" name="actual_receipt_date_time" required
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Packaging &amp; Seal Condition on Receipt <span class="text-red-600">*</span>
                    </label>
                    <select id="receiptConditionVerified" name="receipt_condition_verified" required
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all shadow-2xs cursor-pointer">
                        <option value="Seals Verified Matching Form" selected>Seals verified &amp; intact matching handover form</option>
                        <option value="Seals Broken Discrepancy">Seals broken / discrepancy with handover sheet</option>
                        <option value="Unsealed Handover">Delivered unsealed (Urgent forensic custody)</option>
                    </select>
                </div>
            </div>

            <!-- Accompanying Documents Checklist -->
            <div>
                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-2">
                    Accompanying Seizure Forms &amp; Handover Documentation (Check all attached)
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="accompanying_docs[]" value="Form F-10 Handover" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Form F-10 Handover Sheet</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="accompanying_docs[]" value="Seizure Inventory Form" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Field Seizure Inventory</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="accompanying_docs[]" value="Air Waybill Copy" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Air Waybill / Consignment Doc</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="accompanying_docs[]" value="GRA Customs Notice" class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Customs Detainment Notice</span>
                    </label>
                    <label class="flex items-center space-x-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                        <input type="checkbox" name="accompanying_docs[]" value="Court Warrant Order" class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Court Search / Seizure Warrant</span>
                    </label>
                </div>
            </div>

            <!-- Acceptance Decision & Conditional Discrepancy Reason -->
            <div class="space-y-4 pt-2">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-2">
                        Acceptance Decision <span class="text-red-600">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-emerald-500 transition-all flex items-center space-x-3 group"
                            onclick="handleAcceptanceDecisionChange('Accepted')">
                            <input type="radio" name="acceptance_decision" value="Accepted" checked class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs group-hover:text-emerald-700">Accepted</div>
                                <div class="text-[10px] text-slate-500">All seals, numbers and paperwork fully verified</div>
                            </div>
                        </label>

                        <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-amber-500 transition-all flex items-center space-x-3 group"
                            onclick="handleAcceptanceDecisionChange('Accepted with Discrepancy')">
                            <input type="radio" name="acceptance_decision" value="Accepted with Discrepancy" class="text-amber-600 focus:ring-amber-500 w-4 h-4">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs group-hover:text-amber-700">Accepted with Discrepancy</div>
                                <div class="text-[10px] text-slate-500">Weight variance, damaged seal, or minor notation</div>
                            </div>
                        </label>

                        <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-red-500 transition-all flex items-center space-x-3 group"
                            onclick="handleAcceptanceDecisionChange('Rejected')">
                            <input type="radio" name="acceptance_decision" value="Rejected" class="text-red-600 focus:ring-red-500 w-4 h-4">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs group-hover:text-red-700">Rejected / Pending Resolution</div>
                                <div class="text-[10px] text-slate-500">Chain of custody compromised or missing papers</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Discrepancy / Rejection Reason Box (Conditionally Shown) -->
                <div id="discrepancyReasonContainer" class="hidden p-4 bg-amber-50 rounded-xl border border-amber-200 space-y-2">
                    <label class="block text-amber-900 font-extrabold text-[11px] tracking-wider uppercase">
                        Reason for Discrepancy or Rejection <span class="text-red-600">*</span>
                    </label>
                    <textarea id="discrepancyReason" name="discrepancy_reason" rows="2"
                        placeholder="Detail the specific discrepancy (e.g. Handover form listed gross weight as 15.2kg, physical balance reads 14.85kg; seal GH-NC-902144 was loose on arrival...)"
                        class="w-full p-3 bg-white border border-amber-300 rounded-xl text-slate-900 font-medium outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20 text-xs"></textarea>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION E: EXAMINATION AND DISCOVERED CONTENTS                           -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6">
            <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center font-bold shadow-md shadow-teal-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Section E: Forensic Opening, Examination &amp; Discovered Contents</h3>
                        <p class="text-xs text-slate-500">Unsealing protocol, detailed exhibit inventory, photographic record, sampling and resealing</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-1 bg-teal-50 text-teal-700 text-[10px] font-mono-code font-bold rounded-lg border border-teal-200 uppercase tracking-wider">
                        Laboratory Examination
                    </span>
                </div>
            </div>

            <!-- Opening Authorization & Protocol Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Date &amp; Time Opened <span class="text-red-600">*</span>
                    </label>
                    <input type="datetime-local" id="parcelDateTimeOpened" name="date_time_opened" required
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Officer / Analyst Opening Parcel <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="openingOfficer" name="opening_officer" required
                        placeholder="e.g. Dr. K. Quaye, Chief Forensic Chemist"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Opening Authorization / Mandate <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="openingAuthorization" name="opening_authorization" required
                        placeholder="e.g. Section 47 Narcotic Control Act 1019 Warrant"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Condition of Original Seal When Opened <span class="text-red-600">*</span>
                    </label>
                    <select id="originalSealConditionWhenOpened" name="original_seal_condition_when_opened" required
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all shadow-2xs cursor-pointer">
                        <option value="Intact Cut With Shears" selected>Intact (Cut with sterile forensic shears in presence of witnesses)</option>
                        <option value="Compromised Prior to Cutting">Compromised / Tampered prior to cutting</option>
                        <option value="No Seal Present">No seal was attached</option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Discovered Exhibits / Contents Itemizer -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-slate-900 text-sm">Detailed Contents &amp; Discovered Exhibits</h4>
                        <p class="text-[11px] text-slate-500">Record each individual substance, concealment package, or item discovered inside the parcel</p>
                    </div>
                    <button type="button" onclick="addDiscoveredExhibitItem()"
                        class="px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span>Add Discovered Exhibit Item</span>
                    </button>
                </div>

                <!-- Discovered Items Container -->
                <div id="discoveredExhibitsList" class="space-y-3">
                    <!-- Default Initial Item 1 -->
                    <div class="discovered-item p-4 bg-slate-50 rounded-xl border border-slate-200/90 relative space-y-3" data-item-idx="1">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/70">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-teal-100 text-teal-800 font-mono-code font-bold text-[10px] rounded">EXHIBIT #1</span>
                                <span class="font-bold text-slate-800 text-xs">Primary Discovered Exhibit / Narcotic Substance</span>
                            </div>
                            <span class="text-[10px] font-mono-code text-slate-400">Item Index: 1</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Exhibit Ref Number</label>
                                <input type="text" name="exhibits[0][ref]" value="EXH-PCL-01" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-teal-600">
                            </div>

                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Commodity / Exhibit Category</label>
                                <select name="exhibits[0][category]" required onchange="handleExhibitCategoryChange(this)"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-teal-600">
                                    <option value="Narcotics & Controlled Substances" selected>Narcotics &amp; Controlled Substances</option>
                                    <option value="Chemical Precursor">Chemical Precursor / Reagent</option>
                                    <option value="Concealment Packaging">Concealment Packaging / False Cavity</option>
                                    <option value="Digital Device">Digital Device (Phone / Tracker / SIM)</option>
                                    <option value="Document / Currency">Document / Financial Instrument</option>
                                    <option value="Other">Other Exhibit Item</option>
                                </select>
                                <div class="exhibit-category-other-container hidden mt-1.5">
                                    <input type="text" name="exhibits[0][category_other]" placeholder="Specify other exhibit category..."
                                        class="w-full px-2.5 py-1.5 bg-teal-50/60 border border-teal-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-teal-600">
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Quantity / Count</label>
                                <input type="text" name="exhibits[0][quantity]" placeholder="e.g. 5 Compressed Slab Blocks" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-teal-600">
                            </div>

                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Gross Weight &amp; Unit</label>
                                <div class="flex items-center space-x-1.5">
                                    <input type="number" step="0.001" name="exhibits[0][gross_weight]" placeholder="e.g. 5.250" required
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-teal-600">
                                    <select name="exhibits[0][unit]" class="px-2 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-bold text-slate-800">
                                        <option value="kg" selected>kg</option>
                                        <option value="g">g</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Detailed Description &amp; Concealment Method</label>
                                <input type="text" name="exhibits[0][description]" required
                                    placeholder="e.g. Off-white compressed powder enclosed in silver foil, wrapped in black carbon paper and grease layer"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-medium text-xs outline-none focus:border-teal-600">
                            </div>

                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Primary Presumptive Field Reaction</label>
                                <input type="text" name="exhibits[0][presumptive_result]" required
                                    placeholder="e.g. Positive (Scott Reagent - Intense Blue Precipitation / Cocaine)"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-medium text-xs outline-none focus:border-teal-600">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Examination Photographs: Before, During and After -->
            <div class="pt-2">
                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5 flex items-center justify-between">
                    <span>Photographs: Before Opening, De-Concealment Extraction &amp; Final Exhibits</span>
                    <span class="text-[9px] font-mono-code text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-bold uppercase">Before / During / After</span>
                </label>
                <div class="border-2 border-dashed border-slate-300 hover:border-teal-500 rounded-2xl p-4 bg-slate-50/70 hover:bg-teal-50/20 transition-all cursor-pointer relative"
                    onclick="document.getElementById('parcelExamPhotos').click()">
                    <input type="file" id="parcelExamPhotos" multiple accept="image/*" class="hidden" onchange="previewParcelPhotos(event, 'parcelExamPhotosPreview')">
                    <div class="flex flex-col items-center justify-center py-4 text-center">
                        <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center mb-2 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="font-extrabold text-slate-800 text-xs">Upload Step-by-Step Examination Photos</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Include photos of seal cutting, concealment disassembly, exposed contraband, and scale readings</p>
                    </div>
                </div>
                <!-- Thumbnail Preview Grid -->
                <div id="parcelExamPhotosPreview" class="flex flex-wrap gap-3 mt-3 empty:hidden"></div>
            </div>

            <!-- Sampling, Reserved Aliquots & Repackaging / Resealing -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Sample Aliquots Taken &amp; Identifiers
                    </label>
                    <input type="text" id="samplesTakenDetails" name="samples_taken_details"
                        placeholder="e.g. SMP-01A (2.0g for GC-MS Assay)"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Reserved Sample Weight &amp; Safe Storage
                    </label>
                    <input type="text" id="reservedSampleDetails" name="reserved_sample_details"
                        placeholder="e.g. 10.0g stored in Cold Vault C-4"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Repackaging Details
                    </label>
                    <input type="text" id="repackagingDetails" name="repackaging_details"
                        placeholder="e.g. Replaced into NACOC Heavy-Duty Evidence Pouch"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        New Security Seal Serial Number(s) <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="newSealNumbers" name="new_seal_numbers" required
                        placeholder="e.g. GH-NC-RESEAL-90812"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 text-xs outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all shadow-2xs">
                </div>
            </div>

            <!-- Analyst Findings & Remarks -->
            <div>
                <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                    Forensic Findings, Qualitative Summary &amp; Analyst Remarks <span class="text-red-600">*</span>
                </label>
                <textarea id="analystFindings" name="analyst_findings" rows="3" required
                    placeholder="e.g. Preliminary chemical screening indicates the presence of Cocaine Hydrochloride in all 5 slabs. Net weight of suspected narcotic commodity is approximately 5.050 kg. Samples transferred to Laboratory Instrumental Chromatography Unit for confirmatory quantification."
                    class="w-full p-3.5 bg-white border border-slate-300 rounded-xl text-slate-900 font-medium outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 transition-all shadow-2xs resize-y min-h-[85px]"></textarea>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION F: STORAGE AND CHAIN OF CUSTODY                                   -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm space-y-6">
            <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">Section F: Storage, Custody Transfers &amp; Final Disposition</h3>
                        <p class="text-xs text-slate-500">Secured repository location, chronological movements, temporary removals, authorizing officer and closure</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-mono-code font-bold rounded-lg border border-indigo-200 uppercase tracking-wider">
                        Chain of Custody
                    </span>
                </div>
            </div>

            <!-- Vault Location & Person Responsible -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Storage Facility / Area <span class="text-red-600">*</span>
                    </label>
                    <select id="storageFacility" name="storage_facility" required onchange="handleStorageFacilityChange(this.value)"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 transition-all shadow-2xs cursor-pointer">
                        <option value="NACOC Central Evidence Vault" selected>NACOC Central Evidence Vault (Main HQ)</option>
                        <option value="Forensic Laboratory Cold Vault">Forensic Laboratory Cold Storage Vault (Chemistry)</option>
                        <option value="Narcotic Seizure High-Security Safe">Narcotic Seizure High-Security Safe (Locker 1)</option>
                        <option value="Airport Interdiction Temporary Armory">Airport Interdiction Temporary Armory (KIA)</option>
                        <option value="Port Container Inspection Yard">Port Container Inspection Yard (Tema)</option>
                        <option value="Other">Other Secured Storage Facility</option>
                    </select>

                    <!-- Conditional Other Input Box -->
                    <div id="storageFacilityOtherContainer" class="hidden mt-2">
                        <label class="block text-indigo-900 font-bold text-[10px] uppercase mb-1">
                            Specify Other Storage Facility / Location <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="storageFacilityOther" name="storage_facility_other"
                            placeholder="e.g. Regional Command Secured Bunker, Bank of Ghana Bullion Vault..."
                            class="w-full px-3.5 py-2.5 bg-indigo-50/60 border border-indigo-300 rounded-xl text-slate-900 font-bold text-xs outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 shadow-2xs">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Shelf / Locker / Bin Reference <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="storageLocationRef" name="storage_location_ref" required
                        placeholder="e.g. Vault-B / Shelf-04 / Bin-12"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 text-xs outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 transition-all shadow-2xs">
                </div>

                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                        Person Responsible for Custody <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="custodyResponsiblePerson" name="custody_responsible_person" required
                        value="Senior Vault Custodian (Evidence Management Unit)"
                        class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 transition-all shadow-2xs">
                </div>
            </div>

            <!-- Dynamic Chain of Custody Transfer Log Repeater -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-slate-900 text-sm">Chronological Custody Transfer Events</h4>
                        <p class="text-[11px] text-slate-500">Record every physical movement, releasing officer, receiving officer, seal status and reason</p>
                    </div>
                    <button type="button" onclick="addCustodyTransferEvent()"
                        class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span>Log Custody Movement</span>
                    </button>
                </div>

                <div id="custodyTransferEventsList" class="space-y-3">
                    <!-- Default Event 1: Intake Transfer -->
                    <div class="custody-event p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3" data-event-idx="1">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 font-mono-code font-bold text-[10px] rounded">EVENT #1</span>
                                <span class="font-bold text-slate-800 text-xs">Initial Intake Custody Handover</span>
                            </div>
                            <span class="text-[10px] font-mono-code text-slate-400">Intake Transfer</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Transfer Date &amp; Time</label>
                                <input type="datetime-local" name="transfers[0][date_time]" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                            </div>

                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Releasing Officer (Name &amp; ID)</label>
                                <input type="text" name="transfers[0][releasing_officer]" placeholder="e.g. Officer D. Asare (NC-6029)" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                            </div>

                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Receiving Officer (Name &amp; ID)</label>
                                <input type="text" name="transfers[0][receiving_officer]" placeholder="e.g. Analyst E. Mensah (FS-101)" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                            </div>

                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Transfer Reason / Purpose</label>
                                <select name="transfers[0][purpose]" required onchange="handleTransferPurposeChange(this)"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                                    <option value="Initial Intake Handover" selected>Initial Intake Handover</option>
                                    <option value="Laboratory Analysis">Laboratory Chemical Analysis</option>
                                    <option value="Vault Storage">Vault Secured Archival</option>
                                    <option value="Court Production">Court Evidence Tender</option>
                                    <option value="Re-analysis / Re-test">Re-analysis / Re-inspection</option>
                                    <option value="Destruction Disposal">Authorized Destruction / Disposal</option>
                                    <option value="Other">Other Transfer Purpose / Reason</option>
                                </select>
                                <div class="transfer-purpose-other-container hidden mt-1.5">
                                    <input type="text" name="transfers[0][purpose_other]" placeholder="Specify other transfer purpose..."
                                        class="w-full px-2.5 py-1.5 bg-indigo-50/60 border border-indigo-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Seal Number Verified at Handover</label>
                                <input type="text" name="transfers[0][seal_number]" placeholder="e.g. GH-NC-902144 / GH-NC-902145" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-indigo-600">
                            </div>

                            <div>
                                <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Condition &amp; Seal Status at Handover</label>
                                <select name="transfers[0][condition]" required
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                                    <option value="Intact Verified" selected>Seals intact &amp; numbers verified matching log</option>
                                    <option value="Resealed Under New Number">Resealed under new verified security number</option>
                                    <option value="Discrepancy Noted">Discrepancy / irregularity noted in remarks</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Temporary Removal & Return Section -->
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="tempRemovalToggle" onchange="toggleTempRemovalFields(this)"
                        class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                    <label for="tempRemovalToggle" class="text-xs font-extrabold text-slate-800 cursor-pointer">
                        Currently Flagged for Temporary Removal (e.g. Active Court Production or External Special Assay)
                    </label>
                </div>

                <div id="tempRemovalFields" class="hidden grid grid-cols-1 md:grid-cols-3 gap-3 pt-2">
                    <div>
                        <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Temporary Removal Date</label>
                        <input type="date" id="tempRemovalDate" name="temp_removal_date"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-semibold outline-none focus:border-indigo-600">
                    </div>
                    <div>
                        <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Expected Return Date</label>
                        <input type="date" id="tempExpectedReturnDate" name="temp_expected_return_date"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-semibold outline-none focus:border-indigo-600">
                    </div>
                    <div>
                        <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Authorized Escorting Custodian</label>
                        <input type="text" id="tempEscortCustodian" name="temp_escort_custodian" placeholder="e.g. Lead Evidence Custodian"
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 text-xs font-semibold outline-none focus:border-indigo-600">
                    </div>
                </div>
            </div>

            <!-- Final Disposition -->
            <div class="pt-2 space-y-4">
                <div>
                    <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-2">
                        Final Disposition Status <span class="text-red-600">*</span>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-indigo-500 transition-all flex items-center space-x-2.5 group">
                            <input type="radio" name="final_disposition" value="Stored in Custody" checked class="text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs group-hover:text-indigo-700">Stored in Custody</div>
                                <div class="text-[9px] text-slate-500">Active secure evidence</div>
                            </div>
                        </label>

                        <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-blue-500 transition-all flex items-center space-x-2.5 group">
                            <input type="radio" name="final_disposition" value="Court Submission" class="text-blue-600 focus:ring-blue-500 w-4 h-4">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs group-hover:text-blue-700">Court Submission</div>
                                <div class="text-[9px] text-slate-500">Tendered in court</div>
                            </div>
                        </label>

                        <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-emerald-500 transition-all flex items-center space-x-2.5 group">
                            <input type="radio" name="final_disposition" value="Returned to Owner" class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs group-hover:text-emerald-700">Returned</div>
                                <div class="text-[9px] text-slate-500">Released to owner</div>
                            </div>
                        </label>

                        <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-red-500 transition-all flex items-center space-x-2.5 group">
                            <input type="radio" name="final_disposition" value="Authorized Disposal" class="text-red-600 focus:ring-red-500 w-4 h-4">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs group-hover:text-red-700">Authorized Disposal</div>
                                <div class="text-[9px] text-slate-500">Incinerated / destroyed</div>
                            </div>
                        </label>

                        <label class="p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-purple-500 transition-all flex items-center space-x-2.5 group">
                            <input type="radio" name="final_disposition" value="Transferred External" class="text-purple-600 focus:ring-purple-500 w-4 h-4">
                            <div>
                                <div class="font-extrabold text-slate-900 text-xs group-hover:text-purple-700">Transferred</div>
                                <div class="text-[9px] text-slate-500">External agency/lab</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                            Authorizing Officer / Magistrate Name
                        </label>
                        <input type="text" id="dispositionAuthorizingOfficer" name="disposition_authorizing_officer"
                            placeholder="e.g. Justice Samuel Addo / Director General"
                            class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 transition-all shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                            Supporting Document / Court Order Reference
                        </label>
                        <input type="text" id="dispositionSupportingDoc" name="disposition_supporting_doc"
                            placeholder="e.g. High Court Order HC/ACC/2026/891"
                            class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-mono-code font-bold text-slate-900 text-xs outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 transition-all shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-slate-800 font-extrabold text-[11px] tracking-wider uppercase mb-1.5">
                            Disposition / Closure Date
                        </label>
                        <input type="date" id="dispositionClosureDate" name="disposition_closure_date"
                            class="w-full px-3.5 py-3 bg-white border border-slate-300 rounded-xl font-bold text-slate-900 text-xs outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 transition-all shadow-2xs">
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM ACTION BUTTONS -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <button type="button" onclick="switchBackToStandardDocket()" 
                    class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all cursor-pointer">
                    &larr; Switch Back to Standard Suspect Docket
                </button>
                <button type="button" onclick="resetCargoParcelForm()" 
                    class="px-5 py-3 bg-slate-100 hover:bg-red-50 hover:text-red-600 text-slate-600 font-bold text-xs rounded-xl transition-all cursor-pointer">
                    Reset Parcel Form
                </button>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" onclick="handleCargoParcelPreviewDraft()" 
                    class="px-5 py-3 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-sm transition-all cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Preview Parcel Summary</span>
                </button>
                <button type="submit" 
                    class="px-7 py-3 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-amber-600/30 transition-all cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Register Cargo / Parcel Docket</span>
                </button>
            </div>
        </div>

    </form>
</div>

<!-- CARGO PARCEL FRONTEND SCRIPTS -->
<script>
    // Unique Discovered Items & Custody Events Counters
    let discoveredItemCounter = 1;
    let custodyEventCounter = 1;

    // Generate unique parcel reference number e.g. PRC-2026-7842 automatically
    function generateUniqueParcelRef() {
        const year = new Date().getFullYear();
        const rand = Math.floor(1000 + Math.random() * 9000);
        return `PRC-${year}-${rand}`;
    }

    function assignNewParcelRef() {
        const ref = generateUniqueParcelRef();
        const input = document.getElementById('parcelReferenceNumber');
        const badge = document.getElementById('bannerParcelRef');
        if (input) input.value = ref;
        if (badge) badge.textContent = ref;
        return ref;
    }

    // Toggle Cargo/Parcel form vs standard arrestDocketForm
    window.handleCargoParcelOperationTypeSwitch = function(val) {
        const standardForm = document.getElementById('arrestDocketForm');
        const cargoContainer = document.getElementById('cargoParcelFormContainer');
        const caseTypeBadge = document.getElementById('caseTypeBadge');

        const isCargo = (val === 'Cargo/Parcel');

        if (isCargo) {
            if (standardForm) standardForm.classList.add('hidden');
            if (cargoContainer) {
                cargoContainer.classList.remove('hidden');
                // Automatically assign unique reference for each new parcel
                const refInput = document.getElementById('parcelReferenceNumber');
                if (refInput && !refInput.value) {
                    assignNewParcelRef();
                }
                const now = new Date();
                const nowStr = now.toISOString().slice(0, 16);
                const dtReceived = document.getElementById('parcelDateTimeReceived');
                const dtSeizure = document.getElementById('parcelSeizureDateTime');
                const dtReceipt = document.getElementById('actualReceiptDateTime');
                const dtOpened = document.getElementById('parcelDateTimeOpened');

                if (dtReceived && !dtReceived.value) dtReceived.value = nowStr;
                if (dtSeizure && !dtSeizure.value) dtSeizure.value = nowStr;
                if (dtReceipt && !dtReceipt.value) dtReceipt.value = nowStr;
                if (dtOpened && !dtOpened.value) dtOpened.value = nowStr;

                cargoContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            if (caseTypeBadge) {
                caseTypeBadge.textContent = 'Cargo / Parcel Mode';
                caseTypeBadge.className = 'px-2.5 py-1 bg-amber-50 text-amber-700 text-[10px] font-mono-code font-bold rounded-lg border border-amber-200 uppercase tracking-wider';
            }
        } else {
            if (cargoContainer) cargoContainer.classList.add('hidden');
            if (standardForm) standardForm.classList.remove('hidden');
            if (caseTypeBadge) {
                caseTypeBadge.textContent = 'Single Person Mode';
                caseTypeBadge.className = 'px-2.5 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-mono-code font-bold rounded-lg border border-emerald-200 uppercase tracking-wider';
            }
        }
    };

    function switchBackToStandardDocket() {
        if (typeof selectOperationType === 'function') {
            selectOperationType('', 'Select Operation Type...', null);
        } else {
            window.handleCargoParcelOperationTypeSwitch('');
        }
        const standardForm = document.getElementById('arrestDocketForm');
        if (standardForm) {
            standardForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Acceptance Decision Conditional Visibility
    function handleAcceptanceDecisionChange(decision) {
        const box = document.getElementById('discrepancyReasonContainer');
        if (!box) return;
        if (decision === 'Accepted with Discrepancy' || decision === 'Rejected') {
            box.classList.remove('hidden');
            const textarea = document.getElementById('discrepancyReason');
            if (textarea) textarea.setAttribute('required', 'required');
        } else {
            box.classList.add('hidden');
            const textarea = document.getElementById('discrepancyReason');
            if (textarea) textarea.removeAttribute('required');
        }
    }

    // Temporary Removal Conditional Visibility
    function toggleTempRemovalFields(checkbox) {
        const fields = document.getElementById('tempRemovalFields');
        if (fields) {
            if (checkbox.checked) {
                fields.classList.remove('hidden');
            } else {
                fields.classList.add('hidden');
            }
        }
    }

    // Geolocation helper
    function getCurrentGpsCoords() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude.toFixed(6);
                    const lng = position.coords.longitude.toFixed(6);
                    const input = document.getElementById('seizureGps');
                    if (input) {
                        input.value = `${lat}° N, ${lng}° W`;
                    }
                },
                (error) => {
                    alert('Unable to retrieve GPS coordinates automatically. Please input manually.');
                }
            );
        } else {
            alert('Geolocation is not supported by your browser.');
        }
    }

    // Photo Preview Helper
    function previewParcelPhotos(event, previewContainerId) {
        const container = document.getElementById(previewContainerId);
        if (!container) return;
        const files = event.target.files;
        if (!files || files.length === 0) return;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.startsWith('image/')) continue;
            const reader = new FileReader();
            reader.onload = function(e) {
                const wrapper = document.createElement('div');
                wrapper.className = 'relative w-24 h-24 rounded-xl overflow-hidden border-2 border-slate-200 shadow-xs group';
                wrapper.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <button type="button" onclick="this.parentElement.remove()" title="Remove Photo"
                        class="absolute top-1 right-1 w-5 h-5 bg-red-600/90 text-white rounded-full flex items-center justify-center text-[10px] opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                        &times;
                    </button>
                    <span class="absolute bottom-1 left-1 px-1 bg-black/60 text-white text-[8px] font-mono-code rounded max-w-[80px] truncate">
                        ${file.name}
                    </span>
                `;
                container.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        }
    }

    // Dynamic Exhibit Item in Section E
    function addDiscoveredExhibitItem() {
        discoveredItemCounter++;
        const idx = discoveredItemCounter;
        const container = document.getElementById('discoveredExhibitsList');
        if (!container) return;

        const card = document.createElement('div');
        card.className = 'discovered-item p-4 bg-slate-50 rounded-xl border border-slate-200/90 relative space-y-3';
        card.setAttribute('data-item-idx', idx);

        card.innerHTML = `
            <div class="flex items-center justify-between pb-2 border-b border-slate-200/70">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-teal-100 text-teal-800 font-mono-code font-bold text-[10px] rounded">EXHIBIT #${idx}</span>
                    <span class="font-bold text-slate-800 text-xs">Discovered Exhibit Item</span>
                </div>
                <button type="button" onclick="this.closest('.discovered-item').remove()" title="Remove Exhibit Item"
                    class="text-red-500 hover:text-red-700 font-bold text-xs flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Remove Item</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Exhibit Ref Number</label>
                    <input type="text" name="exhibits[${idx - 1}][ref]" value="EXH-PCL-${idx < 10 ? '0' + idx : idx}" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-teal-600">
                </div>

                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Commodity / Exhibit Category</label>
                    <select name="exhibits[${idx - 1}][category]" required onchange="handleExhibitCategoryChange(this)"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-teal-600">
                        <option value="Narcotics & Controlled Substances" selected>Narcotics &amp; Controlled Substances</option>
                        <option value="Chemical Precursor">Chemical Precursor / Reagent</option>
                        <option value="Concealment Packaging">Concealment Packaging / False Cavity</option>
                        <option value="Digital Device">Digital Device (Phone / Tracker / SIM)</option>
                        <option value="Document / Currency">Document / Financial Instrument</option>
                        <option value="Other">Other Exhibit Item</option>
                    </select>
                    <div class="exhibit-category-other-container hidden mt-1.5">
                        <input type="text" name="exhibits[${idx - 1}][category_other]" placeholder="Specify other exhibit category..."
                            class="w-full px-2.5 py-1.5 bg-teal-50/60 border border-teal-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-teal-600">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Quantity / Count</label>
                    <input type="text" name="exhibits[${idx - 1}][quantity]" placeholder="e.g. 2 Foil Packets" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-teal-600">
                </div>

                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Gross Weight &amp; Unit</label>
                    <div class="flex items-center space-x-1.5">
                        <input type="number" step="0.001" name="exhibits[${idx - 1}][gross_weight]" placeholder="e.g. 1.200" required
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-teal-600">
                        <select name="exhibits[${idx - 1}][unit]" class="px-2 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-bold text-slate-800">
                            <option value="kg" selected>kg</option>
                            <option value="g">g</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Detailed Description &amp; Concealment Method</label>
                    <input type="text" name="exhibits[${idx - 1}][description]" required
                        placeholder="e.g. White crystalline chunks concealed within double-walled thermos flask"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-medium text-xs outline-none focus:border-teal-600">
                </div>

                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Primary Presumptive Field Reaction</label>
                    <input type="text" name="exhibits[${idx - 1}][presumptive_result]" required
                        placeholder="e.g. Positive (Marquis - Deep Purple / Heroin)"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-medium text-xs outline-none focus:border-teal-600">
                </div>
            </div>
        `;

        container.appendChild(card);
    }

    // Dynamic Custody Transfer Event in Section F
    function addCustodyTransferEvent() {
        custodyEventCounter++;
        const idx = custodyEventCounter;
        const container = document.getElementById('custodyTransferEventsList');
        if (!container) return;

        const card = document.createElement('div');
        card.className = 'custody-event p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3';
        card.setAttribute('data-event-idx', idx);

        const nowStr = new Date().toISOString().slice(0, 16);

        card.innerHTML = `
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 font-mono-code font-bold text-[10px] rounded">EVENT #${idx}</span>
                    <span class="font-bold text-slate-800 text-xs">Chain of Custody Transfer Movement</span>
                </div>
                <button type="button" onclick="this.closest('.custody-event').remove()" title="Remove Movement Event"
                    class="text-red-500 hover:text-red-700 font-bold text-xs flex items-center gap-1 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Remove Event</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Transfer Date &amp; Time</label>
                    <input type="datetime-local" name="transfers[${idx - 1}][date_time]" value="${nowStr}" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                </div>

                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Releasing Officer (Name &amp; ID)</label>
                    <input type="text" name="transfers[${idx - 1}][releasing_officer]" placeholder="e.g. Officer Name &amp; ID" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                </div>

                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Receiving Officer (Name &amp; ID)</label>
                    <input type="text" name="transfers[${idx - 1}][receiving_officer]" placeholder="e.g. Receiving Officer Name &amp; ID" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                </div>

                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Transfer Reason / Purpose</label>
                    <select name="transfers[${idx - 1}][purpose]" required onchange="handleTransferPurposeChange(this)"
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                        <option value="Laboratory Analysis">Laboratory Chemical Analysis</option>
                        <option value="Vault Storage">Vault Secured Archival</option>
                        <option value="Court Production">Court Evidence Tender</option>
                        <option value="Re-analysis / Re-test">Re-analysis / Re-inspection</option>
                        <option value="Destruction Disposal">Authorized Destruction / Disposal</option>
                        <option value="Other">Other Transfer Purpose / Reason</option>
                    </select>
                    <div class="transfer-purpose-other-container hidden mt-1.5">
                        <input type="text" name="transfers[${idx - 1}][purpose_other]" placeholder="Specify other transfer purpose..."
                            class="w-full px-2.5 py-1.5 bg-indigo-50/60 border border-indigo-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Seal Number Verified at Handover</label>
                    <input type="text" name="transfers[${idx - 1}][seal_number]" placeholder="e.g. New Seal Number" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-mono-code font-bold text-xs outline-none focus:border-indigo-600">
                </div>

                <div>
                    <label class="block text-slate-600 font-bold text-[10px] uppercase mb-1">Condition &amp; Seal Status at Handover</label>
                    <select name="transfers[${idx - 1}][condition]" required
                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-bold text-xs outline-none focus:border-indigo-600">
                        <option value="Intact Verified" selected>Seals intact &amp; numbers verified matching log</option>
                        <option value="Resealed Under New Number">Resealed under new verified security number</option>
                        <option value="Discrepancy Noted">Discrepancy / irregularity noted in remarks</option>
                    </select>
                </div>
            </div>
        `;

        container.appendChild(card);
    }

    // Handlers for "Other" conditional input fields across dropdowns
    function handleParcelCategoryChange(val) {
        const box = document.getElementById('parcelCategoryOtherContainer');
        const input = document.getElementById('parcelCategoryOther');
        if (!box) return;
        if (val === 'Other') {
            box.classList.remove('hidden');
            if (input) {
                input.setAttribute('required', 'required');
                input.focus();
            }
        } else {
            box.classList.add('hidden');
            if (input) {
                input.removeAttribute('required');
                input.value = '';
            }
        }
    }

    function handleSeizingUnitChange(val) {
        const box = document.getElementById('seizingUnitOtherContainer');
        const input = document.getElementById('seizingUnitOther');
        if (!box) return;
        if (val === 'Other' || val === 'Other Taskforce') {
            box.classList.remove('hidden');
            if (input) {
                input.setAttribute('required', 'required');
                input.focus();
            }
        } else {
            box.classList.add('hidden');
            if (input) {
                input.removeAttribute('required');
                input.value = '';
            }
        }
    }

    function handleExhibitCategoryChange(selectEl) {
        const parent = selectEl.closest('div');
        if (!parent) return;
        const otherBox = parent.querySelector('.exhibit-category-other-container');
        const otherInput = otherBox ? otherBox.querySelector('input') : null;
        if (!otherBox) return;
        if (selectEl.value === 'Other') {
            otherBox.classList.remove('hidden');
            if (otherInput) {
                otherInput.setAttribute('required', 'required');
                otherInput.focus();
            }
        } else {
            otherBox.classList.add('hidden');
            if (otherInput) {
                otherInput.removeAttribute('required');
                otherInput.value = '';
            }
        }
    }

    function handleStorageFacilityChange(val) {
        const box = document.getElementById('storageFacilityOtherContainer');
        const input = document.getElementById('storageFacilityOther');
        if (!box) return;
        if (val === 'Other') {
            box.classList.remove('hidden');
            if (input) {
                input.setAttribute('required', 'required');
                input.focus();
            }
        } else {
            box.classList.add('hidden');
            if (input) {
                input.removeAttribute('required');
                input.value = '';
            }
        }
    }

    function handleTransferPurposeChange(selectEl) {
        const parent = selectEl.closest('div');
        if (!parent) return;
        const otherBox = parent.querySelector('.transfer-purpose-other-container');
        const otherInput = otherBox ? otherBox.querySelector('input') : null;
        if (!otherBox) return;
        if (selectEl.value === 'Other') {
            otherBox.classList.remove('hidden');
            if (otherInput) {
                otherInput.setAttribute('required', 'required');
                otherInput.focus();
            }
        } else {
            otherBox.classList.add('hidden');
            if (otherInput) {
                otherInput.removeAttribute('required');
                otherInput.value = '';
            }
        }
    }

    // Reset Cargo / Parcel Form
    function resetCargoParcelForm() {
        if (confirm('Are you sure you want to reset all entered cargo and parcel fields?')) {
            const form = document.getElementById('cargoParcelForm');
            if (form) form.reset();
            assignNewParcelRef();
            const previews = ['parcelIntakePhotosPreview', 'parcelExamPhotosPreview'];
            previews.forEach(pId => {
                const el = document.getElementById(pId);
                if (el) el.innerHTML = '';
            });
            handleAcceptanceDecisionChange('Accepted');

            // Hide all other containers
            const otherContainers = document.querySelectorAll('#parcelCategoryOtherContainer, #seizingUnitOtherContainer, #storageFacilityOtherContainer, .exhibit-category-other-container, .transfer-purpose-other-container');
            otherContainers.forEach(c => c.classList.add('hidden'));
        }
    }

    // Form Submit handler
    function handleCargoParcelSubmit() {
        const ref = document.getElementById('parcelReferenceNumber')?.value || 'PRC-2026';
        alert(`Cargo / Parcel Forensic Docket [${ref}] validated successfully!\n\nSections A through F:\n- Parcel Registration & Intake\n- Seizure Circumstances & Origin\n- Physical Pre-Opening Characterization\n- Forensic Receipt & Acceptance\n- Examination & Discovered Exhibits\n- Chain of Custody & Vault Repository`);
    }

    function handleCargoParcelPreviewDraft() {
        const ref = document.getElementById('parcelReferenceNumber')?.value || 'PRC-2026';
        const cat = document.getElementById('parcelCategory')?.value || 'N/A';
        const weight = document.getElementById('intakeGrossWeight')?.value || '0.00';
        const unit = document.getElementById('intakeWeightUnit')?.value || 'kg';
        const seal = document.getElementById('sealSerialNumbers')?.value || 'N/A';
        const status = document.getElementById('parcelStatus')?.value || 'Received';

        alert(`--- CARGO / PARCEL DOCKET SUMMARY ---\n\nReference: ${ref}\nCategory: ${cat}\nStatus: ${status}\nGross Weight: ${weight} ${unit}\nIntact Seal(s): ${seal}\nExhibits Logged: ${document.querySelectorAll('.discovered-item').length}\nCustody Transfers: ${document.querySelectorAll('.custody-event').length}\n\nReady for forensic processing.`);
    }

    // Auto-listen to changes on hidden operationTypeSelect if modified by other scripts
    document.addEventListener('DOMContentLoaded', function() {
        const hiddenInput = document.getElementById('operationTypeSelect');
        if (hiddenInput && hiddenInput.value === 'Cargo/Parcel') {
            window.handleCargoParcelOperationTypeSwitch('Cargo/Parcel');
        }
    });
</script>
