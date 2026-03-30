@props(['content' => [], 'settings' => []])

@php
    $types = $content['types'] ?? [
        '' => 'All',
        'villa' => 'Villa',
        'apartment' => 'Apartment',
        'house' => 'House',
        'studio' => 'Studio',
        'office' => 'Office',
        'land' => 'Land',
    ];
@endphp

<div class="flat-control-search abs" x-data="{
    activeTab: 'rent',
    getAction() {
        return this.activeTab === 'sale' ? '{{ route('properties.index') }}' : '{{ route('rental-properties.index') }}';
    }
}">
    <div class="container">
        <div class="flat-tab flat-tab-form">
            {{-- Tabs: For Rent / For Sale --}}
            <ul class="nav-tab-form style-1 justify-content-center" role="tablist">
                <li class="nav-tab-item" role="presentation">
                    <a href="#" class="nav-link-item" :class="activeTab === 'rent' ? 'active' : ''" @click.prevent="activeTab = 'rent'">For Rent</a>
                </li>
                <li class="nav-tab-item" role="presentation">
                    <a href="#" class="nav-link-item" :class="activeTab === 'sale' ? 'active' : ''" @click.prevent="activeTab = 'sale'">For Sale</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade active show" role="tabpanel">
                    <div class="form-sl">
                        <form method="get" :action="getAction()">
                            <div class="wd-find-select shadow-3">
                                <div class="inner-group">
                                    {{-- Type --}}
                                    <div class="form-group-1 search-form form-style">
                                        <label>Type</label>
                                        <select name="type" class="form-select">
                                            @foreach($types as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Location --}}
                                    <div class="form-group-2 form-style">
                                        <label>Location</label>
                                        <div class="group-ip">
                                            <input type="text" class="form-control" name="city" placeholder="Search Location" value="">
                                            <a href="#" class="icon icon-location"></a>
                                        </div>
                                    </div>

                                    {{-- Keyword --}}
                                    <div class="form-group-3 form-style">
                                        <label>Keyword</label>
                                        <input type="text" class="form-control" name="search" placeholder="Search Keyword" value="">
                                    </div>
                                </div>

                                <div class="box-btn-advanced">
                                    {{-- Advanced toggle --}}
                                    <div class="form-group-4 box-filter">
                                        <a class="tf-btn btn-line filter-advanced pull-right" onclick="document.getElementById('homeAdvancedFilters').classList.toggle('d-none')">
                                            <span class="text-1">Advanced</span>
                                            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M5.5 12.375V3.4375M5.5 12.375C5.86467 12.375 6.21441 12.5199 6.47227 12.7777C6.73013 13.0356 6.875 13.3853 6.875 13.75C6.875 14.1147 6.73013 14.4644 6.47227 14.7223C6.21441 14.9801 5.86467 15.125 5.5 15.125M5.5 12.375C5.13533 12.375 4.78559 12.5199 4.52773 12.7777C4.26987 13.0356 4.125 13.3853 4.125 13.75C4.125 14.1147 4.26987 14.4644 4.52773 14.7223C4.78559 14.9801 5.13533 15.125 5.5 15.125M5.5 15.125V18.5625M16.5 12.375V3.4375M16.5 12.375C16.8647 12.375 17.2144 12.5199 17.4723 12.7777C17.7301 13.0356 17.875 13.3853 17.875 13.75C17.875 14.1147 17.7301 14.4644 17.4723 14.7223C17.2144 14.9801 16.8647 15.125 16.5 15.125M16.5 12.375C16.1353 12.375 15.7856 12.5199 15.5277 12.7777C15.2699 13.0356 15.125 13.3853 15.125 13.75C15.125 14.1147 15.2699 14.4644 15.5277 14.7223C15.7856 14.9801 16.1353 15.125 16.5 15.125M16.5 15.125V18.5625M11 6.875V3.4375M11 6.875C11.3647 6.875 11.7144 7.01987 11.9723 7.27773C12.2301 7.53559 12.375 7.88533 12.375 8.25C12.375 8.61467 12.2301 8.96441 11.9723 9.22227C11.7144 9.48013 11.3647 9.625 11 9.625M11 6.875C10.6353 6.875 10.2856 7.01987 10.0277 7.27773C9.76987 7.53559 9.625 7.88533 9.625 8.25C9.625 8.61467 9.76987 8.96441 10.0277 9.22227C10.2856 9.48013 10.6353 9.625 11 9.625M11 9.625V18.5625" stroke="#161E2D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    </div>
                                    <button type="submit" class="tf-btn btn-search primary">Search <i class="icon icon-search"></i></button>
                                </div>
                            </div>

                            {{-- Advanced Filters --}}
                            <div id="homeAdvancedFilters" class="wd-search-form d-none">
                                <div class="grid-2 group-box">
                                    <div class="group-select grid-2">
                                        <div class="box-select">
                                            <label class="title-select fw-6">Min Price (€)</label>
                                            <input type="number" name="min_price" class="form-control" placeholder="Min">
                                        </div>
                                        <div class="box-select">
                                            <label class="title-select fw-6">Max Price (€)</label>
                                            <input type="number" name="max_price" class="form-control" placeholder="Max">
                                        </div>
                                    </div>
                                    <div class="group-select grid-2">
                                        <div class="box-select">
                                            <label class="title-select fw-6">Bedrooms</label>
                                            <select name="bedrooms" class="form-select">
                                                <option value="">Any</option>
                                                @for($i = 1; $i <= 10; $i++)
                                                    <option value="{{ $i }}">{{ $i }}+</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="box-select">
                                            <label class="title-select fw-6">Bathrooms</label>
                                            <select name="bathrooms" class="form-select">
                                                <option value="">Any</option>
                                                @for($i = 1; $i <= 5; $i++)
                                                    <option value="{{ $i }}">{{ $i }}+</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
