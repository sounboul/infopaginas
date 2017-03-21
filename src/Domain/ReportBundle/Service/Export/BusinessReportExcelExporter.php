<?php

namespace Domain\ReportBundle\Service\Export;

use Domain\BusinessBundle\Entity\BusinessProfile;
use Domain\ReportBundle\Manager\AdUsageReportManager;
use Domain\ReportBundle\Manager\BusinessOverviewReportManager;
use Domain\ReportBundle\Manager\KeywordsReportManager;
use Domain\ReportBundle\Model\BusinessOverviewModel;
use Domain\ReportBundle\Model\Exporter\ExcelExporterModel;
use Domain\ReportBundle\Util\DatesUtil;
use Oxa\Sonata\AdminBundle\Util\Helpers\AdminHelper;
use Symfony\Component\HttpFoundation\Response;

class BusinessReportExcelExporter extends ExcelExporterModel
{
    const TITLE_MAX_LENGTH = 31;

    /**
     * @var BusinessOverviewReportManager $businessOverviewReportManager
     */
    protected $businessOverviewReportManager;

    /**
     * @var KeywordsReportManager $keywordsReportManager
     */
    protected $keywordsReportManager;

    /**
     * @var AdUsageReportManager $adUsageReportManager
     */
    protected $adUsageReportManager;

    protected $mainTableInitRow         = 9;
    protected $mainTableInitCol         = 'B';

    protected $currentOverviewInitRow   = 9;
    protected $currentOverviewInitCol   = 'B';

    protected $previousOverviewInitRow  = 9;
    protected $previousOverviewInitCol  = 'E';

    protected $keywordsTableInitRow     = 9;
    protected $keywordsTableInitCol     = 'H';

    protected $currentYearInitRow       = 9;
    protected $currentYearInitCol       = 'K';

    protected $previousYearInitRow      = 9;
    protected $previousYearInitCol      = 'N';

    protected $interactionInitRow       = 9;
    protected $interactionInitCol       = 'R';

    /**
     * @param BusinessOverviewReportManager $service
     */
    public function setBusinessOverviewReportManager(BusinessOverviewReportManager $service)
    {
        $this->businessOverviewReportManager = $service;
    }

    /**
     * @param KeywordsReportManager $service
     */
    public function setKeywordsReportManager(KeywordsReportManager $service)
    {
        $this->keywordsReportManager = $service;
    }

    /**
     * @param AdUsageReportManager $service
     */
    public function setAdUsageReportManager(AdUsageReportManager $service)
    {
        $this->adUsageReportManager = $service;
    }

    /**
     * @param array $params
     * @return Response
     * @throws \PHPExcel_Exception
     */
    public function getResponse($params = []) : Response
    {
        /* @var BusinessProfile $businessProfile */
        $businessProfile = $params['businessProfile'];

        $filename = $this->businessOverviewReportManager
            ->getBusinessOverviewReportName($businessProfile->getSlug(), self::FORMAT);

        $title = mb_substr($businessProfile->getName(), 0, self::TITLE_MAX_LENGTH);

        $this->phpExcelObject = $this->phpExcel->createPHPExcelObject();

        $this->phpExcelObject = $this->setData($params);

        $this->phpExcelObject->getProperties()->setTitle($title);
        $this->phpExcelObject->getActiveSheet()->setTitle($title);

        return $this->sendResponse($filename);
    }

