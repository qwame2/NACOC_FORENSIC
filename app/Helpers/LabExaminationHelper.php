<?php

if (!function_exists('renderExaminationInformationForm')) {
    /**
     * Render the Lab Analysis Examination Information form.
     * Used exclusively within the Lab Analysis show_review page.
     * Returns an empty string if not in Lab Analysis unit context.
     *
     * @param bool|null $isLabAnalysis
     * @param array $viewData
     * @return string
     */
    function renderExaminationInformationForm(?bool $isLabAnalysis = null, array $viewData = []): string
    {
        if ($isLabAnalysis === null) {
            $isLabAnalysis = (bool) ($viewData['isLabAnalysis'] ?? false);
        }

        if (!$isLabAnalysis) {
            return '';
        }

        return view('partials.examination_information_form', $viewData)->render();
    }
}
