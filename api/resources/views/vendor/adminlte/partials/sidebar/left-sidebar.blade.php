<aside class="main-sidebar {{ config('adminlte.classes_sidebar', 'sidebar-dark-primary elevation-4') }}" role="complementary" aria-label="Panel lateral de navegación">

    {{-- Sidebar brand logo --}}
    @if(config('adminlte.logo_img_xl'))
        @include('adminlte::partials.common.brand-logo-xl')
    @else
        @include('adminlte::partials.common.brand-logo-xs')
    @endif

    {{-- Sidebar menu --}}
    <div class="sidebar">
        <nav class="pt-2" aria-label="Navegación principal">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" data-accordion="false">
                {{-- Ítem Autores --}}
                <li class="nav-item">
                    <a href="{{ route('autores.index') }}"
                       class="nav-link {{ request()->routeIs('autores.*') ? 'active' : '' }}"
                       @if(request()->routeIs('autores.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-user" aria-hidden="true"></i>
                        <p>Autores</p>
                    </a>
                </li>

                {{-- Ítem Dibujantes --}}
                <li class="nav-item">
                    <a href="{{ route('dibujantes.index') }}"
                       class="nav-link {{ request()->routeIs('dibujantes.*') ? 'active' : '' }}"
                       @if(request()->routeIs('dibujantes.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-pencil-alt" aria-hidden="true"></i>
                        <p>Dibujantes</p>
                    </a>
                </li>

                {{-- Ítem Editoriales --}}
                <li class="nav-item">
                    <a href="{{ route('editoriales.index') }}"
                       class="nav-link {{ request()->routeIs('editoriales.*') ? 'active' : '' }}"
                       @if(request()->routeIs('editoriales.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-building" aria-hidden="true"></i>
                        <p>Editoriales</p>
                    </a>
                </li>

                {{-- Ítem Géneros --}}
                <li class="nav-item">
                    <a href="{{ route('generos.index') }}"
                       class="nav-link {{ request()->routeIs('generos.*') ? 'active' : '' }}"
                       @if(request()->routeIs('generos.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-tags" aria-hidden="true"></i>
                        <p>Géneros</p>
                    </a>
                </li>

                {{-- Ítem Mangas --}}
                <li class="nav-item">
                    <a href="{{ route('mangas.index') }}"
                       class="nav-link {{ request()->routeIs('mangas.*') ? 'active' : '' }}"
                       @if(request()->routeIs('mangas.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-book" aria-hidden="true"></i>
                        <p>Mangas</p>
                    </a>
                </li>

                {{-- Ítem Tomos --}}
                <li class="nav-item">
                    <a href="{{ route('tomos.index') }}"
                       class="nav-link {{ request()->routeIs('tomos.*') ? 'active' : '' }}"
                       @if(request()->routeIs('tomos.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-archive" aria-hidden="true"></i>
                        <p>Tomos</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

</aside>