    /**
     * @param array $parameters
     * @return \PHPExcel
     * @throws \PHPExcel_Exception
     */
    protected function setData($parameters)
    {
        $previousParams     = $this->businessOverviewReportManager->getPreviousPeriodSearchParams(
            $parameters,
            DatesUtil::DEFAULT_PERIOD
        );
        $currentYearParams  = $this->businessOverviewReportManager->getThisYearSearchParams($parameters);
        $previousYearParams = $this->businessOverviewReportManager->getThisLastSearchParams($parameters);

        $interactionCurrentData  = $this->businessOverviewReportManager->getBusinessOverviewReportData($parameters);
        $interactionPreviousData = $this->businessOverviewReportManager->getBusinessOverviewReportData($previousParams);

        $keywordsData = $this->keywordsReportManager->getKeywordsData($parameters);

        $interactionCurrentYearData  = $this->businessOverviewReportManager
            ->getBusinessOverviewReportData($currentYearParams);
        $interactionPreviousYearData = $this->businessOverviewReportManager
            ->getBusinessOverviewReportData($previousYearParams);

        $this->activeSheet = $this->phpExcelObject->setActiveSheetIndex(0);

        $this->generateCommonHeader(
            current($interactionCurrentData['dates']),
            end($interactionCurrentData['dates'])
        );
        $this->generateTotalTable($interactionCurrentData['total']);
        $this->generateBusinessInfoTable($parameters['businessProfile']);

        $this->generateCurrentOverviewTable($interactionCurrentData);
        $this->generatePreviousOverviewTable($interactionPreviousData);
        $this->generateKeywordsTable($keywordsData);
        $this->generateYearTable(
            [
                'data'      => $interactionCurrentYearData,
                'title'     => 'Current Year',
                'initRow'   => $this->currentYearInitRow,
                'initCol'   => $this->currentYearInitCol,
            ]
        );
        $this->generateYearTable(
            [
                'data'      => $interactionPreviousYearData,
                'title'     => 'Previous Year',
                'initRow'   => $this->previousYearInitRow,
                'initCol'   => $this->previousYearInitCol,
            ]
        );

        $this->generateInteractionTable($interactionCurrentData);

        return $this->phpExcelObject;
    }

    protected function generateTotalTable($total)
    {
        $this->activeSheet->setCellValue('E6', 'Total Profile Impressions');
        $this->activeSheet->setCellValue('F6', $total[BusinessOverviewModel::TYPE_CODE_IMPRESSION]);
        $this->activeSheet->setCellValue('E7', 'Total Profile Views');
        $this->activeSheet->setCellValue('F7', $total[BusinessOverviewModel::TYPE_CODE_VIEW]);

        $this->setFontStyle('E', 6);
        $this->setFontStyle('E', 7);

        $this->setHeaderFontStyle('E', 6);
        $this->setHeaderFontStyle('E', 7);

        $this->setHeaderFontStyle('F', 6);
        $this->setHeaderFontStyle('F', 7);
    }

    protected function generateBusinessInfoTable(BusinessProfile $businessProfile)
    {
        $this->activeSheet->setCellValue('E2', 'Name');
        $this->activeSheet->setCellValue('F2', $businessProfile->getName());
        $this->activeSheet->setCellValue('E3', 'Address');
        $this->activeSheet->setCellValue('F3', $businessProfile->getFullAddress());

        $this->setFontStyle('E', 2);
        $this->setFontStyle('E', 3);

        $this->setHeaderFontStyle('E', 2);
        $this->setHeaderFontStyle('E', 3);

        $this->setHeaderFontStyle('F', 2);
        $this->setHeaderFontStyle('F', 3);
    }

    protected function generateCurrentOverviewTable($interactionCurrentData)
    {
        $row = $this->currentOverviewInitRow;
        $col = $this->currentOverviewInitCol;

        $this->activeSheet->setCellValue($col . $row, 'Date');
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $col++;

        $this->activeSheet->setCellValue($col . $row, 'Impressions');
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $col++;

        $this->activeSheet->setCellValue($col . $row, 'Views');
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $row++;

        foreach ($interactionCurrentData['results'] as $overview) {
            $col = $this->currentOverviewInitCol;
            $this->activeSheet->setCellValue($col . $row, $overview['date']);

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);

            $col++;
            $this->activeSheet->setCellValue($col . $row, $overview[BusinessOverviewModel::TYPE_CODE_IMPRESSION]);

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);

