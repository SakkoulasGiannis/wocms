@props(['section'])

@php
    $content = $section->content;
    $settings = $section->settings;
@endphp

<section class="contact-form py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-12">
            <div>
                @if(!empty($content['heading']))
                    <h2 class="text-4xl font-bold mb-4">{{ $content['heading'] }}</h2>
                @endif

                @if(!empty($content['text']))
                    <p class="text-lg text-gray-700 mb-8">{{ $content['text'] }}</p>
                @endif

                @if($settings['show_info'] ?? true)
                    <div class="space-y-4">
                        @if(!empty($content['email']))
                            <div class="flex items-center gap-3">
                                <span class="text-blue-600 text-xl">✉️</span>
                                <span>{{ $content['email'] }}</span>
                            </div>
                        @endif

                        @if(!empty($content['phone']))
                            <div class="flex items-center gap-3">
                                <span class="text-blue-600 text-xl">📞</span>
                                <span>{{ $content['phone'] }}</span>
                            </div>
                        @endif

                        @if(!empty($content['address']))
                            <div class="flex items-center gap-3">
                                <span class="text-blue-600 text-xl">📍</span>
                                <span>{{ $content['address'] }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="bg-white p-8 rounded-lg shadow-md">
                <form>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Name</label>
                        <input type="text" class="w-full border border-gray-300 rounded px-4 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Email</label>
                        <input type="email" class="w-full border border-gray-300 rounded px-4 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Message</label>
                        <textarea class="w-full border border-gray-300 rounded px-4 py-2" rows="5"></textarea>
                    </div>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                        Send Message
                    </button>
                </form>
            </div>
        </div>

        @if($settings['show_map'] ?? false)
            {{-- Map integration would go here --}}
            <div class="mt-12 h-64 bg-gray-200 rounded-lg">
                <!-- Google Maps or similar -->
            </div>
        @endif
    </div>
</section>
