<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 7/13/16
 * Time: 7:57 PM
 */

namespace Domain\ReportBundle\Service\Export;

use Domain\ReportBundle\Entity\SubscriptionReport;
use Domain\ReportBundle\Manager\SubscriptionReportManager;
use Domain\ReportBundle\Model\Exporter\ExcelExporterModel;
use Domain\ReportBundle\Model\ExporterInterface;
use Domain\ReportBundle\Model\ReportInterface;
use Domain\ReportBundle\Util\DatesUtil;
use Exporter\Source\SourceIteratorInterface;
use Liuggio\ExcelBundle\Factory;
use Oxa\Sonata\AdminBundle\Util\Helpers\AdminHelper;
use Sonata\CoreBundle\Exporter\Exporter as BaseExporter;
use Spraed\PDFGeneratorBundle\PDFGenerator\PDFGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\Translator;

/**
 * Class SubscriptionExcelExporter
 * @package Domain\ReportBundle\Export
 */
class SubscriptionExcelExporter extends ExcelExporterModel
{
    /**
     * @var EngineInterface $templateEngine
     */
    protected $templateEngine;

    /**
     * @var SubscriptionReportManager $subscriptionReportManager
     */
    protected $subscriptionReportManager;

    /**
     * @param SubscriptionReportManager $service
     */
    public function setSubscriptionReportManager(SubscriptionReportManager $service)
    {
        $this->subscriptionReportManager = $service;
    }

