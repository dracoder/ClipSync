<div class="card flex justify-center" x-data="{
    open: false,
    toggleDropdown() {
        this.open = !this.open;
    },
}">
@php($langauges = ['en','it'])
    <div ref="dropdown"  class="dropdown" :class="{ 'dropdown-open': open }">
        <div class="border-2 border-black p-1 rounded-md bg-white" @click="toggleDropdown">
            <img src="/img/flags/{{ app()->getLocale() }}.svg" alt="{{ app()->getLocale() }}" class="w-8 h-5" />
        </div>
        <ul  class="dropdown-content bg-white border-2 border-black p-1 flex flex-col gap-1 w-full rounded-md mt-1">
            @foreach($langauges as $lang)
            <li>
                <a href="{{ route('locale.change',$lang) }}" class="cursor-pointer">
                    <img src="/img/flags/{{ $lang }}.svg" alt="{{ $lang }}" class="w-8 border-2 border-black" />
                </a>
            </li>
            @endforeach
        </ul>
    </div>
    <div ref="dropdown" class="hidden"></div>
</div>