<?php
function generateCertificate($fn,$ln,$em,$uni,$nati,$id){
	$v = $id;
	
	$of=strtotime("+1 year");
	
	require('../fpdf16/fpdf.php');
	$pdf=new FPDF();
	$pdf->AddPage();
	$pdf->SetFont('Arial','B',20);
	$pdf->image('../fpdf16/border.jpg',0,0,211);
	$pdf->cell(190,10,'',0,1);
	$pdf->Cell(190,15,'The Society of Digital Information and Wireless',0,1,'C');
	$pdf->Cell(190,15,'Communications',0,1,'C');
	$pdf->SetFont('Arial','B',15);
	$pdf->cell(190,15,'(SDIWC)',0,1,'C');
	$pdf->cell(190,5,'',0,1);
	$pdf->SetFont('Arial','B',25);
	$pdf->cell(190,30,'Membership Certification',0,1,'C');
	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(190,10,'The SDIWC  certifies that',0,1,'C');
	$pdf->SetFont('Times','I',25);
	$str=$fn." ".$ln;
	$fileName=strtolower($fn."-".$ln);
	$pdf->Cell(190,15,$str,0,1,'C');
	$pdf->SetFont('Arial','B',17);
//$pdf->Cell(190,15,'Chairman of DCMRF',0,1,'C');
	$exp='ID: '.$v ;
	$pdf->Cell(190,15,$exp,0,1,'C');
	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(190,15,'is a member of SDIWC. This membership allows the mentioned name',0,1,'C');
	$pdf->Cell(190,15,'to take advantages from the SDIWC.',0,1,'C');
	$pdf->cell(80,50,'',0,1);
	$pdf->cell(15);
	$pdf->SetFont('Arial','B',10);
	$exp='Expiration date: '.date("F d, Y",$of);
	$pdf->Cell(120,10,'',0);
	$pdf->Cell(40,10,'SDIWC chairman',0,1);
	$pdf->image('../fpdf16/1.jpg',150,230,30);
	$pdf->image('../fpdf16/logo.jpg',25,210,50);
	$out='../certifications/'.$fileName."_".time().'.pdf';
	$pdf->Output($out);
	
	$return=$fileName."_".time().'.pdf';
	return $return;
}