    /**
     * @param string $code
     * @param string $format
     * @param array $objects
     * @param array $parameters
     * @return Response
     * @throws \PHPExcel_Exception
     */
    public function getResponse(string $code, string $format, array $objects, $parameters = []) : Response
    {
        $filename = $this->subscriptionReportManager->generateReportName($format, 'subscription_report');

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();

        $phpExcelObject->getProperties()
            ->setTitle($this->translator->trans('export.title.subscription_report', [], 'AdminReportBundle'))
        ;

        $phpExcelObject = $this->setData($phpExcelObject, $objects, $parameters);

        $phpExcelObject->getActiveSheet()
            ->setTitle(
                $this->translator->trans('export.title.active_sheet', [], 'AdminReportBundle')
            );
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * @param \PHPExcel $phpExcelObject
     * @param array $objects
     * @return \PHPExcel
     * @throws \PHPExcel_Exception
     */
    public function setData(\PHPExcel $phpExcelObject, array $objects, $parameters)
    {
        $subscriptionPlans = $this->subscriptionReportManager->getSubscriptionPlans();

        $dates = $dates = DatesUtil::getReportDates($parameters);

        // count data
        $subscriptionData = $this->subscriptionReportManager
            ->getSubscriptionsQuantities($objects, $dates, $subscriptionPlans);

        $activeSheet = $phpExcelObject->setActiveSheetIndex(0);

        // generated date
        $activeSheet->setCellValue(
            'B2',
            $this->translator->trans('export.generated_date', [], 'AdminReportBundle')
        );

        $activeSheet->setCellValue(
            'B3',
            new \DateTime()
        );

        $activeSheet->mergeCells('B2:C2');
        $activeSheet->mergeCells('B3:C3');

        // start date period
        $activeSheet->setCellValue(
            'B5',
            $this->translator->trans('export.date_period', [], 'AdminReportBundle')
        );

        $activeSheet->mergeCells('B5:C5');

        $dates = (array) $subscriptionData['dates'];

        $activeSheet->setCellValue(
            'B6',
            $this->translator->trans('export.start_date', [], 'AdminReportBundle')
        );
        $activeSheet->setCellValue(
            'C6',
            $this->translator->trans('export.end_date', [], 'AdminReportBundle')
        );

        $activeSheet->setCellValue(
            'B7',
            current($dates)
        );
        $activeSheet->setCellValue(
            'C7',
            end($dates)
        );
        // end date period

        $cell = $initCell = 9;
        $row = $initRow = $maxRow = 'B';

        // start header
        $activeSheet->setCellValue(
            $row.$cell,
            $this->translator->trans('list.label_date', [], 'AdminReportBundle')
        );

        // for each subscription plan
        foreach ($subscriptionData['subscription_quantities'] as $subscription) {
            ++$row;
            $activeSheet->setCellValue(
                $row.$cell,
                $subscription['name']
            );

            $activeSheet
                ->getStyle($row.$cell)
                ->getAlignment()
                ->setWrapText(true);
        }

        ++$row;
        $activeSheet->setCellValue(
            $row.$cell,
            $this->translator->trans('list.label_total', [], 'AdminReportBundle')
        );
        // end header

        // start main data (date and quantity)
        foreach ($objects as $object) {
            /** @var SubscriptionReport $object */
            $row = $initRow;

            ++$cell;
            $activeSheet->setCellValue($row.$cell, $object->getDate()->format(AdminHelper::DATE_FORMAT));

            // for each subscription plan
            foreach ($object->getSubscriptionReportSubscriptions() as $srSubscription) {
                ++$row;
                $activeSheet->setCellValue(
                    $row.$cell,
                    $srSubscription->getQuantity()
                );
            }

            ++$row;
            $activeSheet->setCellValue(
                $row.$cell,
                $object->getTotal()
            );

            if ($row > $maxRow) {
                $maxRow = $row;
            }
        }
        // end main data (date and quantity)

        // start footer (total quantities)
        $row = $initRow;
        ++$cell;
        $activeSheet->setCellValue(
            $row.$cell,
            $this->translator->trans('list.label_total', [], 'AdminReportBundle')
        );

        // for each subscription plan
        foreach ($subscriptionData['subscription_total_quantities'] as $subscription) {
            ++$row;
            $activeSheet->setCellValue(
                $row.$cell,
                $subscription['quantity']
            );
        }

        ++$row;
        $activeSheet->setCellValue(
            $row.$cell,
            $subscriptionData['total_quantity']
        );
        // end footer (total quantities)

        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN
                )
            )
        );

        $fontStyleArray = array(
            'font'  => array(
                'bold'  => true,
            ));

        // apply styles
        $maxRow++;
        $cell++;
        // to main table
        for ($r = $initRow; $r < $maxRow; $r++) {
            for ($c = $initCell; $c < $cell; $c++) {
                $activeSheet
                    ->getColumnDimension($r)
                    ->setAutoSize(true)
                ;

                $activeSheet
                    ->getStyle($r.$c)
                    ->applyFromArray($styleArray)
                ;

                $activeSheet
                    ->getRowDimension($c)
                    ->setRowHeight(15)
                ;

                // set font weight as bold to the last line
                if ($c == $initCell || $c == $cell - 1) {
                    $activeSheet
                        ->getStyle($r.$c)
                        ->applyFromArray($fontStyleArray)
                    ;
                }
            }
        }

        // to header table
        $textAlignStyleArray = [
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            )
        ];

        $styleArray = array_merge($styleArray, $textAlignStyleArray);

        $activeSheet
            ->getStyle('B2')
            ->applyFromArray($fontStyleArray)
        ;

        $activeSheet
            ->getStyle('B5')
            ->applyFromArray($fontStyleArray)
        ;

        for ($r = 'B'; $r < 'D'; $r++) {
            for ($c = 2; $c < 4; $c++) {
                $activeSheet
                    ->getColumnDimension($r)
                    ->setAutoSize(true)
                ;

                $activeSheet
                    ->getStyle($r.$c)
                    ->applyFromArray($styleArray)
                ;

                $activeSheet
                    ->getRowDimension($c)
                    ->setRowHeight(15)
                ;
            }
        }

        for ($r = 'B'; $r < 'D'; $r++) {
            for ($c = 5; $c < 8; $c++) {
                $activeSheet
                    ->getColumnDimension($r)
                    ->setAutoSize(true)
                ;

                $activeSheet
                    ->getStyle($r.$c)
                    ->applyFromArray($styleArray)
                ;

                $activeSheet
                    ->getRowDimension($c)
                    ->setRowHeight(15)
                ;
            }
        }

        return $phpExcelObject;
    }
}
