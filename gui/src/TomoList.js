import React, { useContext } from 'react';
import { Card, Button, Pagination } from 'react-bootstrap';
import { FaShoppingCart, FaInfoCircle } from 'react-icons/fa';
import { CartContext } from './CartContext';

const TomoList = ({ tomos, pagination, onPageChange, onShowInfo, isLoggedIn }) => {
  const { cart, addToCart } = useContext(CartContext);
  const data = tomos.data ? tomos.data : tomos;

  if (!data.length) return <p>No se encontraron resultados para los filtros seleccionados.</p>;

  return (
    <div className="container my-4">
      <div className="row">
        {data.map((tomo) => {
          const isInCart = cart.some((item) => item.id === tomo.id);
          return (
            <div key={tomo.id} className="col-md-3 mb-4 d-flex">
              <Card className="w-100 h-100">
                <Card.Img
                  variant="top"
                  src={tomo.portada}         // <-- uso directo del campo
                  alt={`${tomo.manga?.titulo} Tomo ${tomo.numero_tomo}`}
                  style={{ objectFit: 'cover', height: '200px' }}
                />
                <Card.Body className="d-flex flex-column">
                  <Card.Title>
                    {tomo.manga?.titulo} Tomo {tomo.numero_tomo} — {tomo.idioma}
                  </Card.Title>
                  <Card.Text>Precio: ${parseFloat(tomo.precio).toFixed(0)}</Card.Text>
                  <div className="mt-auto d-flex justify-content-center">
                    {isLoggedIn && (
                      <Button
                        variant="primary"
                        className="me-2"
                        onClick={() => addToCart(tomo)}
                        disabled={isInCart}
                      >
                        <FaShoppingCart /> Agrega al Carrito
                      </Button>
                    )}
                    <Button variant="info" onClick={() => onShowInfo(tomo)}>
                      <FaInfoCircle /> Info
                    </Button>
                  </div>
                </Card.Body>
              </Card>
            </div>
          );
        })}
      </div>
      {pagination && (
        <div className="d-flex justify-content-center">
          <Pagination>
            {[...Array(pagination.lastPage)].map((_, i) => (
              <Pagination.Item
                key={i+1}
                active={i+1 === pagination.currentPage}
                onClick={() => onPageChange(i+1)}
              >
                {i+1}
              </Pagination.Item>
            ))}
          </Pagination>
        </div>
      )}
    </div>
  );
};

export default TomoList;
