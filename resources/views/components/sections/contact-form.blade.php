@props(['content', 'settings'])

<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            @if(!empty($content['heading']))
                <h2 class="text-4xl font-bold text-center mb-4">{{ $content['heading'] }}</h2>
            @endif
            @if(!empty($content['text']))
                <p class="text-center text-gray-600 mb-8">{{ $content['text'] }}</p>
            @endif

            <form class="bg-white p-8 rounded-lg shadow-md">
                @foreach(($content['fields'] ?? []) as $field)
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">{{ $field['label'] ?? '' }}</label>
                        @if(($field['type'] ?? 'text') === 'textarea')
                            <textarea name="{{ $field['name'] ?? '' }}" rows="4" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-blue-500" {{ ($field['required'] ?? false) ? 'required' : '' }}></textarea>
                        @else
                            <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['name'] ?? '' }}" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-blue-500" {{ ($field['required'] ?? false) ? 'required' : '' }}>
                        @endif
                    </div>
                @endforeach

                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                    {{ $content['submit_text'] ?? 'Send Message' }}
                </button>
            </form>
        </div>
    </div>
</section>
