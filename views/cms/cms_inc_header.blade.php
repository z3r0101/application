<nav class="navbar navbar-expand cms-topbar">
    <div class="collapse navbar-collapse">
        <button class="cms-mobile-menu c-hamburger c-hamburger--htx">
            <span>toggle menu</span>
        </button>
        <ul class="navbar-nav ml-auto">
            @if (isset($CONFIG['cms']['header']['links']))
                @foreach($CONFIG['cms']['header']['links'] as $Index => $Link)
                    <li class="nav-item"><a href="{!!$Link['href']!!}" class="nav-link" {!!(isset($Link['onclick'])) ? 'onclick="'.$Link['onclick'].'"' : ''!!}>{!!$Link['caption']!!}</a></li>
                @endforeach
            @endif
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" title="{{CMS_Users_FullName}}" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-user"></i> {{CMS_Users_Name}}
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="{{$CONFIG['website']['path'].$CONFIG['cms']['route_name']}}/administrator/my-account/post" title=""><i class="fas fa-user"></i> My Account</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{$CONFIG['website']['path'].$CONFIG['cms']['route_name']}}/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </li>
        </ul>
    </div>
</nav>