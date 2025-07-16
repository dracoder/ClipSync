<div class="container mx-auto justify-center my-[10vh]">

    <h1 class="text-center clip-text text-2xl lg:text-4xl"><b>{{__('home.hero_cta')}} 🤓</b></h1>
    <div class="grid grid-cols-1  md:grid-cols-2 xl:grid-cols-2 mt-3 justify-center gap-4 mx-4">

        {{-- CLIP --}}
        <div class="bg-white border-t-[2px] border-l-[2px] border-b-8 border-r-8 border-gray-950 rounded-[19px] pb-4">
            <div class="color-bg rounded-t-[19px] -mt-3">
                <h4 class="m-3 text-2xl p-4 clip-text" style="font-weight:bolder">
                    <b>{{__('clip.title')}}</b>
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 lg:col-span-4 lg:pl-4">
                        <img src="{{url('img/clip.png')}}" alt="{{__('clip.title')}}" class="rounded-lg  mx-auto">
                    </div>
                    <div class="col-span-12 lg:col-span-8 mb-4">
                        <p class="text-lg px-4 mb-8">
                            {!! __('clip.description') !!}
                        </p>
                        <div class="text-center">
                            <a class=" p-4 color-bg bg-yellow-100 mx-auto justify-center rounded-full uppercase font-black border-b-4 border-r-4 border-gray-950 hover:bg-black hover:text-yellow-200 transition-all" href="{{url('/clip')}}">
                                {{__('clip.cta')}}
                            </a>
                            <p class="text-xs mt-5 text-center">{{__('globals.free_cta')}}</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        {{-- CLIP --}}
        <div class="bg-white  border-b-8 border-r-8 border-gray-950 rounded-[19px] pb-4">
            <div class="color-bg rounded-t-[19px] -mt-3">
                <h4 class="m-3 text-2xl p-4 clip-text" style="font-weight:bolder">
                    <b>{{__('clip.title')}}</b>
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 lg:col-span-4 lg:pl-4">
                        <img src="{{url('img/clip.png')}}" alt="{{__('clip.title')}}" class="rounded-lg  mx-auto">
                    </div>
                    <div class="col-span-12 lg:col-span-8 mb-4">
                        <p class="text-lg px-4 mb-8">
                            {!! __('clip.description') !!}
                        </p>
                        <div class="text-center">
                            <a class=" p-4 color-bg bg-yellow-100 mx-auto justify-center rounded-full uppercase font-black border-b-4 border-r-4 border-gray-950 hover:bg-black hover:text-yellow-200 transition-all" href="{{url('/clip')}}">
                                {{__('clip.cta')}}
                            </a>
                            <p class="text-xs mt-5 text-center">{{__('globals.free_cta')}}</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        

        {{--
        THOSE 4 CARDS ARE FOR THE STUDIO
        will keep them here as reference and remove later
        --}}

        @if(false)
            <div class="card shadow bg-white">
                <div class="card-header color-bg">
                    <h4 class="m-3" style="font-weight:bolder">
                        <b>{{__('JOIN ROOMS YOU LOVE')}}</b>
                    </h4>
                </div>
                <div class="card-body">
                    <p>
                        @if(app()->getLocale () == 'it')
                            Ti interessa un argomento in particolare?<br> Cerca tra le stanze create da altri utenti e unisciti alla conversazione.
                        @else
                            Are you interested in a particular topic?<br> Search through the rooms created by other users and join the conversation.
                        @endif
                    </p>
                </div>
            </div>
        
            <div class="card shadow bg-white">
                <div class="card-header color-bg">
                    <h4 class="m-3" style="font-weight:bolder">
                        <b>{{__('CREATE YOUR OWN')}}</b>
                    </h4>
                </div>
                <div class="card-body">
                    <p>
                        @if(app()->getLocale () == 'it')
                            Crea le tue stanze, saranno tue per sempre, e incontra persone simili a te.
                            Le stanze create sono permanenti e ne potrai gestire l'argomento, i partecipanti e rendere l'esperienza unica per tutti.
                            Potrai nominare altri amministratori che come te potranno gestire la stanza.
                        @else
                            Create your own rooms, own them forever, and meet like-minded people.
                            The rooms created are permanent and you can manage the topic, the participants and make the experience unique for everyone.
                            You will be able to add administrators that will manage the room.
                            {{--Multiple administrators are allowed but only the room owner can enable or revoke their permission.--}}
                        @endif
                    </p>
                </div>
            </div>
        
            <div class="card shadow bg-white">
                <div class="card-header color-bg">
                    <h4 class="m-3" style="font-weight:bolder">
                        <b>{{__('VIDEO CHAT AND MORE')}}</b>
                    </h4>
                </div>
                <div class="card-body">
                    <p>
                        @if(app()->getLocale () == 'it')
                            In ogni stanza troverai persone che trasmettono dalle loro webcam e una chat pubblica dove ognuno può inviare messaggi.
                        @else
                            In every room you will find people streaming from their cameras and also a public board where everyone can chat.
                        @endif
                    </p>
                </div>
            </div>
        
            <div class="card shadow bg-white">
                <div class="card-header color-bg">
                    <h4 class="m-3" style="font-weight:bolder">
                        <b>{{__('SPECTATORS WELCOME')}}</b>
                    </h4>
                </div>
                <div class="card-body">
                    <p>
                        @if(app()->getLocale () == 'it')
                            Preferisci non mostrarti in cam? Nessun problema.<br>
                            Ti basterà entrare in una stanza e seguire la conversazione anche sulla chat pubblica.
                            Quando lo desideri, potrai chiedere ad un amministratore di parlare o abilitare la tua cam.
                        @else
                            Don't want to be seen on camera? No problem!<br>
                            Just enter in a room and follow the conversation also on the public chat board.
                            When you're ready, you can ask an administrator to speak or show you on camera.
                        @endif
        
                    </p>
                </div>
            </div>
        @endif
    
    </div>
</div>





@if (false)
    {{--REFERENCE--}}
    <div class="col-md-6 col-lg-4 mt-3">
        <div class="card shadow">
            <div class="card-header color-bg">
                <h4 class="m-3" style="font-weight:bolder">
                    <b>TITLE</b>
                </h4>
            </div>
            <div class="card-body">
                <p>
                    TEXT
                </p>
            </div>
        </div>
    </div>
@endif
