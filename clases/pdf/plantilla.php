<?php
	require '../../fpdf/fpdf.php';
	
	class PDF extends FPDF
	{
		function Header()
		{
		    global $title;
		    $this->Image('../../images/logo.png', 22, 15, 30 );
		    // Arial bold 15
		    $this->SetFont('Arial','B',15);
		    // Calculamos ancho y posición del título.
		    $w = $this->GetStringWidth($title)+65;
		    $this->SetX((200-$w));
		    // Colores de los bordes, fondo y texto
		    $this->SetDrawColor(0,80,180);
		    $this->SetFillColor(231,235,218);
		    $this->SetTextColor(030,030,030);
		    // // Ancho del borde (1 mm)
		    // $this->SetLineWidth(1);
		    // Título
		    $this->Cell($w,20,$title,0,1,'C',true);	
		    // Salto de línea
		    $this->Ln(15);		
		}
			
		function Footer()
		{
			$this->SetY(-15);
			$this->SetFont('Arial','I', 8);
			$this->Cell(0,10, 'Pagina '.$this->PageNo().'/{nb}',0,0,'C' );
		}

		function ChapterTitle($num, $label)
		{
		    // Arial 12
		    $this->SetFont('Arial','',12);
		    // Color de fondo
		    $this->SetFillColor(200,220,255);
		    // Título
		    $this->Cell(0,10,utf8_decode("$num $label"),0,1,'L',true);
		    // Salto de línea
		    $this->Ln(4);
		}

		function ChapterBody($datos)
		{
		    // Leemos el fichero
		    $txt = $datos;
		    // Times 12
		    $this->SetFont('Times','',12);
		    // Imprimimos el texto justificado
		    $this->MultiCell(0,5,$txt);
		    // Salto de línea
		    $this->Ln();
		    // Cita en itálica
		    $this->SetFont('','I');
		    // $this->Cell(0,5,'(fin del extracto)');
		}

		function PrintChapter($num,$title,$datos)
		{
		    //$this->AddPage();
		    $this->ChapterTitle($num, $title);
		    $this->ChapterBody($datos);
		  
		}		
	}
?>