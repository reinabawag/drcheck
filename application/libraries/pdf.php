<?php
if (!defined('BASEPATH')) exit('No direct script access allowed'); 

require_once APPPATH."/third_party/fpdf/fpdf.php";

class pdf extends FPDF
{
	function __construct()
	{
		parent::__construct();
	}

	function Header()
	{
	    // // Logo
	    // $this->Image('assets/images/amwire_logo.gif',90,6,24);
	    // // Arial bold 15
	    // $this->SetFont('Arial','B',10);
	    // // Move to the right
	    // $this->Cell(80);
	    // $this->ln(20);
	    // // Title
	    // $this->Cell(0,0,'American Wire & Cable Co., Inc.',0,0,'C');
	    // // Line break
	    // $this->Ln(20);
	    $this->Image('assets/images/amwire_logo.gif', 90, 1, 25);
		$this->ln(10);
		$this->SetFont('Arial','B',10);
		$this->cell(45);
		$this->Cell(100,10,'American Wire & Cable Co., Inc',0,1,'C');
		// $this->cell(45);
		// $this->Cell(100,3,'Login Report',0,1,'C');
		// $this->ln();
	}

	// Page footer
	function Footer()
	{
	    // Position at 1.5 cm from bottom
	    $this->SetY(-15);
	    // Arial italic 8
	    $this->SetFont('Arial','I',8);
	    // Page number
	    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
	}

	function FancyTable($header, $data)
{
    // Colors, line width and bold font
    $this->SetFillColor(255,0,0);
    $this->SetTextColor(255);
    $this->SetDrawColor(128,0,0);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
    // Header
    $w = array(40, 35, 40, 45);
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
    $this->Ln();
    // Color and font restoration
    $this->SetFillColor(224,235,255);
    $this->SetTextColor(0);
    $this->SetFont('');
    // Data
    $fill = false;
    foreach($data as $row)
    {
        $this->Cell($w[0],6,$row[0],'LR',0,'L',$fill);
        $this->Cell($w[1],6,$row[1],'LR',0,'L',$fill);
        $this->Cell($w[2],6,number_format($row[2]),'LR',0,'R',$fill);
        $this->Cell($w[3],6,number_format($row[3]),'LR',0,'R',$fill);
        $this->Ln();
        $fill = !$fill;
    }
    // Closing line
    $this->Cell(array_sum($w),0,'','T');
}
}