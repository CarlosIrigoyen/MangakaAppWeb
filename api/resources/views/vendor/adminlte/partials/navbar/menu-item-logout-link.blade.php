@php( $logout_url = View::getSection('logout_url') ?? config('adminlte.logout_url', 'logout') )

@if (config('adminlte.use_route_url', false))
    @php( $logout_url = $logout_url ? route($logout_url) : '' )
@else
    @php( $logout_url = $logout_url ? url($logout_url) : '' )
@endif

<li class="nav-item">
    <form id="logout-form" action="{{ $logout_url }}" method="POST" style="display: inline;">
        @if(config('adminlte.logout_method'))
            {{ method_field(config('adminlte.logout_method')) }}
        @endif
        {{ csrf_field() }}

        <button type="submit"
                class="nav-link btn btn-link p-0 logout-button"
                style="color: rgba(0,0,0,1); display:flex; align-items:center; text-decoration:none;">
            <i class="fa fa-fw fa-power-off" aria-hidden="true" style="color: rgba(0,0,0,1); margin-right: .4rem;"></i>
            <span>{{ __('adminlte::adminlte.log_out') }}</span>
        </button>
    </form>
</li>

