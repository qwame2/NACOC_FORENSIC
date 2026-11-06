{{--
    Partial: partials/lab_examination_result_card.blade.php
    Description: Legacy alias partial delegating to partials/lab-examination-card.blade.php.
    Required Inputs (@props):
      - $labExam (array): Laboratory examination result array.
      - $labHeadingName (string): Display name of the suspect or joint owners.
      - $case (object|null): The ForensicCase instance.
--}}@props([
    'labExam' => [],
    'labHeadingName' => 'Primary Suspect',
    'case' => null,
])
@include('partials.lab-examination-card', [
    'labExam' => isset($labExam['card_has_lab_data'])
        ? $labExam
        : app(\App\Services\CaseLabExamResolver::class)->formatLabExamForCard(
            $labExam ?: ($sDossierLabExam ?? []),
            $case ?? null,
            $labHeadingName ?: ($labExamHeadingName ?? ($sName ?? 'Primary Suspect')),
        ),
    'labHeadingName' => $labHeadingName ?: ($labExamHeadingName ?? ($sName ?? 'Primary Suspect')),
    'case' => $case ?? null,
])
