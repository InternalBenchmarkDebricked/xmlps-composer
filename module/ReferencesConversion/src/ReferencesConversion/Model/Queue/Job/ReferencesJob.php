<?php

namespace ReferencesConversion\Model\Queue\Job;

use Manager\Model\Queue\Job\AbstractQueueJob;
use Manager\Entity\Job;

/**
 * Parses References from NLM XML document
 */
class ReferencesJob extends AbstractQueueJob
{
    /**
     * Parse references
     *
     * @param Job $job
     * @return Job $job
     */
    public function process(Job $job)
    {
        $references = $this->sm->get('ReferencesConversion\Model\Converter\References');

        // Fetch the document to convert
        if ($job->inputFileFormat == JOB_INPUT_TYPE_PDF) {
            $xmlDocument =
                $job->getStageDocument(JOB_CONVERSION_STAGE_PDF_EXTRACT);
        } else {
            $xmlDocument =
                $job->getStageDocument(JOB_CONVERSION_STAGE_NLMXML);
        }
        if (!$xmlDocument) {
            throw new \Exception('Couldn\'t find the stage document');
        }

        // Parse the references
        $outputFile = $job->getDocumentPath() . '/document.bib';
        $parsCitReferencesFile = $job->getDocumentPath() . '/references/parsCit.txt';
        $references->setInputFile($xmlDocument->path);
        $references->setParsCitReferencesFilePath($parsCitReferencesFile);
        $references->setOutputDirectory($job->getDocumentPath());
        $references->setOutputFile($outputFile);
        $references->convert();

        if (file_exists($parsCitReferencesFile)) {
            $job->conversionStage = JOB_CONVERSION_STAGE_REFERENCES;
        }
        else {
            $job->referenceParsingSuccess = true;
            $job->conversionStage = JOB_CONVERSION_STAGE_BIBTEX;
        }

        if (!$references->getStatus()) {
            $job->status = JOB_STATUS_FAILED;
            return $job;
        }

        $documentDAO = $this->sm->get('Manager\Model\DAO\DocumentDAO');
        $referenceBibtexDocument = $documentDAO->getInstance();
        $referenceBibtexDocument->path = $outputFile;
        $referenceBibtexDocument->job = $job;
        $referenceBibtexDocument->conversionStage = JOB_CONVERSION_STAGE_REFERENCES;

        $job->documents[] = $referenceBibtexDocument;

        return $job;
    }
}
