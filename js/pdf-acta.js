// Genera el Acta de Entrega de Dotación en PDF replicando el formato oficial
// TH-FO-33 de AGROPECUARIA CHIQUIQUE SAS, con los datos de un empleado específico.
// Requiere: jsPDF + jspdf-autotable (CDN) y js/logo.js cargados antes que este archivo.

function generarActaPDF(entrega) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: 'mm', format: 'letter' });

  const pageWidth = doc.internal.pageSize.getWidth();
  const marginLeft = 30;
  const marginRight = 30;
  const contentWidth = pageWidth - marginLeft - marginRight;
  const blue = [91, 155, 213];
  const gray = [90, 90, 90];
  const dark = [30, 30, 30];

  // ---- Encabezado ----
  let y = 18;
  try {
    doc.addImage(LOGO_BASE64, 'PNG', marginLeft, y - 6, 20, 16.8);
  } catch (e) { /* si el logo no carga, se omite sin romper el PDF */ }

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.setTextColor(...dark);
  doc.text('ENTREGA DE DOTACIÓN', pageWidth / 2, y, { align: 'center' });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(...gray);
  doc.text('CÓDIGO: TH-FO-33', pageWidth - marginRight, y - 3, { align: 'right' });
  doc.text('VERSIÓN: 01', pageWidth - marginRight, y + 2, { align: 'right' });

  y += 8;
  doc.setDrawColor(...blue);
  doc.setLineWidth(0.6);
  doc.line(marginLeft, y, pageWidth - marginRight, y);
  y += 9;

  // ---- Cuerpo ----
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(11);
  doc.setTextColor(...dark);

  const lugarFecha = entrega.lugar || `Yotoco – Valle del Cauca, ${fechaLargaEs(entrega.fecha ? new Date(entrega.fecha) : new Date())}`;
  doc.text(lugarFecha, marginLeft, y);
  y += 7;

  const parrafo1 = `Con la presente acta se le hace entrega de la siguiente dotación al trabajador ${entrega.nombre} identificado con cédula de ciudadanía ${entrega.cedula} el cual actualmente desempeña el cargo de ${entrega.cargo}.`;
  const l1 = doc.splitTextToSize(parrafo1, contentWidth);
  doc.text(l1, marginLeft, y);
  y += l1.length * 5 + 3;

  doc.text('El trabajador declara haber recibido la dotación descrita y se compromete a:', marginLeft, y);
  y += 6;

  const compromisos = [
    'Darle el uso adecuado durante la jornada laboral dentro y fuera de la empresa.',
    'De igual manera mantenerla en buen estado, para así optimizar su vida útil.',
    'En caso de daño o pérdida debe reportar inmediatamente al vigía ocupacional.',
    'La dotación pertenece a la empresa, por tal motivo en caso de terminación del contrato, se debe hacer devolución de la dotación suministrada.',
  ];
  compromisos.forEach((c) => {
    const lines = doc.splitTextToSize(`•  ${c}`, contentWidth - 4);
    doc.text(lines, marginLeft + 2, y);
    y += lines.length * 5 + 1;
  });

  y += 1;
  doc.setFont('helvetica', 'bold');
  const base = doc.splitTextToSize('Basado en el Código Sustantivo de Trabajo Art. 230 – 235.', contentWidth);
  doc.text(base, marginLeft, y);
  y += base.length * 5 + 3;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9.5);
  const legal = 'Usando la dotación de ropa y calzado de trabajo estoy cumpliendo con mis deberes y responsabilidades como trabajador definidos en la ley a través de la siguiente normatividad: CODIGO SUSTANTIVO DEL TRABAJO; Art. 230 - 235; DECRETO 1295 DE 1994: Art. 22. DECRETO 1072 DE 2015 Capitulo 6.SG-SST Artículo 2.2.4.6.10. Responsabilidades del trabajador.';
  const legalLines = doc.splitTextToSize(legal, contentWidth);
  doc.text(legalLines, marginLeft, y);
  y += legalLines.length * 4.4 + 6;

  // ---- Tabla de items ----
  const filasMinimas = 5;
  const items = entrega.items || [];
  const body = items.map((it, idx) => [
    String(idx + 1),
    it.descripcion || '',
    it.talla || '',
    String(it.cantidad ?? ''),
  ]);
  while (body.length < filasMinimas) {
    body.push(['', '', '', '']);
  }

  doc.autoTable({
    startY: y,
    margin: { left: marginLeft, right: marginRight },
    head: [['ÍTEM', 'DESCRIPCIÓN DE DOTACIÓN', 'TALLA', 'CANT.']],
    body,
    theme: 'grid',
    headStyles: { fillColor: blue, textColor: 255, fontStyle: 'bold', halign: 'center' },
    styles: { fontSize: 10, cellPadding: 1.8, lineColor: [200, 200, 200] },
    columnStyles: {
      0: { cellWidth: 16, halign: 'center' },
      1: { cellWidth: contentWidth - 16 - 28 - 22 },
      2: { cellWidth: 28, halign: 'center' },
      3: { cellWidth: 22, halign: 'center' },
    },
  });

  y = doc.lastAutoTable.finalY + 20;
  const signatureBlockHeight = 30; // "Para la..." + gap + lines + 2 text lines
  const footerReserve = 25; // espacio reservado para el pie de página
  if (y + signatureBlockHeight > doc.internal.pageSize.getHeight() - footerReserve) {
    doc.addPage();
    y = 30;
  }

  doc.setFontSize(11);
  doc.text('Para la respectiva constancia firman las partes interesadas:', marginLeft, y);
  y += 20;

  const col2X = marginLeft + contentWidth / 2 + 5;
  doc.setLineWidth(0.3);
  doc.setDrawColor(60, 60, 60);
  doc.line(marginLeft, y, marginLeft + contentWidth / 2 - 10, y);
  doc.line(col2X, y, pageWidth - marginRight, y);
  y += 5;
  doc.setFontSize(9.5);
  doc.text('Trabajador', marginLeft, y);
  doc.text('Dirección Administrativa', col2X, y);
  y += 4.5;
  doc.text('y Financiera', col2X, y);

  // ---- Pie de página ----
  const footerY = doc.internal.pageSize.getHeight() - 20;
  doc.setDrawColor(...blue);
  doc.setLineWidth(0.4);
  doc.line(marginLeft, footerY, pageWidth - marginRight, footerY);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(...gray);
  doc.text('AGROPECUARIA CHIQUIQUE SAS', pageWidth / 2, footerY + 5, { align: 'center' });
  doc.setFont('helvetica', 'normal');
  doc.text('E-mail: chiquique12@gmail.com', pageWidth / 2, footerY + 9.5, { align: 'center' });

  return doc;
}

function descargarActaPDF(entrega) {
  const doc = generarActaPDF(entrega);
  const nombreArchivo = `Acta_Entrega_${(entrega.cedula || 'sin_cedula')}.pdf`;
  doc.save(nombreArchivo);
}
