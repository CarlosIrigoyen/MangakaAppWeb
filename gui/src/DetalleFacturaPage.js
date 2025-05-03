// src/DetalleFacturaPage.js
import React, { useEffect, useState, useRef } from 'react';
import { useParams } from 'react-router-dom';
import { Table, Spinner, Alert, Button } from 'react-bootstrap';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';

const DetalleFacturaPage = () => {
  const { id } = useParams();
  const [factura, setFactura] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const facturaRef = useRef();

  useEffect(() => {
    const fetchFactura = async () => {
      try {
        const token = localStorage.getItem('token');
        const res = await fetch(`http://localhost:8000/api/mis-facturas/${id}`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        if (!res.ok) throw new Error('No se pudo cargar la factura');
        const data = await res.json();
        setFactura(data);
      } catch (e) {
        setError(e.message);
      } finally {
        setLoading(false);
      }
    };
    fetchFactura();
  }, [id]);

  if (loading) return <Spinner animation="border" />;
  if (error)   return <Alert variant="danger">{error}</Alert>;

  const descargarComoPdf = async () => {
    const element = facturaRef.current;
    // 1) Captura a canvas en alta resolución
    const canvas = await html2canvas(element, { scale: 2 });
    const imgData = canvas.toDataURL('image/png');

    // 2) Configura jsPDF y márgenes
    const pdf = new jsPDF({ unit: 'pt', format: 'a4' });
    const pageWidth  = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const margin = 40;  // margen de 40pt por cada lado

    // 3) Calcula dimensiones de la imagen para que respete márgenes
    const imgWidth  = pageWidth - margin * 2;
    const imgHeight = (canvas.height * imgWidth) / canvas.width;

    // 4) Inserta imagen con offset igual al margen
    pdf.addImage(imgData, 'PNG', margin, margin, imgWidth, imgHeight);

    // 5) Guarda el PDF
    const nombre = factura.numero.replace(/^FAC-/, '');
    pdf.save(`Factura-${nombre}.pdf`);
  };

  const fechaSolo = new Date(factura.fecha).toLocaleDateString();

  return (
    <div className="p-5 bg-white text-dark" style={{ maxWidth: 800, margin: 'auto' }}>
      {/* ─────────────── CONTENEDOR A CAPTURAR ─────────────── */}
      <div ref={facturaRef}>
        {/* Encabezado con logo */}
        <div className="d-flex justify-content-end mb-4">
          <img src="/img/Mangaka.png" alt="Logo" width={120} />
        </div>

        {/* Datos */}
        <div className="row mb-4">
          <div className="col-6">
            <h6>Datos del Cliente</h6>
            <p className="mb-1">{factura.cliente.nombre}</p>
          </div>
          <div className="col-6 text-end">
            <h6>Factura Nº {factura.numero.replace(/^FAC-/, '')}</h6>
            <p className="mb-1">Fecha: {fechaSolo}</p>
          </div>
        </div>

        {/* Tabla */}
        <Table bordered>
          <thead className="bg-dark text-white">
            <tr>
              <th>Concepto</th>
              <th className="text-center">Cantidad</th>
              <th className="text-end">Precio</th>
              <th className="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            {factura.detalles.map(d => (
              <tr key={d.tomo_id}>
                <td>{`${d.titulo} – Tomo ${d.numero_tomo}`}</td>
                <td className="text-center">{d.cantidad}</td>
                {/* Símbolo cambiado a $ */}
                <td className="text-end">${(+d.precio_unitario).toFixed(2)}</td>
                <td className="text-end">${(+d.subtotal).toFixed(2)}</td>
              </tr>
            ))}
          </tbody>
        </Table>

        {/* Totales */}
        <div className="d-flex justify-content-end mt-3">
          <div style={{ width: 200 }}>
            <div className="d-flex justify-content-between">
              <span>Subtotal</span>
              <span>${factura.detalles
                .reduce((sum, d) => sum + Number(d.subtotal), 0)
                .toFixed(2)
              }</span>
            </div>
            <hr />
            <div className="d-flex justify-content-between fw-bold">
              <span>Total</span>
              <span>${(+factura.total).toFixed(2)}</span>
            </div>
          </div>
        </div>
      </div>

      {/* ───────── BOTÓN DESCARGAR ───────── */}
      <div className="text-end mt-4">
        <Button onClick={descargarComoPdf}>Descargar Factura (PDF)</Button>
      </div>
    </div>
  );
};

export default DetalleFacturaPage;
