@extends('layouts.store')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16 sm:py-24 text-center">
    <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-gold-100 flex items-center justify-center text-maroon-800 text-2xl">
        ✉️
    </div>
    <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal mb-4">
        Unsubscribed Successfully
    </h1>
    <p class="text-sm text-rich-black/70 leading-relaxed mb-8 max-w-md mx-auto">
        Your email has been removed from our marketing newsletter list. You will no longer receive promotional updates or style notes from Dhansree Trends.
    </p>
    <a href="{{ url('/') }}"
       class="inline-block px-6 py-3 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shadow-xs">
        Return to Home Page
    </a>
</div>
@endsection
