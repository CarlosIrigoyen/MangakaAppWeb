import React, { createContext, useState, useEffect } from 'react';

export const CartContext = createContext();

export const CartProvider = ({ children }) => {
  // Cargar carrito desde sessionStorage al iniciar
  const [cart, setCart] = useState(() => {
    const savedCart = sessionStorage.getItem('cart');
    return savedCart ? JSON.parse(savedCart) : [];
  });

  // Guardar carrito en sessionStorage cada vez que cambia
  useEffect(() => {
    sessionStorage.setItem('cart', JSON.stringify(cart));
  }, [cart]);

  // Agrega un nuevo ítem si no está en el carrito
  const addToCart = (item) => {
    if (!cart.find(cartItem => cartItem.id === item.id)) {
      setCart([...cart, { ...item, quantity: 1 }]);
    }
  };

  // Actualiza la cantidad de un ítem en el carrito
  const updateCartItem = (itemId, quantity) => {
    const updatedCart = cart.map(item =>
      item.id === itemId ? { ...item, quantity } : item
    );
    setCart(updatedCart);
  };

  // Elimina un ítem del carrito
  const removeCartItem = (itemId) => {
    const updatedCart = cart.filter(item => item.id !== itemId);
    setCart(updatedCart);
  };

  // Vacía el carrito y limpia sessionStorage
  const clearCart = () => {
    setCart([]);
    sessionStorage.removeItem('cart');
  };

  return (
    <CartContext.Provider value={{ cart, addToCart, updateCartItem, removeCartItem, clearCart }}>
      {children}
    </CartContext.Provider>
  );
};
