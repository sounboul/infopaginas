<?php

namespace Domain\ReportBundle\Service\Export;

use Domain\ReportBundle\Manager\UserActionReportManager;
use Domain\ReportBundle\Model\Exporter\ExcelExporterModel;
use Domain\ReportBundle\Model\UserActionModel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserActionExcelExporter
 * @package Domain\ReportBundle\Export
 */
class UserActionExcelExporter extends ExcelExporterModel
{
    /**
     * @var UserActionReportManager $userActionReportManager
     */
    protected $userActionReportManager;

    protected $mainTableInitRow = 2;
    protected $mainTableInitCol = 'B';

    /**
     * @param UserActionReportManager $service
     */
    public function setUserActionReportManager(UserActionReportManager $service)
    {
        $this->userActionReportManager = $service;
    }

    /**
     * @param array $parameters
     * @return Array
     * @throws \PHPExcel_Exception
     */
    public function getResponse($parameters = [])
    {
        $title = $this->translator->trans('export.title.user_action_report', [], 'AdminReportBundle');
        $title = $this->getSafeTitle($title);

        $data = $this->userActionReportManager->getUserActionReportExportData();

        $files = [];

        foreach ($data['results'] as $page) {
            $path = $this->generateTempFilePath($parameters['exportPath']);

            $this->phpExcelObject = $this->phpExcel->createPHPExcelObject();
            $this->phpExcelObject = $this->setData([
                'mapping' => $data['mapping'],
                'results' => $page,
            ]);

            $this->phpExcelObject->getProperties()->setTitle($title);
            $this->phpExcelObject->getActiveSheet()->setTitle($title);

            $status = $this->saveResponse($path);

            if ($status) {
                $files[] = $path;
            }
        }

        unset($data);

        return $files;
    }

    /**
     * @param array $data
     * @return \PHPExcel
     * @throws \PHPExcel_Exception
     */
    protected function setData($data)
    {
        $this->activeSheet = $this->phpExcelObject->setActiveSheetIndex(0);
        $this->generateMainTable($data);

        return $this->phpExcelObject;
    }

    protected function generateMainTable($data)
    {
        $row = $this->mainTableInitRow;
        $col = $this->mainTableInitCol;

        $this->setFontStyle($col, $row);
        $this->setBorderStyle($col, $row);

        foreach ($data['mapping'] as $name) {
            $this->activeSheet->setCellValue(
                $col . $row,
                $this->translator->trans($name, [], 'AdminReportBundle')
            );

            $this->setTextAlignmentStyle($col, $row);
            $this->setFontStyle($col, $row);
            $this->setBorderStyle($col, $row);
            $col++;
        }

        $eventsMapping = UserActionModel::EVENT_TYPES;

        foreach ($data['results'] as $rowData) {
            $col = $this->mainTableInitCol;
            $row++;

            foreach ($data['mapping'] as $key => $value) {
                if ($key == UserActionReportManager::MONGO_DB_FIELD_DATA) {
                    $info = implode(PHP_EOL, $rowData[$key]);
                    $this->activeSheet->setCellValue($col . $row, $info);
                    $this->activeSheet->getRowDimension($row)->setRowHeight(-1);
                    $this->activeSheet->getStyle($col . $row)->getAlignment()->setWrapText(true);
                } elseif ($key == UserActionReportManager::MONGO_DB_FIELD_ACTION) {
                    $this->activeSheet->setCellValue(
                        $col . $row,
                        $this->translator->trans($eventsMapping[$rowData[$key]], [], 'AdminReportBundle')
                    );
                } else {
                    $this->activeSheet->setCellValue($col . $row, $rowData[$key]);
                }

                $this->setColumnSizeStyle($col);
                $this->setBorderStyle($col, $row);

                $col++;
            }

            $this->setRowSizeStyle($row);
        }
    }
}
