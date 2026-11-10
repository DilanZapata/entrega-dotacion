// Genera el Acta de Entrega de Equipos en Préstamo (arnés, casco, eslinga, etc.).
// Deja constancia de la actividad, el código/serial de cada equipo y su estado al
// entregar y al devolver.
// Requiere: jsPDF + jspdf-autotable (CDN), js/app.js y js/logo.js cargados antes.

function generarActaPrestamo(prestamo) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: 'mm', format: 'letter' });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const marginLeft = 18;
  const marginRight = 18;
  const contentWidth = pageWidth - marginLeft - marginRight;
  const blue = [91, 155, 213];
  const gray = [90, 90, 90];
  const dark = [30, 30, 30];
  const devuelto = prestamo.estado === 'devuelto';

  const tituloSeccion = (texto, y) => {
    doc.setFillColor(...blue);
    doc.rect(marginLeft, y - 4.5, contentWidth, 6.5, 'F');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(9);
    doc.setTextColor(255, 255, 255);
    doc.text(texto, marginLeft + 2, y);
    doc.setTextColor(...dark);
  };

  const asegurarEspacio = (y, alto) => {
    if (y + alto > pageHeight - 15) {
      doc.addPage();
      return 22;
    }
    return y;
  };

  // ---- Encabezado ----
  let y = 18;
  try {
    doc.addImage(LOGO_BASE64, 'PNG', marginLeft, y - 7, 18, 15.1);
  } catch (e) { /* si el logo no carga, se omite sin romper el PDF */ }

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(13);
  doc.setTextColor(...dark);
  doc.text('ACTA DE ENTREGA DE EQUIPOS EN PRÉSTAMO', pageWidth / 2 + 8, y - 1, { align: 'center' });
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8.5);
  doc.setTextColor(...gray);
  doc.text('Equipos de protección y trabajo seguro', pageWidth / 2 + 8, y + 4, { align: 'center' });

  y += 10;
  doc.setDrawColor(...blue);
  doc.setLineWidth(0.6);
  doc.line(marginLeft, y, pageWidth - marginRight, y);
  y += 8;

  // ---- Cuerpo ----
  const fecha = prestamo.fecha_prestamo ? new Date(prestamo.fecha_prestamo.replace(' ', 'T')) : new Date();
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10.5);
  doc.setTextColor(...dark);
  doc.text(`Yotoco – Valle del Cauca, ${fechaLargaEs(fecha)}`, marginLeft, y);
  y += 7;

  const vinculacion = prestamo.vinculacion === 'CONTRATISTA' ? ' (contratista)' : '';
  const parrafo = `Con la presente acta se hace entrega, en calidad de préstamo, de los equipos relacionados a continuación al trabajador ${prestamo.nombre}${vinculacion}, identificado con cédula de ciudadanía ${prestamo.cedula}, quien desempeña el cargo de ${prestamo.cargo}.`;
  let lines = doc.splitTextToSize(parrafo, contentWidth);
  doc.text(lines, marginLeft, y);
  y += lines.length * 4.8 + 4;

  tituloSeccion('ACTIVIDAD PARA LA QUE SE PRESTAN LOS EQUIPOS', y);
  y += 8;
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9.5);
  doc.text('Actividad:', marginLeft, y);
  doc.setFont('helvetica', 'normal');
  lines = doc.splitTextToSize(prestamo.actividad || '', contentWidth - 22);
  doc.text(lines, marginLeft + 22, y);
  y += lines.length * 4.5 + 2.5;

  doc.setFont('helvetica', 'bold');
  doc.text('Lugar:', marginLeft, y);
  doc.text('Devolución prevista:', marginLeft + 95, y);
  doc.setFont('helvetica', 'normal');
  doc.text(prestamo.lugar || '________________________', marginLeft + 22, y);
  doc.text(prestamo.fecha_devolucion_prevista || '______________', marginLeft + 131, y);
  y += 9;

  // ---- Equipos entregados ----
  tituloSeccion('EQUIPOS ENTREGADOS Y ESTADO AL MOMENTO DE LA ENTREGA', y);
  y += 4;
  doc.autoTable({
    startY: y,
    margin: { left: marginLeft, right: marginRight },
    head: [['ÍTEM', 'EQUIPO', 'CÓDIGO', 'MARCA / SERIAL', 'ESTADO', 'OBSERVACIONES']],
    body: (prestamo.items || []).map((it, i) => [
      String(i + 1),
      it.tipo || '',
      it.codigo || '',
      [it.marca, it.serial].filter(Boolean).join(' / '),
      it.estado_entrega || '',
      it.observaciones || '',
    ]),
    theme: 'grid',
    headStyles: { fillColor: [30, 41, 59], textColor: 255, fontStyle: 'bold', halign: 'center', fontSize: 8 },
    styles: { fontSize: 8.5, cellPadding: 1.6, lineColor: [180, 180, 180] },
    columnStyles: {
      0: { cellWidth: 11, halign: 'center' },
      1: { cellWidth: 34 },
      2: { cellWidth: 24, halign: 'center' },
      3: { cellWidth: 38 },
      4: { cellWidth: 20, halign: 'center' },
    },
  });
  y = doc.lastAutoTable.finalY + 7;

  // ---- Compromisos ----
  y = asegurarEspacio(y, 50);
  tituloSeccion('COMPROMISOS DEL TRABAJADOR', y);
  y += 7;
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8.8);
  const compromisos = [
    'Usar los equipos únicamente para la actividad indicada en esta acta y de acuerdo con la capacitación recibida.',
    'Inspeccionarlos antes de cada uso e informar de inmediato cualquier daño, defecto o caída que hayan soportado. Un equipo que haya detenido una caída o presente daños no debe volver a usarse.',
    'Cuidarlos, no modificarlos ni prestarlos a terceros, y devolverlos al terminar la actividad en el estado en que los recibió, salvo el desgaste normal por el uso.',
  ];
  compromisos.forEach((c) => {
    lines = doc.splitTextToSize(`•  ${c}`, contentWidth - 3);
    doc.text(lines, marginLeft + 1.5, y);
    y += lines.length * 4.1 + 1.2;
  });
  y += 1;
  doc.setFontSize(7.8);
  doc.setTextColor(...gray);
  lines = doc.splitTextToSize('Referencia: Decreto 1072 de 2015, artículo 2.2.4.6.10 (responsabilidades de los trabajadores) y Resolución 4272 de 2021 (requisitos mínimos de seguridad para trabajo en alturas).', contentWidth);
  doc.text(lines, marginLeft, y);
  y += lines.length * 3.6 + 3;
  doc.setTextColor(...dark);

  // ---- Firmas de entrega ----
  y = asegurarEspacio(y, 24);
  y += 10;
  const col2X = marginLeft + contentWidth / 2 + 6;
  doc.setLineWidth(0.3);
  doc.setDrawColor(60, 60, 60);
  doc.line(marginLeft, y, marginLeft + contentWidth / 2 - 8, y);
  doc.line(col2X, y, pageWidth - marginRight, y);
  y += 4.5;
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.8);
  doc.text('Recibe (trabajador)', marginLeft, y);
  doc.text('Entrega', col2X, y);
  y += 4;
  doc.setFont('helvetica', 'normal');
  doc.text(`${prestamo.nombre} · C.C. ${prestamo.cedula}`, marginLeft, y);
  doc.text(prestamo.entrega || 'Nombre: ______________________', col2X, y);
  y += 9;

  // ---- Devolución ----
  y = asegurarEspacio(y, 11 + (prestamo.items || []).length * 6.5 + 31);
  tituloSeccion('DEVOLUCIÓN DE LOS EQUIPOS', y);
  y += 4;
  doc.autoTable({
    startY: y,
    margin: { left: marginLeft, right: marginRight },
    head: [['ÍTEM', 'EQUIPO', 'CÓDIGO', 'ESTADO AL DEVOLVER', 'OBSERVACIONES']],
    body: (prestamo.items || []).map((it, i) => [
      String(i + 1),
      it.tipo || '',
      it.codigo || '',
      devuelto ? (it.estado_devolucion || '') : '',
      devuelto ? (it.observaciones_devolucion || '') : '',
    ]),
    theme: 'grid',
    headStyles: { fillColor: [30, 41, 59], textColor: 255, fontStyle: 'bold', halign: 'center', fontSize: 8 },
    styles: { fontSize: 8.5, cellPadding: 1.6, lineColor: [180, 180, 180], minCellHeight: 6.5 },
    columnStyles: {
      0: { cellWidth: 11, halign: 'center' },
      1: { cellWidth: 34 },
      2: { cellWidth: 24, halign: 'center' },
      3: { cellWidth: 42, halign: 'center' },
    },
  });
  y = doc.lastAutoTable.finalY + 6;

  y = asegurarEspacio(y, 22);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8.8);
  doc.text('Fecha de devolución:', marginLeft, y);
  doc.setFont('helvetica', 'normal');
  doc.text(devuelto && prestamo.fecha_devolucion ? prestamo.fecha_devolucion.slice(0, 10) : '______________', marginLeft + 36, y);
  y += 11;
  doc.setLineWidth(0.3);
  doc.setDrawColor(60, 60, 60); // la tabla anterior deja el trazo en gris claro
  doc.line(marginLeft, y, marginLeft + contentWidth / 2 - 8, y);
  doc.line(col2X, y, pageWidth - marginRight, y);
  y += 4.5;
  doc.setFont('helvetica', 'bold');
  doc.text('Devuelve (trabajador)', marginLeft, y);
  doc.text('Recibe', col2X, y);
  if (devuelto && prestamo.recibe_devolucion) {
    doc.setFont('helvetica', 'normal');
    doc.text(prestamo.recibe_devolucion, col2X, y + 4);
  }

  // ---- Pie de página en todas las hojas ----
  const paginas = doc.internal.getNumberOfPages();
  for (let p = 1; p <= paginas; p++) {
    doc.setPage(p);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(...gray);
    doc.text('AGROPECUARIA CHIQUIQUE SAS', pageWidth / 2, pageHeight - 10, { align: 'center' });
  }

  return doc;
}

function descargarActaPrestamo(prestamo) {
  const doc = generarActaPrestamo(prestamo);
  doc.save(`Acta_Prestamo_${prestamo.cedula || 'sin_cedula'}_${(prestamo.fecha_prestamo || '').slice(0, 10)}.pdf`);
}
