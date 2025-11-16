@php( $logout_url = View::getSection('logout_url') ?? config('adminlte.logout_url', 'logout') )

@if (config('adminlte.use_route_url', false))
    @php( $logout_url = $logout_url ? route($logout_url) : '' )
@else
    @php( $logout_url = $logout_url ? url($logout_url) : '' )
@endif

<li class="nav-item">
    <a class="nav-link logout-link"
       href="#"
       style="color: rgba(0,0,0,1) !important; display:flex; align-items:center;"
       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        <i class="fa fa-fw fa-power-off" aria-hidden="true" style="color: rgba(0,0,0,1) !important; margin-right: .4rem;"></i>
        {{ __('adminlte::adminlte.log_out') }}
    </a>

    <form id="logout-form" action="{{ $logout_url }}" method="POST" style="display: none;">
        @if(config('adminlte.logout_method'))
            {{ method_field(config('adminlte.logout_method')) }}
        @endif
        {{ csrf_field() }}
    </form>
</li>
