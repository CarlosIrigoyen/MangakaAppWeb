import React, { useState, useEffect } from 'react';

const SidebarFilters = ({ onFilterChange }) => {
  // Estado para filtros exclusivos y de precio
  const [filters, setFilters] = useState({
    author: null,
    language: null,
    manga: null,
    editorial: null,
    minPrice: '',
    maxPrice: '',
    searchText: '',
    applyPriceFilter: 0,
  });

  const [availableFilters, setAvailableFilters] = useState({
    authors: [],
    languages: [],
    mangas: [],
    editorials: [],
  });

  // Secciones colapsables
  const [openSections, setOpenSections] = useState({
    authors: true,
    languages: true,
    mangas: true,
    editorials: true,
    price: true,
  });

  // Cargar filtros disponibles desde el backend
  useEffect(() => {
    async function fetchFilters() {
      try {
        const response = await fetch('http://localhost:8000/api/filters');
        const result = await response.json();
        setAvailableFilters(result);
      } catch (error) {
        console.error('Error fetching filters', error);
      }
    }
    fetchFilters();
  }, []);

  // Actualiza filtros y notifica al padre
  const updateFilters = (newFilters) => {
    setFilters(newFilters);
    const transformed = {
      authors: newFilters.author ? [newFilters.author] : [],
      languages: newFilters.language ? [newFilters.language] : [],
      mangas: newFilters.manga ? [newFilters.manga] : [],
      editorials: newFilters.editorial ? [newFilters.editorial] : [],
      searchText: newFilters.searchText,
      sortBy: 'titulo,numero_tomo',
    };

    if (newFilters.applyPriceFilter === 1 && newFilters.minPrice !== '' && newFilters.maxPrice !== '') {
      transformed.applyPriceFilter = 1;
      transformed.minPrice = parseFloat(newFilters.minPrice).toFixed(2);
      transformed.maxPrice = parseFloat(newFilters.maxPrice).toFixed(2);

  console.log('→ enviando filtros al padre:', transformed);
    }

    onFilterChange(transformed);
  };

  // Exclusivos
  const handleExclusiveChange = (field, value) => {
    const updated = filters[field] === value ? null : value;
    updateFilters({ ...filters, [field]: updated });
  };

  // Texto de búsqueda
  const handleSearchTextChange = (e) => {
    updateFilters({ ...filters, searchText: e.target.value });
  };

  // Inputs de precio
  const handlePriceInputChange = (e) => {
    const { name, value } = e.target;
    // Solo dígitos
    if (/^\d*$/.test(value)) {
      setFilters({ ...filters, [name]: value });
    }
  };

  const applyPrice = () => updateFilters({ ...filters, applyPriceFilter: 1 });
  const clearPriceFilter = () => updateFilters({ ...filters, applyPriceFilter: 0, minPrice: '', maxPrice: '' });

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
            {availableFilters.authors.map(a => (
              <div key={a.id}>
                <input
                  type="radio"
                  id={`author-${a.id}`}
                  name="author"
                  checked={filters.author === a.id}
                  onChange={() => handleExclusiveChange('author', a.id)}
                />
                <label htmlFor={`author-${a.id}`} className="ms-1">
                  {a.nombre} {a.apellido}
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
            {availableFilters.languages.map((lang, i) => (
              <div key={i}>
                <input
                  type="radio"
                  id={`language-${lang}`}
                  name="language"
                  checked={filters.language === lang}
                  onChange={() => handleExclusiveChange('language', lang)}
                />
                <label htmlFor={`language-${lang}`} className="ms-1">{lang}</label>
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
            {availableFilters.mangas.map(m => (
              <div key={m.id}>
                <input
                  type="radio"
                  id={`manga-${m.id}`}
                  name="manga"
                  checked={filters.manga === m.id}
                  onChange={() => handleExclusiveChange('manga', m.id)}
                />
                <label htmlFor={`manga-${m.id}`} className="ms-1">{m.titulo}</label>
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
            {availableFilters.editorials.map(e => (
              <div key={e.id}>
                <input
                  type="radio"
                  id={`editorial-${e.id}`}
                  name="editorial"
                  checked={filters.editorial === e.id}
                  onChange={() => handleExclusiveChange('editorial', e.id)}
                />
                <label htmlFor={`editorial-${e.id}`} className="ms-1">{e.nombre}</label>
              </div>
            ))}
          </div>
        )}
      </div>
      <hr />

      {/* Precio */}
      <div className="mb-3">
        <div className="d-flex justify-content-between align-items-center">
          <h6 className="mb-0">Precio</h6>
          <button className="btn btn-sm btn-light" onClick={() => toggleSection('price')}>
            {openSections.price ? '-' : '+'}
          </button>
        </div>
        {openSections.price && (
          <div>
            <div className="mb-2">
              <label className="form-label">Mínimo</label>
              <input
                type="number"
                name="minPrice"
                min="0"
                step="1"
                className="form-control"
                placeholder="Ej: 100"
                value={filters.minPrice}
                onChange={handlePriceInputChange}
              />
            </div>
            <div className="mb-2">
              <label className="form-label">Máximo</label>
              <input
                type="number"
                name="maxPrice"
                min="0"
                step="1"
                className="form-control"
                placeholder="Ej: 500"
                value={filters.maxPrice}
                onChange={handlePriceInputChange}
              />
            </div>
            <div className="d-flex justify-content-between">
              <button className="btn btn-primary btn-sm" onClick={applyPrice} disabled={!filters.minPrice || !filters.maxPrice}>
                Aplicar
              </button>
              <button className="btn btn-light btn-sm" onClick={clearPriceFilter}>
                Limpiar
              </button>
            </div>
          </div>
        )}
      </div>
      <hr />
    </div>
  );
};

export default SidebarFilters;