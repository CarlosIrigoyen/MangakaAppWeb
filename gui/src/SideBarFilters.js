import React, { useState, useEffect } from 'react';

const SidebarFilters = ({ onFilterChange }) => {
  // Estado para filtros exclusivos
  const [filters, setFilters] = useState({
    author: null,
    language: null,
    manga: null,
    editorial: null,
    priceRange: [0, 100], // Valores por defecto, se actualizarán
    searchText: '',
    applyPriceFilter: 0,
  });

  const [availableFilters, setAvailableFilters] = useState({
    authors: [],
    languages: [],
    mangas: [],
    editorials: [],
    minPrice: 0,
    maxPrice: 0,
  });

  // Imprime el rango de precios actual del filtro, no del availableFilters
  console.log('Rango de precios actual:', filters.priceRange);

  // Estados para secciones colapsables
  const [openSections, setOpenSections] = useState({
    authors: true,
    languages: true,
    mangas: true,
    editorials: true,
    price: true,
  });

  // Obtener filtros disponibles del backend
  useEffect(() => {
    async function fetchFilters() {
      try {
        const response = await fetch('http://localhost:8000/api/filters');
        const result = await response.json();
        // Actualizamos el estado de los filtros disponibles
        setAvailableFilters(result);
        // También actualizamos el rango de precios del filtro con los valores recibidos
        setFilters((prev) => ({
          ...prev,
          priceRange: [result.minPrice, result.maxPrice],
        }));
      } catch (error) {
        console.error('Error fetching filters', error);
      }
    }
    fetchFilters();
  }, []);

  // Función para actualizar filtros y enviar al componente padre
  const updateFilters = (newFilters) => {
    setFilters(newFilters);
    console.log('Actualizando filtros, priceRange:', newFilters.priceRange);
    let transformedFilters = {
      authors: newFilters.author ? [newFilters.author] : [],
      languages: newFilters.language ? [newFilters.language] : [],
      mangas: newFilters.manga ? [newFilters.manga] : [],
      editorials: newFilters.editorial ? [newFilters.editorial] : [],
      searchText: newFilters.searchText,
      sortBy: 'titulo,numero_tomo',
    };

    if (newFilters.applyPriceFilter === 1) {
      const formattedMin = parseFloat(newFilters.priceRange[0]).toFixed(2);
      const formattedMax = parseFloat(newFilters.priceRange[1]).toFixed(2);
      transformedFilters = {
        ...transformedFilters,
        applyPriceFilter: 1,
        minPrice: formattedMin,
        maxPrice: formattedMax,
      };
    }

    console.log('Filtros transformados enviados al padre:', transformedFilters);
    onFilterChange(transformedFilters);
  };

  // Para filtros exclusivos: si se hace clic sobre el mismo valor se deselecciona
  const handleExclusiveChange = (filterType, value) => {
    const current = filters[filterType];
    const updated = current === value ? null : value;
    updateFilters({ ...filters, [filterType]: updated });
  };

  // Actualiza el searchText y dispara el updateFilters inmediatamente
  const handleSearchTextChange = (event) => {
    updateFilters({ ...filters, searchText: event.target.value });
  };

  const toggleSection = (section) => {
    setOpenSections({ ...openSections, [section]: !openSections[section] });
  };

  return (
    <div className="sidebar bg-secondary text-white p-3" style={{ width: '200px' }}>
      {/* Autores */}
      <div className="mb-3">
        <div className="d-flex justify-content-between align-items-center">
          <h6 className="mb-0">Autores</h6>
          <button className="btn btn-sm btn-light" onClick={() => toggleSection('authors')}>
            {openSections.authors ? '-' : '+'}
          </button>
        </div>
        {openSections.authors && (
          <div>
            {availableFilters.authors.map((author) => (
              <div key={author.id}>
                <input
                  type="radio"
                  id={`author-${author.id}`}
                  name="author"
                  value={author.id}
                  checked={filters.author === author.id}
                  onChange={() => handleExclusiveChange('author', author.id)}
                />
                <label htmlFor={`author-${author.id}`} className="ms-1">
                  {author.nombre + ' ' + author.apellido}
                </label>
              </div>
            ))}
          </div>
        )}
      </div>
      <hr />

      {/* Idiomas */}
      <div className="mb-3">
        <div className="d-flex justify-content-between align-items-center">
          <h6 className="mb-0">Idiomas</h6>
          <button className="btn btn-sm btn-light" onClick={() => toggleSection('languages')}>
            {openSections.languages ? '-' : '+'}
          </button>
        </div>
        {openSections.languages && (
          <div>
            {availableFilters.languages.map((language, index) => (
              <div key={index}>
                <input
                  type="radio"
                  id={`language-${language}`}
                  name="language"
                  value={language}
                  checked={filters.language === language}
                  onChange={() => handleExclusiveChange('language', language)}
                />
                <label htmlFor={`language-${language}`} className="ms-1">
                  {language}
                </label>
              </div>
            ))}
          </div>
        )}
      </div>
      <hr />

      {/* Mangas */}
      <div className="mb-3">
        <div className="d-flex justify-content-between align-items-center">
          <h6 className="mb-0">Mangas</h6>
          <button className="btn btn-sm btn-light" onClick={() => toggleSection('mangas')}>
            {openSections.mangas ? '-' : '+'}
          </button>
        </div>
        {openSections.mangas && (
          <div>
            {availableFilters.mangas.map((manga) => (
              <div key={manga.id}>
                <input
                  type="radio"
                  id={`manga-${manga.id}`}
                  name="manga"
                  value={manga.id}
                  checked={filters.manga === manga.id}
                  onChange={() => handleExclusiveChange('manga', manga.id)}
                />
                <label htmlFor={`manga-${manga.id}`} className="ms-1">
                  {manga.titulo}
                </label>
              </div>
            ))}
          </div>
        )}
      </div>
      <hr />

      {/* Editoriales */}
      <div className="mb-3">
        <div className="d-flex justify-content-between align-items-center">
          <h6 className="mb-0">Editoriales</h6>
          <button className="btn btn-sm btn-light" onClick={() => toggleSection('editorials')}>
            {openSections.editorials ? '-' : '+'}
          </button>
        </div>
        {openSections.editorials && (
          <div>
            {availableFilters.editorials.map((editorial) => (
              <div key={editorial.id}>
                <input
                  type="radio"
                  id={`editorial-${editorial.id}`}
                  name="editorial"
                  value={editorial.id}
                  checked={filters.editorial === editorial.id}
                  onChange={() => handleExclusiveChange('editorial', editorial.id)}
                />
                <label htmlFor={`editorial-${editorial.id}`} className="ms-1">
                  {editorial.nombre}
                </label>
              </div>
            ))}
          </div>
        )}
      </div>
      <hr />
    </div>
  );
};

export default SidebarFilters;
