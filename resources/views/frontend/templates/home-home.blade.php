@extends('frontend.layout')

@section('title', $node->title ?? $title ?? 'Page')

@push('styles')
<style>
* { box-sizing: border-box; } body {margin: 0;}.hover\:shadow-xl:hover{box-shadow:rgba(0, 0, 0, 0.1) 0px 20px 25px -5px, rgba(0, 0, 0, 0.04) 0px 10px 10px -5px;}
</style>
@endpush

@section('content')
<div class="bg-white"><div class="relative isolate px-6 pt-14 lg:px-8"><div aria-hidden="true" class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"><div id="i8ybk" class="relative left-[calc(50%-11rem)] aspect-1155/678 w-144.5 -translate-x-1/2 rotate-30 bg-linear-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-288.75">
        </div></div><div class="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56"><div class="text-center"><h1 class="font-semibold tracking-tight text-balance text-gray-900 text-5xl">
            Κατασκευή Εφαρμογών & Κατασκευή eShop
          </h1><p class="mt-8 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8">
            Σχεδιάζουμε και αναπτύσσουμε custom web εφαρμογές και e-shop για επιχειρήσεις που θέλουν
            αυτοματισμούς,
            επεκτασιμότητα και πραγματική εμπορική απόδοση.
          </p><div class="mt-10 flex items-center justify-center gap-x-6"><a href="/contact-us" class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Επικοινωνήστε μαζί μας!</a><a href="/services" class="text-sm/6 font-semibold text-gray-900">
              Δες τις υπηρεσίες μας <span aria-hidden="true">→</span></a></div></div></div><div aria-hidden="true" class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]"><div id="ijqh6" class="relative left-[calc(50%+3rem)] aspect-1155/678 w-144.5 -translate-x-1/2 bg-linear-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%+36rem)] sm:w-288.75">
        </div></div></div></div><div id="services" class="py-24 sm:py-32 bg-gray-100"><div class="mx-auto max-w-7xl px-6 lg:px-8"><div class="mx-auto lg:mx-0"><h2 class="text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl">
          Custom λύσεις για επιχειρήσεις
        </h2><p class="mt-6 text-lg/8 text-gray-700">
          Αναπτύσσουμε εφαρμογές και e-shop προσαρμοσμένα στις διαδικασίες της επιχείρησής σου,
          όχι έτοιμες λύσεις τύπου template.
        </p></div><dl class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 text-base/7 sm:grid-cols-2 lg:mx-0 lg:max-w-none lg:grid-cols-3"><div><dt class="font-semibold text-gray-900">Κατασκευή Web Εφαρμογών
          </dt><dd class="mt-1 text-gray-600">
            Custom εφαρμογές (CRM, ERP, dashboards, portals) με επιχειρησιακή λογική,
            αυτοματισμούς και δυνατότητα μελλοντικής επέκτασης.
          </dd></div><div><dt class="font-semibold text-gray-900">Κατασκευή eShop
          </dt><dd class="mt-1 text-gray-600">
            Ανάπτυξη e-shop με έμφαση στο performance, SEO και τις πωλήσεις.
            Διασυνδέσεις με ERP, πληρωμές, logistics και marketplaces.
          </dd></div><div><dt class="font-semibold text-gray-900">Custom Ανάπτυξη
          </dt><dd class="mt-1 text-gray-600">
            Δεν δουλεύουμε με έτοιμα πακέτα. Κάθε έργο σχεδιάζεται με βάση
            τις πραγματικές ανάγκες της επιχείρησης.
          </dd></div><div><dt class="font-semibold text-gray-900">Ασφάλεια & Σταθερότητα
          </dt><dd class="mt-1 text-gray-600">
            Σύγχρονες πρακτικές ασφάλειας, έλεγχοι πρόσβασης και σταθερή αρχιτεκτονική
            για απαιτητικά business περιβάλλοντα.
          </dd></div><div><dt class="font-semibold text-gray-900">Διασυνδέσεις & APIs
          </dt><dd class="mt-1 text-gray-600">
            Ενσωματώνουμε τράπεζες, ERP, τρίτα συστήματα και APIs,
            εξαλείφοντας χειροκίνητες διαδικασίες.
          </dd></div><div><dt class="font-semibold text-gray-900">Υποστήριξη & Εξέλιξη
          </dt><dd class="mt-1 text-gray-600">
            Συνεχής τεχνική υποστήριξη και δυνατότητα εξέλιξης της εφαρμογής
            καθώς μεγαλώνει η επιχείρησή σου.
          </dd></div></dl></div></div><div class="bg-white py-24 sm:py-32"><div class="mx-auto max-w-7xl px-6 lg:px-8"><h2 class="text-center text-lg/8 font-semibold text-gray-900">Επιχειρήσεις που μας εμπιστεύονται για την
        ανάπτυξη των εφαρμογών τους
      </h2><div class="mx-auto mt-10 grid max-w-lg grid-cols-4 items-center gap-x-8 gap-y-10 sm:max-w-xl sm:grid-cols-6 sm:gap-x-10 lg:mx-0 lg:max-w-none lg:grid-cols-5"><img width="158" height="48" src="/storage/5/chronoro.webp" alt="Marron" class="col-span-2 max-h-12 w-full object-contain lg:col-span-1"/><img width="158" height="48" src="/storage/6/marron.webp" alt="Marron" class="col-span-2 max-h-12 w-full object-contain lg:col-span-1"/><img width="158" height="48" src="/storage/1/optimal.webp" alt="optimal" class="col-span-2 max-h-12 w-full object-contain lg:col-span-1"/><img width="158" height="48" src="/storage/2/kretaeiendom.webp" alt="Kretaeiendom" class="col-span-2 max-h-12 w-full object-contain lg:col-span-1"/><img width="158" height="48" src="/storage/3/e-shoes.webp" alt="E-shoes" class="col-span-2 max-h-12 w-full object-contain sm:col-start-2 lg:col-span-1"/><img width="158" height="48" src="/storage/4/fitness-store-logo.webp" alt="fitness store" class="col-span-2 col-start-2 max-h-12 w-full object-contain sm:col-start-auto lg:col-span-1"/></div></div></div>
@endsection
