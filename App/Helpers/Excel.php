<?php

set_time_limit(0);
ini_set('memory_limit', '1024M');

use \PHPExcel;

require 'PHPExcel_Cell_MyValueBinder.php';
/**
* Excel
*
* @author   Fabian Siatama
*
* Esta clase permite exportar cualquier grilla ExtJs en formato EXCEL o PDF
*/
class Excel
{
	private $data;
	private $format;
	private $head;
	private $total;
	private $fileName;
	private $description;
	private $rendererName;
	private $rendererLibraryPath;
	private $objPHPExcel;
	private $rowNumber = 1;
	private $rowHeaderNumber = 1;
	private $worksheet;
	private $numberColumns = 0;
	private $arrHead = [];
	private $arrChartData = [];
	

	/**
	 * __construct
	 * 
	 * @param array  $result       Array con todos los datos del resultado de la consulta (Datos, Total, Graficas)
	 * @param string $format       Formato que el usuario pide de salida (xls, xlsx, pdf).
	 * @param array  $head         Array con los titulos de la grilla.
	 * @param string $fileName     Nombre del archivo de salida.
	 * @param array  $description  array con el titulo del reporte y la descripcion de los filtros.
	 *
	 * @access public
	 *
	 * @return mixed Value.
	 */
	public function __construct(array $result, $format, array $head, $fileName, array $description = [])
	{
		$this->data        = $result['data'];
		$this->format      = $format;
		$this->head        = $head;
		$this->total       = $result['total'];
		$this->fileName    = $fileName;
		$this->description = $description;

		$area    = (!empty($result['areaChartData'])) ? $result['areaChartData'] : false ;
		$pie     = (!empty($result['pieChartData'])) ? $result['pieChartData'] : false ;
		$columns = (!empty($result['columnChartData'])) ? $result['columnChartData'] : false ;

		$this->arrChartData = [
			'columns' => $columns,
			'area'    => $area,
			'pie'     => $pie,
		];

		$this->rendererName        = PHPExcel_Settings::PDF_RENDERER_MPDF;
		$rendererLibrary           = 'MPDF54';
		$this->rendererLibraryPath = PATH_APP.'lib/' . $rendererLibrary;
		$this->objPHPExcel         = new PHPExcel();
		$this->setProperties();
		//$this->objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
		//$this->objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);

		$objPageSetup = new PHPExcel_Worksheet_PageSetup();
		$objPageSetup->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_LETTER);
		$objPageSetup->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
		$objPageSetup->setFitToWidth(1);
		$this->objPHPExcel->getActiveSheet()->setPageSetup($objPageSetup);
		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_MyValueBinder() );
	}

	private function ValueBinder(PHPExcel_Cell $cell, $value = null)
	{
		var_dump($cell, $value);
	}

	private function getHeaderStyle()
	{
		return [
			'font' => [
				'bold'  => true,
				'color' => [ 'argb' => PHPExcel_Style_Color::COLOR_WHITE ]
			],
			'alignment' => [
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			],
			'borders' => [
				'top'     => [ 'style' => PHPExcel_Style_Border::BORDER_THIN ]
			],
			'fill' => [
				'type'       => PHPExcel_Style_Fill::FILL_SOLID,
				'startcolor' => [ 'argb' => 'FF1F497D' ]
			]
		];
	}

	private function getTitleStyle()
	{
		return [
			'font' => [
				'bold' => true,
				'size' => '12',
			],
			'borders' => [
				'inside' => [
					'style' => PHPExcel_Style_Border::BORDER_THIN,
					'color' => [
						'argb' => 'FFDFD7CA'
					]
				],
				'outline' => [
					'style' => PHPExcel_Style_Border::BORDER_THIN,
					'color' => [
						'argb' => 'FFDFD7CA'
					]
				]
			],
			'fill' => [
				'type'       => PHPExcel_Style_Fill::FILL_SOLID,
				'startcolor' => [ 'argb' => 'FFF8F5F0' ]
			],
		];
	}

	private function getDescriptionStyle()
	{
		return [
			'font' => [
				'size' => '8',
			]
		];
	}

	private function getStringFormat()
	{
		return [
			'numberformat' => [
				'code' => PHPExcel_Style_NumberFormat::FORMAT_GENERAL,
			]
		];
	}

	private function setRowNumber($rowNumber)
	{
		$this->rowNumber = $rowNumber;
	}

	private function setProperties()
	{
		// Set properties
		$this->objPHPExcel->getProperties()->setCreator("Fabian Siatama")
									 ->setTitle("Quintero Hermanos Ltda Document www.sicex.com")
									 ->setSubject("Quintero Hermanos Ltda. Document www.sicex.com")
									 ->setDescription("Reporte generado desde www.sicex.com")
									 ->setKeywords("office 2007 openxml php")
									 ->setCategory("www.sicex.com");
	}

	private function letterColumn($number)
	{
		$residue = $number % 26;
		$integer = floor(($number / 26));
		
		if ($number > 26) {
			$secondLetter = ($residue == 0) ? $residue + 1 : $residue;
			$letter = chr($integer + 64).$this->letterColumn($secondLetter);
		} else {

			if ($number == 26) {
				$letter = chr(90);
			} else {
				$letter = chr($residue + 64);
			}
		}
		return $letter;
	}

	private function setColumnFormat()
	{
		foreach ($this->arrHead as $key => $value) {
			
			$arr          = Helpers::arrayColumn($this->data, $value);
			$firstElement = array_shift($arr);
			$range        = $key.($this->rowHeaderNumber + 1).':'.$key.($this->rowHeaderNumber + $this->total);
			
			if (is_numeric($firstElement) && $value != 'id_posicion' && $value != 'id_empresa' && $value != 'periodo') {
				
				//var_dump($range);
				$this->objPHPExcel->getActiveSheet()->getStyle($range)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

			} else {
				
				$this->objPHPExcel->getActiveSheet()->getStyle($range)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

			}
			
		}
	}

	private function writeTitle()
	{
		if (!empty($this->description)) {
			
			$numberColumns = count($this->head);
			$rowNumber     = $this->rowNumber;

			foreach ($this->description as $key => $value) {

				$cell  = $this->letterColumn($numberColumns);
				$range = 'A'.$rowNumber.':'.$cell.$rowNumber;
				
				$this->objPHPExcel->getActiveSheet()->mergeCells($range);
				$this->objPHPExcel->getActiveSheet()->getCell('A'.$rowNumber)->setValue($value);

				//var_dump($key, $value);

				if ($key == 'title') {
					$this->objPHPExcel->getActiveSheet()->getStyle($range)->applyFromArray( $this->getTitleStyle() );
				} else {
					$this->objPHPExcel->getActiveSheet()->getStyle($range)->applyFromArray( $this->getDescriptionStyle() );
				}
				$rowNumber += 1;
				$this->setRowNumber($rowNumber);
			}
			$rowNumber += 1;
			$this->setRowNumber($rowNumber);
		}
	}

	private function writeHeader()
	{
		$cell = 'A';

		$arrHead = [];
		
		foreach($this->head as $fieldName => $fieldTitle) {
			$this->numberColumns += 1;

			$cell = $this->letterColumn($this->numberColumns);

			$this->worksheet->getColumnDimension($cell)->setAutoSize(true);
			$this->worksheet->setCellValueByColumnAndRow( ($this->numberColumns-1), $this->rowNumber, ($fieldTitle) );
			
			$arrHead[$cell] = $fieldName;
		}

		$this->arrHead = $arrHead;

		$this->objPHPExcel->getActiveSheet()->getStyle('A'.$this->rowNumber.':'.$cell.$this->rowNumber)->applyFromArray( $this->getHeaderStyle() );
	}

	private function writeBody()
	{
		$arrData = [];

		foreach ($this->data as $key => $dataRow) {

			foreach ($dataRow as $subKey => $dataCell) {
				
				if (array_key_exists($subKey, $this->head)) {
					
					$index = array_search($subKey,array_keys($this->head));

					$arrData[$key][$index] = $dataCell;

				}

			}

		}
		$rowNumber = $this->rowNumber;

		foreach ($arrData as $key => $data) {
			$rowNumber += 1;
			$this->setRowNumber($rowNumber);

			ksort($data);
			//var_dump($data);
			$this->worksheet->fromArray( $data, '', 'A'.$this->rowNumber, true);
		}

	}

	private function drawPie($xAxis, $series)
	{
		$cell = array_search($xAxis, $this->arrHead);
		$xAxisTickValues  = [];
		$dataSeriesValues = [];
		$dataseriesLabels = [];
		$rowNumber        = $this->rowNumber;

		if ($cell) {
			$range = '$'.$cell.'$'.($this->rowHeaderNumber);
			$dataseriesLabels = [
				new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!'.$range, NULL, 1),	//	2011
			];

			$range = '$'.$cell.'$'.($this->rowHeaderNumber + 1).':$'.$cell.'$'.($this->rowHeaderNumber + $this->total);

			$xAxisTickValues = [
				new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!'.$range, NULL, $this->total),	//	Q1 to Q4
			];
			foreach ($series as $key => $value) {
				$cell = array_search($key, $this->arrHead);
				if ($cell) {
					$range = '$'.$cell.'$'.($this->rowHeaderNumber + 1).':$'.$cell.'$'.($this->rowHeaderNumber + $this->total);
					$dataSeriesValues[] = new PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!'.$range, NULL, $this->total);
				}
			}
			//var_dump($dataseriesLabels, $xAxisTickValues, $dataSeriesValues);

			$chartSeries = new PHPExcel_Chart_DataSeries(
				PHPExcel_Chart_DataSeries::TYPE_PIECHART,				// plotType
				PHPExcel_Chart_DataSeries::GROUPING_STANDARD,			// plotGrouping
				range(0, count($dataSeriesValues)-1),					// plotOrder
				$dataseriesLabels,										// plotLabel
				$xAxisTickValues,										// plotCategory
				$dataSeriesValues										// plotValues
			);

			$layout = new PHPExcel_Chart_Layout();
			$layout->setShowVal(TRUE);
			$layout->setShowPercent(TRUE);

			//	Set the series in the plot area
			$plotarea = new PHPExcel_Chart_PlotArea(NULL, [$chartSeries]);
			//	Set the chart legend
			$legend   = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_RIGHT, NULL, false);
			$title    = new PHPExcel_Chart_Title('Test Pie Chart');


			//	Create the chart
			$chart = new PHPExcel_Chart(
				'chartPie',		// name
				$title,		// title
				$legend,		// legend
				$plotarea,		// plotArea
				true,			// plotVisibleOnly
				0,				// displayBlanksAs
				NULL,			// xAxisLabel
				NULL			// yAxisLabel		- Pie charts don't have a Y-Axis
			);

			//	Set the position where the chart should appear in the worksheet
			$chart->setTopLeftPosition('A'.$rowNumber);
			$rowNumber += 20;
			$this->setRowNumber($rowNumber);

			$chart->setBottomRightPosition('H'.$rowNumber);

			//	Add the chart to the worksheet
			$this->objPHPExcel->getActiveSheet()->addChart($chart);
		}
	}

	private function drawArea($xAxis, $series)
	{
		$cell = array_search($xAxis, $this->arrHead);
		$xAxisTickValues  = [];
		$dataSeriesValues = [];
		$dataseriesLabels = [];
		$rowNumber        = $this->rowNumber;

		if ($cell) {
			$range = '$'.$cell.'$'.($this->rowHeaderNumber + 1).':$'.$cell.'$'.($this->rowHeaderNumber + $this->total);
			$dataseriesLabels = [
				new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!'.$range, NULL, 1)
			];

			$xAxisTickValues = array(
				new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!'.$range, NULL, 1)
			);

			foreach ($series as $key => $value) {
				$cell = array_search($key, $this->arrHead);
				if ($cell) {
					$range = '$'.$cell.'$'.($this->rowHeaderNumber + 1).':$'.$cell.'$'.($this->rowHeaderNumber + $this->total);
					$dataSeriesValues[] = new PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!'.$range, NULL, $this->total);
				}
			}
			//var_dump($dataseriesLabels, $xAxisTickValues, $dataSeriesValues);

			$chartSeries = new PHPExcel_Chart_DataSeries(
				PHPExcel_Chart_DataSeries::TYPE_AREACHART,				// plotType
				PHPExcel_Chart_DataSeries::GROUPING_PERCENT_STACKED,	// plotGrouping
				range(0, count($dataSeriesValues)-1),					// plotOrder
				$dataseriesLabels,										// plotLabel
				$xAxisTickValues,										// plotCategory
				$dataSeriesValues										// plotValues
			);

			$plotarea = new PHPExcel_Chart_PlotArea(NULL, [$chartSeries]);
			//	Set the chart legend
			$legend = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_TOPRIGHT, NULL, false);

			$title = new PHPExcel_Chart_Title('Test %age-Stacked Area Chart');
			$yAxisLabel = new PHPExcel_Chart_Title('Value ($k)');


			//	Create the chart
			$chart = new PHPExcel_Chart(
				'chartArea',		// name
				$title,			// title
				$legend,		// legend
				$plotarea,		// plotArea
				true,			// plotVisibleOnly
				0,				// displayBlanksAs
				NULL,			// xAxisLabel
				$yAxisLabel		// yAxisLabel
			);


			//	Set the position where the chart should appear in the worksheet
			$chart->setTopLeftPosition('A'.$rowNumber);
			$rowNumber += 20;
			$this->setRowNumber($rowNumber);

			$chart->setBottomRightPosition('H'.$rowNumber);

			//	Add the chart to the worksheet
			$this->objPHPExcel->getActiveSheet()->addChart($chart);
		}
	}

	private function drawColumns($xAxis, $series)
	{
		$cell = array_search($xAxis, $this->arrHead);
		$xAxisTickValues  = [];
		$dataSeriesValues = [];
		$dataseriesLabels = [];
		$rowNumber        = $this->rowNumber;

		if ($cell) {
			$range = '$'.$cell.'$'.($this->rowHeaderNumber + 1).':$'.$cell.'$'.($this->rowHeaderNumber + $this->total);
			$dataseriesLabels = [
				new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!'.$range, NULL, 1)
			];

			$xAxisTickValues = array(
				new PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!'.$range, NULL, 1)
			);

			foreach ($series as $key => $value) {
				$cell = array_search($key, $this->arrHead);
				if ($cell) {
					$range = '$'.$cell.'$'.($this->rowHeaderNumber + 1).':$'.$cell.'$'.($this->rowHeaderNumber + $this->total);
					$dataSeriesValues[] = new PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!'.$range, NULL, $this->total);
				}
			}
			var_dump($dataseriesLabels, $xAxisTickValues, $dataSeriesValues);

			$chartSeries = new PHPExcel_Chart_DataSeries(
				PHPExcel_Chart_DataSeries::TYPE_BARCHART,		// plotType
				PHPExcel_Chart_DataSeries::GROUPING_STANDARD,	// plotGrouping
				range(0, count($dataSeriesValues)-1),			// plotOrder
				$dataseriesLabels,								// plotLabel
				$xAxisTickValues,								// plotCategory
				$dataSeriesValues								// plotValues
			);

			$chartSeries->setPlotDirection(PHPExcel_Chart_DataSeries::DIRECTION_COL);

			//	Set the series in the plot area
			$plotarea = new PHPExcel_Chart_PlotArea(NULL, array($chartSeries));
			//	Set the chart legend
			$legend = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_RIGHT, NULL, false);

			$title = new PHPExcel_Chart_Title('Test Column Chart');
			$yAxisLabel = new PHPExcel_Chart_Title('Value ($k)');



			//	Create the chart
			$chart = new PHPExcel_Chart(
				'chartColumn',		// name
				$title,			// title
				$legend,		// legend
				$plotarea,		// plotArea
				true,			// plotVisibleOnly
				0,				// displayBlanksAs
				NULL,			// xAxisLabel
				$yAxisLabel		// yAxisLabel
			);


			//	Set the position where the chart should appear in the worksheet
			$chart->setTopLeftPosition('A'.$rowNumber);
			$rowNumber += 20;
			$this->setRowNumber($rowNumber);

			$chart->setBottomRightPosition('H'.$rowNumber);

			//	Add the chart to the worksheet
			$this->objPHPExcel->getActiveSheet()->addChart($chart);
		}
	}

	private function drawCharts()
	{
		foreach ($this->arrChartData as $key => $data) {
			if ($data !== false && !empty($data['xAxis']) && !empty($data['series'])) {
				$method = 'draw' . Inflector::camel($key);
				$this->$method($data['xAxis'], $data['series']);
			}
		}
	}

	private function save()
	{
		switch ($this->format){
			case '1':
				$fileName = $this->fileName.'.xlsx';
				$objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
				$objWriter = new PHPExcel_Writer_Excel2007($this->objPHPExcel);
			break;
			case '2':
				$fileName = $this->fileName.'.xls';
				$this->objPHPExcel->getActiveSheet()->setShowGridLines(false);
				$objWriter = new PHPExcel_Writer_Excel5($this->objPHPExcel);
			break;
			case '3':
				$fileName = $this->fileName.'.pdf';
				$archivo = $nombre.'.pdf';
				$this->objPHPExcel->getActiveSheet()->setShowGridLines(false);
				
				if (!PHPExcel_Settings::setPdfRenderer(
						$this->rendererName,
						$this->rendererLibraryPath
					)) {
					die(
						'NOTICE: Please set the '.$rendererName .' and .'.$rendererLibraryPath .'values' .
						'<br />' .
						'at the top of this script as appropriate for your directory structure'
					);
				}
				$objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'PDF');
				$objWriter->setSheetIndex(0);
			break;
			case '4':
				$fileName = $this->fileName.'.txt';
				$objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'CSV')->setDelimiter(',')
																	  ->setEnclosure('"')
																	  ->setLineEnding("\r\n")
																	  ->setSheetIndex(0);
			break;
		}
		$objWriter->setIncludeCharts(TRUE);
		$objWriter->setPreCalculateFormulas(true);
		$objWriter->save(PATH_REPORTS . $fileName);
		return $fileName;
	}

	public function write()
	{
		$rowNumber = 2;
		$this->setRowNumber($rowNumber);
		$this->worksheet = $this->objPHPExcel->getActiveSheet();

		$this->writeTitle();

		$this->writeHeader();

		$this->rowHeaderNumber = $this->rowNumber;
		$this->setColumnFormat();
		
		$this->writeBody();

		//$this->drawCharts();


		$fileName = $this->save();

		if (file_exists(PATH_REPORTS . $fileName)) {
			$result = [
				'success' => true,
				'file'    => $fileName
			];
		} else {
			$result = [
				'success' => false,
				'error'   => Lang::get('error.write_excel_error')
			];
		}
		return $result;
	}
}