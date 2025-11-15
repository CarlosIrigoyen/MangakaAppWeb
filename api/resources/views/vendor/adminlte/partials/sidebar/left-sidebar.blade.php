<aside class="main-sidebar {{ config('adminlte.classes_sidebar', 'sidebar-dark-primary elevation-4') }}" role="complementary" aria-label="Panel lateral de navegación">

    {{-- Sidebar brand logo --}}
    @if(config('adminlte.logo_img_xl'))
        @include('adminlte::partials.common.brand-logo-xl')
    @else
        @include('adminlte::partials.common.brand-logo-xs')
    @endif

    {{-- Sidebar menu --}}
    <div class="sidebar">
        <nav class="pt-2" aria-label="Navegación principal del sitio">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" data-accordion="false">
                {{-- Menú Gestión de Recursos: proporcionamos un control con aria --}}
                <li class="nav-item has-treeview {{ request()->routeIs('autores.*') || request()->routeIs('dibujantes.*') ? 'menu-open' : '' }}">
                    {{-- Control del submenú --}}
                    <a href="#" class="nav-link" role="button"
                       aria-expanded="{{ (request()->routeIs('autores.*') || request()->routeIs('dibujantes.*')) ? 'true' : 'false' }}"
                       aria-controls="submenu-recursos" id="btn-submenu-recursos">
                        <i class="nav-icon fas fa-folder" aria-hidden="true"></i>
                        <p>
                            Gestión de recursos
                            <i class="right fas fa-angle-left" aria-hidden="true"></i>
                        </p>
                    </a>

                    {{-- Submenu: role group y id para aria-controls --}}
                    <ul id="submenu-recursos" class="nav nav-treeview" role="group" aria-labelledby="btn-submenu-recursos">
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
                    </ul>
                </li>

                {{-- Ítem Editoriales --}}
                <li class="nav-item">
                    <a href="{{ route('editoriales.index') }}" class="nav-link {{ request()->routeIs('editoriales.*') ? 'active' : '' }}" @if(request()->routeIs('editoriales.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-building" aria-hidden="true"></i>
                        <p>Editoriales</p>
                    </a>
                </li>

                {{-- Ítem Generos --}}
                <li class="nav-item">
                    <a href="{{ route('generos.index') }}" class="nav-link {{ request()->routeIs('generos.*') ? 'active' : '' }}" @if(request()->routeIs('generos.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-tags" aria-hidden="true"></i>
                        <p>Géneros</p>
                    </a>
                </li>

                {{-- Ítem Mangas --}}
                <li class="nav-item">
                    <a href="{{ route('mangas.index') }}" class="nav-link {{ request()->routeIs('mangas.*') ? 'active' : '' }}" @if(request()->routeIs('mangas.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-book" aria-hidden="true"></i>
                        <p>Mangas</p>
                    </a>
                </li>

                {{-- Ítem Tomos --}}
                <li class="nav-item">
                    <a href="{{ route('tomos.index') }}" class="nav-link {{ request()->routeIs('tomos.*') ? 'active' : '' }}" @if(request()->routeIs('tomos.*')) aria-current="page" @endif>
                        <i class="nav-icon fas fa-archive" aria-hidden="true"></i>
                        <p>Tomos</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

</aside>

