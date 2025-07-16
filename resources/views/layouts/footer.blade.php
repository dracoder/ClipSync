<div class="footer py-3 color-bg" style="border-radius: 0 90px 0 0">
    <div class="row">
        <div class="col-md-3 d-flex justify-content-start align-items-center">
            <img src="{{url('img/clipsync.svg')}}?v=2" style="max-height: 50px;" >
            <a href="{{url('/')}}" class="no-link-color">
                <p class="clip-logo">CLIPSYNC</p>
            </a>
        </div>
        <div class="col-md-9">
            <p class="small px-3">
                <a href="{{url('/about')}}">{{__('About')}}</a> •
                <a href="{{url('/terms')}}">{{__('Terms and Conditions')}}</a> •
                <a href="{{url('/guidelines')}}">{{__('Guidelines')}}</a> •
                <a href="{{url('/contacts')}}">{{__('Contact Us')}}</a> •
                <a href="{{url('/privacy')}}">{{__('Privacy & Cookie Policy')}}</a>
                <br><br>
                <span class="px-1">&copy; {{config('app.name')}}.com - {{__('All rights reserved')}}.</span>
            </p>
        </div>
    </div>
</div>
