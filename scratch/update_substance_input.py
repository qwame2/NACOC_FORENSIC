import re

def update_create_blade():
    path = r'c:\xampp\htdocs\ForensicNacoc\resources\views\Lab_Analysis\create.blade.php'
    with open(path, 'r', encoding='utf-8') as f:
        code = f.read()

    old_badge = """<span
                                                                class="text-[9px] font-mono-code font-bold text-emerald-700 bg-emerald-100/90 px-1.5 py-0.5 rounded border border-emerald-200 uppercase">AUTO-FETCHED</span>"""
    new_badge = """<span
                                                                class="text-[9px] font-mono-code font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 uppercase">EDITABLE SPECIFICATION</span>"""

    old_input = """<input type="text"
                                                            id="confirmatorySubstanceIdentification"
                                                            name="confirmatory_substance_identification"
                                                            readonly placeholder="Fetched from drug manifest"
                                                            class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-sans cursor-not-allowed">"""

    new_input = """<input type="text"
                                                            id="confirmatorySubstanceIdentification"
                                                            name="confirmatory_substance_identification"
                                                            placeholder="Enter substance identification (e.g. Heroin, Cocaine, Cannabis)"
                                                            oninput="this.setAttribute('data-user-edited', 'true')"
                                                            onchange="this.setAttribute('data-user-edited', 'true')"
                                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans">"""

    if old_badge in code:
        code = code.replace(old_badge, new_badge, 1)
        print('create.blade.php: Badge replaced')
    else:
        print('create.blade.php: Badge not found')

    if old_input in code:
        code = code.replace(old_input, new_input, 1)
        print('create.blade.php: Input replaced')
    else:
        print('create.blade.php: Input not found')

    with open(path, 'w', encoding='utf-8') as f:
        f.write(code)

def update_review_form():
    path = r'c:\xampp\htdocs\ForensicNacoc\resources\views\partials\examination_information_form.blade.php'
    with open(path, 'r', encoding='utf-8') as f:
        code = f.read()

    old_input = """<input type="text"
                                        id="confirmatorySubstanceIdentificationReview"
                                        name="confirmatory_substance_identification"
                                        value="{{ $savedLabSubstanceId ?: ($labSubstanceId ?: ($case->confirmatory_substance_identification ?: '')) }}"
                                        readonly placeholder="Fetched from drug manifest"
                                        class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs outline-none shadow-2xs font-sans cursor-not-allowed">"""

    new_input = """<input type="text"
                                        id="confirmatorySubstanceIdentificationReview"
                                        name="confirmatory_substance_identification"
                                        value="{{ $savedLabSubstanceId ?: ($labSubstanceId ?: ($case->confirmatory_substance_identification ?: '')) }}"
                                        placeholder="Enter substance identification (e.g. Heroin, Cocaine, Cannabis)"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl font-extrabold text-slate-900 text-xs focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 shadow-2xs font-sans">"""

    if old_input in code:
        code = code.replace(old_input, new_input, 1)
        print('examination_information_form.blade.php: Input replaced')
    else:
        print('examination_information_form.blade.php: Input not found')

    with open(path, 'w', encoding='utf-8') as f:
        f.write(code)

if __name__ == '__main__':
    update_create_blade()
    update_review_form()
