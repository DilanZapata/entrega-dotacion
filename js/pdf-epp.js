// Genera el Formato para Entrega Individual de Elementos de Protección Personal (EPP)
// replicando el formato oficial 0512-FT-002-INS de AGROPECUARIA CHIQUIQUE SAS.
// El documento se genera EN BLANCO (para imprimir y diligenciar a mano) salvo los
// datos del trabajador, que ya se conocen.
// Requiere: jsPDF + jspdf-autotable (CDN) y js/logo.js cargados antes que este archivo.

function generarFormatoEPP(empleado) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: 'mm', format: 'letter' });

  const pageWidth = doc.internal.pageSize.getWidth();
  const marginLeft = 16;
  const marginRight = 16;
  const contentWidth = pageWidth - marginLeft - marginRight;
  const blue = [91, 155, 213];
  const gray = [90, 90, 90];
  const dark = [30, 30, 30];

  // ---- Encabezado ----
  let y = 16;
  try {
    doc.addImage(LOGO_BASE64, 'PNG', marginLeft, y - 6, 18, 15.1);
  } catch (e) { /* si el logo no carga, se omite sin romper el PDF */ }

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11.5);
  doc.setTextColor(...dark);
  const tituloLines = doc.splitTextToSize(
    'FORMATO PARA ENTREGA INDIVIDUAL DE ELEMENTOS DE PROTECCIÓN PERSONAL (EPP), DOTACIÓN DE ROPA Y CALZADO.',
    contentWidth - 36 - 32
  );
  doc.text(tituloLines, marginLeft + 40, y - 5, { align: 'left' });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(...gray);
  doc.text('Código: 0512-FT-002-INS', pageWidth - marginRight, y - 5, { align: 'right' });
  doc.text('Versión: 03', pageWidth - marginRight, y, { align: 'right' });
  doc.text('Fecha: 15/07/2025', pageWidth - marginRight, y + 5, { align: 'right' });

  y += 10;
  doc.setDrawColor(...blue);
  doc.setLineWidth(0.6);
  doc.line(marginLeft, y, pageWidth - marginRight, y);
  y += 8;

  // ---- Sección 1: datos del trabajador ----
  doc.setFillColor(...blue);
  doc.rect(marginLeft, y - 4.5, contentWidth, 6.5, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(255, 255, 255);
  doc.text('DATOS DEL TRABAJADOR A QUIEN SE LE ENTREGA EL ELEMENTO', marginLeft + 2, y);
  y += 9;

  doc.setTextColor(...dark);
  doc.setFontSize(9.5);
  doc.setFont('helvetica', 'bold');
  doc.text('NOMBRE:', marginLeft, y);
  doc.text('CÉDULA No.:', marginLeft + 95, y);
  doc.setFont('helvetica', 'normal');
  doc.text(empleado.nombre || '', marginLeft + 22, y);
  doc.text(empleado.cedula || '', marginLeft + 120, y);
  y += 7;
  doc.setFont('helvetica', 'bold');
  doc.text('CARGO:', marginLeft, y);
  doc.setFont('helvetica', 'normal');
  doc.text(empleado.cargo || '', marginLeft + 22, y);
  y += 8;

  // ---- Sección 2: tabla de EPP entregados (en blanco, para diligenciar a mano) ----
  doc.setFillColor(...blue);
  doc.rect(marginLeft, y - 4.5, contentWidth, 6.5, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(255, 255, 255);
  doc.text('ELEMENTOS DE PROTECCIÓN PERSONAL (EPP) ENTREGADOS', marginLeft + 2, y);
  y += 6;

  const filas = [];
  for (let i = 1; i <= 10; i++) filas.push([String(i), '', '', '', '']);

  doc.autoTable({
    startY: y,
    margin: { left: marginLeft, right: marginRight },
    head: [['ÍTEM', 'EPP ENTREGADOS:', 'CANTIDAD', 'FECHA', 'FIRMA RECIBIDO']],
    body: filas,
    theme: 'grid',
    headStyles: { fillColor: [30, 41, 59], textColor: 255, fontStyle: 'bold', halign: 'center', fontSize: 8.5 },
    styles: { fontSize: 9, cellPadding: 2.6, lineColor: [180, 180, 180], minCellHeight: 7 },
    columnStyles: {
      0: { cellWidth: 12, halign: 'center' },
      1: { cellWidth: contentWidth - 12 - 24 - 24 - 34 },
      2: { cellWidth: 24, halign: 'center' },
      3: { cellWidth: 24, halign: 'center' },
      4: { cellWidth: 34, halign: 'center' },
    },
  });

  y = doc.lastAutoTable.finalY + 8;

  // ---- Sección 3: responsable de la entrega ----
  doc.setFillColor(...blue);
  doc.rect(marginLeft, y - 4.5, contentWidth, 6.5, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(255, 255, 255);
  doc.text('DATOS DEL RESPONSABLE DE LA ENTREGA DE LOS ELEMENTOS', marginLeft + 2, y);
  y += 9;

  doc.setTextColor(...dark);
  doc.setFontSize(9.5);
  doc.setFont('helvetica', 'bold');
  doc.setLineWidth(0.2);
  doc.setDrawColor(150, 150, 150);

  doc.text('NOMBRE:', marginLeft, y);
  doc.line(marginLeft + 20, y, marginLeft + 88, y);
  doc.text('CÉDULA No.:', marginLeft + 95, y);
  doc.line(marginLeft + 120, y, pageWidth - marginRight, y);
  y += 7;
  doc.text('CARGO:', marginLeft, y);
  doc.line(marginLeft + 20, y, marginLeft + 88, y);
  doc.text('FIRMA:', marginLeft + 95, y);
  doc.line(marginLeft + 112, y, pageWidth - marginRight, y);
  y += 6;

  // ---- Sección 4: compromiso ----
  doc.setFillColor(...blue);
  doc.rect(marginLeft, y - 4.5, contentWidth, 6.5, 'F');
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(255, 255, 255);
  doc.text('COMPROMISO', marginLeft + 2, y);
  y += 7;

  doc.setTextColor(...dark);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8.3);
  const compromiso1 = 'Me comprometo a utilizar correctamente durante la jornada laboral los elementos de protección personal recibidos y mantenerlos en buen estado, dando cumplimiento a las normas de Seguridad y Salud en el Trabajo que contribuyen a mi bienestar físico, psicológico y social.';
  let lines = doc.splitTextToSize(compromiso1, contentWidth);
  doc.text(lines, marginLeft, y);
  y += lines.length * 4 + 2.5;

  const compromiso2 = 'Usando los equipos y elementos de protección personal (incluyendo ropa de trabajo) estoy cumpliendo con mis deberes y responsabilidades como trabajador definidos en la ley a través de la siguiente normatividad: CODIGO SUSTANTIVO DEL TRABAJO; Art. 56 y Art. 58 numeral 7; LEY 9 DE 1979; Art. 88; DECRETO 1295 DE 1994: Art. 22. DECRETO 1072 DE 2015 Capitulo 6.SG-SST Artículo 2.2.4.6.10. Responsabilidades del trabajador.';
  lines = doc.splitTextToSize(compromiso2, contentWidth);
  doc.text(lines, marginLeft, y);
  y += lines.length * 4 + 2.5;

  const compromiso3 = 'El presente compromiso aplica para los elementos de protección personal entregados.';
  lines = doc.splitTextToSize(compromiso3, contentWidth);
  doc.text(lines, marginLeft, y);
  y += lines.length * 4 + 8;

  // ---- Firma final ----
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setLineWidth(0.2);
  doc.setDrawColor(150, 150, 150);

  doc.text('FIRMA DE QUIEN RECIBE EL ELEMENTO Y LEE EL COMPROMISO:', marginLeft, y);
  y += 9;
  doc.line(marginLeft, y, marginLeft + 95, y);
  y += 8;
  doc.text('CÉDULA No.:', marginLeft, y);
  doc.line(marginLeft + 27, y, marginLeft + 85, y);

  return doc;
}

function descargarFormatoEPP(empleado) {
  const doc = generarFormatoEPP(empleado);
  const nombreArchivo = `Formato_EPP_${(empleado.cedula || 'sin_cedula')}_${Date.now()}.pdf`;
  doc.save(nombreArchivo);
}
