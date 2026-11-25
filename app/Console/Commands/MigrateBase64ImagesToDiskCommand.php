<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MediaStorageService;
use App\Models\SingleSuspectCase;
use App\Models\MultipleSuspectCase;
use App\Models\CaseSuspect;
use App\Models\StaffCaseSubmission;

class MigrateBase64ImagesToDiskCommand extends Command
{
    protected $signature = 'forensic:migrate-images-to-disk';
    protected $description = 'Converts all embedded Base64 mugshots and evidence images in the database into partitioned files on disk, freeing database storage.';

    public function handle(MediaStorageService $mediaService)
    {
        $this->info("Scanning database for Base64 images to offload to disk...");

        $convertedMugshots = 0;
        $convertedEvidence = 0;

        // 1. StaffCaseSubmission
        $submissions = StaffCaseSubmission::all();
        foreach ($submissions as $sub) {
            $payload = is_array($sub->payload) ? $sub->payload : [];
            $jsonBefore = json_encode($payload, JSON_UNESCAPED_SLASHES);
            if (str_contains($jsonBefore, 'data:image')) {
                $caseNo = $sub->case_number_preview ?? ('SUB-' . $sub->id);
                $updatedPayload = $mediaService->processPayloadImages($payload, $caseNo);
                $sub->payload = $updatedPayload;
                $sub->save();
                $convertedMugshots++;
                $this->line("Migrated images in StaffCaseSubmission #{$sub->id} ({$caseNo})");
            }
        }

        // 2. SingleSuspectCase
        $singleCases = SingleSuspectCase::all();
        foreach ($singleCases as $sc) {
            $dirty = false;
            $caseNo = $sc->case_number ?? ('SSC-' . $sc->id);

            foreach (['mugshot_front', 'mugshot_left', 'mugshot_right'] as $angle) {
                if ($mediaService->isBase64Image($sc->{$angle})) {
                    $sc->{$angle} = $mediaService->processMugshot($sc->{$angle}, $caseNo, $angle, 0);
                    $dirty = true;
                    $convertedMugshots++;
                }
            }

            if (!empty($sc->docket_data)) {
                $docket = is_array($sc->docket_data) ? $sc->docket_data : (json_decode($sc->docket_data, true) ?: []);
                $jsonBefore = json_encode($docket);
                if (str_contains($jsonBefore, 'data:image/')) {
                    $docket = $mediaService->processDocketDataImages($docket, $caseNo);
                    $sc->docket_data = $docket;
                    $dirty = true;
                }
            }

            if ($dirty) {
                $sc->save();
                $this->line("Migrated images in SingleSuspectCase {$caseNo}");
            }
        }

        // 3. CaseSuspect
        $suspects = CaseSuspect::all();
        foreach ($suspects as $idx => $cs) {
            $dirty = false;
            $caseNo = 'SUSPECT-' . $cs->id;

            foreach (['mugshot_front', 'mugshot_left', 'mugshot_right'] as $angle) {
                if ($mediaService->isBase64Image($cs->{$angle})) {
                    $cs->{$angle} = $mediaService->processMugshot($cs->{$angle}, $caseNo, $angle, $idx);
                    $dirty = true;
                    $convertedMugshots++;
                }
            }

            if ($dirty) {
                $cs->save();
                $this->line("Migrated images in CaseSuspect #{$cs->id}");
            }
        }

        // 4. MultipleSuspectCase
        $multiCases = MultipleSuspectCase::all();
        foreach ($multiCases as $mc) {
            if (!empty($mc->docket_data)) {
                $docket = is_array($mc->docket_data) ? $mc->docket_data : (json_decode($mc->docket_data, true) ?: []);
                $jsonBefore = json_encode($docket);
                if (str_contains($jsonBefore, 'data:image/')) {
                    $caseNo = $mc->case_number ?? ('MSC-' . $mc->id);
                    $docket = $mediaService->processDocketDataImages($docket, $caseNo);
                    $mc->docket_data = $docket;
                    $mc->save();
                    $this->line("Migrated docket images in MultipleSuspectCase {$caseNo}");
                }
            }
        }

        $this->info("Migration completed successfully!");
        $this->info("Total converted mugshot & evidence images offloaded to disk: {$convertedMugshots}");
        return 0;
    }
}