            $col++;
            $this->activeSheet->setCellValue($col . $row, $overview[BusinessOverviewModel::TYPE_CODE_VIEW]);

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);

            $row++;
        }
    }

    protected function generateInteractionTable($interactionData)
    {
        $row = $this->interactionInitRow;
        $col = $this->interactionInitCol;

        $mapping = BusinessOverviewModel::EVENT_TYPES;

        foreach (current($interactionData['results']) as $key => $item) {
            if (!empty($mapping[$key])) {
                $label = $mapping[$key];
            } else {
                $label = $key;
            }

            $this->activeSheet->setCellValue(
                $col . $row,
                $this->translator->trans($label)
            );

            $this->setTextAlignmentStyle($col, $row);
            $this->setFontStyle($col, $row);
            $this->setBorderStyle($col, $row);
            $col++;
        }

        foreach ($interactionData['results'] as $rowData) {
            $col = $this->interactionInitCol;
            $row++;

            foreach ($rowData as $item) {
                $this->activeSheet->setCellValue($col . $row, $item);

                $this->setColumnSizeStyle($col);
                $this->setBorderStyle($col, $row);

                $col++;
            }

            $this->setRowSizeStyle($row);
        }

        $col = $this->interactionInitCol;
        $row++;

        $this->activeSheet->setCellValue(
            $col . $row,
            $this->translator->trans('interaction_report.total')
        );
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);

        foreach ($interactionData['total'] as $item) {
            $col++;

            $this->activeSheet->setCellValue($col . $row, $item);

            $this->setColumnSizeStyle($col);
            $this->setFontStyle($col, $row);
            $this->setBorderStyle($col, $row);
        }

        $this->setRowSizeStyle($row);
    }

    protected function generatePreviousOverviewTable($interactionPreviousData)
    {
        $row = $this->previousOverviewInitRow;
        $col = $this->previousOverviewInitCol;

        $this->activeSheet->setCellValue($col . $row, 'Previous Month Impressions');
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $col++;

        $this->activeSheet->setCellValue($col . $row, 'Previous Month Views');
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $row++;

        foreach ($interactionPreviousData['results'] as $overview) {
            $col = $this->previousOverviewInitCol;
            $this->activeSheet->setCellValue($col . $row, $overview[BusinessOverviewModel::TYPE_CODE_IMPRESSION]);

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);

            $col++;
            $this->activeSheet->setCellValue($col . $row, $overview[BusinessOverviewModel::TYPE_CODE_VIEW]);

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);
            $row++;
        }
    }

    protected function generateKeywordsTable($keywordsData)
    {
        $row = $this->keywordsTableInitRow;
        $col = $this->keywordsTableInitCol;

        $this->activeSheet->setCellValue($col . $row, 'Keyword');
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $col++;

        $this->activeSheet->setCellValue($col . $row, 'Number of searches');
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $row++;

        foreach ($keywordsData['results'] as $keyword => $searches) {
            $col = $this->keywordsTableInitCol;
            $this->activeSheet->setCellValue($col . $row, $keyword);

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);

            $col++;
            $this->activeSheet->setCellValue($col . $row, $searches);

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);

            $row++;
        }
    }

    protected function generateYearTable($params)
    {
        $row = $params['initRow'];
        $col = $params['initCol'];

        $this->activeSheet->setCellValue($col . $row, $params['title']);
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $col++;

        $this->activeSheet->setCellValue($col . $row, 'Impressions');
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $col++;

        $this->activeSheet->setCellValue($col . $row, 'Views');
        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);
        $row++;

        foreach ($params['data']['results'] as $overview) {
            $col = $params['initCol'];

            $this->activeSheet->setCellValue(
                $col . $row,
                DatesUtil::convertMonthlyFormattedDate($overview['date'], AdminHelper::DATE_FULL_MONTH_FORMAT)
            );

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);

            $col++;
            $this->activeSheet->setCellValue($col . $row, $overview[BusinessOverviewModel::TYPE_CODE_IMPRESSION]);

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);

            $col++;
            $this->activeSheet->setCellValue($col . $row, $overview[BusinessOverviewModel::TYPE_CODE_VIEW]);

            $this->setColumnSizeStyle($col);
            $this->setBorderStyle($col, $row);

            $row++;
        }
    }

    protected function getExcelService()
    {
        return $this->phpExcel;
    }

    protected function getAdUsageReportManager() : AdUsageReportManager
    {
        return $this->adUsageReportManager;
    }

    protected function getKeywordsReportManager() : KeywordsReportManager
    {
        return $this->keywordsReportManager;
    }

    protected function getBusinessOverviewReportManager() : BusinessOverviewReportManager
    {
        return $this->businessOverviewReportManager;
    }
}
